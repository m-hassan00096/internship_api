<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Define permissions
        $permissions = [
            // Admin permissions
            'view users',
            'create users',
            'update users',
            'delete users',
            'view reports',
            'manage settings',

            // Order & Profile permissions
            'view orders',
            'create orders',
            'update orders',
            'view profile',
            'update profile',

            // Vendor permissions
            'manage products',
            'manage staff',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // 3. Create roles and assign permissions
        // Super Admin (Admin Portal)
        $superAdminRole = Role::findOrCreate('Super Admin', 'web');
        $superAdminRole->syncPermissions(Permission::all());

        // Admin Support (Admin Staff)
        $adminSupportRole = Role::findOrCreate('Admin Support', 'web');
        $adminSupportRole->syncPermissions([
            'view users',
            'view reports',
            'view orders',
            'view profile',
        ]);

        // Vendor Owner (Vendor Portal)
        $vendorOwnerRole = Role::findOrCreate('Vendor Owner', 'web');
        $vendorOwnerRole->syncPermissions([
            'view orders',
            'create orders',
            'update orders',
            'manage products',
            'manage staff',
            'view profile',
            'update profile',
        ]);

        // Vendor Cashier (Vendor Staff)
        $vendorCashierRole = Role::findOrCreate('Vendor Cashier', 'web');
        $vendorCashierRole->syncPermissions([
            'view orders',
            'create orders',
            'update orders',
            'view profile',
            'update profile',
        ]);

        // 4. Seed Admin 1 and staff
        $admin1 = User::firstOrCreate(
            ['email' => 'admin1@example.com'],
            [
                'phone' => '01011111111',
                'password' => 'password123',
                'role' => UserRole::ADMIN,
                'manager_id' => null,
                'is_active' => true,
            ]
        );
        $admin1->syncRoles([$superAdminRole]);

        $staffAdmin1 = User::firstOrCreate(
            ['email' => 'staff1_admin1@example.com'],
            [
                'phone' => '01011111112',
                'password' => 'password123',
                'role' => UserRole::ADMIN,
                'manager_id' => $admin1->id,
                'is_active' => true,
            ]
        );
        $staffAdmin1->update(['manager_id' => $admin1->id]);
        $staffAdmin1->syncRoles([$adminSupportRole]);

        // 5. Seed Admin 2 and staff
        $admin2 = User::firstOrCreate(
            ['email' => 'admin2@example.com'],
            [
                'phone' => '01022222221',
                'password' => 'password123',
                'role' => UserRole::ADMIN,
                'manager_id' => null,
                'is_active' => true,
            ]
        );
        $admin2->syncRoles([$superAdminRole]);

        $staffAdmin2 = User::firstOrCreate(
            ['email' => 'staff1_admin2@example.com'],
            [
                'phone' => '01022222222',
                'password' => 'password123',
                'role' => UserRole::ADMIN,
                'manager_id' => $admin2->id,
                'is_active' => true,
            ]
        );
        $staffAdmin2->update(['manager_id' => $admin2->id]);
        $staffAdmin2->syncRoles([$adminSupportRole]);

        // 6. Seed Vendor 1 and staff
        $vendor1 = User::firstOrCreate(
            ['email' => 'vendor1@example.com'],
            [
                'phone' => '01033333331',
                'password' => 'password123',
                'role' => UserRole::VENDOR,
                'manager_id' => null,
                'is_active' => true,
            ]
        );
        $vendor1->syncRoles([$vendorOwnerRole]);

        $cashier1 = User::firstOrCreate(
            ['email' => 'cashier1@example.com'],
            [
                'phone' => '01033333332',
                'password' => 'password123',
                'role' => UserRole::VENDOR,
                'manager_id' => $vendor1->id,
                'is_active' => true,
            ]
        );
        $cashier1->update(['manager_id' => $vendor1->id]);
        $cashier1->syncRoles([$vendorCashierRole]);

        // 7. Seed Vendor 2 and staff
        $vendor2 = User::firstOrCreate(
            ['email' => 'vendor2@example.com'],
            [
                'phone' => '01044444441',
                'password' => 'password123',
                'role' => UserRole::VENDOR,
                'manager_id' => null,
                'is_active' => true,
            ]
        );
        $vendor2->syncRoles([$vendorOwnerRole]);

        $cashier2 = User::firstOrCreate(
            ['email' => 'cashier2@example.com'],
            [
                'phone' => '01044444442',
                'password' => 'password123',
                'role' => UserRole::VENDOR,
                'manager_id' => $vendor2->id,
                'is_active' => true,
            ]
        );
        $cashier2->update(['manager_id' => $vendor2->id]);
        $cashier2->syncRoles([$vendorCashierRole]);

        // 8. Seed Client
        $client = User::firstOrCreate(
            ['email' => 'client@example.com'],
            [
                'phone' => '01000000001',
                'password' => 'password123',
                'role' => UserRole::CLIENT,
                'manager_id' => null,
                'is_active' => true,
            ]
        );
        $client->update(['is_active' => true]);
        $client->syncPermissions([
            'view profile',
            'update profile',
        ]);
    }
}
