<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create/Update Admin Utama (admin@gmail.com)
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'username' => 'admin',
                'nip' => 'ADM001',
                'name' => 'Admin ICON',
                'password' => Hash::make(config('auth.admin_password', env('ADMIN_PASSWORD', 'admin123'))),
                'role' => 'admin',
                'department' => 'Operasional Pusat',
                'phone' => '081234567890',
                'email_verified_at' => now(),
            ]
        );

        // 2. Call SalesSeeder for all 16 sales accounts
        $this->call(SalesSeeder::class);
    }
}
