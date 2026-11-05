<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            [
                'email' => 'admin@smu.local',
            ],
            [
                'name' => 'SMU Administrator',
                'password' => Hash::make(env('ADMIN_PASSWORD') ?: throw new RuntimeException('Set ADMIN_PASSWORD in src/.env before seeding.')),
                'email_verified_at' => now(),
            ]
        );
    }
}
