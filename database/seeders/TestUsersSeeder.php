<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    /**
     * Seed test users with known credentials for manual testing.
     */
    public function run(): void
    {
        // Admin user (tenant_id = 1, but can access all data)
        User::create([
            'tenant_id' => 1,
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN,
        ]);

        // Manager for tenant 1
        User::create([
            'tenant_id' => 1,
            'name' => 'Test Manager',
            'email' => 'manager@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::MANAGER,
        ]);

        // Manager for tenant 2
        User::create([
            'tenant_id' => 2,
            'name' => 'Test Manager 2',
            'email' => 'manager2@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::MANAGER,
        ]);

        // Tenant user for tenant 1
        User::create([
            'tenant_id' => 1,
            'name' => 'Test Tenant',
            'email' => 'tenant@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::TENANT,
        ]);

        // Second tenant user for tenant 1
        User::create([
            'tenant_id' => 1,
            'name' => 'Test Tenant 2',
            'email' => 'tenant2@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::TENANT,
        ]);

        // Tenant user for tenant 2
        User::create([
            'tenant_id' => 2,
            'name' => 'Test Tenant 3',
            'email' => 'tenant3@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::TENANT,
        ]);
    }
}

