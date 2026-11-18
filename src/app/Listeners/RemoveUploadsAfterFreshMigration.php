<?php

namespace App\Listeners;

use App\Models\InitiativeFile;
use Illuminate\Database\Events\DatabaseRefreshed;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Output\ConsoleOutput;

// migrate:fresh empties every table, so the uploaded files it leaves behind belong to nothing.
// They are removed straight after the tables are rebuilt, before any seeding.
class RemoveUploadsAfterFreshMigration
{
    // Where InitiativeFile::store() puts every upload, on the local disk (storage/app/private).
    private const FOLDER = 'initiatives';

    public function handle(DatabaseRefreshed $event): void
    {
        // Tests and trial runs on a temporary database share this storage folder, so they never remove anything.
        if (app()->environment('testing')) {
            return;
        }

        // The uploads belong to the default database. Refreshing another one leaves them alone.
        if ($event->database !== null && $event->database !== config('database.default')) {
            return;
        }

        $disk = Storage::disk('local');

        // Only files that no record points to are removed. After a fresh migration that is all of them.
        $kept = InitiativeFile::query()->pluck('stored_path')->flip();
        $removed = 0;

        foreach ($disk->allFiles(self::FOLDER) as $path) {
            if (! $kept->has($path) && $disk->delete($path)) {
                $removed++;
            }
        }

        // Deepest folders first, so a folder emptied above can go too.
        foreach (collect($disk->allDirectories(self::FOLDER))->sortByDesc(fn ($dir) => substr_count($dir, '/')) as $dir) {
            if ($disk->files($dir) === [] && $disk->directories($dir) === []) {
                $disk->deleteDirectory($dir);
            }
        }

        Log::info('Uploaded files removed after migrate:fresh.', ['files' => $removed]);

        if (app()->runningInConsole()) {
            (new ConsoleOutput)->writeln("  <info>Removed {$removed} previously uploaded file(s) from storage/app/private/".self::FOLDER.'.</info>');
        }
    }
}
