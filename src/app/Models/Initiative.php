<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Initiative extends Model
{
    protected $fillable = [
        'department_id',
        'name',
        'table_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(InitiativeFile::class);
    }

    // Every uploaded file, newest first, grouped by type.
    public function filesByType(): Collection
    {
        return $this->files()
            ->with('uploader:id,name')
            ->latest('id')
            ->get()
            ->groupBy('type');
    }
}
