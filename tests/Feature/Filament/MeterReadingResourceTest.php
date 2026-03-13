<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\MeterReadingResource;
use App\Filament\Resources\MeterReadingResource\Pages\CreateMeterReading;
use App\Filament\Resources\MeterReadingResource\Pages\EditMeterReading;
use App\Filament\Resources\MeterReadingResource\Pages\ListMeterReadings;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * MeterReadingResource Feature Tests
 *
 * Tests Filament MeterReadingResource integration including:
 * - Page rendering (List, Create, Edit)
 * - Tenant-scoped data access
 * - Authorization and tenant isolation
 * - Navigation visibility by role
 *
 * Phase 5 Requirements:
 * - Admin can list readings
 * - Tenant can ONLY see their own meter's readings
 *
 * @group filament
 * @group meter-reading-resource
 */
class MeterReadingResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ProvidersSeeder::class);
    }

    // ========================================
    // LIST PAGE RENDERING
    // ========================================

    /** @test */
    public function admin_can_render_meter_reading_list_page(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $this->actingAs($admin);

        Livewire::test(ListMeterReadings::class)
            ->assertSuccessful();
    }

    /** @test */
    public function manager_can_render_meter_reading_list_page(): void
    {
        $tenant = Tenant::factory()->create();
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($manager);

        Livewire::test(ListMeterReadings::class)
            ->assertSuccessful();
    }

    /** @test */
    public function tenant_user_can_render_meter_reading_list_page(): void
    {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create(['tenant_id' => $tenant->id]);
        $tenantUser = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => $tenant->id,
            'property_id' => $property->id,
        ]);

        $this->actingAs($tenantUser);

        // MeterReadingPolicy.viewAny() returns true for TENANT role
        Livewire::test(ListMeterReadings::class)
            ->assertSuccessful();
    }

    // ========================================
    // TENANT SCOPING - Admin/Manager
    // ========================================

    /** @test */
    public function admin_can_see_meter_readings_scoped_to_their_tenant(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => $tenant1->id,
        ]);

        // Create meters and readings for tenant1
        $meter1 = Meter::factory()->forTenantId($tenant1->id)->create();
        $tenant1Readings = MeterReading::factory()->count(3)->create([
            'tenant_id' => $tenant1->id,
            'meter_id' => $meter1->id,
        ]);

        // Create meters and readings for tenant2 (should NOT be visible)
        $meter2 = Meter::factory()->forTenantId($tenant2->id)->create();
        $tenant2Readings = MeterReading::factory()->count(2)->create([
            'tenant_id' => $tenant2->id,
            'meter_id' => $meter2->id,
        ]);

        $this->actingAs($admin);

        Livewire::test(ListMeterReadings::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($tenant1Readings)
            ->assertCanNotSeeTableRecords($tenant2Readings);
    }

    /** @test */
    public function manager_can_see_meter_readings_scoped_to_their_tenant(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => $tenant1->id,
        ]);

        // Create meters and readings for tenant1
        $meter1 = Meter::factory()->forTenantId($tenant1->id)->create();
        $tenant1Readings = MeterReading::factory()->count(3)->create([
            'tenant_id' => $tenant1->id,
            'meter_id' => $meter1->id,
        ]);

        // Create meters and readings for tenant2 (should NOT be visible)
        $meter2 = Meter::factory()->forTenantId($tenant2->id)->create();
        $tenant2Readings = MeterReading::factory()->count(2)->create([
            'tenant_id' => $tenant2->id,
            'meter_id' => $meter2->id,
        ]);

        $this->actingAs($manager);

        Livewire::test(ListMeterReadings::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($tenant1Readings)
            ->assertCanNotSeeTableRecords($tenant2Readings);
    }

    // ========================================
    // TENANT SCOPING - Tenant User (Critical Test for Phase 5)
    // ========================================

    /** @test */
    public function tenant_user_can_access_meter_readings_list(): void
    {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create(['tenant_id' => $tenant->id]);

        $tenantUser = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => $tenant->id,
            'property_id' => $property->id,
        ]);

        $this->actingAs($tenantUser);

        // Tenant users can access the meter readings list page
        // (they see readings scoped to their tenant via HierarchicalScope)
        Livewire::test(ListMeterReadings::class)
            ->assertSuccessful();
    }

    /** @test */
    public function tenant_user_cannot_see_readings_from_other_tenants(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $property1 = Property::factory()->create(['tenant_id' => $tenant1->id]);
        $property2 = Property::factory()->create(['tenant_id' => $tenant2->id]);

        $tenantUser1 = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => $tenant1->id,
            'property_id' => $property1->id,
        ]);

        $meter1 = Meter::factory()->create([
            'tenant_id' => $tenant1->id,
            'property_id' => $property1->id,
        ]);

        $meter2 = Meter::factory()->create([
            'tenant_id' => $tenant2->id,
            'property_id' => $property2->id,
        ]);

        $tenant1Readings = MeterReading::factory()->count(3)->create([
            'tenant_id' => $tenant1->id,
            'meter_id' => $meter1->id,
        ]);

        $tenant2Readings = MeterReading::factory()->count(2)->create([
            'tenant_id' => $tenant2->id,
            'meter_id' => $meter2->id,
        ]);

        $this->actingAs($tenantUser1);

        // Tenant user from tenant1 should NOT see readings from tenant2
        Livewire::test(ListMeterReadings::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($tenant1Readings)
            ->assertCanNotSeeTableRecords($tenant2Readings);
    }

    // ========================================
    // SUPERADMIN ACCESS
    // ========================================

    /** @test */
    public function superadmin_can_see_all_meter_readings_across_tenants(): void
    {
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'tenant_id' => null,
        ]);

        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $meter1 = Meter::factory()->forTenantId($tenant1->id)->create();
        $meter2 = Meter::factory()->forTenantId($tenant2->id)->create();

        $tenant1Readings = MeterReading::factory()->count(3)->create([
            'tenant_id' => $tenant1->id,
            'meter_id' => $meter1->id,
        ]);

        $tenant2Readings = MeterReading::factory()->count(2)->create([
            'tenant_id' => $tenant2->id,
            'meter_id' => $meter2->id,
        ]);

        $this->actingAs($superadmin);

        // Superadmin should see readings from ALL tenants
        Livewire::test(ListMeterReadings::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($tenant1Readings)
            ->assertCanSeeTableRecords($tenant2Readings);
    }

    // ========================================
    // CREATE PAGE RENDERING
    // ========================================

    /** @test */
    public function admin_can_render_create_meter_reading_page(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $this->actingAs($admin);

        Livewire::test(CreateMeterReading::class)
            ->assertSuccessful();
    }

    /** @test */
    public function manager_can_render_create_meter_reading_page(): void
    {
        $tenant = Tenant::factory()->create();
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($manager);

        Livewire::test(CreateMeterReading::class)
            ->assertSuccessful();
    }

    // ========================================
    // EDIT PAGE RENDERING
    // ========================================

    /** @test */
    public function admin_can_render_edit_meter_reading_page(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => $tenant->id,
        ]);

        $meter = Meter::factory()->forTenantId($tenant->id)->create();
        $reading = MeterReading::factory()->create([
            'tenant_id' => $tenant->id,
            'meter_id' => $meter->id,
        ]);

        $this->actingAs($admin);

        Livewire::test(EditMeterReading::class, ['record' => $reading->id])
            ->assertSuccessful();
    }

    /** @test */
    public function manager_can_render_edit_meter_reading_page(): void
    {
        $tenant = Tenant::factory()->create();
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => $tenant->id,
        ]);

        $meter = Meter::factory()->forTenantId($tenant->id)->create();
        $reading = MeterReading::factory()->create([
            'tenant_id' => $tenant->id,
            'meter_id' => $meter->id,
        ]);

        $this->actingAs($manager);

        Livewire::test(EditMeterReading::class, ['record' => $reading->id])
            ->assertSuccessful();
    }

    // ========================================
    // CROSS-TENANT ACCESS PREVENTION
    // ========================================

    /** @test */
    public function manager_cannot_edit_meter_reading_from_other_tenant(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => $tenant1->id,
        ]);

        $meter2 = Meter::factory()->forTenantId($tenant2->id)->create();
        $reading2 = MeterReading::factory()->create([
            'tenant_id' => $tenant2->id,
            'meter_id' => $meter2->id,
        ]);

        $this->actingAs($manager);

        // Should throw 404 because HierarchicalScope filters it out
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::test(EditMeterReading::class, ['record' => $reading2->id]);
    }

    /** @test */
    public function tenant_user_cannot_edit_meter_readings(): void
    {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create(['tenant_id' => $tenant->id]);

        $tenantUser = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => $tenant->id,
            'property_id' => $property->id,
        ]);

        $meter = Meter::factory()->create([
            'tenant_id' => $tenant->id,
            'property_id' => $property->id,
        ]);

        $reading = MeterReading::factory()->create([
            'tenant_id' => $tenant->id,
            'meter_id' => $meter->id,
        ]);

        $this->actingAs($tenantUser);

        // Tenant users have read-only access - cannot edit meter readings
        // MeterReadingPolicy.update() only allows SUPERADMIN, ADMIN, MANAGER
        Livewire::test(EditMeterReading::class, ['record' => $reading->id])
            ->assertForbidden();
    }

    // ========================================
    // NAVIGATION VISIBILITY
    // ========================================

    /** @test */
    public function meter_reading_navigation_is_visible_to_admin(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);

        $this->actingAs($admin);

        $this->assertTrue(MeterReadingResource::shouldRegisterNavigation());
    }

    /** @test */
    public function meter_reading_navigation_is_visible_to_manager(): void
    {
        $tenant = Tenant::factory()->create();
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($manager);

        $this->assertTrue(MeterReadingResource::shouldRegisterNavigation());
    }

    /** @test */
    public function meter_reading_navigation_is_visible_to_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $property = Property::factory()->create(['tenant_id' => $tenant->id]);
        $tenantUser = User::factory()->create([
            'role' => UserRole::TENANT,
            'tenant_id' => $tenant->id,
            'property_id' => $property->id,
        ]);

        $this->actingAs($tenantUser);

        $this->assertTrue(MeterReadingResource::shouldRegisterNavigation());
    }

    // ========================================
    // RESOURCE CONFIGURATION
    // ========================================

    /** @test */
    public function meter_reading_resource_uses_correct_model(): void
    {
        $this->assertEquals(MeterReading::class, MeterReadingResource::getModel());
    }

    /** @test */
    public function meter_reading_resource_has_localized_navigation_label(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->actingAs($admin);

        $label = MeterReadingResource::getNavigationLabel();
        $this->assertIsString($label);
        $this->assertNotEmpty($label);
    }
}
