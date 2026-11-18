<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminSeeder extends Seeder
{
    public const EMAIL = 'admin@smu.local';

    // Runs again on every db:seed, so it must not reset a password that has since been changed.
    public function run(): void
    {
        if (User::where('email', self::EMAIL)->exists()) {
            return;
        }

        User::create([
            'email' => self::EMAIL,
            'name' => 'SMU Administrator',
            'password' => Hash::make(env('ADMIN_PASSWORD') ?: throw new RuntimeException('Set ADMIN_PASSWORD in src/.env before seeding.')),
            'email_verified_at' => now(),
        ]);
    }
}
