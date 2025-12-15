<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\InputMethod;
use App\Enums\MeterType;
use App\Enums\ValidationStatus;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MeterReadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_meter_reading_has_fillable_attributes(): void
    {
        $fillable = [
            'tenant_id',
            'meter_id',
            'reading_date',
            'value',
            'zone',
            'entered_by',
            'reading_values',
            'input_method',
            'validation_status',
            'photo_path',
            'validated_by',
            'validated_at',
            'validation_notes',
        ];

        $meterReading = new MeterReading();

        $this->assertSame($fillable, $meterReading->getFillable());
    }

    public function test_meter_reading_casts_attributes_correctly(): void
    {
        $meter = Meter::factory()->create(['type' => MeterType::ELECTRICITY]);
        $user = User::factory()->manager($meter->tenant_id)->create();

        $meterReading = MeterReading::factory()->create([
            'tenant_id' => $meter->tenant_id,
            'meter_id' => $meter->id,
            'reading_date' => now(),
            'value' => 123.45,
            'zone' => 'day',
            'entered_by' => $user->id,
            'reading_values' => ['total' => 123.45],
            'input_method' => InputMethod::MANUAL,
            'validation_status' => ValidationStatus::PENDING,
        ]);

        $this->assertInstanceOf(Carbon::class, $meterReading->reading_date);
        $this->assertIsString($meterReading->value);
        $this->assertSame('123.45', $meterReading->value);
        $this->assertEquals(['total' => 123.45], $meterReading->reading_values);
        $this->assertSame(InputMethod::MANUAL, $meterReading->input_method);
        $this->assertSame(ValidationStatus::PENDING, $meterReading->validation_status);
    }

    public function test_meter_reading_belongs_to_meter(): void
    {
        $meter = Meter::factory()->create(['type' => MeterType::ELECTRICITY]);

        $meterReading = MeterReading::factory()->forMeter($meter)->create();

        $this->assertInstanceOf(Meter::class, $meterReading->meter);
        $this->assertEquals($meter->id, $meterReading->meter->id);
    }

    public function test_meter_reading_belongs_to_entered_by_user(): void
    {
        $user = User::factory()->manager()->create();
        $meter = Meter::factory()->create([
            'tenant_id' => $user->tenant_id,
            'type' => MeterType::ELECTRICITY,
        ]);

        $meterReading = MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'tenant_id' => $user->tenant_id,
            'entered_by' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $meterReading->enteredBy);
        $this->assertEquals($user->id, $meterReading->enteredBy->id);
    }

    public function test_meter_reading_belongs_to_validated_by_user(): void
    {
        $validator = User::factory()->manager()->create();
        $meter = Meter::factory()->forTenantId($validator->tenant_id)->create(['type' => MeterType::ELECTRICITY]);

        $reading = MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'tenant_id' => $validator->tenant_id,
            'validation_status' => ValidationStatus::VALIDATED,
            'validated_by' => $validator->id,
        ]);

        $this->assertInstanceOf(User::class, $reading->validatedBy);
        $this->assertEquals($validator->id, $reading->validatedBy->id);
    }

    public function test_meter_reading_respects_tenant_isolation(): void
    {
        $tenant1 = User::factory()->tenant(1)->create(['property_id' => null]);
        $tenant2 = User::factory()->tenant(2)->create(['property_id' => null]);

        $meter1 = Meter::factory()->forTenantId($tenant1->tenant_id)->create(['type' => MeterType::ELECTRICITY]);
        $meter2 = Meter::factory()->forTenantId($tenant2->tenant_id)->create(['type' => MeterType::ELECTRICITY]);

        MeterReading::factory()->forMeter($meter1)->create();
        MeterReading::factory()->forMeter($meter2)->create();

        $this->actingAs($tenant1);

        $readings = MeterReading::all();

        $this->assertCount(1, $readings);
        $this->assertEquals($tenant1->tenant_id, $readings->first()->tenant_id);
    }

    public function test_meter_reading_allows_non_monotonic_values(): void
    {
        $meter = Meter::factory()->create(['type' => MeterType::ELECTRICITY]);

        MeterReading::factory()->forMeter($meter)->create([
            'value' => 1000.0,
            'reading_date' => now()->subDay(),
        ]);

        $lowerReading = MeterReading::factory()->forMeter($meter)->create([
            'value' => 900.0,
            'reading_date' => now(),
        ]);

        $this->assertInstanceOf(MeterReading::class, $lowerReading);
        $this->assertSame(900.0, $lowerReading->getEffectiveValue());
    }

    public function test_meter_reading_stores_decimal_values_with_two_decimals(): void
    {
        $meterReading = MeterReading::factory()->create([
            'value' => 1234.567,
        ]);

        $this->assertSame('1234.57', $meterReading->value);
    }

    public function test_scope_for_period_filters_correctly(): void
    {
        $meter = Meter::factory()->create(['type' => MeterType::ELECTRICITY]);

        MeterReading::factory()->forMeter($meter)->create([
            'reading_date' => now()->subDays(10),
        ]);

        $inRange = MeterReading::factory()->forMeter($meter)->create([
            'reading_date' => now()->subDays(5),
        ]);

        MeterReading::factory()->forMeter($meter)->create([
            'reading_date' => now(),
        ]);

        $start = now()->subDays(7)->startOfDay()->toDateTimeString();
        $end = now()->subDays(3)->endOfDay()->toDateTimeString();

        $readings = MeterReading::forPeriod($start, $end)->get();

        $this->assertCount(1, $readings);
        $this->assertSame($inRange->id, $readings->first()->id);
    }

    public function test_scope_for_zone_filters_correctly(): void
    {
        $meter = Meter::factory()->create(['type' => MeterType::ELECTRICITY]);

        $dayReading = MeterReading::factory()->forMeter($meter)->withZone('day')->create();
        MeterReading::factory()->forMeter($meter)->withZone('night')->create();
        $noZoneReading = MeterReading::factory()->forMeter($meter)->create(['zone' => null]);

        $dayReadings = MeterReading::forZone('day')->get();
        $this->assertCount(1, $dayReadings);
        $this->assertSame($dayReading->id, $dayReadings->first()->id);

        $noZoneReadings = MeterReading::forZone(null)->get();
        $this->assertCount(1, $noZoneReadings);
        $this->assertSame($noZoneReading->id, $noZoneReadings->first()->id);
    }

    public function test_meter_reading_factory_creates_valid_reading(): void
    {
        $meterReading = MeterReading::factory()->create();

        $this->assertInstanceOf(MeterReading::class, $meterReading);
        $this->assertNotNull($meterReading->meter_id);
        $this->assertNotNull($meterReading->value);
        $this->assertNotNull($meterReading->reading_date);
        $this->assertNotNull($meterReading->entered_by);
    }

    public function test_latest_reading_for_meter_returns_most_recent(): void
    {
        $meter = Meter::factory()->create(['type' => MeterType::ELECTRICITY]);

        MeterReading::factory()->forMeter($meter)->create([
            'value' => 1000.0,
            'reading_date' => now()->subDays(10),
        ]);

        $latestReading = MeterReading::factory()->forMeter($meter)->create([
            'value' => 1100.0,
            'reading_date' => now(),
        ]);

        $result = MeterReading::query()
            ->where('meter_id', $meter->id)
            ->latest('reading_date')
            ->first();

        $this->assertNotNull($result);
        $this->assertEquals($latestReading->id, $result->id);
        $this->assertSame('1100.00', $result->value);
    }
}
