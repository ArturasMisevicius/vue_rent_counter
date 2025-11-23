<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class HierarchicalUsersSeeder extends Seeder
{
    /**
     * Seed hierarchical users: superadmin, admins with subscriptions, and tenants.
     */
    public function run(): void
    {
        // Create one superadmin account
        $superadmin = User::factory()
            ->superadmin()
            ->create([
                'name' => 'System Superadmin',
                'email' => 'superadmin@example.com',
                'password' => Hash::make('password'),
            ]);

        // Create first admin with subscription
        $admin1 = User::factory()
            ->admin(1)
            ->create([
                'name' => 'Vilnius Properties Ltd',
                'email' => 'admin1@example.com',
                'password' => Hash::make('password'),
                'organization_name' => 'Vilnius Properties Ltd',
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

        // Create second admin with subscription
        $admin2 = User::factory()
            ->admin(2)
            ->create([
                'name' => 'Baltic Real Estate',
                'email' => 'admin2@example.com',
                'password' => Hash::make('password'),
                'organization_name' => 'Baltic Real Estate',
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

        // Create third admin with expired subscription
        $admin3 = User::factory()
            ->admin(3)
            ->create([
                'name' => 'Old Town Management',
                'email' => 'admin3@example.com',
                'password' => Hash::make('password'),
                'organization_name' => 'Old Town Management',
            ]);

        // Create expired subscription for admin3
        Subscription::factory()
            ->for($admin3)
            ->create([
                'plan_type' => 'basic',
                'status' => 'expired',
                'starts_at' => now()->subYear(),
                'expires_at' => now()->subDays(10),
                'max_properties' => 10,
                'max_tenants' => 50,
            ]);

        // Get properties for tenant assignments
        // Properties for tenant_id 1
        $properties1 = Property::where('tenant_id', 1)->limit(3)->get();
        
        // Create tenants for admin1
        if ($properties1->count() >= 3) {
            User::factory()
                ->tenant(1, $properties1[0]->id, $admin1->id)
                ->create([
                    'name' => 'Jonas Petraitis',
                    'email' => 'jonas.petraitis@example.com',
                    'password' => Hash::make('password'),
                ]);

            User::factory()
                ->tenant(1, $properties1[1]->id, $admin1->id)
                ->create([
                    'name' => 'Ona Kazlauskienė',
                    'email' => 'ona.kazlauskiene@example.com',
                    'password' => Hash::make('password'),
                ]);

            User::factory()
                ->tenant(1, $properties1[2]->id, $admin1->id)
                ->create([
                    'name' => 'Petras Jonaitis',
                    'email' => 'petras.jonaitis@example.com',
                    'password' => Hash::make('password'),
                ]);
        }

        // Properties for tenant_id 2
        $properties2 = Property::where('tenant_id', 2)->limit(2)->get();
        
        // Create tenants for admin2
        if ($properties2->count() >= 2) {
            User::factory()
                ->tenant(2, $properties2[0]->id, $admin2->id)
                ->create([
                    'name' => 'Marija Vasiliauskaite',
                    'email' => 'marija.vasiliauskaite@example.com',
                    'password' => Hash::make('password'),
                ]);

            User::factory()
                ->tenant(2, $properties2[1]->id, $admin2->id)
                ->create([
                    'name' => 'Andrius Butkus',
                    'email' => 'andrius.butkus@example.com',
                    'password' => Hash::make('password'),
                ]);
        }

        // Create one inactive tenant for admin1
        if ($properties1->count() >= 1) {
            User::factory()
                ->tenant(1, $properties1[0]->id, $admin1->id)
                ->inactive()
                ->create([
                    'name' => 'Deactivated Tenant',
                    'email' => 'deactivated@example.com',
                    'password' => Hash::make('password'),
                ]);
        }
    }
}
