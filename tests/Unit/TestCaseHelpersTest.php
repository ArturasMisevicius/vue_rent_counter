<?php

declare(strict_types=1);

namespace Tests\Unit;

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
use Tests\TestCase;

/**
 * Test the TestCase helper methods to ensure they work correctly.
 */
class TestCaseHelpersTest extends TestCase
{
    public function test_acting_as_admin_creates_admin_user_with_tenant_context(): void
    {
        $admin = $this->actingAsAdmin(1);

        $this->assertInstanceOf(User::class, $admin);
        $this->assertEquals(UserRole::ADMIN, $admin->role);
        $this->assertEquals(1, $admin->tenant_id);
        $this->assertAuthenticatedAs($admin);
        $this->assertTenantContext(1);
    }

    public function test_acting_as_admin_accepts_custom_attributes(): void
    {
        $admin = $this->actingAsAdmin(1, ['name' => 'Custom Admin']);

        $this->assertEquals('Custom Admin', $admin->name);
        $this->assertEquals(UserRole::ADMIN, $admin->role);
    }

    public function test_acting_as_manager_creates_manager_user_with_tenant_context(): void
    {
        $manager = $this->actingAsManager(2);

        $this->assertInstanceOf(User::class, $manager);
        $this->assertEquals(UserRole::MANAGER, $manager->role);
        $this->assertEquals(2, $manager->tenant_id);
        $this->assertAuthenticatedAs($manager);
        $this->assertTenantContext(2);
    }

    public function test_acting_as_tenant_creates_tenant_user_with_tenant_context(): void
    {
        $tenant = $this->actingAsTenant(3);

        $this->assertInstanceOf(User::class, $tenant);
        $this->assertEquals(UserRole::TENANT, $tenant->role);
        $this->assertEquals(3, $tenant->tenant_id);
        $this->assertAuthenticatedAs($tenant);
        $this->assertTenantContext(3);
    }

    public function test_acting_as_superadmin_creates_superadmin_without_tenant_context(): void
    {
        $superadmin = $this->actingAsSuperadmin();

        $this->assertInstanceOf(User::class, $superadmin);
        $this->assertEquals(UserRole::SUPERADMIN, $superadmin->role);
        $this->assertNull($superadmin->tenant_id);
        $this->assertAuthenticatedAs($superadmin);
        $this->assertNoTenantContext();
    }

    public function test_create_test_property_with_tenant_id(): void
    {
        $property = $this->createTestProperty(1);

        $this->assertInstanceOf(Property::class, $property);
        $this->assertEquals(1, $property->tenant_id);
        $this->assertEquals(PropertyType::APARTMENT, $property->type);
        $this->assertNotEmpty($property->address);
    }

    public function test_create_test_property_with_attributes_array(): void
    {
        $property = $this->createTestProperty([
            'tenant_id' => 2,
            'type' => PropertyType::HOUSE,
            'area_sqm' => 100.0,
        ]);

        $this->assertEquals(2, $property->tenant_id);
        $this->assertEquals(PropertyType::HOUSE, $property->type);
        $this->assertEquals(100.0, $property->area_sqm);
    }

    public function test_create_test_building_creates_building_with_tenant(): void
    {
        $building = $this->createTestBuilding(1);

        $this->assertInstanceOf(Building::class, $building);
        $this->assertEquals(1, $building->tenant_id);
        $this->assertNotEmpty($building->name);
        $this->assertNotEmpty($building->address);
    }

    public function test_create_test_meter_creates_meter_for_property(): void
    {
        $property = $this->createTestProperty(1);
        $meter = $this->createTestMeter($property->id, MeterType::WATER_COLD);

        $this->assertInstanceOf(Meter::class, $meter);
        $this->assertEquals($property->id, $meter->property_id);
        $this->assertEquals($property->tenant_id, $meter->tenant_id);
        $this->assertEquals(MeterType::WATER_COLD, $meter->type);
    }

    public function test_create_test_meter_reading_creates_reading_with_manager(): void
    {
        $property = $this->createTestProperty(1);
        $meter = $this->createTestMeter($property->id);
        $reading = $this->createTestMeterReading($meter->id, 100.5);

        $this->assertInstanceOf(MeterReading::class, $reading);
        $this->assertEquals($meter->id, $reading->meter_id);
        $this->assertEquals($meter->tenant_id, $reading->tenant_id);
        $this->assertEquals(100.5, $reading->value);
        $this->assertNotNull($reading->entered_by);
        
        // Verify manager was created
        $manager = User::find($reading->entered_by);
        $this->assertNotNull($manager);
        $this->assertEquals(UserRole::MANAGER, $manager->role);
    }

    public function test_create_test_meter_reading_reuses_existing_manager(): void
    {
        $property = $this->createTestProperty(1);
        $meter = $this->createTestMeter($property->id);
        
        // Create first reading (creates manager)
        $reading1 = $this->createTestMeterReading($meter->id, 100.0);
        $managerId1 = $reading1->entered_by;
        
        // Create second reading (should reuse manager)
        $reading2 = $this->createTestMeterReading($meter->id, 150.0);
        $managerId2 = $reading2->entered_by;
        
        $this->assertEquals($managerId1, $managerId2, 'Should reuse existing manager');
    }

    public function test_create_test_invoice_creates_invoice_for_property(): void
    {
        $property = $this->createTestProperty(1);
        $invoice = $this->createTestInvoice($property->id);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($property->tenant_id, $invoice->tenant_id);
        $this->assertNotNull($invoice->property);
        $this->assertEquals($property->id, $invoice->property->id);
        $this->assertNotNull($invoice->billing_period_start);
        $this->assertNotNull($invoice->billing_period_end);
    }

    public function test_ensure_tenant_exists_creates_organization(): void
    {
        $organization = $this->ensureTenantExists(5);

        $this->assertInstanceOf(Organization::class, $organization);
        $this->assertEquals(5, $organization->id);
        $this->assertTrue($organization->is_active);
        $this->assertNotNull($organization->subscription_ends_at);
    }

    public function test_ensure_tenant_exists_returns_existing_organization(): void
    {
        $org1 = $this->ensureTenantExists(6);
        $org2 = $this->ensureTenantExists(6);

        $this->assertEquals($org1->id, $org2->id);
    }

    public function test_within_tenant_executes_callback_in_tenant_context(): void
    {
        $this->actingAsAdmin(1);
        $this->assertTenantContext(1);

        $result = $this->withinTenant(2, function () {
            $this->assertTenantContext(2);
            return 'executed';
        });

        $this->assertEquals('executed', $result);
        $this->assertTenantContext(1); // Context restored
    }

    public function test_within_tenant_restores_context_on_exception(): void
    {
        $this->actingAsAdmin(1);
        $this->assertTenantContext(1);

        try {
            $this->withinTenant(2, function () {
                throw new \Exception('Test exception');
            });
        } catch (\Exception $e) {
            // Expected
        }

        $this->assertTenantContext(1); // Context restored even after exception
    }

    public function test_assert_tenant_context_passes_with_correct_tenant(): void
    {
        $this->actingAsAdmin(1);
        
        $this->assertTenantContext(1); // Should not throw
    }

    public function test_assert_no_tenant_context_passes_when_no_context(): void
    {
        $this->actingAsSuperadmin();
        
        $this->assertNoTenantContext(); // Should not throw
    }

    public function test_tear_down_clears_tenant_context(): void
    {
        $this->actingAsAdmin(1);
        $this->assertTenantContext(1);
        
        // Simulate tearDown
        app(\App\Services\TenantContext::class)->clear();
        auth()->logout();
        
        $this->assertNoTenantContext();
    }

    public function test_multiple_role_helpers_can_be_used_in_sequence(): void
    {
        // Create admin
        $admin = $this->actingAsAdmin(1);
        $this->assertEquals(UserRole::ADMIN, $admin->role);
        $this->assertTenantContext(1);
        
        // Switch to manager
        $manager = $this->actingAsManager(2);
        $this->assertEquals(UserRole::MANAGER, $manager->role);
        $this->assertTenantContext(2);
        
        // Switch to tenant
        $tenant = $this->actingAsTenant(3);
        $this->assertEquals(UserRole::TENANT, $tenant->role);
        $this->assertTenantContext(3);
    }

    public function test_helpers_work_with_different_tenant_ids(): void
    {
        $property1 = $this->createTestProperty(1);
        $property2 = $this->createTestProperty(2);
        $property3 = $this->createTestProperty(3);

        $this->assertEquals(1, $property1->tenant_id);
        $this->assertEquals(2, $property2->tenant_id);
        $this->assertEquals(3, $property3->tenant_id);
        
        // Verify organizations were created
        $this->assertDatabaseHas('organizations', ['id' => 1]);
        $this->assertDatabaseHas('organizations', ['id' => 2]);
        $this->assertDatabaseHas('organizations', ['id' => 3]);
    }
}
