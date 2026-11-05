<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role as SpatieRole;

// A department role only says which department a user belongs to. It holds no permissions:
// initiatives are given to each user directly, so people in one department can differ.
class Role extends SpatieRole
{
    public const ADMIN = 'admin';

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function isAdmin(): bool
    {
        return $this->name === self::ADMIN;
    }

    public function label(): string
    {
        return $this->isAdmin() ? 'Administrator' : ($this->department?->name ?? $this->name);
    }

    // Administrator first, then the departments by name.
    public static function forSelection(): Collection
    {
        return static::query()
            ->with('department')
            ->get()
            ->sortBy(fn (self $role) => [$role->isAdmin() ? 0 : 1, $role->label()])
            ->values();
    }
}
