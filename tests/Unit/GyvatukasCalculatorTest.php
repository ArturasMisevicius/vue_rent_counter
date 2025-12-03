<?php

use App\Enums\MeterType;
use App\Models\Building;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Services\GyvatukasCalculator;
use Carbon\Carbon;

beforeEach(function () {
    $this->calculator = new GyvatukasCalculator();
});

test('isHeatingSeason returns true for October through April', function () {
    // Heating season months: October (10) through April (4)
    expect($this->calculator->isHeatingSeason(Carbon::create(2024, 10, 15)))->toBeTrue();
    expect($this->calculator->isHeatingSeason(Carbon::create(2024, 11, 15)))->toBeTrue();
    expect($this->calculator->isHeatingSeason(Carbon::create(2024, 12, 15)))->toBeTrue();
    expect($this->calculator->isHeatingSeason(Carbon::create(2024, 1, 15)))->toBeTrue();
    expect($this->calculator->isHeatingSeason(Carbon::create(2024, 2, 15)))->toBeTrue();
    expect($this->calculator->isHeatingSeason(Carbon::create(2024, 3, 15)))->toBeTrue();
    expect($this->calculator->isHeatingSeason(Carbon::create(2024, 4, 15)))->toBeTrue();
});

test('isHeatingSeason returns false for May through September', function () {
    // Non-heating season months: May (5) through September (9)
    expect($this->calculator->isHeatingSeason(Carbon::create(2024, 5, 15)))->toBeFalse();
    expect($this->calculator->isHeatingSeason(Carbon::create(2024, 6, 15)))->toBeFalse();
    expect($this->calculator->isHeatingSeason(Carbon::create(2024, 7, 15)))->toBeFalse();
    expect($this->calculator->isHeatingSeason(Carbon::create(2024, 8, 15)))->toBeFalse();
    expect($this->calculator->isHeatingSeason(Carbon::create(2024, 9, 15)))->toBeFalse();
});

test('calculateWinterGyvatukas returns stored summer average', function () {
    $building = Building::factory()->create([
        'gyvatukas_summer_average' => 150.50,
    ]);

    $result = $this->calculator->calculateWinterGyvatukas($building);

    expect($result)->toBe(150.50);
});

test('calculateWinterGyvatukas returns zero when no summer average stored', function () {
    $building = Building::factory()->create([
        'gyvatukas_summer_average' => null,
    ]);

    $result = $this->calculator->calculateWinterGyvatukas($building);

    expect($result)->toBe(0.0);
});

test('distributeCirculationCost divides equally when method is equal', function () {
    $building = Building::factory()->create();
    
    // Create 3 properties with different areas
    Property::factory()->create(['building_id' => $building->id, 'area_sqm' => 50]);
    Property::factory()->create(['building_id' => $building->id, 'area_sqm' => 75]);
    Property::factory()->create(['building_id' => $building->id, 'area_sqm' => 100]);

    $distribution = $this->calculator->distributeCirculationCost($building, 300.0, 'equal');

    expect($distribution)->toHaveCount(3);
    expect(array_values($distribution))->each->toBe(100.0);
});

test('distributeCirculationCost divides by area when method is area', function () {
    $building = Building::factory()->create();
    
    // Create properties with known areas
    $prop1 = Property::factory()->create(['building_id' => $building->id, 'area_sqm' => 50]);
    $prop2 = Property::factory()->create(['building_id' => $building->id, 'area_sqm' => 100]);
    $prop3 = Property::factory()->create(['building_id' => $building->id, 'area_sqm' => 150]);

    // Total area: 300 sqm, Total cost: 600
    $distribution = $this->calculator->distributeCirculationCost($building, 600.0, 'area');

    expect($distribution)->toHaveCount(3);
    expect($distribution[$prop1->id])->toBe(100.0); // 50/300 * 600 = 100
    expect($distribution[$prop2->id])->toBe(200.0); // 100/300 * 600 = 200
    expect($distribution[$prop3->id])->toBe(300.0); // 150/300 * 600 = 300
});

test('distributeCirculationCost returns empty array for building with no properties', function () {
    $building = Building::factory()->create();

    $distribution = $this->calculator->distributeCirculationCost($building, 300.0, 'equal');

    expect($distribution)->toBeEmpty();
});

test('calculateSummerGyvatukas calculates circulation energy using formula', function () {
    $building = Building::factory()->create();
    $property = Property::factory()->create(['building_id' => $building->id]);

    // Create heating meter with readings
    $heatingMeter = Meter::factory()->create([
        'property_id' => $property->id,
        'type' => MeterType::HEATING,
    ]);

    // Create hot water meter with readings
    $hotWaterMeter = Meter::factory()->create([
        'property_id' => $property->id,
        'type' => MeterType::WATER_HOT,
    ]);

    $billingMonth = Carbon::create(2024, 6, 1); // June (summer)
    $startDate = $billingMonth->copy()->startOfMonth();
    $endDate = $billingMonth->copy()->endOfMonth();

    // Heating readings: 1000 kWh consumed
    MeterReading::factory()->create([
        'meter_id' => $heatingMeter->id,
        'reading_date' => $startDate,
        'value' => 5000,
    ]);
    MeterReading::factory()->create([
        'meter_id' => $heatingMeter->id,
        'reading_date' => $endDate,
        'value' => 6000,
    ]);

    // Hot water readings: 10 m³ consumed
    MeterReading::factory()->create([
        'meter_id' => $hotWaterMeter->id,
        'reading_date' => $startDate,
        'value' => 100,
    ]);
    MeterReading::factory()->create([
        'meter_id' => $hotWaterMeter->id,
        'reading_date' => $endDate,
        'value' => 110,
    ]);

    $result = $this->calculator->calculateSummerGyvatukas($building, $billingMonth);

    // Q_circ = Q_total - (V_water × c × ΔT)
    // Q_circ = 1000 - (10 × 1.163 × 45)
    // Q_circ = 1000 - 523.35 = 476.65
    expect($result)->toBe(476.65);
});

test('calculate uses winter method during heating season', function () {
    $building = Building::factory()->create([
        'gyvatukas_summer_average' => 200.0,
    ]);

    $winterMonth = Carbon::create(2024, 12, 1); // December (heating season)
    $result = $this->calculator->calculate($building, $winterMonth);

    expect($result)->toBe(200.0);
});

test('calculate uses summer method during non-heating season', function () {
    $building = Building::factory()->create();
    $property = Property::factory()->create(['building_id' => $building->id]);

    // Create meters with minimal readings
    $heatingMeter = Meter::factory()->create([
        'property_id' => $property->id,
        'type' => MeterType::HEATING,
    ]);
    $hotWaterMeter = Meter::factory()->create([
        'property_id' => $property->id,
        'type' => MeterType::WATER_HOT,
    ]);

    $summerMonth = Carbon::create(2024, 7, 1); // July (non-heating season)
    $startDate = $summerMonth->copy()->startOfMonth();
    $endDate = $summerMonth->copy()->endOfMonth();

    MeterReading::factory()->create([
        'meter_id' => $heatingMeter->id,
        'reading_date' => $startDate,
        'value' => 0,
    ]);
    MeterReading::factory()->create([
        'meter_id' => $heatingMeter->id,
        'reading_date' => $endDate,
        'value' => 100,
    ]);

    MeterReading::factory()->create([
        'meter_id' => $hotWaterMeter->id,
        'reading_date' => $startDate,
        'value' => 0,
    ]);
    MeterReading::factory()->create([
        'meter_id' => $hotWaterMeter->id,
        'reading_date' => $endDate,
        'value' => 1,
    ]);

    $result = $this->calculator->calculate($building, $summerMonth);

    // Should use summer calculation (not winter average)
    expect($result)->toBeGreaterThanOrEqual(0.0);
});

test('Building calculateSummerAverage stores average and updates timestamp', function () {
    $building = Building::factory()->create();
    $property = Property::factory()->create(['building_id' => $building->id]);

    // Create meters
    $heatingMeter = Meter::factory()->create([
        'property_id' => $property->id,
        'type' => MeterType::HEATING,
    ]);
    $hotWaterMeter = Meter::factory()->create([
        'property_id' => $property->id,
        'type' => MeterType::WATER_HOT,
    ]);

    // Create readings for May through September
    for ($month = 5; $month <= 9; $month++) {
        $startDate = Carbon::create(2024, $month, 1);
        $endDate = Carbon::create(2024, $month, 1)->endOfMonth();

        MeterReading::factory()->create([
            'meter_id' => $heatingMeter->id,
            'reading_date' => $startDate,
            'value' => ($month - 5) * 100,
        ]);
        MeterReading::factory()->create([
            'meter_id' => $heatingMeter->id,
            'reading_date' => $endDate,
            'value' => ($month - 5) * 100 + 100,
        ]);

        MeterReading::factory()->create([
            'meter_id' => $hotWaterMeter->id,
            'reading_date' => $startDate,
            'value' => ($month - 5) * 10,
        ]);
        MeterReading::factory()->create([
            'meter_id' => $hotWaterMeter->id,
            'reading_date' => $endDate,
            'value' => ($month - 5) * 10 + 1,
        ]);
    }

    $startDate = Carbon::create(2024, 5, 1);
    $endDate = Carbon::create(2024, 9, 30);

    $average = $building->calculateSummerAverage($startDate, $endDate);

    expect($average)->toBeGreaterThan(0.0);
    expect((float) $building->gyvatukas_summer_average)->toBe($average);
    expect($building->gyvatukas_last_calculated)->not->toBeNull();
});
