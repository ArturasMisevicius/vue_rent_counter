<?php

use App\Models\Meter;
use App\Models\MeterReading;
use App\ValueObjects\ConsumptionData;

test('creates consumption data with valid readings', function () {
    $meter = Meter::factory()->create();
    $startReading = MeterReading::factory()->for($meter)->create(['value' => 100]);
    $endReading = MeterReading::factory()->for($meter)->create(['value' => 150]);
    
    $consumption = new ConsumptionData($startReading, $endReading);
    
    expect($consumption->amount())->toBe(50.0)
        ->and($consumption->hasConsumption())->toBeTrue();
});

test('throws exception when end reading is less than start reading', function () {
    $meter = Meter::factory()->create();
    $startReading = MeterReading::factory()->for($meter)->create(['value' => 150]);
    $endReading = MeterReading::factory()->for($meter)->create(['value' => 100]);
    
    new ConsumptionData($startReading, $endReading);
})->throws(InvalidArgumentException::class);

test('detects zero consumption', function () {
    $meter = Meter::factory()->create();
    $startReading = MeterReading::factory()->for($meter)->create(['value' => 100]);
    $endReading = MeterReading::factory()->for($meter)->create(['value' => 100]);
    
    $consumption = new ConsumptionData($startReading, $endReading);
    
    expect($consumption->hasConsumption())->toBeFalse()
        ->and($consumption->amount())->toBe(0.0);
});

test('includes zone in consumption data', function () {
    $meter = Meter::factory()->create(['supports_zones' => true]);
    $startReading = MeterReading::factory()->for($meter)->create(['value' => 100, 'zone' => 'day']);
    $endReading = MeterReading::factory()->for($meter)->create(['value' => 150, 'zone' => 'day']);
    
    $consumption = new ConsumptionData($startReading, $endReading, 'day');
    
    expect($consumption->zone)->toBe('day');
});

test('generates snapshot array with all required fields', function () {
    $meter = Meter::factory()->create();
    $startReading = MeterReading::factory()->for($meter)->create([
        'value' => 100,
        'reading_date' => '2024-01-01'
    ]);
    $endReading = MeterReading::factory()->for($meter)->create([
        'value' => 150,
        'reading_date' => '2024-01-31'
    ]);
    
    $consumption = new ConsumptionData($startReading, $endReading);
    $snapshot = $consumption->toSnapshot();
    
    expect($snapshot)->toHaveKeys([
        'start_reading_id',
        'start_value',
        'start_date',
        'end_reading_id',
        'end_value',
        'end_date',
        'zone',
        'consumption'
    ])
        ->and($snapshot['consumption'])->toBe(50.0)
        ->and($snapshot['start_value'])->toBe(100.0)
        ->and($snapshot['end_value'])->toBe(150.0);
});
