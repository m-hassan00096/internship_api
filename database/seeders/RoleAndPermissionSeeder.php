<?php

namespace Database\Seeders;

use App\Enums\PermissionScope;
use App\Enums\UserScope;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Define permissions with scopes
        // Admin permissions
        $adminPermissions = [
            'view users',
            'create users',
            'update users',
            'delete users',
            'view reports',
            'manage settings',
        ];

        foreach ($adminPermissions as $permission) {
            Permission::findOrCreate($permission, 'web')->update([
                'scope' => PermissionScope::ADMIN,
            ]);
        }

        // Vendor permissions
        $vendorPermissions = [
            'view orders',
            'create orders',
            'update orders',
            'view profile',
            'update profile',
            'manage products',
            'manage staff',
        ];

        foreach ($vendorPermissions as $permission) {
            Permission::findOrCreate($permission, 'web')->update([
                'scope' => PermissionScope::VENDOR,
            ]);
        }

        // 3. Create global roles and assign permissions
        setPermissionsTeamId(null);

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

        // 4. Seed Admin 1 and staff
        $admin1 = User::firstOrCreate(
            ['email' => 'admin1@example.com'],
            [
                'phone' => '01011111111',
                'password' => 'password123',
                'role' => UserScope::ADMIN,
                'manager_id' => null,
                'is_active' => true,
            ]
        );
        setPermissionsTeamId(1);
        $admin1->syncRoles([$superAdminRole]);

        $staffAdmin1 = User::firstOrCreate(
            ['email' => 'staff1_admin1@example.com'],
            [
                'phone' => '01011111112',
                'password' => 'password123',
                'role' => UserScope::ADMIN,
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
                'role' => UserScope::ADMIN,
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
                'role' => UserScope::ADMIN,
                'manager_id' => $admin2->id,
                'is_active' => true,
            ]
        );
        $staffAdmin2->update(['manager_id' => $admin2->id]);
        $staffAdmin2->syncRoles([$adminSupportRole]);

        // 6. Seed Vendor 1 and staff with team roles
        $vendor1 = User::firstOrCreate(
            ['email' => 'vendor1@example.com'],
            [
                'phone' => '01033333331',
                'password' => 'password123',
                'role' => UserScope::VENDOR,
                'manager_id' => null,
                'is_active' => true,
            ]
        );

        setPermissionsTeamId($vendor1->id);

        $vendor1OwnerRole = Role::findOrCreate('Owner', 'web');
        $vendor1OwnerRole->syncPermissions([
            'view orders',
            'create orders',
            'update orders',
            'manage products',
            'manage staff',
            'view profile',
            'update profile',
        ]);
        $vendor1->syncRoles([$vendor1OwnerRole]);

        $vendor1CashierRole = Role::findOrCreate('Cashier', 'web');
        $vendor1CashierRole->syncPermissions([
            'view orders',
            'create orders',
            'update orders',
            'view profile',
            'update profile',
        ]);

        $cashier1 = User::firstOrCreate(
            ['email' => 'cashier1@example.com'],
            [
                'phone' => '01033333332',
                'password' => 'password123',
                'role' => UserScope::VENDOR,
                'manager_id' => $vendor1->id,
                'is_active' => true,
            ]
        );
        $cashier1->update(['manager_id' => $vendor1->id]);
        $cashier1->syncRoles([$vendor1CashierRole]);

        // 7. Seed Vendor 2 and staff with team roles
        $vendor2 = User::firstOrCreate(
            ['email' => 'vendor2@example.com'],
            [
                'phone' => '01044444441',
                'password' => 'password123',
                'role' => UserScope::VENDOR,
                'manager_id' => null,
                'is_active' => true,
            ]
        );

        setPermissionsTeamId($vendor2->id);

        $vendor2OwnerRole = Role::findOrCreate('Owner', 'web');
        $vendor2OwnerRole->syncPermissions([
            'view orders',
            'create orders',
            'update orders',
            'manage products',
            'manage staff',
            'view profile',
            'update profile',
        ]);
        $vendor2->syncRoles([$vendor2OwnerRole]);

        $vendor2CashierRole = Role::findOrCreate('Cashier', 'web');
        $vendor2CashierRole->syncPermissions([
            'view orders',
            'create orders',
            'update orders',
            'view profile',
            'update profile',
        ]);

        $cashier2 = User::firstOrCreate(
            ['email' => 'cashier2@example.com'],
            [
                'phone' => '01044444442',
                'password' => 'password123',
                'role' => UserScope::VENDOR,
                'manager_id' => $vendor2->id,
                'is_active' => true,
            ]
        );
        $cashier2->update(['manager_id' => $vendor2->id]);
        $cashier2->syncRoles([$vendor2CashierRole]);

        // 8. Seed Client
        $client = User::firstOrCreate(
            ['email' => 'client@example.com'],
            [
                'phone' => '01000000001',
                'password' => 'password123',
                'role' => UserScope::CLIENT,
                'manager_id' => null,
                'is_active' => true,
            ]
        );
        $client->update(['is_active' => true]);
        setPermissionsTeamId(1);
        $client->syncPermissions([
            'view profile',
            'update profile',
        ]);
    }
}
