<?php

namespace App\Imports\CallCenter;

use App\Models\CallCenterUpload;
use Illuminate\Support\Facades\DB;

// Writes how far a background upload has got, for the progress bar. The rows are saved inside one transaction
// so a failed file changes nothing, and nothing written inside it can be seen until it ends; so progress is
// written through a second connection to the same database, which the page sees at once.
class UploadProgress
{
    private const CONNECTION = 'call_center_progress';

    // Write at most this often while rows are being read.
    private const EVERY_SECONDS = 1.0;

    private float $lastWrite = 0.0;

    private ?string $connection = null;

    public function __construct(private CallCenterUpload $upload)
    {
    }

    public function phase(string $phase, ?int $total = null): void
    {
        $this->write(['phase' => $phase, 'rows_total' => $total, 'rows_done' => 0], true);
    }

    public function advance(int $done, bool $now = false): void
    {
        $this->write(['rows_done' => $done], $now);
    }

    private function write(array $values, bool $now): void
    {
        $time = microtime(true);

        if (! $now && $time - $this->lastWrite < self::EVERY_SECONDS) {
            return;
        }

        $this->lastWrite = $time;

        DB::connection($this->connectionName())
            ->table('call_center_uploads')
            ->where('id', $this->upload->id)
            ->update($values + ['updated_at' => now()]);
    }

    private function connectionName(): string
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        $default = config('database.default');
        $settings = config("database.connections.{$default}");

        // An in-memory SQLite database (the tests) is a different database on every connection, and there
        // the page is not watching anyway.
        if (($settings['driver'] ?? null) === 'sqlite' && ($settings['database'] ?? null) === ':memory:') {
            return $this->connection = $default;
        }

        if (config('database.connections.'.self::CONNECTION) === null) {
            config(['database.connections.'.self::CONNECTION => $settings]);
        }

        return $this->connection = self::CONNECTION;
    }
}
