<?php

use App\Enums\MeterType;
use App\Models\Building;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('command calculates summer average for all buildings', function () {
    // Create a building with properties and meters
    $building = Building::factory()->create([
        'address' => 'Test Building, Vilnius',
        'total_apartments' => 2,
    ]);

    $property1 = Property::factory()->create([
        'building_id' => $building->id,
        'area_sqm' => 50,
    ]);

    $property2 = Property::factory()->create([
        'building_id' => $building->id,
        'area_sqm' => 60,
    ]);

    // Create heating meters for both properties
    $heatingMeter1 = Meter::factory()->create([
        'property_id' => $property1->id,
        'type' => MeterType::HEATING,
    ]);

    $heatingMeter2 = Meter::factory()->create([
        'property_id' => $property2->id,
        'type' => MeterType::HEATING,
    ]);

    // Create hot water meters for both properties
    $hotWaterMeter1 = Meter::factory()->create([
        'property_id' => $property1->id,
        'type' => MeterType::WATER_HOT,
    ]);

    $hotWaterMeter2 = Meter::factory()->create([
        'property_id' => $property2->id,
        'type' => MeterType::WATER_HOT,
    ]);

    // Create meter readings for summer months (May-September)
    $year = now()->year;
    
    // May readings
    MeterReading::factory()->create([
        'meter_id' => $heatingMeter1->id,
        'reading_date' => Carbon::create($year, 5, 1),
        'value' => 1000,
    ]);
    MeterReading::factory()->create([
        'meter_id' => $heatingMeter1->id,
        'reading_date' => Carbon::create($year, 5, 31),
        'value' => 1100,
    ]);

    MeterReading::factory()->create([
        'meter_id' => $heatingMeter2->id,
        'reading_date' => Carbon::create($year, 5, 1),
        'value' => 2000,
    ]);
    MeterReading::factory()->create([
        'meter_id' => $heatingMeter2->id,
        'reading_date' => Carbon::create($year, 5, 31),
        'value' => 2150,
    ]);

    MeterReading::factory()->create([
        'meter_id' => $hotWaterMeter1->id,
        'reading_date' => Carbon::create($year, 5, 1),
        'value' => 10,
    ]);
    MeterReading::factory()->create([
        'meter_id' => $hotWaterMeter1->id,
        'reading_date' => Carbon::create($year, 5, 31),
        'value' => 12,
    ]);

    MeterReading::factory()->create([
        'meter_id' => $hotWaterMeter2->id,
        'reading_date' => Carbon::create($year, 5, 1),
        'value' => 20,
    ]);
    MeterReading::factory()->create([
        'meter_id' => $hotWaterMeter2->id,
        'reading_date' => Carbon::create($year, 5, 31),
        'value' => 23,
    ]);

    // Run the command
    $this->artisan('gyvatukas:calculate-summer-average', ['--year' => $year])
        ->expectsOutput("Calculating for summer period: {$year}-05-01 to {$year}-09-30")
        ->expectsOutput('Found 1 building(s) to process.')
        ->assertExitCode(0);

    // Verify the building was updated
    $building->refresh();
    
    expect($building->gyvatukas_summer_average)->not->toBeNull();
    expect($building->gyvatukas_last_calculated)->not->toBeNull();
    expect($building->gyvatukas_last_calculated->isToday())->toBeTrue();
});

test('command handles buildings with no meter data gracefully', function () {
    // Create a building without any meters
    $building = Building::factory()->create([
        'address' => 'Empty Building, Vilnius',
        'total_apartments' => 1,
    ]);

    $year = now()->year;

    // Run the command
    $this->artisan('gyvatukas:calculate-summer-average', ['--year' => $year])
        ->assertExitCode(0);

    // Verify the building was updated with zero average
    $building->refresh();
    
    expect($building->gyvatukas_summer_average)->toBe('0.00');
    expect($building->gyvatukas_last_calculated)->not->toBeNull();
});

test('command uses previous year when run in early months', function () {
    // Mock the current date to be in January
    Carbon::setTestNow(Carbon::create(2024, 1, 15));

    $building = Building::factory()->create();

    // Run the command without year option
    $this->artisan('gyvatukas:calculate-summer-average')
        ->expectsOutput('Calculating for summer period: 2023-05-01 to 2023-09-30')
        ->assertExitCode(0);

    Carbon::setTestNow(); // Reset
});

test('command uses current year when run in later months', function () {
    // Mock the current date to be in October
    Carbon::setTestNow(Carbon::create(2024, 10, 1));

    $building = Building::factory()->create();

    // Run the command without year option
    $this->artisan('gyvatukas:calculate-summer-average')
        ->expectsOutput('Calculating for summer period: 2024-05-01 to 2024-09-30')
        ->assertExitCode(0);

    Carbon::setTestNow(); // Reset
});

test('command displays summary table', function () {
    // Create multiple buildings
    Building::factory()->count(3)->create();

    $year = now()->year;

    // Run the command
    $this->artisan('gyvatukas:calculate-summer-average', ['--year' => $year])
        ->expectsTable(
            ['Status', 'Count'],
            [
                ['Successful', 3],
                ['Failed', 0],
                ['Total', 3],
            ]
        )
        ->assertExitCode(0);
});

test('command handles no buildings gracefully', function () {
    $year = now()->year;

    // Run the command with no buildings in database
    $this->artisan('gyvatukas:calculate-summer-average', ['--year' => $year])
        ->expectsOutput('No buildings found in the system.')
        ->assertExitCode(0);
});
