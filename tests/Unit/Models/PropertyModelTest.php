<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\PropertyType;
use App\Models\Building;
use App\Models\Meter;
use App\Models\Property;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PropertyModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_property_can_be_created_with_all_types(): void
    {
        $types = [
            PropertyType::APARTMENT,
            PropertyType::HOUSE,
        ];

        foreach ($types as $type) {
            $property = Property::factory()->create([
                'type' => $type,
            ]);

            $this->assertInstanceOf(Property::class, $property);
            $this->assertEquals($type, $property->type);
        }
    }

    public function test_property_has_building_relationship(): void
    {
        $building = Building::factory()->create();
        $property = Property::factory()->create(['building_id' => $building->id]);

        $this->assertInstanceOf(Building::class, $property->building);
        $this->assertEquals($building->id, $property->building->id);
    }

    public function test_property_has_tenants_relationship(): void
    {
        $property = Property::factory()->create();
        $tenant = Tenant::factory()->create();

        $property->tenants()->attach($tenant->id, [
            'assigned_at' => now(),
            'vacated_at' => null,
        ]);

        $this->assertCount(1, $property->tenants);
        $this->assertEquals($tenant->id, $property->tenants->first()->id);
    }

    public function test_property_has_meters_relationship(): void
    {
        $property = Property::factory()->create();
        $meter = Meter::factory()->create(['property_id' => $property->id]);

        $this->assertCount(1, $property->meters);
        $this->assertEquals($meter->id, $property->meters->first()->id);
    }

    public function test_property_tenant_assignments_include_historical(): void
    {
        $property = Property::factory()->create();
        $activeTenant = Tenant::factory()->create();
        $historicalTenant = Tenant::factory()->create();

        $property->tenantAssignments()->attach($activeTenant->id, [
            'assigned_at' => now(),
            'vacated_at' => null,
        ]);

        $property->tenantAssignments()->attach($historicalTenant->id, [
            'assigned_at' => now()->subMonths(6),
            'vacated_at' => now()->subMonths(1),
        ]);

        $this->assertCount(2, $property->tenantAssignments);
    }

    public function test_property_type_cast_to_enum(): void
    {
        $property = Property::factory()->create([
            'type' => PropertyType::APARTMENT,
        ]);

        $this->assertInstanceOf(PropertyType::class, $property->type);
        $this->assertEquals(PropertyType::APARTMENT, $property->type);
    }

    public function test_property_area_cast_to_decimal(): void
    {
        $property = Property::factory()->create([
            'area_sqm' => 75.55,
        ]);

        $this->assertEquals('75.55', $property->area_sqm);
    }

    public function test_of_type_scope_filters_properties(): void
    {
        Property::factory()->create(['type' => PropertyType::APARTMENT]);
        Property::factory()->create(['type' => PropertyType::HOUSE]);
        Property::factory()->create(['type' => PropertyType::APARTMENT]);

        $apartments = Property::ofType(PropertyType::APARTMENT)->get();
        $houses = Property::ofType(PropertyType::HOUSE)->get();

        $this->assertCount(2, $apartments);
        $this->assertCount(1, $houses);
    }

    public function test_apartments_scope_filters_apartments(): void
    {
        Property::factory()->create(['type' => PropertyType::APARTMENT]);
        Property::factory()->create(['type' => PropertyType::HOUSE]);
        Property::factory()->create(['type' => PropertyType::APARTMENT]);

        $apartments = Property::apartments()->get();

        $this->assertCount(2, $apartments);
        foreach ($apartments as $apartment) {
            $this->assertEquals(PropertyType::APARTMENT, $apartment->type);
        }
    }

    public function test_houses_scope_filters_houses(): void
    {
        Property::factory()->create(['type' => PropertyType::APARTMENT]);
        Property::factory()->create(['type' => PropertyType::HOUSE]);
        Property::factory()->create(['type' => PropertyType::HOUSE]);

        $houses = Property::houses()->get();

        $this->assertCount(2, $houses);
        foreach ($houses as $house) {
            $this->assertEquals(PropertyType::HOUSE, $house->type);
        }
    }

    public function test_property_tenant_pivot_has_timestamps(): void
    {
        $property = Property::factory()->create();
        $tenant = Tenant::factory()->create();

        $assignedAt = now()->subDays(10);
        $property->tenants()->attach($tenant->id, [
            'assigned_at' => $assignedAt,
            'vacated_at' => null,
        ]);

        $pivotData = $property->tenants->first()->pivot;

        $this->assertNotNull($pivotData->assigned_at);
        $this->assertNull($pivotData->vacated_at);
        // Check if assigned_at is a string or Carbon instance
        if (is_string($pivotData->assigned_at)) {
            $this->assertEquals($assignedAt->format('Y-m-d H:i:s'), $pivotData->assigned_at);
        } else {
            $this->assertEquals($assignedAt->format('Y-m-d H:i:s'), $pivotData->assigned_at->format('Y-m-d H:i:s'));
        }
    }

    public function test_tenants_relationship_excludes_vacated(): void
    {
        $property = Property::factory()->create();
        $activeTenant = Tenant::factory()->create();
        $vacatedTenant = Tenant::factory()->create();

        $property->tenantAssignments()->attach($activeTenant->id, [
            'assigned_at' => now(),
            'vacated_at' => null,
        ]);

        $property->tenantAssignments()->attach($vacatedTenant->id, [
            'assigned_at' => now()->subMonths(6),
            'vacated_at' => now()->subMonths(1),
        ]);

        // tenants() should only return active (non-vacated) tenants
        $activeTenants = $property->tenants;

        $this->assertCount(1, $activeTenants);
        $this->assertEquals($activeTenant->id, $activeTenants->first()->id);
    }
}
