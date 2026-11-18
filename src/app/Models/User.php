<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
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

    // The department this user belongs to, through their role. Admins have none.
    public function department(): ?Department
    {
        return $this->roles->first(fn (Role $role) => $role->department_id !== null)?->department;
    }

    public function canOpenInitiative(Initiative $initiative): bool
    {
        return $this->permissions->contains('initiative_id', $initiative->id);
    }
}
