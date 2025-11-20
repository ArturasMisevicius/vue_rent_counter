<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TestTenantsSeeder extends Seeder
{
    /**
     * Seed test tenant (renter) records linked to properties and users.
     */
    public function run(): void
    {
        // Get tenant users (renters) from the users table
        $tenantUser1 = User::where('email', 'tenant@test.com')->first();
        $tenantUser2 = User::where('email', 'tenant2@test.com')->first();
        $tenantUser3 = User::where('email', 'tenant3@test.com')->first();

        // Get properties for tenant 1 (property management company)
        $tenant1Properties = Property::where('tenant_id', 1)->get();
        
        // Get properties for tenant 2 (property management company)
        $tenant2Properties = Property::where('tenant_id', 2)->get();

        // Create tenant (renter) record for first property of tenant 1
        // Link to tenant@test.com user
        if ($tenant1Properties->count() > 0 && $tenantUser1) {
            Tenant::create([
                'tenant_id' => 1,
                'name' => $tenantUser1->name,
                'email' => $tenantUser1->email,
                'phone' => '+370 600 12345',
                'property_id' => $tenant1Properties[0]->id,
                'lease_start' => Carbon::now()->subYear(),
                'lease_end' => Carbon::now()->addYear(),
            ]);
        }

        // Create tenant (renter) record for second property of tenant 1
        // Link to tenant2@test.com user
        if ($tenant1Properties->count() > 1 && $tenantUser2) {
            Tenant::create([
                'tenant_id' => 1,
                'name' => $tenantUser2->name,
                'email' => $tenantUser2->email,
                'phone' => '+370 600 23456',
                'property_id' => $tenant1Properties[1]->id,
                'lease_start' => Carbon::now()->subMonths(6),
                'lease_end' => Carbon::now()->addMonths(18),
            ]);
        }

        // Create tenant (renter) record for first property of tenant 2
        // Link to tenant3@test.com user
        if ($tenant2Properties->count() > 0 && $tenantUser3) {
            Tenant::create([
                'tenant_id' => 2,
                'name' => $tenantUser3->name,
                'email' => $tenantUser3->email,
                'phone' => '+370 600 34567',
                'property_id' => $tenant2Properties[0]->id,
                'lease_start' => Carbon::now()->subMonths(8),
                'lease_end' => Carbon::now()->addMonths(16),
            ]);
        }

        // Create additional tenant (renter) for second property of tenant 2
        // This tenant doesn't have a user account (can't log in)
        if ($tenant2Properties->count() > 1) {
            Tenant::create([
                'tenant_id' => 2,
                'name' => 'Jonas Petraitis',
                'email' => 'jonas.petraitis@example.com',
                'phone' => '+370 600 45678',
                'property_id' => $tenant2Properties[1]->id,
                'lease_start' => Carbon::now()->subMonths(3),
                'lease_end' => Carbon::now()->addMonths(21),
            ]);
        }
    }
}

