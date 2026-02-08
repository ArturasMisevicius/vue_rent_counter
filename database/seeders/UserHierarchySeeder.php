<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Property;
use App\Models\SystemTenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * User Hierarchy Seeder
 * 
 * Creates a realistic user hierarchy for testing multi-tenant functionality.
 */
class UserHierarchySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create system tenant for superadmin
        $systemTenant = SystemTenant::firstOrCreate([
            'name' => 'System Administration',
            'slug' => 'system-admin',
        ]);

        // Create superadmin
        $superadmin = User::firstOrCreate(
            ['email' => 'superadmin@tenanto.com'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('password'),
                'role' => UserRole::SUPERADMIN,
                'is_super_admin' => true,
                'is_active' => true,
                'email_verified_at' => now(),
                'system_tenant_id' => $systemTenant->id,
            ]
        );

        // Create multiple tenant organizations
        $tenantIds = [1, 2, 3];
        
        foreach ($tenantIds as $tenantId) {
            // Create admin for each tenant
            $admin = User::firstOrCreate(
                ['email' => "admin{$tenantId}@example.com"],
                [
                    'name' => "Admin User {$tenantId}",
                    'password' => Hash::make('password'),
                    'role' => UserRole::ADMIN,
                    'tenant_id' => $tenantId,
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'organization_name' => "Property Management {$tenantId}",
                ]
            );

            // Create manager for each tenant
            $manager = User::firstOrCreate(
                ['email' => "manager{$tenantId}@example.com"],
                [
                    'name' => "Manager User {$tenantId}",
                    'password' => Hash::make('password'),
                    'role' => UserRole::MANAGER,
                    'tenant_id' => $tenantId,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            // Create properties for this tenant
            $properties = Property::factory()
                ->count(3)
                ->create(['tenant_id' => $tenantId]);

            // Create tenant users for each property
            foreach ($properties as $property) {
                $tenant = User::firstOrCreate(
                    ['email' => "tenant{$tenantId}p{$property->id}@example.com"],
                    [
                        'name' => "Tenant User {$tenantId}-{$property->id}",
                        'password' => Hash::make('password'),
                        'role' => UserRole::TENANT,
                        'tenant_id' => $tenantId,
                        'property_id' => $property->id,
                        'parent_user_id' => $admin->id,
                        'is_active' => true,
                        'email_verified_at' => now(),
                    ]
                );

                // Create API tokens for some users
                if (rand(1, 3) === 1) {
                    $tenant->createApiToken('mobile-app');
                }
            }

            // Create some inactive/suspended users for testing
            User::factory()
                ->count(2)
                ->inactive()
                ->create(['tenant_id' => $tenantId]);

            User::factory()
                ->count(1)
                ->suspended('Policy violation')
                ->create(['tenant_id' => $tenantId]);

            User::factory()
                ->count(1)
                ->unverified()
                ->create(['tenant_id' => $tenantId]);
        }

        $this->command->info('User hierarchy seeded successfully!');
        $this->command->info('Created:');
        $this->command->info('- 1 Superadmin');
        $this->command->info('- 3 Admins (one per tenant)');
        $this->command->info('- 3 Managers (one per tenant)');
        $this->command->info('- 9 Tenant users (3 per tenant)');
        $this->command->info('- Various inactive/suspended/unverified users');
    }
}