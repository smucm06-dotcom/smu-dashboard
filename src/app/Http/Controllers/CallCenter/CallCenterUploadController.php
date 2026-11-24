<?php

namespace App\Http\Controllers\CallCenter;

use App\Http\Controllers\Controller;
use App\Jobs\CallCenter\ProcessCallCenterUpload;
use App\Models\CallCenterUpload;
use App\Models\Initiative;
use App\Support\CallCenterCampaigns;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

// The call center pages' two big uploads (our calls file and the department list). The file is kept and read
// in the background (ProcessCallCenterUpload); the page shows a progress bar from show() until it is done.
class CallCenterUploadController extends Controller
{
    // Before the queue worker picks an upload up. Longer than this, and it is probably not running.
    private const WORKER_WAIT_SECONDS = 60;

    public function store(Request $request, Initiative $initiative)
    {
        $type = $request->input('type');

        abort_unless(
            $initiative->is_active && is_string($type) && array_key_exists($type, CallCenterCampaigns::imports($initiative)),
            404
        );

        $maxMb = (int) config('call_center.max_upload_mb', 100);

        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'extensions:csv,xlsx', 'max:'.($maxMb * 1024)],
        ], [
            'file.required' => 'Choose a CSV or Excel (.xlsx) file to upload.',
            'file.file' => 'The file could not be uploaded. Please try again.',
            'file.uploaded' => 'The file could not be uploaded. Please try again.',
            'file.extensions' => 'Only CSV and Excel (.xlsx) files can be uploaded here.',
            'file.max' => "The file must be {$maxMb} MB or smaller.",
        ]);

        if ($validator->fails()) {
            return $this->refused($request, $initiative, $type, $validator->errors()->first('file'));
        }

        // One at a time, so two files never change the same calls at once.
        $running = CallCenterUpload::where('initiative_id', $initiative->id)->unfinished()->latest('id')->first();

        if ($running !== null && ! $running->hasStalled()) {
            return $this->refused($request, $initiative, $type, 'Another file ("'.$running->original_name.'") is still being read. Wait for it to finish, then upload again.');
        }

        $running?->update(['status' => CallCenterUpload::STATUS_FAILED, 'phase' => null, 'finished_at' => now(), 'result' => ['failure' => 'Reading the file stopped part way. Nothing was saved. Please upload it again.', 'fileName' => $running->original_name]]);

        $file = $request->file('file');

        try {
            $stored = $file->store("initiatives/{$initiative->id}/{$type}", 'local');

            if ($stored === false) {
                return $this->refused($request, $initiative, $type, 'The file could not be uploaded. Please try again.');
            }

            $upload = CallCenterUpload::create([
                'initiative_id' => $initiative->id,
                'type' => $type,
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                'stored_path' => $stored,
                'fingerprint' => hash_file('sha256', $file->getRealPath()) ?: null,
                'status' => CallCenterUpload::STATUS_QUEUED,
                'uploaded_by' => $request->user()->id,
            ]);
        } catch (Throwable $e) {
            report($e);

            return $this->refused($request, $initiative, $type, 'The file could not be uploaded. Please try again.');
        }

        ProcessCallCenterUpload::dispatch($upload->id);

        if ($request->expectsJson()) {
            return response()->json(['upload' => $this->status($upload->fresh())], 202);
        }

        return redirect()->to(route('initiatives.show', $initiative).'#initiative-files');
    }

    // How far the upload has got, for the progress bar.
    public function show(Initiative $initiative, CallCenterUpload $upload): JsonResponse
    {
        abort_unless((int) $upload->initiative_id === (int) $initiative->id, 404);

        return response()->json(['upload' => $this->status($upload)], 200, ['Cache-Control' => 'no-store']);
    }

    public static function status(CallCenterUpload $upload): array
    {
        $stalled = $upload->hasStalled();
        $waiting = $upload->status === CallCenterUpload::STATUS_QUEUED
            ? (int) $upload->created_at->diffInSeconds(now())
            : 0;

        return [
            'id' => $upload->id,
            'type' => $upload->type,
            'fileName' => $upload->original_name,
            'status' => $stalled ? CallCenterUpload::STATUS_FAILED : $upload->status,
            'phase' => $upload->phase,
            'rowsDone' => $upload->rows_done,
            'rowsTotal' => $upload->rows_total,
            'startedAt' => $upload->started_at?->toIso8601String(),
            // The worker has not picked it up: it is probably not running.
            'workerLate' => $waiting > self::WORKER_WAIT_SECONDS,
            'stalled' => $stalled,
            'url' => route('initiatives.call-center.uploads.show', [$upload->initiative_id, $upload->id]),
        ];
    }

    private function refused(Request $request, Initiative $initiative, string $type, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return redirect()
            ->to(route('initiatives.show', $initiative).'#initiative-files')
            ->with('initiativeFileError', ['type' => $type, 'message' => $message]);
    }
}
