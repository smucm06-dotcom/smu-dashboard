<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    public const DISABLED_MESSAGE = 'This account has been disabled. Please contact the administrator.';

    // The same as the database, so a user just created here already has these values.
    protected $attributes = [
        'is_active' => true,
        'must_change_password' => false,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'must_change_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Role::ADMIN);
    }

    // The departments this user belongs to, through their roles, by name. A user can belong to several;
    // admins belong to none, because they can open everything.
    public function departments(): Collection
    {
        return new Collection($this->roles
            ->filter(fn (Role $role) => $role->department_id !== null)
            ->map(fn (Role $role) => $role->department)
            ->filter()
            ->sortBy('name')
            ->values()
            ->all());
    }

    public function belongsToDepartment(Department $department): bool
    {
        return $this->departments()->contains($department);
    }

    public function canOpenInitiative(Initiative $initiative): bool
    {
        return $this->permissions->contains('initiative_id', $initiative->id);
    }
}
