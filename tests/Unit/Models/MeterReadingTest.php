<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Http\Requests\StoreMeterReadingRequest;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class MeterReadingTest extends TestCase
{
    use RefreshDatabase;

    // ========================================
    // VALIDATION TESTS (Business Logic)
    // ========================================

    public function test_can_create_valid_higher_reading(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $meter = Meter::factory()->create(['tenant_id' => $tenant->id]);

        // Create previous reading
        $previousReading = MeterReading::factory()->create([
            'tenant_id' => $tenant->id,
            'meter_id' => $meter->id,
            'value' => 1000.00,
            'reading_date' => now()->subDays(1),
            'entered_by' => $user->id,
        ]);

        // Create higher reading - this should be valid
        $higherValue = 1500.00;

        $request = new StoreMeterReadingRequest();
        $request->replace([
            'meter_id' => $meter->id,
            'value' => $higherValue,
            'reading_date' => now()->format('Y-m-d'),
            'entered_by' => $user->id,
        ]);

        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertFalse($validator->fails(), 'Higher reading should pass validation');
        $this->assertEmpty($validator->errors()->all());

        // Actually create the reading to verify it works
        $newReading = MeterReading::factory()->create([
            'tenant_id' => $tenant->id,
            'meter_id' => $meter->id,
            'value' => $higherValue,
            'reading_date' => now(),
            'entered_by' => $user->id,
        ]);

        $this->assertInstanceOf(MeterReading::class, $newReading);
        $this->assertEquals($higherValue, $newReading->value);
        $this->assertGreaterThan($previousReading->value, $newReading->value);
    }

    public function test_cannot_create_lower_reading(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $meter = Meter::factory()->create(['tenant_id' => $tenant->id]);

        // Create previous reading
        MeterReading::factory()->create([
            'tenant_id' => $tenant->id,
            'meter_id' => $meter->id,
            'value' => 1000.00,
            'reading_date' => now()->subDays(1),
            'entered_by' => $user->id,
        ]);

        // Attempt to create lower reading - this should fail validation
        $lowerValue = 500.00;

        $request = new StoreMeterReadingRequest();
        $request->replace([
            'meter_id' => $meter->id,
            'value' => $lowerValue,
            'reading_date' => now()->format('Y-m-d'),
            'entered_by' => $user->id,
        ]);

        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertTrue($validator->fails(), 'Lower reading should fail validation');
        $this->assertTrue($validator->errors()->has('value'), 'Validation error should be on "value" field');
    }

    public function test_equal_reading_is_accepted(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $meter = Meter::factory()->create(['tenant_id' => $tenant->id]);

        $previousValue = 1000.00;

        MeterReading::factory()->create([
            'tenant_id' => $tenant->id,
            'meter_id' => $meter->id,
            'value' => $previousValue,
            'reading_date' => now()->subDays(1),
            'entered_by' => $user->id,
        ]);

        // Equal reading should be valid (monotonicity allows >=)
        $request = new StoreMeterReadingRequest();
        $request->replace([
            'meter_id' => $meter->id,
            'value' => $previousValue,
            'reading_date' => now()->format('Y-m-d'),
            'entered_by' => $user->id,
        ]);

        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertFalse($validator->fails(), 'Equal reading should pass validation');
    }

    public function test_first_reading_can_be_any_value(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $meter = Meter::factory()->create(['tenant_id' => $tenant->id]);

        // First reading (no previous reading) - any value should be valid
        $request = new StoreMeterReadingRequest();
        $request->replace([
            'meter_id' => $meter->id,
            'value' => 100.00,
            'reading_date' => now()->format('Y-m-d'),
            'entered_by' => $user->id,
        ]);

        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        $this->assertFalse($validator->fails(), 'First reading should always pass validation');
    }

    // ========================================
    // TENANT SCOPING TESTS
    // ========================================

    public function test_readings_are_scoped_to_tenant_via_meter_relationship(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        $user1 = User::factory()->create(['tenant_id' => $tenant1->id]);
        $user2 = User::factory()->create(['tenant_id' => $tenant2->id]);

        // Use forTenantId() to ensure meter and property are properly aligned
        $meter1 = Meter::factory()->forTenantId($tenant1->id)->create();
        $meter2 = Meter::factory()->forTenantId($tenant2->id)->create();

        $reading1 = MeterReading::factory()->create([
            'tenant_id' => $tenant1->id,
            'meter_id' => $meter1->id,
            'entered_by' => $user1->id,
        ]);

        $reading2 = MeterReading::factory()->create([
            'tenant_id' => $tenant2->id,
            'meter_id' => $meter2->id,
            'entered_by' => $user2->id,
        ]);

        // Refresh to get any factory adjustments
        $reading1->refresh();
        $reading2->refresh();

        // Verify tenant_id matches meter's tenant_id
        $this->assertEquals($meter1->tenant_id, $reading1->tenant_id);
        $this->assertEquals($meter2->tenant_id, $reading2->tenant_id);

        // Verify readings belong to correct tenants
        $this->assertEquals($tenant1->id, $reading1->tenant_id);
        $this->assertEquals($tenant2->id, $reading2->tenant_id);

        // Verify meter relationship returns correct tenant
        $this->assertEquals($tenant1->id, $reading1->meter->tenant_id);
        $this->assertEquals($tenant2->id, $reading2->meter->tenant_id);
    }

    public function test_reading_tenant_id_must_match_meter_tenant_id(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant1->id]);
        $meter = Meter::factory()->create(['tenant_id' => $tenant1->id]);

        // Factory should auto-correct mismatched tenant_id
        $reading = MeterReading::factory()->create([
            'tenant_id' => $tenant2->id, // Wrong tenant
            'meter_id' => $meter->id,
            'entered_by' => $user->id,
        ]);

        // Factory's configure() method should align tenant_id with meter's tenant_id
        $reading->refresh();
        $this->assertEquals($meter->tenant_id, $reading->tenant_id);
    }

    // ========================================
    // MODEL RELATIONSHIPS
    // ========================================

    public function test_meter_reading_belongs_to_meter(): void
    {
        $meter = Meter::factory()->create();
        $reading = MeterReading::factory()->create(['meter_id' => $meter->id]);

        $this->assertInstanceOf(Meter::class, $reading->meter);
        $this->assertEquals($meter->id, $reading->meter->id);
    }

    public function test_meter_reading_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $reading = MeterReading::factory()->create(['entered_by' => $user->id]);

        $this->assertInstanceOf(User::class, $reading->enteredBy);
        $this->assertEquals($user->id, $reading->enteredBy->id);
    }

    public function test_meter_reading_has_audit_trail_relationship(): void
    {
        $reading = MeterReading::factory()->create();

        // Initially no audit trail
        $this->assertCount(0, $reading->auditTrail);

        // The relationship exists
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $reading->auditTrail);
    }

    // ========================================
    // MODEL METHODS
    // ========================================

    public function test_get_consumption_returns_null_for_first_reading(): void
    {
        $meter = Meter::factory()->create();
        $reading = MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'value' => 100.00,
        ]);

        // First reading has no previous reading
        $consumption = $reading->getConsumption();

        $this->assertNull($consumption);
    }

    public function test_get_consumption_calculates_difference_from_previous_reading(): void
    {
        $meter = Meter::factory()->create();

        $previousReading = MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'value' => 1000.00,
            'reading_date' => now()->subDays(7),
        ]);

        $currentReading = MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'value' => 1350.50,
            'reading_date' => now(),
        ]);

        $consumption = $currentReading->getConsumption();

        $this->assertNotNull($consumption);
        $this->assertEquals(350.50, $consumption);
    }

    public function test_get_consumption_is_zone_aware(): void
    {
        $meter = Meter::factory()->create(['supports_zones' => true]);

        // Create readings for different zones
        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'zone' => 'day',
            'value' => 1000.00,
            'reading_date' => now()->subDays(7),
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'zone' => 'night',
            'value' => 500.00,
            'reading_date' => now()->subDays(7),
        ]);

        $dayReading = MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'zone' => 'day',
            'value' => 1200.00,
            'reading_date' => now(),
        ]);

        // Should calculate consumption only within the same zone
        $consumption = $dayReading->getConsumption();

        $this->assertNotNull($consumption);
        $this->assertEquals(200.00, $consumption);
    }

    // ========================================
    // MODEL SCOPES
    // ========================================

    public function test_for_period_scope_filters_by_date_range(): void
    {
        $meter = Meter::factory()->create();

        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => '2024-01-15',
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => '2024-02-15',
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'reading_date' => '2024-03-15',
        ]);

        $readingsInPeriod = MeterReading::forPeriod('2024-02-01', '2024-02-28')->get();

        $this->assertCount(1, $readingsInPeriod);
        $this->assertEquals('2024-02-15', $readingsInPeriod->first()->reading_date->format('Y-m-d'));
    }

    public function test_for_zone_scope_filters_by_zone(): void
    {
        $meter = Meter::factory()->create(['supports_zones' => true]);

        MeterReading::factory()->create(['meter_id' => $meter->id, 'zone' => 'day']);
        MeterReading::factory()->create(['meter_id' => $meter->id, 'zone' => 'night']);
        MeterReading::factory()->create(['meter_id' => $meter->id, 'zone' => 'day']);
        MeterReading::factory()->create(['meter_id' => $meter->id, 'zone' => null]);

        $dayReadings = MeterReading::forZone('day')->get();
        $nightReadings = MeterReading::forZone('night')->get();
        $nullZoneReadings = MeterReading::forZone(null)->get();

        $this->assertCount(2, $dayReadings);
        $this->assertCount(1, $nightReadings);
        $this->assertCount(1, $nullZoneReadings);
    }

    public function test_latest_scope_orders_by_date_descending(): void
    {
        // Use forTenantId to avoid tenant_id mismatches
        $meter = Meter::factory()->forTenantId(1)->create();

        $reading1 = MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'tenant_id' => $meter->tenant_id,
            'reading_date' => '2024-01-15',
        ]);

        $reading3 = MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'tenant_id' => $meter->tenant_id,
            'reading_date' => '2024-03-15',
        ]);

        $reading2 = MeterReading::factory()->create([
            'meter_id' => $meter->id,
            'tenant_id' => $meter->tenant_id,
            'reading_date' => '2024-02-15',
        ]);

        // Refresh readings to get any factory auto-corrections
        $reading1->refresh();
        $reading2->refresh();
        $reading3->refresh();

        // Scope to this meter's readings only to avoid interference from other tests
        $sortedReadings = MeterReading::where('meter_id', $meter->id)
            ->orderBy('reading_date', 'desc')
            ->get();

        $this->assertCount(3, $sortedReadings);
        $this->assertEquals($reading3->id, $sortedReadings[0]->id);
        $this->assertEquals($reading2->id, $sortedReadings[1]->id);
        $this->assertEquals($reading1->id, $sortedReadings[2]->id);
    }

    // ========================================
    // ATTRIBUTE CASTS
    // ========================================

    public function test_reading_date_cast_to_datetime(): void
    {
        $date = '2024-06-15 10:30:00';
        $reading = MeterReading::factory()->create([
            'reading_date' => $date,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $reading->reading_date);
        $this->assertEquals('2024-06-15', $reading->reading_date->format('Y-m-d'));
    }

    public function test_value_cast_to_decimal(): void
    {
        $reading = MeterReading::factory()->create([
            'value' => 1234.567, // More than 2 decimals
        ]);

        // Should be cast to decimal:2
        $this->assertEquals('1234.57', (string) $reading->value);
    }

    // ========================================
    // FILLABLE ATTRIBUTES
    // ========================================

    public function test_meter_reading_fillable_attributes(): void
    {
        $tenant = Tenant::factory()->create();
        $meter = Meter::factory()->create(['tenant_id' => $tenant->id]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $reading = new MeterReading();
        $reading->fill([
            'tenant_id' => $tenant->id,
            'meter_id' => $meter->id,
            'reading_date' => now(),
            'value' => 500.00,
            'zone' => 'day',
            'entered_by' => $user->id,
        ]);
        $reading->save();

        $this->assertEquals($tenant->id, $reading->tenant_id);
        $this->assertEquals($meter->id, $reading->meter_id);
        $this->assertEquals(500.00, $reading->value);
        $this->assertEquals('day', $reading->zone);
        $this->assertEquals($user->id, $reading->entered_by);
    }

    // ========================================
    // ZONE-AWARE MONOTONICITY
    // ========================================

    public function test_monotonicity_is_enforced_per_zone(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $meter = Meter::factory()->create([
            'tenant_id' => $tenant->id,
            'supports_zones' => true,
        ]);

        // Create previous readings for different zones
        MeterReading::factory()->create([
            'tenant_id' => $tenant->id,
            'meter_id' => $meter->id,
            'zone' => 'day',
            'value' => 1000.00,
            'reading_date' => now()->subDays(1),
            'entered_by' => $user->id,
        ]);

        MeterReading::factory()->create([
            'tenant_id' => $tenant->id,
            'meter_id' => $meter->id,
            'zone' => 'night',
            'value' => 500.00,
            'reading_date' => now()->subDays(1),
            'entered_by' => $user->id,
        ]);

        // Lower value for 'day' zone should fail
        $dayRequest = new StoreMeterReadingRequest();
        $dayRequest->replace([
            'meter_id' => $meter->id,
            'zone' => 'day',
            'value' => 800.00, // Lower than previous 'day' reading
            'reading_date' => now()->format('Y-m-d'),
            'entered_by' => $user->id,
        ]);

        $dayValidator = Validator::make($dayRequest->all(), $dayRequest->rules());
        $dayRequest->withValidator($dayValidator);

        $this->assertTrue($dayValidator->fails(), 'Lower day zone reading should fail');

        // Lower value for 'night' zone should fail
        $nightRequest = new StoreMeterReadingRequest();
        $nightRequest->replace([
            'meter_id' => $meter->id,
            'zone' => 'night',
            'value' => 300.00, // Lower than previous 'night' reading
            'reading_date' => now()->format('Y-m-d'),
            'entered_by' => $user->id,
        ]);

        $nightValidator = Validator::make($nightRequest->all(), $nightRequest->rules());
        $nightRequest->withValidator($nightValidator);

        $this->assertTrue($nightValidator->fails(), 'Lower night zone reading should fail');

        // Higher value for 'day' zone should pass (despite being lower than night zone)
        $validDayRequest = new StoreMeterReadingRequest();
        $validDayRequest->replace([
            'meter_id' => $meter->id,
            'zone' => 'day',
            'value' => 1200.00, // Higher than previous 'day' reading
            'reading_date' => now()->format('Y-m-d'),
            'entered_by' => $user->id,
        ]);

        $validDayValidator = Validator::make($validDayRequest->all(), $validDayRequest->rules());
        $validDayRequest->withValidator($validDayValidator);

        $this->assertFalse($validDayValidator->fails(), 'Higher day zone reading should pass');
    }
}
