<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds. Creates the initial admin (patient) account
     * using ADMIN_NAME / ADMIN_EMAIL / ADMIN_PASSWORD, or healthy defaults.
     */
    public function run(): void
    {
        if (User::where('role', User::ROLE_ADMIN)->exists()) {
            Log::info('AdminUserSeeder skipped: an admin account already exists.');

            return;
        }

        $email = env('ADMIN_EMAIL', 'admin@example.com');

        User::query()->create([
            'name' => env('ADMIN_NAME', 'Health App Admin'),
            'email' => $email,
            'password' => env('ADMIN_PASSWORD')
                ? Hash::make(env('ADMIN_PASSWORD'))
                : Hash::make('ChangeMe123!'),
            'role' => User::ROLE_ADMIN,
        ]);

        Log::info("AdminUserSeeder created admin account for: {$email}");
    }
}