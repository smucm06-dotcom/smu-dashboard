<?php

namespace App\Jobs\CallCenter;

use App\Imports\CallCenter\ImportFailed;
use App\Imports\CallCenter\UploadProgress;
use App\Models\CallCenterUpload;
use App\Models\InitiativeFile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

// Reads one call center upload in the background: every row is checked and saved, then the calls are linked
// to the department's list. All of it happens in one transaction, so a file with any problem changes nothing,
// as on the other pages. The page follows its progress (see CallCenterUploadController::show).
class ProcessCallCenterUpload implements ShouldQueue
{
    use Queueable;

    // A million rows take a few minutes; this leaves plenty of room.
    public int $timeout = 3600;

    // A failed upload is reported, not tried again.
    public int $tries = 1;

    public function __construct(public int $uploadId)
    {
        $this->onQueue(config('call_center.queue'));
    }

    public function handle(): void
    {
        $upload = CallCenterUpload::with('initiative')->find($this->uploadId);

        if ($upload === null || $upload->status !== CallCenterUpload::STATUS_QUEUED) {
            return;
        }

        $campaign = config('call_center.campaigns.'.$upload->initiative?->table_name);
        $importClass = $campaign['imports'][$upload->type] ?? null;

        if ($importClass === null) {
            $this->finish($upload, ['failure' => 'This upload is not set up for this page.'], null);

            return;
        }

        $upload->update(['status' => CallCenterUpload::STATUS_RUNNING, 'phase' => CallCenterUpload::PHASE_READING, 'started_at' => now()]);

        $progress = new UploadProgress($upload);
        $path = Storage::disk('local')->path($upload->stored_path);
        $extension = strtolower(pathinfo($upload->original_name, PATHINFO_EXTENSION));

        try {
            $this->refuseRepeat($upload);

            $result = DB::transaction(function () use ($upload, $campaign, $importClass, $path, $extension, $progress) {
                $file = InitiativeFile::create([
                    'initiative_id' => $upload->initiative_id,
                    'type' => $upload->type,
                    'original_name' => $upload->original_name,
                    'stored_path' => $upload->stored_path,
                    'uploaded_by' => $upload->uploaded_by,
                ]);

                $result = app($importClass)->import($path, $extension, $file, $progress);
                $file->update(['row_count' => $result['added']]);

                return $result + [
                    'links' => app($campaign['linker'])->link($progress),
                    'fileId' => $file->id,
                ];
            });
        } catch (ImportFailed $e) {
            $this->finish($upload, $e->result(), null);

            return;
        } catch (Throwable $e) {
            report($e);
            $this->finish($upload, ['failure' => 'The file could not be saved. Please try again.'], null);

            return;
        }

        // The lists it replaced are gone from the database, so their stored copies go too.
        foreach ($result['removePaths'] ?? [] as $stored) {
            Storage::disk('local')->delete($stored);
        }

        unset($result['removePaths']);

        $this->finish($upload, $result, $result['fileId']);
    }

    // The queue gave up on it (it ran too long, say): the page must not wait for it for ever.
    public function failed(?Throwable $e): void
    {
        $upload = CallCenterUpload::find($this->uploadId);

        if ($upload !== null && ! $upload->isFinished()) {
            $this->finish($upload, ['failure' => 'Reading the file took too long or was stopped. Nothing was saved. Please try again.'], null);
        }
    }

    // The same calls file twice would count every call twice.
    private function refuseRepeat(CallCenterUpload $upload): void
    {
        if ($upload->type !== InitiativeFile::TYPE_OUR_VISITS || $upload->fingerprint === null) {
            return;
        }

        $earlier = CallCenterUpload::query()
            ->where('initiative_id', $upload->initiative_id)
            ->where('type', $upload->type)
            ->where('fingerprint', $upload->fingerprint)
            ->where('status', CallCenterUpload::STATUS_DONE)
            ->whereNotNull('initiative_file_id')
            ->where('id', '<>', $upload->id)
            ->latest('id')
            ->first();

        if ($earlier !== null) {
            throw new ImportFailed(
                'This file was already uploaded on '.$earlier->created_at->timezone('Asia/Karachi')->format('j M Y').' as "'.$earlier->original_name.'". '
                .'Its calls are on the page already, so nothing was added.'
            );
        }
    }

    private function finish(CallCenterUpload $upload, array $result, ?int $fileId): void
    {
        $failed = isset($result['failure']);

        if ($failed) {
            Storage::disk('local')->delete($upload->stored_path);
        }

        $upload->update([
            'status' => $failed ? CallCenterUpload::STATUS_FAILED : CallCenterUpload::STATUS_DONE,
            'phase' => null,
            'result' => $result + ['fileName' => $upload->original_name],
            'initiative_file_id' => $fileId,
            'finished_at' => now(),
        ]);

        Log::info('Call center upload finished.', [
            'upload_id' => $upload->id,
            'initiative_id' => $upload->initiative_id,
            'type' => $upload->type,
            'status' => $upload->status,
            'rows' => $result['added'] ?? null,
        ]);
    }
}
