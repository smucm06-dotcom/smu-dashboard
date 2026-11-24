<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// One call center file being read in the background, then its result (see the call_center_uploads table).
class CallCenterUpload extends Model
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';

    // The steps a running upload goes through, in order.
    public const PHASE_READING = 'reading';
    public const PHASE_SAVING = 'saving';
    public const PHASE_LINKING = 'linking';

    // A running upload that has not moved for this long has stopped (the worker was restarted, say).
    public const STALLED_AFTER_MINUTES = 15;

    protected $fillable = [
        'initiative_id',
        'type',
        'original_name',
        'stored_path',
        'fingerprint',
        'status',
        'phase',
        'rows_total',
        'rows_done',
        'result',
        'initiative_file_id',
        'uploaded_by',
        'started_at',
        'finished_at',
        'result_seen_at',
    ];

    protected $casts = [
        'rows_total' => 'integer',
        'rows_done' => 'integer',
        'result' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'result_seen_at' => 'datetime',
    ];

    public function initiative(): BelongsTo
    {
        return $this->belongsTo(Initiative::class);
    }

    // Uploads still waiting or being read.
    public function scopeUnfinished(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_QUEUED, self::STATUS_RUNNING]);
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_DONE, self::STATUS_FAILED], true);
    }

    public function hasStalled(): bool
    {
        return $this->status === self::STATUS_RUNNING
            && $this->updated_at !== null
            && $this->updated_at->lt(now()->subMinutes(self::STALLED_AFTER_MINUTES));
    }
}
