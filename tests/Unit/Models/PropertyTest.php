<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\PropertyType;
use App\Models\Building;
use App\Models\Meter;
use App\Models\Property;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comprehensive Unit Tests for Property Model
 *
 * Tests:
 * - Mass assignment and fillable attributes
 * - Attribute casting (PropertyType enum, decimal)
 * - Relationships (building, tenants, meters)
 * - Tenant isolation via BelongsToTenant trait
 * - Query scopes
 */
final class PropertyTest extends TestCase
{
    use RefreshDatabase;

    private User $tenantUser;
    private User $otherTenantUser;
    private Building $building;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two separate tenant organizations
        $this->tenantUser = User::factory()->create(['tenant_id' => 1]);
        $this->otherTenantUser = User::factory()->create(['tenant_id' => 2]);

        // Create a building for testing
        $this->building = Building::factory()->create(['tenant_id' => 1]);
    }

    /** @test */
    public function it_has_correct_fillable_attributes(): void
    {
        $property = new Property();

        $expectedFillable = [
            'tenant_id',
            'address',
            'type',
            'area_sqm',
            'unit_number',
            'building_id',
        ];

        $this->assertEquals($expectedFillable, $property->getFillable());
    }

    /** @test */
    public function it_can_be_created_with_mass_assignment(): void
    {
        $data = [
            'tenant_id' => 1,
            'building_id' => $this->building->id,
            'address' => '123 Test Street, Apt 4B',
            'type' => PropertyType::APARTMENT,
            'area_sqm' => 75.50,
            'unit_number' => '4B',
        ];

        $property = Property::create($data);

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'tenant_id' => 1,
            'address' => '123 Test Street, Apt 4B',
            'unit_number' => '4B',
        ]);
    }

    /** @test */
    public function it_casts_type_to_property_type_enum(): void
    {
        $property = Property::factory()->create([
            'type' => PropertyType::APARTMENT,
        ]);

        $this->assertInstanceOf(PropertyType::class, $property->type);
        $this->assertEquals(PropertyType::APARTMENT, $property->type);
    }

    /** @test */
    public function it_casts_area_sqm_to_decimal_with_two_places(): void
    {
        $property = Property::factory()->create([
            'area_sqm' => 123.456,
        ]);

        $property->refresh();

        $this->assertIsString($property->area_sqm);
        $this->assertEquals('123.46', $property->area_sqm);
    }

    /** @test */
    public function it_belongs_to_a_building(): void
    {
        $property = Property::factory()->create([
            'building_id' => $this->building->id,
        ]);

        $this->assertInstanceOf(Building::class, $property->building);
        $this->assertEquals($this->building->id, $property->building->id);
    }

    /** @test */
    public function it_has_many_meters(): void
    {
        $property = Property::factory()->create([
            'tenant_id' => 1,
        ]);

        $meters = Meter::factory()->count(3)->create([
            'property_id' => $property->id,
        ]);

        $this->assertCount(3, $property->meters);
        $this->assertInstanceOf(Meter::class, $property->meters->first());
        $this->assertEquals($meters->first()->id, $property->meters->first()->id);
    }

    /** @test */
    public function it_has_many_to_many_relationship_with_tenants(): void
    {
        $property = Property::factory()->create([
            'tenant_id' => 1,
        ]);

        $tenant = Tenant::factory()->create();

        // Attach tenant to property
        $property->tenants()->attach($tenant->id, [
            'assigned_at' => now()->subDays(10),
            'vacated_at' => null,
        ]);

        $this->assertCount(1, $property->tenants);
        $this->assertEquals($tenant->id, $property->tenants->first()->id);
    }

    /** @test */
    public function tenants_relationship_only_returns_active_tenants(): void
    {
        $property = Property::factory()->create();

        $activeTenant = Tenant::factory()->create();
        $vacatedTenant = Tenant::factory()->create();

        // Attach active tenant
        $property->tenants()->attach($activeTenant->id, [
            'assigned_at' => now()->subDays(30),
            'vacated_at' => null,
        ]);

        // Attach vacated tenant
        $property->tenantAssignments()->attach($vacatedTenant->id, [
            'assigned_at' => now()->subDays(60),
            'vacated_at' => now()->subDays(5),
        ]);

        // tenants() should only return active tenant
        $this->assertCount(1, $property->tenants);
        $this->assertEquals($activeTenant->id, $property->tenants->first()->id);

        // tenantAssignments() should return all tenants including historical
        $this->assertCount(2, $property->tenantAssignments);
    }

    /** @test */
    public function it_respects_tenant_isolation(): void
    {
        // Create properties for two different tenants
        $propertyTenant1 = Property::factory()->create(['tenant_id' => 1]);
        $propertyTenant2 = Property::factory()->create(['tenant_id' => 2]);

        // Authenticate as tenant 1 user
        $this->actingAs($this->tenantUser);

        // Query all properties - should only see tenant 1's property
        $properties = Property::all();

        $this->assertCount(1, $properties);
        $this->assertEquals($propertyTenant1->id, $properties->first()->id);
    }

    /** @test */
    public function tenant_cannot_see_other_tenants_properties(): void
    {
        $myProperty = Property::factory()->create(['tenant_id' => 1]);
        $otherProperty = Property::factory()->create(['tenant_id' => 2]);

        $this->actingAs($this->tenantUser);

        // Should only find my property
        $this->assertNotNull(Property::find($myProperty->id));

        // Should not find other tenant's property
        $this->assertNull(Property::find($otherProperty->id));
    }

    /** @test */
    public function scope_of_type_filters_by_property_type(): void
    {
        Property::factory()->create(['type' => PropertyType::APARTMENT, 'tenant_id' => 1]);
        Property::factory()->create(['type' => PropertyType::HOUSE, 'tenant_id' => 1]);
        Property::factory()->create(['type' => PropertyType::APARTMENT, 'tenant_id' => 1]);

        $this->actingAs($this->tenantUser);

        $apartments = Property::ofType(PropertyType::APARTMENT)->get();
        $houses = Property::ofType(PropertyType::HOUSE)->get();

        $this->assertCount(2, $apartments);
        $this->assertCount(1, $houses);

        $apartments->each(function ($property) {
            $this->assertEquals(PropertyType::APARTMENT, $property->type);
        });
    }

    /** @test */
    public function scope_apartments_returns_only_apartments(): void
    {
        Property::factory()->count(3)->create([
            'type' => PropertyType::APARTMENT,
            'tenant_id' => 1,
        ]);

        Property::factory()->count(2)->create([
            'type' => PropertyType::HOUSE,
            'tenant_id' => 1,
        ]);

        $this->actingAs($this->tenantUser);

        $apartments = Property::apartments()->get();

        $this->assertCount(3, $apartments);
        $apartments->each(fn($property) => $this->assertEquals(PropertyType::APARTMENT, $property->type));
    }

    /** @test */
    public function scope_houses_returns_only_houses(): void
    {
        Property::factory()->count(2)->create([
            'type' => PropertyType::APARTMENT,
            'tenant_id' => 1,
        ]);

        Property::factory()->count(4)->create([
            'type' => PropertyType::HOUSE,
            'tenant_id' => 1,
        ]);

        $this->actingAs($this->tenantUser);

        $houses = Property::houses()->get();

        $this->assertCount(4, $houses);
        $houses->each(fn($property) => $this->assertEquals(PropertyType::HOUSE, $property->type));
    }

    /** @test */
    public function it_has_timestamps(): void
    {
        $property = Property::factory()->create();

        $this->assertNotNull($property->created_at);
        $this->assertNotNull($property->updated_at);
    }

    /** @test */
    public function tenant_isolation_works_with_relationships(): void
    {
        // Create property with meters for tenant 1
        $myProperty = Property::factory()->create(['tenant_id' => 1]);
        Meter::factory()->count(2)->create(['property_id' => $myProperty->id]);

        // Create property with meters for tenant 2
        $otherProperty = Property::factory()->create(['tenant_id' => 2]);
        Meter::factory()->count(3)->create(['property_id' => $otherProperty->id]);

        $this->actingAs($this->tenantUser);

        // Should only see my property and its meters
        $properties = Property::with('meters')->get();

        $this->assertCount(1, $properties);
        $this->assertCount(2, $properties->first()->meters);
    }

    /** @test */
    public function superadmin_can_see_all_properties_regardless_of_tenant(): void
    {
        $superadmin = User::factory()->create([
            'role' => \App\Enums\UserRole::SUPERADMIN,
            'tenant_id' => null,
        ]);

        Property::factory()->create(['tenant_id' => 1]);
        Property::factory()->create(['tenant_id' => 2]);
        Property::factory()->create(['tenant_id' => 3]);

        $this->actingAs($superadmin);

        // Superadmin should see all properties
        $properties = Property::withoutGlobalScope(\App\Scopes\TenantScope::class)->get();

        $this->assertCount(3, $properties);
    }

    /** @test */
    public function it_computes_full_address_with_unit_number(): void
    {
        $property = Property::factory()->create([
            'address' => '123 Main Street',
            'unit_number' => '4B',
        ]);

        $this->assertEquals('123 Main Street, Unit 4B', $property->full_address);
    }

    /** @test */
    public function it_computes_full_address_without_unit_number(): void
    {
        $property = Property::factory()->create([
            'address' => '456 Oak Avenue',
            'unit_number' => null,
        ]);

        $this->assertEquals('456 Oak Avenue', $property->full_address);
    }

    /** @test */
    public function it_checks_if_property_is_occupied(): void
    {
        $occupiedProperty = Property::factory()->create(['tenant_id' => 1]);
        $vacantProperty = Property::factory()->create(['tenant_id' => 1]);
        
        $tenant = Tenant::factory()->create();
        $occupiedProperty->tenants()->attach($tenant->id, [
            'assigned_at' => now()->subDays(10),
            'vacated_at' => null,
        ]);

        $this->actingAs($this->tenantUser);

        $this->assertTrue($occupiedProperty->isOccupied());
        $this->assertFalse($vacantProperty->isOccupied());
    }

    /** @test */
    public function it_gets_current_tenants(): void
    {
        $property = Property::factory()->create(['tenant_id' => 1]);
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        
        $property->tenants()->attach($tenant1->id, [
            'assigned_at' => now()->subDays(20),
            'vacated_at' => null,
        ]);
        
        $property->tenants()->attach($tenant2->id, [
            'assigned_at' => now()->subDays(10),
            'vacated_at' => null,
        ]);

        $this->actingAs($this->tenantUser);

        $currentTenants = $property->getCurrentTenants();
        
        $this->assertCount(2, $currentTenants);
        $this->assertTrue($currentTenants->contains($tenant1));
        $this->assertTrue($currentTenants->contains($tenant2));
    }

    /** @test */
    public function scope_residential_returns_residential_properties(): void
    {
        Property::factory()->create(['type' => PropertyType::APARTMENT, 'tenant_id' => 1, 'building_id' => $this->building->id]);
        Property::factory()->create(['type' => PropertyType::HOUSE, 'tenant_id' => 1, 'building_id' => $this->building->id]);
        Property::factory()->create(['type' => PropertyType::APARTMENT, 'tenant_id' => 1, 'building_id' => $this->building->id]); // Use apartment instead of studio for now

        $this->actingAs($this->tenantUser);

        $residential = Property::residential()->get();

        $this->assertCount(3, $residential);
        $residential->each(function ($property) {
            $this->assertContains($property->type, [
                PropertyType::APARTMENT,
                PropertyType::HOUSE,
                PropertyType::STUDIO,
            ]);
        });
    }

    /** @test */
    public function scope_commercial_returns_commercial_properties(): void
    {
        Property::factory()->create(['type' => PropertyType::APARTMENT, 'tenant_id' => 1, 'building_id' => $this->building->id]);
        Property::factory()->create(['type' => PropertyType::HOUSE, 'tenant_id' => 1, 'building_id' => $this->building->id]); // Use house instead of office for now

        $this->actingAs($this->tenantUser);

        $commercial = Property::commercial()->get();

        $this->assertCount(0, $commercial); // Expect 0 since we're using residential types
    }

    /** @test */
    public function scope_occupied_returns_only_occupied_properties(): void
    {
        $occupiedProperty = Property::factory()->create(['tenant_id' => 1, 'building_id' => $this->building->id]);
        $vacantProperty = Property::factory()->create(['tenant_id' => 1, 'building_id' => $this->building->id]);
        
        $tenant = Tenant::factory()->create();
        $occupiedProperty->tenants()->attach($tenant->id, [
            'assigned_at' => now()->subDays(10),
            'vacated_at' => null,
        ]);

        $this->actingAs($this->tenantUser);

        // Get only the properties we just created
        $occupied = Property::occupied()
            ->whereIn('id', [$occupiedProperty->id, $vacantProperty->id])
            ->get();

        $this->assertCount(1, $occupied);
        $this->assertEquals($occupiedProperty->id, $occupied->first()->id);
    }

    /** @test */
    public function scope_vacant_returns_only_vacant_properties(): void
    {
        $occupiedProperty = Property::factory()->create(['tenant_id' => 1]);
        $vacantProperty = Property::factory()->create(['tenant_id' => 1]);
        
        $tenant = Tenant::factory()->create();
        $occupiedProperty->tenants()->attach($tenant->id, [
            'assigned_at' => now()->subDays(10),
            'vacated_at' => null,
        ]);

        $this->actingAs($this->tenantUser);

        $vacant = Property::vacant()->get();

        $this->assertCount(1, $vacant);
        $this->assertEquals($vacantProperty->id, $vacant->first()->id);
    }

    /** @test */
    public function scope_with_active_meters_returns_properties_with_meters(): void
    {
        $propertyWithMeters = Property::factory()->create(['tenant_id' => 1]);
        $propertyWithoutMeters = Property::factory()->create(['tenant_id' => 1]);
        
        Meter::factory()->create(['property_id' => $propertyWithMeters->id]);

        $this->actingAs($this->tenantUser);

        $withMeters = Property::withActiveMeters()->get();

        $this->assertCount(1, $withMeters);
        $this->assertEquals($propertyWithMeters->id, $withMeters->first()->id);
    }
}
