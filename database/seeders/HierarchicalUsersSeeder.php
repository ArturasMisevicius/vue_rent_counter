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
        $superadmin = User::create([
            'tenant_id' => null,
            'property_id' => null,
            'parent_user_id' => null,
            'name' => 'System Superadmin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::SUPERADMIN,
            'is_active' => true,
            'organization_name' => null,
            'email_verified_at' => now(),
        ]);

        // Create first admin with subscription
        $admin1 = User::create([
            'tenant_id' => 1,
            'property_id' => null,
            'parent_user_id' => null,
            'name' => 'Vilnius Properties Ltd',
            'email' => 'admin1@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN,
            'is_active' => true,
            'organization_name' => 'Vilnius Properties Ltd',
            'email_verified_at' => now(),
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

        // Create second admin with subscription
        $admin2 = User::create([
            'tenant_id' => 2,
            'property_id' => null,
            'parent_user_id' => null,
            'name' => 'Baltic Real Estate',
            'email' => 'admin2@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN,
            'is_active' => true,
            'organization_name' => 'Baltic Real Estate',
            'email_verified_at' => now(),
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

        // Create third admin with expired subscription
        $admin3 = User::create([
            'tenant_id' => 3,
            'property_id' => null,
            'parent_user_id' => null,
            'name' => 'Old Town Management',
            'email' => 'admin3@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN,
            'is_active' => true,
            'organization_name' => 'Old Town Management',
            'email_verified_at' => now(),
        ]);

        // Create expired subscription for admin3
        Subscription::create([
            'user_id' => $admin3->id,
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
            User::create([
                'tenant_id' => 1,
                'property_id' => $properties1[0]->id,
                'parent_user_id' => $admin1->id,
                'name' => 'Jonas Petraitis',
                'email' => 'jonas.petraitis@example.com',
                'password' => Hash::make('password'),
                'role' => UserRole::TENANT,
                'is_active' => true,
                'organization_name' => null,
                'email_verified_at' => now(),
            ]);

            User::create([
                'tenant_id' => 1,
                'property_id' => $properties1[1]->id,
                'parent_user_id' => $admin1->id,
                'name' => 'Ona Kazlauskienė',
                'email' => 'ona.kazlauskiene@example.com',
                'password' => Hash::make('password'),
                'role' => UserRole::TENANT,
                'is_active' => true,
                'organization_name' => null,
                'email_verified_at' => now(),
            ]);

            User::create([
                'tenant_id' => 1,
                'property_id' => $properties1[2]->id,
                'parent_user_id' => $admin1->id,
                'name' => 'Petras Jonaitis',
                'email' => 'petras.jonaitis@example.com',
                'password' => Hash::make('password'),
                'role' => UserRole::TENANT,
                'is_active' => true,
                'organization_name' => null,
                'email_verified_at' => now(),
            ]);
        }

        // Properties for tenant_id 2
        $properties2 = Property::where('tenant_id', 2)->limit(2)->get();
        
        // Create tenants for admin2
        if ($properties2->count() >= 2) {
            User::create([
                'tenant_id' => 2,
                'property_id' => $properties2[0]->id,
                'parent_user_id' => $admin2->id,
                'name' => 'Marija Vasiliauskaite',
                'email' => 'marija.vasiliauskaite@example.com',
                'password' => Hash::make('password'),
                'role' => UserRole::TENANT,
                'is_active' => true,
                'organization_name' => null,
                'email_verified_at' => now(),
            ]);

            User::create([
                'tenant_id' => 2,
                'property_id' => $properties2[1]->id,
                'parent_user_id' => $admin2->id,
                'name' => 'Andrius Butkus',
                'email' => 'andrius.butkus@example.com',
                'password' => Hash::make('password'),
                'role' => UserRole::TENANT,
                'is_active' => true,
                'organization_name' => null,
                'email_verified_at' => now(),
            ]);
        }

        // Create one inactive tenant for admin1
        if ($properties1->count() >= 1) {
            User::create([
                'tenant_id' => 1,
                'property_id' => $properties1[0]->id,
                'parent_user_id' => $admin1->id,
                'name' => 'Deactivated Tenant',
                'email' => 'deactivated@example.com',
                'password' => Hash::make('password'),
                'role' => UserRole::TENANT,
                'is_active' => false,
                'organization_name' => null,
                'email_verified_at' => now(),
            ]);
        }
    }
}
