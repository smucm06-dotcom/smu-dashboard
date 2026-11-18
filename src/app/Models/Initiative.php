<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class Initiative extends Model
{
    protected $fillable = [
        'department_id',
        'name',
        'short_name',
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

    public function permission(): HasOne
    {
        return $this->hasOne(Permission::class);
    }

    // Admins see every initiative, and anyone else only the ones given to them.
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->isAdmin()
            ? $query
            : $query->whereIn('id', $user->permissions->pluck('initiative_id'));
    }

    public function shortName(): string
    {
        return $this->short_name ?: $this->name;
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
