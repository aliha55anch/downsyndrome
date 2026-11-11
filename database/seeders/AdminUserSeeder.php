<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = trim((string) env('ADMIN_EMAIL'));
        $password = (string) env('ADMIN_PASSWORD');
        $name = trim((string) env('ADMIN_NAME', 'Little Steps Administrator'));

        if ($email === '' || $password === '') {
            throw new RuntimeException('Set ADMIN_EMAIL and ADMIN_PASSWORD in .env before running the admin seeder.');
        }

        $user = User::firstOrNew(['email' => strtolower($email)]);
        $nameParts = preg_split('/\s+/', $name !== '' ? $name : 'Little Steps Administrator', 2);
        $user->forceFill([
            'name' => $name !== '' ? $name : 'Little Steps Administrator',
            'first_name' => $nameParts[0],
            'last_name' => $nameParts[1] ?? null,
            'password' => Hash::make($password),
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ])->save();
    }
}
