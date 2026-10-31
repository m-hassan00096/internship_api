<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        User::firstOrCreate(
            ['email' => 'client@example.com'],
            [
                'phone' => '01000000001',
                'password' => 'password123',
                'role' => UserRole::CLIENT,
                'is_active' => true,
            ]
        );
    }
}
