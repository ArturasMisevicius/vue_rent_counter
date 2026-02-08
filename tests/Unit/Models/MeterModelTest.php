<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\MeterType;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MeterModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_meter_can_be_created_with_all_types(): void
    {
        $types = [
            MeterType::ELECTRICITY,
            MeterType::WATER_COLD,
            MeterType::WATER_HOT,
            MeterType::HEATING,
        ];

        foreach ($types as $type) {
            $meter = Meter::factory()->create([
                'type' => $type,
            ]);

            $this->assertInstanceOf(Meter::class, $meter);
            $this->assertEquals($type, $meter->type);
        }
    }

    public function test_meter_has_property_relationship(): void
    {
        $property = Property::factory()->create();
        $meter = Meter::factory()->create(['property_id' => $property->id]);

        $this->assertInstanceOf(Property::class, $meter->property);
        $this->assertEquals($property->id, $meter->property->id);
    }

    public function test_meter_has_readings_relationship(): void
    {
        $meter = Meter::factory()->create();
        $reading = MeterReading::factory()->create(['meter_id' => $meter->id]);

        $this->assertCount(1, $meter->readings);
        $this->assertEquals($reading->id, $meter->readings->first()->id);
    }

    public function test_meter_type_cast_to_enum(): void
    {
        $meter = Meter::factory()->create([
            'type' => MeterType::ELECTRICITY,
        ]);

        $this->assertInstanceOf(MeterType::class, $meter->type);
        $this->assertEquals(MeterType::ELECTRICITY, $meter->type);
    }

    public function test_meter_installation_date_cast_to_date(): void
    {
        $installationDate = now()->subMonths(6);
        $meter = Meter::factory()->create([
            'installation_date' => $installationDate,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $meter->installation_date);
        $this->assertEquals($installationDate->format('Y-m-d'), $meter->installation_date->format('Y-m-d'));
    }

    public function test_meter_supports_zones_cast_to_boolean(): void
    {
        $meterWithZones = Meter::factory()->create(['supports_zones' => true]);
        $meterWithoutZones = Meter::factory()->create(['supports_zones' => false]);

        $this->assertTrue($meterWithZones->supports_zones);
        $this->assertFalse($meterWithoutZones->supports_zones);
        $this->assertIsBool($meterWithZones->supports_zones);
        $this->assertIsBool($meterWithoutZones->supports_zones);
    }

    public function test_of_type_scope_filters_meters(): void
    {
        Meter::factory()->create(['type' => MeterType::ELECTRICITY]);
        Meter::factory()->create(['type' => MeterType::WATER_COLD]);
        Meter::factory()->create(['type' => MeterType::ELECTRICITY]);
        Meter::factory()->create(['type' => MeterType::HEATING]);

        $electricityMeters = Meter::ofType(MeterType::ELECTRICITY)->get();
        $waterMeters = Meter::ofType(MeterType::WATER_COLD)->get();

        $this->assertCount(2, $electricityMeters);
        $this->assertCount(1, $waterMeters);

        foreach ($electricityMeters as $meter) {
            $this->assertEquals(MeterType::ELECTRICITY, $meter->type);
        }
    }

    public function test_supports_zones_scope_filters_zoned_meters(): void
    {
        Meter::factory()->create(['supports_zones' => true]);
        Meter::factory()->create(['supports_zones' => false]);
        Meter::factory()->create(['supports_zones' => true]);
        Meter::factory()->create(['supports_zones' => false]);

        $zonedMeters = Meter::supportsZones()->get();

        $this->assertCount(2, $zonedMeters);
        foreach ($zonedMeters as $meter) {
            $this->assertTrue($meter->supports_zones);
        }
    }

    public function test_with_latest_reading_scope_loads_reading(): void
    {
        $meter = Meter::factory()->create();

        // Create multiple readings
        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => now()->subDays(10),
            'value' => 100.0,
        ]);

        $latestReading = MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => now(),
            'value' => 200.0,
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => now()->subDays(5),
            'value' => 150.0,
        ]);

        $meterWithReading = Meter::withLatestReading()->find($meter->id);

        $this->assertTrue($meterWithReading->relationLoaded('readings'));
        $this->assertCount(1, $meterWithReading->readings);
        $this->assertEquals($latestReading->id, $meterWithReading->readings->first()->id);
        $this->assertEquals(200.0, $meterWithReading->readings->first()->value);
    }

    public function test_meter_serial_number_is_fillable(): void
    {
        $serialNumber = 'SN-12345678';
        $meter = Meter::factory()->create([
            'serial_number' => $serialNumber,
        ]);

        $this->assertEquals($serialNumber, $meter->serial_number);

        // Test mass assignment
        $property = Property::factory()->create();
        $meter2 = new Meter();
        $meter2->fill([
            'serial_number' => 'SN-87654321',
            'type' => MeterType::ELECTRICITY,
            'property_id' => $property->id,
            'tenant_id' => $property->tenant_id,
            'installation_date' => now(),
        ]);
        $meter2->save();

        $this->assertEquals('SN-87654321', $meter2->serial_number);
    }
}
