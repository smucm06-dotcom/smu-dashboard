<?php

namespace App\Http\Controllers;

use App\Imports\InitiativeFileImport;
use App\Imports\SpreadsheetReader;
use App\Models\Initiative;
use App\Models\InitiativeFile;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Throwable;

class InitiativeFileController extends Controller
{
    // Visit files go through each initiative's own import, which also saves their rows.
    private const LABELS = [
        InitiativeFile::TYPE_DEPARTMENT_LIST => 'department list',
        InitiativeFile::TYPE_OUR_REPORT => 'report',
        InitiativeFile::TYPE_DEPARTMENT_RESPONSE => 'department response',
    ];

    private const EXTENSIONS = [
        InitiativeFile::TYPE_DEPARTMENT_LIST => 'csv,xlsx',
        InitiativeFile::TYPE_OUR_REPORT => 'pdf',
        InitiativeFile::TYPE_DEPARTMENT_RESPONSE => 'pdf,csv,xlsx',
    ];

    private const EXTENSION_MESSAGES = [
        InitiativeFile::TYPE_DEPARTMENT_LIST => 'Only CSV and Excel (.xlsx) files can be uploaded here.',
        InitiativeFile::TYPE_OUR_REPORT => 'Only PDF files can be uploaded here.',
        InitiativeFile::TYPE_DEPARTMENT_RESPONSE => 'Only PDF, CSV and Excel (.xlsx) files can be uploaded here.',
    ];

    public function store(Request $request, Initiative $initiative, SpreadsheetReader $reader)
    {
        $type = $request->input('type');

        abort_unless(
            $initiative->is_active && is_string($type) && array_key_exists($type, self::LABELS),
            404
        );

        $validator = Validator::make($request->all(), $this->rules($type), $this->messages($type));

        if ($validator->fails()) {
            return $this->failed($initiative, $type, $validator->errors()->first());
        }

        $upload = $request->file('file');
        $extension = strtolower($upload->getClientOriginalExtension());
        $import = $this->importFor($initiative, $type, $extension);

        // Everything is read and checked first, so a bad file changes nothing.
        try {
            $this->checkPdf($upload, $extension);
            $prepared = $import?->prepare($upload->getRealPath(), $extension);
            $entries = $this->entries($upload, $extension, $type, $prepared, $reader);
        } catch (RuntimeException $e) {
            return $this->failed($initiative, $type, $e->getMessage());
        }

        $file = null;

        try {
            DB::transaction(function () use ($initiative, $upload, $type, $entries, $import, $prepared, &$file) {
                $file = InitiativeFile::storeUpload($initiative, $upload, $type, [
                    'row_count' => $entries['count'],
                ]);

                $import?->save($prepared, $file);
            });
        } catch (Throwable $e) {
            report($e);

            if ($file !== null) {
                Storage::disk('local')->delete($file->stored_path);
            }

            return $this->failed($initiative, $type, 'The file could not be saved. Please try again.');
        }

        $replaced = $type === InitiativeFile::TYPE_DEPARTMENT_LIST
            ? $this->removeOlderLists($initiative, $file)
            : [];

        return redirect()
            ->to(route('initiatives.show', $initiative).'#initiative-files')
            ->with('initiativeFileSaved', $this->savedMessage($upload, $type, $entries, $replaced));
    }

    public function download(Initiative $initiative, InitiativeFile $file)
    {
        abort_unless(Storage::disk('local')->exists($file->stored_path), 404);

        return Storage::disk('local')->download($file->stored_path, $file->original_name);
    }

    public function destroy(Initiative $initiative, InitiativeFile $file)
    {
        abort_unless($initiative->is_active, 404);

        try {
            $this->remove($file);
        } catch (QueryException $e) {
            report($e);

            return $this->failed($initiative, $file->type, 'The file could not be deleted. Please try again.');
        }

        return redirect()
            ->to(route('initiatives.show', $initiative).'#initiative-files')
            ->with('initiativeFileDeleted', [
                'type' => $file->type,
                'message' => $file->original_name.' was deleted.',
            ]);
    }

    // PDF files are only stored, so nothing here reads their contents.
    private function importFor(Initiative $initiative, string $type, string $extension): ?InitiativeFileImport
    {
        $class = config("initiative_imports.{$initiative->table_name}.{$type}");

        return $class !== null && $extension !== 'pdf' ? app($class) : null;
    }

    // An initiative keeps one department list. This runs after the new file is saved,
    // so a failed upload never loses the current list. Returns the names removed.
    private function removeOlderLists(Initiative $initiative, InitiativeFile $newest): array
    {
        $older = $initiative->files()
            ->where('type', InitiativeFile::TYPE_DEPARTMENT_LIST)
            ->where('id', '<', $newest->id)
            ->get();

        $removed = [];

        foreach ($older as $file) {
            try {
                $this->remove($file);
                $removed[] = $file->original_name;
            } catch (Throwable $e) {
                // The newest list is already in use, and the next upload tries this again.
                report($e);
            }
        }

        return $removed;
    }

    private function remove(InitiativeFile $file): void
    {
        $file->delete();

        // The record goes first: a leftover file is harmless, a record with no file is not.
        if (! Storage::disk('local')->delete($file->stored_path)) {
            Log::warning('Deleted initiative file could not be removed from storage.', ['path' => $file->stored_path]);
        }

        // Deleting cannot be undone, so keep a record of who did it.
        Log::info('Initiative file deleted.', [
            'initiative_id' => $file->initiative_id,
            'type' => $file->type,
            'original_name' => $file->original_name,
            'row_count' => $file->row_count,
            'deleted_by' => auth()->id(),
        ]);
    }

    private function rules(string $type): array
    {
        return [
            'file' => [
                'required',
                'file',
                'extensions:'.self::EXTENSIONS[$type],
                'max:'.($type === InitiativeFile::TYPE_DEPARTMENT_LIST ? 20480 : 102400),
            ],
        ];
    }

    private function messages(string $type): array
    {
        return [
            'file.required' => 'Choose a file to upload.',
            'file.file' => 'The file could not be uploaded. Please try again.',
            'file.uploaded' => 'The file could not be uploaded. Please try again.',
            'file.extensions' => self::EXTENSION_MESSAGES[$type],
            'file.max' => $type === InitiativeFile::TYPE_DEPARTMENT_LIST
                ? 'The file must be 20 MB or smaller.'
                : 'The file must be 100 MB or smaller.',
        ];
    }

    private function checkPdf(UploadedFile $upload, string $extension): void
    {
        if ($extension === 'pdf' && file_get_contents($upload->getRealPath(), false, null, 0, 5) !== '%PDF-') {
            throw new RuntimeException('This file is not a valid PDF.');
        }
    }

    // Files whose rows are saved count those rows. A department list with no import of its
    // own is still counted for Total Schools, and other files are just stored.
    private function entries(UploadedFile $upload, string $extension, string $type, ?array $prepared, SpreadsheetReader $reader): array
    {
        if ($prepared !== null) {
            return ['count' => count($prepared['rows']), 'sheet' => $prepared['sheet']];
        }

        if ($type !== InitiativeFile::TYPE_DEPARTMENT_LIST) {
            return ['count' => null, 'sheet' => null];
        }

        return $reader->countEntries($upload->getRealPath(), $extension)
            ?? throw new RuntimeException('This file has no rows.');
    }

    private function savedMessage(UploadedFile $upload, string $type, array $entries, array $replaced): string
    {
        $message = $upload->getClientOriginalName().' saved as a '.self::LABELS[$type];

        if ($entries['count'] !== null) {
            $message .= ' with '.number_format($entries['count']).' '.($entries['count'] === 1 ? 'entry' : 'entries');
        }

        if ($entries['sheet'] !== null) {
            $message .= ' from sheet "'.$entries['sheet'].'"';
        }

        return $message.'.'.($replaced === [] ? '' : ' It replaced '.implode(', ', $replaced).'.');
    }

    private function failed(Initiative $initiative, string $type, string $message)
    {
        return redirect()
            ->to(route('initiatives.show', $initiative).'#initiative-files')
            ->with('initiativeFileError', ['type' => $type, 'message' => $message]);
    }
}
