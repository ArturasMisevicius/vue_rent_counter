<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Test Users Seeder
 * 
 * Creates test users with known credentials for authentication testing.
 * Provides users for each role (Admin, Manager, Tenant) across multiple tenants
 * to enable comprehensive testing of authentication, authorization, and multi-tenancy.
 * 
 * Test Users Created:
 * - Admin: admin@test.com (tenant_id=1)
 * - Manager: manager@test.com (tenant_id=1), manager2@test.com (tenant_id=2)
 * - Tenant: tenant@test.com (tenant_id=1), tenant2@test.com (tenant_id=1), tenant3@test.com (tenant_id=2)
 * 
 * All users have password: "password"
 * 
 * Requirements: 1.1, 1.2, 1.3, 1.4
 */
final class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin user (tenant_id = 1, but can access all data)
        User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
            'is_active' => true,
            'organization_name' => 'Test Property Management A',
            'email_verified_at' => now(),
        ]);

        // Manager for tenant 1
        User::create([
            'name' => 'Test Manager',
            'email' => 'manager@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::MANAGER,
            'tenant_id' => 1,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Manager for tenant 2
        User::create([
            'name' => 'Test Manager 2',
            'email' => 'manager2@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::MANAGER,
            'tenant_id' => 2,
            'is_active' => true,
            'organization_name' => 'Test Property Management B',
            'email_verified_at' => now(),
        ]);

        // Tenant users for tenant 1
        User::create([
            'name' => 'Test Tenant',
            'email' => 'tenant@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::TENANT,
            'tenant_id' => 1,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Test Tenant 2',
            'email' => 'tenant2@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::TENANT,
            'tenant_id' => 1,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // Tenant user for tenant 2
        User::create([
            'name' => 'Test Tenant 3',
            'email' => 'tenant3@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::TENANT,
            'tenant_id' => 2,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->command->info('Test users created successfully:');
        $this->command->table(
            ['Role', 'Email', 'Password', 'Tenant ID'],
            [
                ['Admin', 'admin@test.com', 'password', '1'],
                ['Manager', 'manager@test.com', 'password', '1'],
                ['Manager', 'manager2@test.com', 'password', '2'],
                ['Tenant', 'tenant@test.com', 'password', '1'],
                ['Tenant', 'tenant2@test.com', 'password', '1'],
                ['Tenant', 'tenant3@test.com', 'password', '2'],
            ]
        );
    }
}