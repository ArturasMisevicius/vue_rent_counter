<?php

use App\Enums\MeterType;
use App\Models\Building;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Services\GyvatukasCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

test('gyvatukas calculation uses eager loading to avoid N+1 queries', function () {
    $building = Building::factory()->create();
    
    // Create 5 properties with heating and hot water meters
    $properties = Property::factory()
        ->count(5)
        ->for($building)
        ->create();
    
    foreach ($properties as $property) {
        $heatingMeter = Meter::factory()
            ->for($property)
            ->create(['type' => MeterType::HEATING]);
        
        $hotWaterMeter = Meter::factory()
            ->for($property)
            ->create(['type' => MeterType::WATER_HOT]);
        
        // Create readings for May 2024
        MeterReading::factory()->for($heatingMeter)->create([
            'reading_date' => '2024-05-01',
            'value' => 1000
        ]);
        MeterReading::factory()->for($heatingMeter)->create([
            'reading_date' => '2024-05-31',
            'value' => 1500
        ]);
        
        MeterReading::factory()->for($hotWaterMeter)->create([
            'reading_date' => '2024-05-01',
            'value' => 10
        ]);
        MeterReading::factory()->for($hotWaterMeter)->create([
            'reading_date' => '2024-05-31',
            'value' => 15
        ]);
    }
    
    $calculator = new GyvatukasCalculator();
    
    // Enable query logging
    DB::enableQueryLog();
    
    $result = $calculator->calculateSummerGyvatukas($building, Carbon::parse('2024-05-15'));
    
    $queries = DB::getQueryLog();
    $queryCount = count($queries);
    
    // Should use eager loading: 
    // 1 query for properties with meters and readings
    // Should NOT be: 1 + (5 properties × 2 meter types × 2 queries) = 21 queries
    expect($queryCount)->toBeLessThan(10)
        ->and($result)->toBeGreaterThan(0);
    
    DB::disableQueryLog();
});

test('gyvatukas calculator uses configuration values', function () {
    // Set custom config values for this test
    config([
        'gyvatukas.water_specific_heat' => 1.163,
        'gyvatukas.temperature_delta' => 45.0,
    ]);
    
    $calculator = new GyvatukasCalculator();
    
    $building = Building::factory()->create();
    $property = Property::factory()->for($building)->create();
    
    $heatingMeter = Meter::factory()->for($property)->create(['type' => MeterType::HEATING]);
    $hotWaterMeter = Meter::factory()->for($property)->create(['type' => MeterType::WATER_HOT]);
    
    MeterReading::factory()->for($heatingMeter)->create([
        'reading_date' => '2024-05-01',
        'value' => 1000
    ]);
    MeterReading::factory()->for($heatingMeter)->create([
        'reading_date' => '2024-05-31',
        'value' => 2000
    ]);
    
    MeterReading::factory()->for($hotWaterMeter)->create([
        'reading_date' => '2024-05-01',
        'value' => 10
    ]);
    MeterReading::factory()->for($hotWaterMeter)->create([
        'reading_date' => '2024-05-31',
        'value' => 20
    ]);
    
    $result = $calculator->calculateSummerGyvatukas($building, Carbon::parse('2024-05-15'));
    
    // With default values: 1000 kWh - (10 m³ × 1.163 × 45) = 1000 - 523.35 = 476.65
    expect($result)->toBe(476.65);
});
