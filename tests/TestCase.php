<?php

namespace Tests;

use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    /**
     * Authenticate as an admin user.
     * 
     * @return User
     */
    protected function actingAsAdmin(): User
    {
        $admin = User::factory()->create([
            'tenant_id' => 1,
            'role' => UserRole::ADMIN,
            'email' => 'test-admin-' . uniqid() . '@test.com',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($admin);

        return $admin;
    }

    /**
     * Authenticate as a manager user for a specific tenant.
     * 
     * @param int $tenantId
     * @return User
     */
    protected function actingAsManager(int $tenantId = 1): User
    {
        $manager = User::factory()->create([
            'tenant_id' => $tenantId,
            'role' => UserRole::MANAGER,
            'email' => 'test-manager-' . uniqid() . '@test.com',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($manager);

        return $manager;
    }

    /**
     * Authenticate as a tenant user for a specific tenant.
     * 
     * @param int $tenantId
     * @return User
     */
    protected function actingAsTenant(int $tenantId = 1): User
    {
        $tenant = User::factory()->create([
            'tenant_id' => $tenantId,
            'role' => UserRole::TENANT,
            'email' => 'test-tenant-' . uniqid() . '@test.com',
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($tenant);

        return $tenant;
    }

    /**
     * Create a test property for a specific tenant.
     * 
     * @param int|array $tenantIdOrAttributes
     * @param array $attributes
     * @return Property
     */
    protected function createTestProperty(int|array $tenantIdOrAttributes = 1, array $attributes = []): Property
    {
        // Support both calling patterns:
        // createTestProperty(1, ['key' => 'value'])
        // createTestProperty(['tenant_id' => 1, 'key' => 'value'])
        if (is_array($tenantIdOrAttributes)) {
            $attributes = $tenantIdOrAttributes;
            $tenantId = $attributes['tenant_id'] ?? 1;
        } else {
            $tenantId = $tenantIdOrAttributes;
        }
        
        return Property::factory()->create(array_merge([
            'tenant_id' => $tenantId,
            'address' => 'Test Address ' . uniqid(),
            'type' => PropertyType::APARTMENT,
            'area_sqm' => 50.0,
            'building_id' => null,
        ], $attributes));
    }

    /**
     * Create a test meter reading for a specific meter.
     * 
     * @param int $meterId
     * @param float $value
     * @param array $attributes
     * @return MeterReading
     */
    protected function createTestMeterReading(int $meterId, float $value, array $attributes = []): MeterReading
    {
        // Get the meter to determine tenant_id
        $meter = \App\Models\Meter::findOrFail($meterId);
        
        // Get or create a manager user for the tenant
        $manager = User::where('tenant_id', $meter->tenant_id)
            ->where('role', UserRole::MANAGER)
            ->first();
        
        if (!$manager) {
            $manager = User::factory()->create([
                'tenant_id' => $meter->tenant_id,
                'role' => UserRole::MANAGER,
            ]);
        }

        return MeterReading::factory()->create(array_merge([
            'tenant_id' => $meter->tenant_id,
            'meter_id' => $meterId,
            'value' => $value,
            'reading_date' => now(),
            'entered_by' => $manager->id,
            'zone' => null,
        ], $attributes));
    }
}
