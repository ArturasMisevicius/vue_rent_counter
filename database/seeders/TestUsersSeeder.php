<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    /**
     * Seed test users with known credentials for manual testing.
     * Updated to support hierarchical user structure.
     */
    public function run(): void
    {
        // Admin user for tenant 1 with subscription
        $admin1 = User::create([
            'tenant_id' => 1,
            'property_id' => null,
            'parent_user_id' => null,
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN,
            'is_active' => true,
            'organization_name' => 'Test Organization 1',
        ]);

        // Create subscription for admin1
        Subscription::create([
            'user_id' => $admin1->id,
            'plan_type' => 'professional',
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
            'max_properties' => 50,
            'max_tenants' => 200,
        ]);

        // Manager for tenant 1 (legacy role)
        User::create([
            'tenant_id' => 1,
            'property_id' => null,
            'parent_user_id' => null,
            'name' => 'Test Manager',
            'email' => 'manager@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::MANAGER,
            'is_active' => true,
            'organization_name' => null,
        ]);

        // Admin user for tenant 2 with subscription
        $admin2 = User::create([
            'tenant_id' => 2,
            'property_id' => null,
            'parent_user_id' => null,
            'name' => 'Test Manager 2',
            'email' => 'manager2@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN,
            'is_active' => true,
            'organization_name' => 'Test Organization 2',
        ]);

        // Create subscription for admin2
        Subscription::create([
            'user_id' => $admin2->id,
            'plan_type' => 'basic',
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addMonths(6),
            'max_properties' => 10,
            'max_tenants' => 50,
        ]);

        // Get properties for tenant assignments
        $properties1 = Property::where('tenant_id', 1)->limit(2)->get();
        $properties2 = Property::where('tenant_id', 2)->limit(1)->get();

        // Tenant user for tenant 1 with property assignment
        if ($properties1->count() >= 1) {
            User::create([
                'tenant_id' => 1,
                'property_id' => $properties1[0]->id,
                'parent_user_id' => $admin1->id,
                'name' => 'Test Tenant',
                'email' => 'tenant@test.com',
                'password' => Hash::make('password'),
                'role' => UserRole::TENANT,
                'is_active' => true,
                'organization_name' => null,
            ]);
        }

        // Second tenant user for tenant 1 with property assignment
        if ($properties1->count() >= 2) {
            User::create([
                'tenant_id' => 1,
                'property_id' => $properties1[1]->id,
                'parent_user_id' => $admin1->id,
                'name' => 'Test Tenant 2',
                'email' => 'tenant2@test.com',
                'password' => Hash::make('password'),
                'role' => UserRole::TENANT,
                'is_active' => true,
                'organization_name' => null,
            ]);
        }

        // Tenant user for tenant 2 with property assignment
        if ($properties2->count() >= 1) {
            User::create([
                'tenant_id' => 2,
                'property_id' => $properties2[0]->id,
                'parent_user_id' => $admin2->id,
                'name' => 'Test Tenant 3',
                'email' => 'tenant3@test.com',
                'password' => Hash::make('password'),
                'role' => UserRole::TENANT,
                'is_active' => true,
                'organization_name' => null,
            ]);
        }
    }
}

