<?php

declare(strict_types=1);

namespace Tests;

use App\Enums\MeterType;
use App\Enums\PropertyType;
use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

/**
 * Base test case for all application tests.
 * 
 * This class provides a comprehensive set of helper methods for testing the
 * Vilnius Utilities Billing Platform with proper multi-tenant context management.
 * 
 * ## Key Features
 * 
 * - **Role-based Authentication**: Helpers for admin, manager, tenant, and superadmin users
 * - **Test Data Creation**: Factory-based helpers with automatic tenant context
 * - **Tenant Context Management**: Automatic setup and cleanup of tenant context
 * - **Tenant Isolation Testing**: Assertions for verifying tenant boundaries
 * - **Cross-tenant Operations**: Support for testing superadmin scenarios
 * 
 * ## Usage Examples
 * 
 * ### Basic Authentication
 * ```php
 * public function test_admin_can_view_properties(): void
 * {
 *     $admin = $this->actingAsAdmin(1);
 *     $property = $this->createTestProperty(1);
 *     
 *     $response = $this->get(route('properties.show', $property));
 *     
 *     $response->assertOk();
 * }
 * ```
 * 
 * ### Multi-tenant Testing
 * ```php
 * public function test_tenant_isolation(): void
 * {
 *     $property1 = $this->createTestProperty(1);
 *     $property2 = $this->createTestProperty(2);
 *     
 *     $this->actingAsManager(1);
 *     
 *     $this->assertCount(1, Property::all());
 *     $this->assertTenantContext(1);
 * }
 * ```
 * 
 * ### Complex Data Setup
 * ```php
 * public function test_invoice_generation(): void
 * {
 *     $manager = $this->actingAsManager(1);
 *     $property = $this->createTestProperty(1);
 *     $meter = $this->createTestMeter($property->id, MeterType::ELECTRICITY);
 *     $reading = $this->createTestMeterReading($meter->id, 100.0);
 *     $invoice = $this->createTestInvoice($property->id);
 *     
 *     $this->assertEquals($property->tenant_id, $invoice->tenant_id);
 * }
 * ```
 * 
 * ## Architecture
 * 
 * - Uses `RefreshDatabase` trait for clean test state
 * - Automatically cleans up `TenantContext` in `tearDown()`
 * - Ensures organizations exist before creating tenant-scoped data
 * - Reuses manager users to avoid N+1 queries in meter reading creation
 * 
 * @see \App\Services\TenantContext For tenant context management
 * @see \App\Traits\BelongsToTenant For tenant scoping trait
 * @see \App\Scopes\TenantScope For global tenant scope
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Clean up tenant context after each test.
     */
    protected function tearDown(): void
    {
        TenantContext::clear();
        
        parent::tearDown();
    }

    /**
     * Authenticate as an admin user with proper tenant context.
     * 
     * Creates an admin user for the specified tenant and sets up
     * the tenant context for the test session.
     * 
     * @param int $tenantId The tenant ID (default: 1)
     * @param array<string, mixed> $attributes Additional user attributes
     * @return User The created admin user
     */
    protected function actingAsAdmin(int $tenantId = 1, array $attributes = []): User
    {
        $admin = User::factory()->create(array_merge([
            'tenant_id' => $tenantId,
            'role' => UserRole::ADMIN,
            'email' => 'test-admin-' . uniqid() . '@test.com',
            'password' => Hash::make('password'),
        ], $attributes));

        $this->actingAs($admin);
        
        // Set tenant context for the test
        if ($tenantId && !$admin->isSuperadmin()) {
            $this->ensureTenantExists($tenantId);
            TenantContext::set($tenantId);
        }

        return $admin;
    }

    /**
     * Authenticate as a manager user with proper tenant context.
     * 
     * Creates a manager user for the specified tenant and sets up
     * the tenant context for the test session.
     * 
     * @param int $tenantId The tenant ID (default: 1)
     * @param array<string, mixed> $attributes Additional user attributes
     * @return User The created manager user
     */
    protected function actingAsManager(int $tenantId = 1, array $attributes = []): User
    {
        $manager = User::factory()->create(array_merge([
            'tenant_id' => $tenantId,
            'role' => UserRole::MANAGER,
            'email' => 'test-manager-' . uniqid() . '@test.com',
            'password' => Hash::make('password'),
        ], $attributes));

        $this->actingAs($manager);
        
        // Set tenant context for the test
        $this->ensureTenantExists($tenantId);
        TenantContext::set($tenantId);

        return $manager;
    }

    /**
     * Authenticate as a tenant user with proper tenant context.
     * 
     * Creates a tenant user for the specified tenant and sets up
     * the tenant context for the test session.
     * 
     * @param int $tenantId The tenant ID (default: 1)
     * @param array<string, mixed> $attributes Additional user attributes
     * @return User The created tenant user
     */
    protected function actingAsTenant(int $tenantId = 1, array $attributes = []): User
    {
        $tenant = User::factory()->create(array_merge([
            'tenant_id' => $tenantId,
            'role' => UserRole::TENANT,
            'email' => 'test-tenant-' . uniqid() . '@test.com',
            'password' => Hash::make('password'),
        ], $attributes));

        $this->actingAs($tenant);
        
        // Set tenant context for the test
        $this->ensureTenantExists($tenantId);
        TenantContext::set($tenantId);

        return $tenant;
    }

    /**
     * Authenticate as a superadmin user.
     * 
     * Creates a superadmin user who can access all tenants.
     * Note: Superadmin users don't have tenant context set automatically.
     * 
     * @param array<string, mixed> $attributes Additional user attributes
     * @return User The created superadmin user
     */
    protected function actingAsSuperadmin(array $attributes = []): User
    {
        $superadmin = User::factory()->create(array_merge([
            'tenant_id' => null,
            'role' => UserRole::SUPERADMIN,
            'email' => 'test-superadmin-' . uniqid() . '@test.com',
            'password' => Hash::make('password'),
        ], $attributes));

        $this->actingAs($superadmin);

        return $superadmin;
    }

    /**
     * Create a test property for a specific tenant.
     * 
     * Supports two calling patterns:
     * - createTestProperty(1, ['key' => 'value'])
     * - createTestProperty(['tenant_id' => 1, 'key' => 'value'])
     * 
     * @param int|array<string, mixed> $tenantIdOrAttributes Tenant ID or attributes array
     * @param array<string, mixed> $attributes Additional attributes (when first param is int)
     * @return Property The created property
     */
    protected function createTestProperty(int|array $tenantIdOrAttributes = 1, array $attributes = []): Property
    {
        // Support both calling patterns
        if (is_array($tenantIdOrAttributes)) {
            $attributes = $tenantIdOrAttributes;
            $tenantId = $attributes['tenant_id'] ?? 1;
        } else {
            $tenantId = $tenantIdOrAttributes;
        }
        
        // Ensure tenant exists
        $this->ensureTenantExists($tenantId);
        
        return Property::factory()->create(array_merge([
            'tenant_id' => $tenantId,
            'address' => 'Test Address ' . uniqid(),
            'type' => PropertyType::APARTMENT,
            'area_sqm' => 50.0,
            'building_id' => null,
        ], $attributes));
    }

    /**
     * Create a test building for a specific tenant.
     * 
     * @param int $tenantId The tenant ID (default: 1)
     * @param array<string, mixed> $attributes Additional building attributes
     * @return Building The created building
     */
    protected function createTestBuilding(int $tenantId = 1, array $attributes = []): Building
    {
        $this->ensureTenantExists($tenantId);
        
        return Building::factory()->create(array_merge([
            'tenant_id' => $tenantId,
            'name' => 'Test Building ' . uniqid(),
            'address' => 'Test Building Address ' . uniqid(),
        ], $attributes));
    }

    /**
     * Create a test meter for a specific property.
     * 
     * @param int $propertyId The property ID
     * @param MeterType|null $type The meter type (default: ELECTRICITY)
     * @param array<string, mixed> $attributes Additional meter attributes
     * @return Meter The created meter
     */
    protected function createTestMeter(int $propertyId, ?MeterType $type = null, array $attributes = []): Meter
    {
        $property = Property::findOrFail($propertyId);
        
        return Meter::factory()->create(array_merge([
            'tenant_id' => $property->tenant_id,
            'property_id' => $propertyId,
            'type' => $type ?? MeterType::ELECTRICITY,
            'serial_number' => 'TEST-' . uniqid(),
        ], $attributes));
    }

    /**
     * Create a test meter reading for a specific meter.
     * 
     * Automatically creates a manager user if one doesn't exist for the tenant.
     * 
     * @param int $meterId The meter ID
     * @param float $value The reading value
     * @param array<string, mixed> $attributes Additional reading attributes
     * @return MeterReading The created meter reading
     */
    protected function createTestMeterReading(int $meterId, float $value, array $attributes = []): MeterReading
    {
        $meter = Meter::findOrFail($meterId);
        
        // Get or create a manager user for the tenant (avoid N+1)
        $manager = User::where('tenant_id', $meter->tenant_id)
            ->where('role', UserRole::MANAGER)
            ->first();
        
        if (!$manager) {
            $manager = User::factory()->create([
                'tenant_id' => $meter->tenant_id,
                'role' => UserRole::MANAGER,
                'email' => 'test-manager-' . uniqid() . '@test.com',
                'password' => Hash::make('password'),
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

    /**
     * Create a test invoice for a specific property.
     * 
     * @param int $propertyId The property ID
     * @param array<string, mixed> $attributes Additional invoice attributes
     * @return Invoice The created invoice
     */
    protected function createTestInvoice(int $propertyId, array $attributes = []): Invoice
    {
        $property = Property::findOrFail($propertyId);
        
        return Invoice::factory()->create(array_merge([
            'tenant_id' => $property->tenant_id,
            'property_id' => $propertyId,
            'billing_period_start' => now()->startOfMonth(),
            'billing_period_end' => now()->endOfMonth(),
        ], $attributes));
    }

    /**
     * Ensure a tenant (organization) exists for testing.
     * 
     * Creates an organization if it doesn't exist for the given tenant ID.
     * 
     * @param int $tenantId The tenant ID
     * @return Organization The organization
     */
    protected function ensureTenantExists(int $tenantId): Organization
    {
        return Organization::firstOrCreate(
            ['id' => $tenantId],
            [
                'name' => 'Test Organization ' . $tenantId,
                'status' => 'active',
                'subscription_plan' => 'basic',
                'subscription_status' => 'active',
                'subscription_expires_at' => now()->addYear(),
            ]
        );
    }

    /**
     * Execute a callback within a specific tenant context.
     * 
     * Useful for testing cross-tenant scenarios or superadmin operations.
     * 
     * @param int $tenantId The tenant ID to switch to
     * @param callable $callback The callback to execute
     * @return mixed The callback result
     */
    protected function withinTenant(int $tenantId, callable $callback): mixed
    {
        $this->ensureTenantExists($tenantId);
        
        return TenantContext::within($tenantId, $callback);
    }

    /**
     * Assert that the current tenant context matches the expected tenant.
     * 
     * @param int $expectedTenantId The expected tenant ID
     * @return void
     */
    protected function assertTenantContext(int $expectedTenantId): void
    {
        $this->assertEquals(
            $expectedTenantId,
            TenantContext::id(),
            "Expected tenant context to be {$expectedTenantId}, but got " . TenantContext::id()
        );
    }

    /**
     * Assert that no tenant context is set.
     * 
     * @return void
     */
    protected function assertNoTenantContext(): void
    {
        $this->assertFalse(
            TenantContext::has(),
            'Expected no tenant context to be set, but tenant ' . TenantContext::id() . ' is active'
        );
    }
}
