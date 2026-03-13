<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OrganizationTenantsSeeder extends Seeder
{
    /**
     * Seed tenant users for each organization.
     */
    public function run(): void
    {
        // Get all admin users (organizations)
        $admins = User::where('role', UserRole::ADMIN)->get();

        foreach ($admins as $admin) {
            // Get properties for this organization
            $properties = Property::where('tenant_id', $admin->tenant_id)->get();

            if ($properties->isEmpty()) {
                $this->command->warn("No properties found for organization {$admin->organization_name} (tenant_id: {$admin->tenant_id})");
                continue;
            }

            // Create 5-10 tenant users for this organization
            $tenantCount = rand(5, 10);
            
            for ($i = 1; $i <= $tenantCount; $i++) {
                // Assign to a random property
                $property = $properties->random();
                
                $tenant = User::create([
                    'name' => fake()->name(),
                    'email' => fake()->unique()->safeEmail(),
                    'password' => Hash::make('password'),
                    'role' => UserRole::TENANT,
                    'tenant_id' => $admin->tenant_id,
                    'property_id' => $property->id,
                    'parent_user_id' => $admin->id,
                    'is_active' => rand(0, 10) > 1, // 90% active
                    'email_verified_at' => now(),
                ]);

                $this->command->info("Created tenant: {$tenant->name} for {$admin->organization_name}");
            }
        }

        $this->command->info('Organization tenants seeded successfully!');
    }
}
