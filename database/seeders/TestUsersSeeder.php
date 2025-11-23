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
        $admin1 = User::factory()
            ->admin(1)
            ->create([
                'name' => 'Test Admin',
                'email' => 'admin@test.com',
                'organization_name' => 'Test Organization 1',
                'password' => Hash::make('password'),
            ]);

        // Create subscription for admin1
        Subscription::factory()
            ->for($admin1)
            ->create([
                'plan_type' => 'professional',
                'status' => 'active',
                'starts_at' => now(),
                'expires_at' => now()->addYear(),
                'max_properties' => 50,
                'max_tenants' => 200,
            ]);

        // Manager for tenant 1 (legacy role)
        User::factory()
            ->manager(1)
            ->create([
                'name' => 'Test Manager',
                'email' => 'manager@test.com',
                'password' => Hash::make('password'),
            ]);

        // Admin user for tenant 2 with subscription
        $admin2 = User::factory()
            ->admin(2)
            ->create([
                'name' => 'Test Manager 2',
                'email' => 'manager2@test.com',
                'organization_name' => 'Test Organization 2',
                'password' => Hash::make('password'),
            ]);

        // Create subscription for admin2
        Subscription::factory()
            ->for($admin2)
            ->create([
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
            User::factory()
                ->tenant(1, $properties1[0]->id, $admin1->id)
                ->create([
                    'name' => 'Test Tenant',
                    'email' => 'tenant@test.com',
                    'password' => Hash::make('password'),
                ]);
        }

        // Second tenant user for tenant 1 with property assignment
        if ($properties1->count() >= 2) {
            User::factory()
                ->tenant(1, $properties1[1]->id, $admin1->id)
                ->create([
                    'name' => 'Test Tenant 2',
                    'email' => 'tenant2@test.com',
                    'password' => Hash::make('password'),
                ]);
        }

        // Tenant user for tenant 2 with property assignment
        if ($properties2->count() >= 1) {
            User::factory()
                ->tenant(2, $properties2[0]->id, $admin2->id)
                ->create([
                    'name' => 'Test Tenant 3',
                    'email' => 'tenant3@test.com',
                    'password' => Hash::make('password'),
                ]);
        }
    }
}
