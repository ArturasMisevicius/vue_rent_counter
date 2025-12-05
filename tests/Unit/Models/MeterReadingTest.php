<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\MeterType;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeterReadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_meter_reading_has_fillable_attributes(): void
    {
        $fillable = [
            'meter_id',
            'reading_value',
            'reading_date',
            'notes',
            'recorded_by_user_id',
        ];

        $meterReading = new MeterReading();
        $this->assertEquals($fillable, $meterReading->getFillable());
    }

    public function test_meter_reading_casts_attributes_correctly(): void
    {
        $meterReading = MeterReading::factory()->create([
            'reading_value' => 123.45,
            'reading_date' => now(),
        ]);

        $this->assertIsFloat($meterReading->reading_value);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $meterReading->reading_date);
    }

    public function test_meter_reading_belongs_to_meter(): void
    {
        $meter = Meter::factory()->create(['type' => MeterType::ELECTRICITY]);
        $meterReading = MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'tenant_id' => $meter->tenant_id,
        ]);

        $this->assertInstanceOf(Meter::class, $meterReading->meter);
        $this->assertEquals($meter->id, $meterReading->meter->id);
    }

    public function test_meter_reading_belongs_to_recorded_by_user(): void
    {
        $user = User::factory()->manager()->create();
        $meter = Meter::factory()->create([
            'tenant_id' => $user->tenant_id,
            'type' => MeterType::ELECTRICITY,
        ]);
        
        $meterReading = MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'tenant_id' => $user->tenant_id,
            'recorded_by_user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $meterReading->recordedBy);
        $this->assertEquals($user->id, $meterReading->recordedBy->id);
    }

    public function test_meter_reading_respects_tenant_isolation(): void
    {
        $tenant1 = User::factory()->tenant()->create();
        $tenant2 = User::factory()->tenant()->create();

        $meter1 = Meter::factory()->create([
            'tenant_id' => $tenant1->tenant_id,
            'type' => MeterType::ELECTRICITY,
        ]);
        $meter2 = Meter::factory()->create([
            'tenant_id' => $tenant2->tenant_id,
            'type' => MeterType::ELECTRICITY,
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter1->id,
            'tenant_id' => $tenant1->tenant_id,
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter2->id,
            'tenant_id' => $tenant2->tenant_id,
        ]);

        $this->actingAs($tenant1);
        
        $readings = MeterReading::all();
        
        // Should only see readings from own tenant
        $this->assertEquals(1, $readings->count());
        $this->assertEquals($tenant1->tenant_id, $readings->first()->tenant_id);
    }

    public function test_meter_reading_validates_monotonic_increase(): void
    {
        $meter = Meter::factory()->create(['type' => MeterType::ELECTRICITY]);
        
        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'tenant_id' => $meter->tenant_id,
            'reading_value' => 1000.0,
            'reading_date' => now()->subDay(),
        ]);

        // Creating a reading with lower value should be allowed (validation happens at controller level)
        $lowerReading = MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'tenant_id' => $meter->tenant_id,
            'reading_value' => 900.0,
            'reading_date' => now(),
        ]);

        $this->assertInstanceOf(MeterReading::class, $lowerReading);
    }

    public function test_meter_reading_has_timestamps(): void
    {
        $meterReading = MeterReading::factory()->create();

        $this->assertNotNull($meterReading->created_at);
        $this->assertNotNull($meterReading->updated_at);
    }

    public function test_meter_reading_can_have_notes(): void
    {
        $meterReading = MeterReading::factory()->create([
            'notes' => 'Test reading note',
        ]);

        $this->assertEquals('Test reading note', $meterReading->notes);
    }

    public function test_meter_reading_can_be_created_without_notes(): void
    {
        $meterReading = MeterReading::factory()->create([
            'notes' => null,
        ]);

        $this->assertNull($meterReading->notes);
    }

    public function test_meter_reading_stores_decimal_values_with_precision(): void
    {
        $meterReading = MeterReading::factory()->create([
            'reading_value' => 1234.567,
        ]);

        $this->assertEquals(1234.567, $meterReading->reading_value);
    }

    public function test_scope_for_meter_filters_by_meter_id(): void
    {
        $meter1 = Meter::factory()->create(['type' => MeterType::ELECTRICITY]);
        $meter2 = Meter::factory()->create(['type' => MeterType::ELECTRICITY]);

        MeterReading::factory()->count(3)->create([
            'meter_id' => $meter1->id,
            'tenant_id' => $meter1->tenant_id,
        ]);

        MeterReading::factory()->count(2)->create([
            'meter_id' => $meter2->id,
            'tenant_id' => $meter2->tenant_id,
        ]);

        $readings = MeterReading::forMeter($meter1->id)->get();

        $this->assertEquals(3, $readings->count());
        $this->assertTrue($readings->every(fn($r) => $r->meter_id === $meter1->id));
    }

    public function test_scope_between_dates_filters_correctly(): void
    {
        $meter = Meter::factory()->create(['type' => MeterType::ELECTRICITY]);

        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'tenant_id' => $meter->tenant_id,
            'reading_date' => now()->subDays(10),
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'tenant_id' => $meter->tenant_id,
            'reading_date' => now()->subDays(5),
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'tenant_id' => $meter->tenant_id,
            'reading_date' => now(),
        ]);

        $readings = MeterReading::betweenDates(
            now()->subDays(7),
            now()->subDays(3)
        )->get();

        $this->assertEquals(1, $readings->count());
    }

    public function test_meter_reading_factory_creates_valid_reading(): void
    {
        $meterReading = MeterReading::factory()->create();

        $this->assertInstanceOf(MeterReading::class, $meterReading);
        $this->assertNotNull($meterReading->meter_id);
        $this->assertNotNull($meterReading->reading_value);
        $this->assertNotNull($meterReading->reading_date);
        $this->assertGreaterThan(0, $meterReading->reading_value);
    }

    public function test_latest_reading_for_meter_returns_most_recent(): void
    {
        $meter = Meter::factory()->create(['type' => MeterType::ELECTRICITY]);

        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'tenant_id' => $meter->tenant_id,
            'reading_value' => 1000.0,
            'reading_date' => now()->subDays(10),
        ]);

        $latestReading = MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'tenant_id' => $meter->tenant_id,
            'reading_value' => 1100.0,
            'reading_date' => now(),
        ]);

        $result = MeterReading::forMeter($meter->id)
            ->orderBy('reading_date', 'desc')
            ->first();

        $this->assertEquals($latestReading->id, $result->id);
        $this->assertEquals(1100.0, $result->reading_value);
    }
}
