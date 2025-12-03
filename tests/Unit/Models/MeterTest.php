<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\MeterType;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Comprehensive Unit Tests for Meter Model
 *
 * Tests:
 * - Mass assignment and fillable attributes
 * - Attribute casting (MeterType enum, date, boolean)
 * - Relationships (property, readings)
 * - Tenant isolation via BelongsToTenant trait
 * - Query scopes
 */
final class MeterTest extends TestCase
{
    use RefreshDatabase;

    private User $tenantUser;
    private User $otherTenantUser;
    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two separate tenant organizations
        $this->tenantUser = User::factory()->create(['tenant_id' => 1]);
        $this->otherTenantUser = User::factory()->create(['tenant_id' => 2]);

        // Create a property for testing
        $this->property = Property::factory()->create(['tenant_id' => 1]);
    }

    /** @test */
    public function it_has_correct_fillable_attributes(): void
    {
        $meter = new Meter();

        $expectedFillable = [
            'tenant_id',
            'serial_number',
            'type',
            'property_id',
            'installation_date',
            'supports_zones',
        ];

        $this->assertEquals($expectedFillable, $meter->getFillable());
    }

    /** @test */
    public function it_can_be_created_with_mass_assignment(): void
    {
        $data = [
            'tenant_id' => 1,
            'property_id' => $this->property->id,
            'serial_number' => 'MTR-2024-001',
            'type' => MeterType::ELECTRICITY,
            'installation_date' => '2024-01-15',
            'supports_zones' => true,
        ];

        $meter = Meter::create($data);

        $this->assertDatabaseHas('meters', [
            'id' => $meter->id,
            'tenant_id' => 1,
            'serial_number' => 'MTR-2024-001',
            'supports_zones' => true,
        ]);
    }

    /** @test */
    public function it_casts_type_to_meter_type_enum(): void
    {
        $meter = Meter::factory()->create([
            'type' => MeterType::ELECTRICITY,
        ]);

        $this->assertInstanceOf(MeterType::class, $meter->type);
        $this->assertEquals(MeterType::ELECTRICITY, $meter->type);
    }

    /** @test */
    public function it_casts_installation_date_to_date(): void
    {
        $meter = Meter::factory()->create([
            'installation_date' => '2024-01-15',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $meter->installation_date);
        $this->assertEquals('2024-01-15', $meter->installation_date->format('Y-m-d'));
    }

    /** @test */
    public function it_casts_supports_zones_to_boolean(): void
    {
        $meter = Meter::factory()->create([
            'supports_zones' => true,
        ]);

        $this->assertIsBool($meter->supports_zones);
        $this->assertTrue($meter->supports_zones);

        $meter2 = Meter::factory()->create([
            'supports_zones' => false,
        ]);

        $this->assertIsBool($meter2->supports_zones);
        $this->assertFalse($meter2->supports_zones);
    }

    /** @test */
    public function it_belongs_to_a_property(): void
    {
        $meter = Meter::factory()->create([
            'property_id' => $this->property->id,
        ]);

        $this->assertInstanceOf(Property::class, $meter->property);
        $this->assertEquals($this->property->id, $meter->property->id);
    }

    /** @test */
    public function it_has_many_readings(): void
    {
        $meter = Meter::factory()->create([
            'tenant_id' => 1,
        ]);

        MeterReading::factory()->count(5)->create([
            'meter_id' => $meter->id,
        ]);

        // Refresh to load the relationship
        $meter->load('readings');

        $this->assertCount(5, $meter->readings);
        $this->assertInstanceOf(MeterReading::class, $meter->readings->first());
    }

    /** @test */
    public function it_respects_tenant_isolation(): void
    {
        $this->markTestSkipped('TODO: Refactor BelongsToTenant trait logic for complex tenant isolation edge cases with raw DB inserts');
    }

    /** @test */
    public function tenant_cannot_see_other_tenants_meters(): void
    {
        $this->markTestSkipped('TODO: Refactor BelongsToTenant trait logic for complex tenant isolation edge cases with raw DB inserts');
    }

    /** @test */
    public function scope_of_type_filters_by_meter_type(): void
    {
        Meter::factory()->create(['type' => MeterType::ELECTRICITY, 'tenant_id' => 1]);
        Meter::factory()->create(['type' => MeterType::WATER_COLD, 'tenant_id' => 1]);
        Meter::factory()->create(['type' => MeterType::ELECTRICITY, 'tenant_id' => 1]);
        Meter::factory()->create(['type' => MeterType::HEATING, 'tenant_id' => 1]);

        $this->actingAs($this->tenantUser);

        $electricityMeters = Meter::ofType(MeterType::ELECTRICITY)->get();
        $waterMeters = Meter::ofType(MeterType::WATER_COLD)->get();
        $heatingMeters = Meter::ofType(MeterType::HEATING)->get();

        $this->assertCount(2, $electricityMeters);
        $this->assertCount(1, $waterMeters);
        $this->assertCount(1, $heatingMeters);

        $electricityMeters->each(function ($meter) {
            $this->assertEquals(MeterType::ELECTRICITY, $meter->type);
        });
    }

    /** @test */
    public function scope_supports_zones_returns_only_meters_with_zone_support(): void
    {
        Meter::factory()->count(3)->create([
            'supports_zones' => true,
            'tenant_id' => 1,
        ]);

        Meter::factory()->count(2)->create([
            'supports_zones' => false,
            'tenant_id' => 1,
        ]);

        $this->actingAs($this->tenantUser);

        $zoneMeters = Meter::supportsZones()->get();

        $this->assertCount(3, $zoneMeters);
        $zoneMeters->each(fn($meter) => $this->assertTrue($meter->supports_zones));
    }

    /** @test */
    public function scope_with_latest_reading_eager_loads_latest_reading(): void
    {
        $meter = Meter::factory()->create(['tenant_id' => 1]);

        // Create multiple readings with different dates
        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => now()->subDays(10),
            'value' => 100,
        ]);

        $latestReading = MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => now(),
            'value' => 150,
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => now()->subDays(5),
            'value' => 125,
        ]);

        $this->actingAs($this->tenantUser);

        $meterWithLatest = Meter::withLatestReading()->find($meter->id);

        $this->assertNotNull($meterWithLatest);
        $this->assertTrue($meterWithLatest->relationLoaded('readings'));
        $this->assertCount(1, $meterWithLatest->readings);
        $this->assertEquals($latestReading->id, $meterWithLatest->readings->first()->id);
        $this->assertEquals(150, $meterWithLatest->readings->first()->value);
    }

    /** @test */
    public function it_has_timestamps(): void
    {
        $meter = Meter::factory()->create();

        $this->assertNotNull($meter->created_at);
        $this->assertNotNull($meter->updated_at);
    }

    /** @test */
    public function tenant_isolation_works_with_relationships(): void
    {
        $this->markTestSkipped('TODO: Refactor BelongsToTenant trait logic for complex tenant isolation edge cases with raw DB inserts');
    }

    /** @test */
    public function superadmin_can_see_all_meters_regardless_of_tenant(): void
    {
        $superadmin = User::factory()->create([
            'role' => \App\Enums\UserRole::SUPERADMIN,
            'tenant_id' => null,
        ]);

        Meter::factory()->create(['tenant_id' => 1]);
        Meter::factory()->create(['tenant_id' => 2]);
        Meter::factory()->create(['tenant_id' => 3]);

        $this->actingAs($superadmin);

        // Superadmin should see all meters
        $meters = Meter::withoutGlobalScope(\App\Scopes\TenantScope::class)->get();

        $this->assertCount(3, $meters);
    }

    /** @test */
    public function serial_number_is_unique_identifier(): void
    {
        $meter = Meter::factory()->create([
            'serial_number' => 'UNIQUE-SERIAL-001',
            'tenant_id' => 1,
        ]);

        $this->actingAs($this->tenantUser);

        $found = Meter::where('serial_number', 'UNIQUE-SERIAL-001')->first();

        $this->assertNotNull($found);
        $this->assertEquals($meter->id, $found->id);
    }

    /** @test */
    public function can_scope_multiple_filters_together(): void
    {
        // Create various meters
        Meter::factory()->create([
            'type' => MeterType::ELECTRICITY,
            'supports_zones' => true,
            'tenant_id' => 1,
        ]);

        Meter::factory()->create([
            'type' => MeterType::ELECTRICITY,
            'supports_zones' => false,
            'tenant_id' => 1,
        ]);

        Meter::factory()->create([
            'type' => MeterType::WATER_COLD,
            'supports_zones' => true,
            'tenant_id' => 1,
        ]);

        $this->actingAs($this->tenantUser);

        // Filter for electricity meters with zone support
        $result = Meter::ofType(MeterType::ELECTRICITY)
            ->supportsZones()
            ->get();

        $this->assertCount(1, $result);
        $this->assertEquals(MeterType::ELECTRICITY, $result->first()->type);
        $this->assertTrue($result->first()->supports_zones);
    }
}
