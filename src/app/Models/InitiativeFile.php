<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class InitiativeFile extends Model
{
    public const TYPE_DEPARTMENT_LIST = 'department_list';
    public const TYPE_OUR_VISITS = 'our_visits';
    public const TYPE_OUR_REPORT = 'our_report';
    public const TYPE_DEPARTMENT_RESPONSE = 'department_response';

    protected $fillable = [
        'initiative_id',
        'type',
        'original_name',
        'stored_path',
        'row_count',
        'uploaded_by',
    ];

    protected $casts = [
        'row_count' => 'integer',
    ];

    // Keeps the file in private storage and records it. If the record fails, the file is removed.
    public static function storeUpload(Initiative $initiative, UploadedFile $upload, string $type, array $attributes = []): self
    {
        $path = $upload->store("initiatives/{$initiative->id}/{$type}", 'local');

        if ($path === false) {
            throw new RuntimeException('The file could not be saved.');
        }

        try {
            return self::create($attributes + [
                'initiative_id' => $initiative->id,
                'type' => $type,
                'original_name' => mb_substr($upload->getClientOriginalName(), 0, 255),
                'stored_path' => $path,
                'uploaded_by' => auth()->id(),
            ]);
        } catch (Throwable $e) {
            Storage::disk('local')->delete($path);

            throw $e;
        }
    }

    public function initiative(): BelongsTo
    {
        return $this->belongsTo(Initiative::class, 'initiative_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
