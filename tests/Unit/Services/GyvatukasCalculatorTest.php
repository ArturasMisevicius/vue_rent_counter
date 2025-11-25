<?php

use App\Models\Building;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Services\GyvatukasCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->calculator = app(GyvatukasCalculator::class);
    
    // Clear logs before each test
    Log::spy();
});

describe('GyvatukasCalculator', function () {
    describe('isHeatingSeason', function () {
        it('returns true for October (heating season start)', function () {
            $date = Carbon::create(2024, 10, 15);
            expect($this->calculator->isHeatingSeason($date))->toBeTrue();
        });

        it('returns true for November (mid heating season)', function () {
            $date = Carbon::create(2024, 11, 15);
            expect($this->calculator->isHeatingSeason($date))->toBeTrue();
        });

        it('returns true for December (mid heating season)', function () {
            $date = Carbon::create(2024, 12, 15);
            expect($this->calculator->isHeatingSeason($date))->toBeTrue();
        });

        it('returns true for January (mid heating season)', function () {
            $date = Carbon::create(2024, 1, 15);
            expect($this->calculator->isHeatingSeason($date))->toBeTrue();
        });

        it('returns true for April (heating season end)', function () {
            $date = Carbon::create(2024, 4, 15);
            expect($this->calculator->isHeatingSeason($date))->toBeTrue();
        });

        it('returns false for May (non-heating season)', function () {
            $date = Carbon::create(2024, 5, 15);
            expect($this->calculator->isHeatingSeason($date))->toBeFalse();
        });

        it('returns false for June (non-heating season)', function () {
            $date = Carbon::create(2024, 6, 15);
            expect($this->calculator->isHeatingSeason($date))->toBeFalse();
        });

        it('returns false for September (non-heating season)', function () {
            $date = Carbon::create(2024, 9, 15);
            expect($this->calculator->isHeatingSeason($date))->toBeFalse();
        });
        
        it('uses configuration for heating season start month', function () {
            // Default config: heating_season_start_month = 10 (October)
            expect(config('gyvatukas.heating_season_start_month'))->toBe(10);
            
            $octoberDate = Carbon::create(2024, 10, 1);
            expect($this->calculator->isHeatingSeason($octoberDate))->toBeTrue();
        });
        
        it('uses configuration for heating season end month', function () {
            // Default config: heating_season_end_month = 4 (April)
            expect(config('gyvatukas.heating_season_end_month'))->toBe(4);
            
            $aprilDate = Carbon::create(2024, 4, 30);
            expect($this->calculator->isHeatingSeason($aprilDate))->toBeTrue();
        });
        
        it('handles edge case at heating season boundaries', function () {
            // Last day of heating season
            $lastDayOfApril = Carbon::create(2024, 4, 30);
            expect($this->calculator->isHeatingSeason($lastDayOfApril))->toBeTrue();
            
            // First day of non-heating season
            $firstDayOfMay = Carbon::create(2024, 5, 1);
            expect($this->calculator->isHeatingSeason($firstDayOfMay))->toBeFalse();
            
            // Last day of non-heating season
            $lastDayOfSeptember = Carbon::create(2024, 9, 30);
            expect($this->calculator->isHeatingSeason($lastDayOfSeptember))->toBeFalse();
            
            // First day of heating season
            $firstDayOfOctober = Carbon::create(2024, 10, 1);
            expect($this->calculator->isHeatingSeason($firstDayOfOctober))->toBeTrue();
        });
    });

    describe('calculateWinterGyvatukas', function () {
        it('returns stored summer average during heating season', function () {
            $building = Building::factory()->create([
                'gyvatukas_summer_average' => '150.50',
            ]);

            $result = $this->calculator->calculateWinterGyvatukas($building);

            expect($result)->toBe(150.50);
            
            // Should not log warning for valid summer average
            Log::shouldNotHaveReceived('warning');
        });

        it('returns 0 when summer average is null', function () {
            $building = Building::factory()->create([
                'gyvatukas_summer_average' => null,
            ]);

            $result = $this->calculator->calculateWinterGyvatukas($building);

            expect($result)->toBe(0.0);
            
            // Should log warning for missing summer average
            Log::shouldHaveReceived('warning')
                ->once()
                ->with('Missing or invalid summer average for building during heating season', [
                    'building_id' => $building->id,
                    'summer_average' => null,
                ]);
        });

        it('returns 0 when summer average is zero', function () {
            $building = Building::factory()->create([
                'gyvatukas_summer_average' => '0.00',
            ]);

            $result = $this->calculator->calculateWinterGyvatukas($building);

            expect($result)->toBe(0.0);
            
            // Should log warning for zero summer average
            Log::shouldHaveReceived('warning')
                ->once()
                ->with('Missing or invalid summer average for building during heating season', [
                    'building_id' => $building->id,
                    'summer_average' => '0.00',
                ]);
        });
        
        it('returns 0 when summer average is negative', function () {
            $building = Building::factory()->create([
                'gyvatukas_summer_average' => '-10.50',
            ]);

            $result = $this->calculator->calculateWinterGyvatukas($building);

            expect($result)->toBe(0.0);
            
            // Should log warning for negative summer average
            Log::shouldHaveReceived('warning')
                ->once()
                ->with('Missing or invalid summer average for building during heating season', [
                    'building_id' => $building->id,
                    'summer_average' => '-10.50',
                ]);
        });
    });

    describe('distributeCirculationCost', function () {
        it('distributes cost equally when method is equal', function () {
            $building = Building::factory()->create();
            
            Property::factory()->count(3)->create([
                'building_id' => $building->id,
                'area_sqm' => 50.0,
            ]);

            $totalCost = 300.0;
            $distribution = $this->calculator->distributeCirculationCost($building, $totalCost, 'equal');

            expect($distribution)->toHaveCount(3);
            expect(array_sum($distribution))->toBe(300.0);
            
            foreach ($distribution as $cost) {
                expect($cost)->toBe(100.0);
            }
            
            // Should not log any warnings for valid equal distribution
            Log::shouldNotHaveReceived('warning');
            Log::shouldNotHaveReceived('error');
        });

        it('distributes cost by area when method is area', function () {
            $building = Building::factory()->create();
            
            $property1 = Property::factory()->create([
                'building_id' => $building->id,
                'area_sqm' => 50.0, // 50% of total
            ]);
            
            $property2 = Property::factory()->create([
                'building_id' => $building->id,
                'area_sqm' => 30.0, // 30% of total
            ]);
            
            $property3 = Property::factory()->create([
                'building_id' => $building->id,
                'area_sqm' => 20.0, // 20% of total
            ]);

            $totalCost = 1000.0;
            $distribution = $this->calculator->distributeCirculationCost($building, $totalCost, 'area');

            expect($distribution)->toHaveCount(3);
            expect($distribution[$property1->id])->toBe(500.0); // 50%
            expect($distribution[$property2->id])->toBe(300.0); // 30%
            expect($distribution[$property3->id])->toBe(200.0); // 20%
            
            // Should not log any warnings for valid area distribution
            Log::shouldNotHaveReceived('warning');
            Log::shouldNotHaveReceived('error');
        });

        it('returns empty array when building has no properties', function () {
            $building = Building::factory()->create();

            $distribution = $this->calculator->distributeCirculationCost($building, 100.0, 'equal');

            expect($distribution)->toBeEmpty();
            
            // Should log warning for building with no properties
            Log::shouldHaveReceived('warning')
                ->once()
                ->with('No properties found for building during circulation cost distribution', [
                    'building_id' => $building->id,
                ]);
        });

        it('falls back to equal distribution when total area is zero', function () {
            $building = Building::factory()->create();
            
            Property::factory()->count(2)->create([
                'building_id' => $building->id,
                'area_sqm' => 0.0,
            ]);

            $totalCost = 200.0;
            $distribution = $this->calculator->distributeCirculationCost($building, $totalCost, 'area');

            expect($distribution)->toHaveCount(2);
            
            foreach ($distribution as $cost) {
                expect($cost)->toBe(100.0);
            }
            
            // Should log warning for zero total area
            Log::shouldHaveReceived('warning')
                ->once()
                ->with('Total area is zero or negative for building', [
                    'building_id' => $building->id,
                    'total_area' => 0.0,
                ]);
        });
        
        it('falls back to equal distribution when total area is negative', function () {
            $building = Building::factory()->create();
            
            Property::factory()->count(2)->create([
                'building_id' => $building->id,
                'area_sqm' => -10.0,
            ]);

            $totalCost = 200.0;
            $distribution = $this->calculator->distributeCirculationCost($building, $totalCost, 'area');

            expect($distribution)->toHaveCount(2);
            
            foreach ($distribution as $cost) {
                expect($cost)->toBe(100.0);
            }
            
            // Should log warning for negative total area
            Log::shouldHaveReceived('warning')
                ->once()
                ->with('Total area is zero or negative for building', [
                    'building_id' => $building->id,
                    'total_area' => -20.0,
                ]);
        });
        
        it('falls back to equal distribution when method is invalid', function () {
            $building = Building::factory()->create();
            
            Property::factory()->count(3)->create([
                'building_id' => $building->id,
                'area_sqm' => 50.0,
            ]);

            $totalCost = 300.0;
            $distribution = $this->calculator->distributeCirculationCost($building, $totalCost, 'invalid_method');

            expect($distribution)->toHaveCount(3);
            
            foreach ($distribution as $cost) {
                expect($cost)->toBe(100.0);
            }
            
            // Should log error for invalid method
            Log::shouldHaveReceived('error')
                ->once()
                ->with('Invalid distribution method specified', [
                    'method' => 'invalid_method',
                    'building_id' => $building->id,
                ]);
        });
        
        it('rounds costs to 2 decimal places for equal distribution', function () {
            $building = Building::factory()->create();
            
            Property::factory()->count(3)->create([
                'building_id' => $building->id,
                'area_sqm' => 50.0,
            ]);

            $totalCost = 100.0; // Will result in 33.333... per property
            $distribution = $this->calculator->distributeCirculationCost($building, $totalCost, 'equal');

            expect($distribution)->toHaveCount(3);
            
            foreach ($distribution as $cost) {
                expect($cost)->toBe(33.33);
            }
        });
        
        it('rounds costs to 2 decimal places for area distribution', function () {
            $building = Building::factory()->create();
            
            $property1 = Property::factory()->create([
                'building_id' => $building->id,
                'area_sqm' => 33.33, // Will result in non-round percentages
            ]);
            
            $property2 = Property::factory()->create([
                'building_id' => $building->id,
                'area_sqm' => 33.33,
            ]);
            
            $property3 = Property::factory()->create([
                'building_id' => $building->id,
                'area_sqm' => 33.34,
            ]);

            $totalCost = 100.0;
            $distribution = $this->calculator->distributeCirculationCost($building, $totalCost, 'area');

            expect($distribution)->toHaveCount(3);
            
            // Each cost should be rounded to 2 decimal places
            foreach ($distribution as $cost) {
                expect($cost)->toBeFloat();
                expect(strlen(substr(strrchr((string)$cost, "."), 1)))->toBeLessThanOrEqual(2);
            }
        });
    });

    describe('calculateSummerGyvatukas', function () {
        it('calculates circulation energy using the formula Q_circ = Q_total - (V_water × c × ΔT)', function () {
            // Create a building with properties and meters
            $building = Building::factory()->create();
            $property = Property::factory()->create(['building_id' => $building->id]);

            // Create heating meter with readings
            $heatingMeter = Meter::factory()->create([
                'property_id' => $property->id,
                'type' => 'heating',
            ]);

            $month = Carbon::create(2024, 6, 1); // June (summer)
            $periodStart = $month->copy()->startOfMonth();
            $periodEnd = $month->copy()->endOfMonth();

            // Create meter readings: 1000 kWh at start, 2000 kWh at end = 1000 kWh consumption
            MeterReading::factory()->create([
                'meter_id' => $heatingMeter->id,
                'reading_date' => $periodStart,
                'value' => 1000.0,
            ]);

            MeterReading::factory()->create([
                'meter_id' => $heatingMeter->id,
                'reading_date' => $periodEnd,
                'value' => 2000.0,
            ]);

            // Create hot water meter with readings
            $hotWaterMeter = Meter::factory()->create([
                'property_id' => $property->id,
                'type' => 'water_hot',
            ]);

            // Create meter readings: 10 m³ at start, 20 m³ at end = 10 m³ consumption
            MeterReading::factory()->create([
                'meter_id' => $hotWaterMeter->id,
                'reading_date' => $periodStart,
                'value' => 10.0,
            ]);

            MeterReading::factory()->create([
                'meter_id' => $hotWaterMeter->id,
                'reading_date' => $periodEnd,
                'value' => 20.0,
            ]);

            $result = $this->calculator->calculateSummerGyvatukas($building, $month);

            // Expected calculation:
            // Q_total = 1000 kWh
            // V_water = 10 m³
            // c = 1.163 kWh/m³·°C (from config)
            // ΔT = 45°C (from config)
            // Water heating energy = 10 × 1.163 × 45 = 523.35 kWh
            // Q_circ = 1000 - 523.35 = 476.65 kWh
            expect($result)->toBe(476.65);
            
            // Should not log warning for valid calculation
            Log::shouldNotHaveReceived('warning');
        });

        it('returns 0 when circulation energy would be negative', function () {
            $building = Building::factory()->create();
            $property = Property::factory()->create(['building_id' => $building->id]);

            // Create heating meter with low consumption
            $heatingMeter = Meter::factory()->create([
                'property_id' => $property->id,
                'type' => 'heating',
            ]);

            $month = Carbon::create(2024, 6, 1);
            $periodStart = $month->copy()->startOfMonth();
            $periodEnd = $month->copy()->endOfMonth();

            MeterReading::factory()->create([
                'meter_id' => $heatingMeter->id,
                'reading_date' => $periodStart,
                'value' => 1000.0,
            ]);

            MeterReading::factory()->create([
                'meter_id' => $heatingMeter->id,
                'reading_date' => $periodEnd,
                'value' => 1100.0, // Only 100 kWh consumption
            ]);

            // Create hot water meter with high consumption
            $hotWaterMeter = Meter::factory()->create([
                'property_id' => $property->id,
                'type' => 'water_hot',
            ]);

            MeterReading::factory()->create([
                'meter_id' => $hotWaterMeter->id,
                'reading_date' => $periodStart,
                'value' => 10.0,
            ]);

            MeterReading::factory()->create([
                'meter_id' => $hotWaterMeter->id,
                'reading_date' => $periodEnd,
                'value' => 50.0, // 40 m³ consumption = 2093.4 kWh for heating
            ]);

            $result = $this->calculator->calculateSummerGyvatukas($building, $month);

            // This would result in negative circulation energy, so should return 0
            expect($result)->toBe(0.0);
            
            // Should log warning for negative circulation energy
            Log::shouldHaveReceived('warning')
                ->once()
                ->with('Negative circulation energy calculated for building', \Mockery::on(function ($context) use ($building, $month) {
                    return $context['building_id'] === $building->id
                        && $context['month'] === $month->format('Y-m')
                        && $context['total_heating'] === 100.0
                        && $context['water_heating'] === 2093.4
                        && $context['circulation'] < 0;
                }));
        });
        
        it('rounds result to 2 decimal places', function () {
            $building = Building::factory()->create();
            $property = Property::factory()->create(['building_id' => $building->id]);

            $heatingMeter = Meter::factory()->create([
                'property_id' => $property->id,
                'type' => 'heating',
            ]);

            $month = Carbon::create(2024, 6, 1);
            $periodStart = $month->copy()->startOfMonth();
            $periodEnd = $month->copy()->endOfMonth();

            // Create readings that will result in non-round number
            MeterReading::factory()->create([
                'meter_id' => $heatingMeter->id,
                'reading_date' => $periodStart,
                'value' => 1000.0,
            ]);

            MeterReading::factory()->create([
                'meter_id' => $heatingMeter->id,
                'reading_date' => $periodEnd,
                'value' => 1333.33, // Will result in 333.33 kWh
            ]);

            $hotWaterMeter = Meter::factory()->create([
                'property_id' => $property->id,
                'type' => 'water_hot',
            ]);

            MeterReading::factory()->create([
                'meter_id' => $hotWaterMeter->id,
                'reading_date' => $periodStart,
                'value' => 10.0,
            ]);

            MeterReading::factory()->create([
                'meter_id' => $hotWaterMeter->id,
                'reading_date' => $periodEnd,
                'value' => 11.11, // Will result in 1.11 m³
            ]);

            $result = $this->calculator->calculateSummerGyvatukas($building, $month);

            // Result should be rounded to 2 decimal places
            expect($result)->toBeFloat();
            expect(strlen(substr(strrchr((string)$result, "."), 1)))->toBeLessThanOrEqual(2);
        });
        
        it('handles building with no heating meters', function () {
            $building = Building::factory()->create();
            $property = Property::factory()->create(['building_id' => $building->id]);

            // Only create hot water meter, no heating meter
            $hotWaterMeter = Meter::factory()->create([
                'property_id' => $property->id,
                'type' => 'water_hot',
            ]);

            $month = Carbon::create(2024, 6, 1);
            $periodStart = $month->copy()->startOfMonth();
            $periodEnd = $month->copy()->endOfMonth();

            MeterReading::factory()->create([
                'meter_id' => $hotWaterMeter->id,
                'reading_date' => $periodStart,
                'value' => 10.0,
            ]);

            MeterReading::factory()->create([
                'meter_id' => $hotWaterMeter->id,
                'reading_date' => $periodEnd,
                'value' => 20.0,
            ]);

            $result = $this->calculator->calculateSummerGyvatukas($building, $month);

            // With no heating energy, result will be negative, so should return 0
            expect($result)->toBe(0.0);
        });
        
        it('handles building with no hot water meters', function () {
            $building = Building::factory()->create();
            $property = Property::factory()->create(['building_id' => $building->id]);

            // Only create heating meter, no hot water meter
            $heatingMeter = Meter::factory()->create([
                'property_id' => $property->id,
                'type' => 'heating',
            ]);

            $month = Carbon::create(2024, 6, 1);
            $periodStart = $month->copy()->startOfMonth();
            $periodEnd = $month->copy()->endOfMonth();

            MeterReading::factory()->create([
                'meter_id' => $heatingMeter->id,
                'reading_date' => $periodStart,
                'value' => 1000.0,
            ]);

            MeterReading::factory()->create([
                'meter_id' => $heatingMeter->id,
                'reading_date' => $periodEnd,
                'value' => 2000.0,
            ]);

            $result = $this->calculator->calculateSummerGyvatukas($building, $month);

            // With no water heating energy, all heating energy is circulation
            expect($result)->toBe(1000.0);
        });
        
        it('handles building with no meter readings', function () {
            $building = Building::factory()->create();
            $property = Property::factory()->create(['building_id' => $building->id]);

            // Create meters but no readings
            Meter::factory()->create([
                'property_id' => $property->id,
                'type' => 'heating',
            ]);

            Meter::factory()->create([
                'property_id' => $property->id,
                'type' => 'water_hot',
            ]);

            $month = Carbon::create(2024, 6, 1);

            $result = $this->calculator->calculateSummerGyvatukas($building, $month);

            // With no readings, consumption is 0, so result is 0
            expect($result)->toBe(0.0);
        });
        
        it('handles multiple properties with mixed meter types', function () {
            $building = Building::factory()->create();
            
            // Property 1: Both heating and hot water
            $property1 = Property::factory()->create(['building_id' => $building->id]);
            $heatingMeter1 = Meter::factory()->create([
                'property_id' => $property1->id,
                'type' => 'heating',
            ]);
            $waterMeter1 = Meter::factory()->create([
                'property_id' => $property1->id,
                'type' => 'water_hot',
            ]);
            
            // Property 2: Only heating
            $property2 = Property::factory()->create(['building_id' => $building->id]);
            $heatingMeter2 = Meter::factory()->create([
                'property_id' => $property2->id,
                'type' => 'heating',
            ]);

            $month = Carbon::create(2024, 6, 1);
            $periodStart = $month->copy()->startOfMonth();
            $periodEnd = $month->copy()->endOfMonth();

            // Property 1 readings
            MeterReading::factory()->create([
                'meter_id' => $heatingMeter1->id,
                'reading_date' => $periodStart,
                'value' => 1000.0,
            ]);
            MeterReading::factory()->create([
                'meter_id' => $heatingMeter1->id,
                'reading_date' => $periodEnd,
                'value' => 1500.0,
            ]);
            MeterReading::factory()->create([
                'meter_id' => $waterMeter1->id,
                'reading_date' => $periodStart,
                'value' => 10.0,
            ]);
            MeterReading::factory()->create([
                'meter_id' => $waterMeter1->id,
                'reading_date' => $periodEnd,
                'value' => 15.0,
            ]);
            
            // Property 2 readings
            MeterReading::factory()->create([
                'meter_id' => $heatingMeter2->id,
                'reading_date' => $periodStart,
                'value' => 2000.0,
            ]);
            MeterReading::factory()->create([
                'meter_id' => $heatingMeter2->id,
                'reading_date' => $periodEnd,
                'value' => 2500.0,
            ]);

            $result = $this->calculator->calculateSummerGyvatukas($building, $month);

            // Total heating: 500 + 500 = 1000 kWh
            // Total water: 5 m³
            // Water heating: 5 × 1.163 × 45 = 261.675 kWh
            // Circulation: 1000 - 261.675 = 738.325 → 738.33 kWh (rounded)
            expect($result)->toBe(738.33);
        });
    });

    describe('calculate', function () {
        it('routes to winter calculation during heating season', function () {
            $building = Building::factory()->create([
                'gyvatukas_summer_average' => '200.00',
            ]);

            $winterMonth = Carbon::create(2024, 1, 15); // January
            $result = $this->calculator->calculate($building, $winterMonth);

            expect($result)->toBe(200.0);
        });

        it('routes to summer calculation during non-heating season', function () {
            $building = Building::factory()->create();
            $property = Property::factory()->create(['building_id' => $building->id]);

            $heatingMeter = Meter::factory()->create([
                'property_id' => $property->id,
                'type' => 'heating',
            ]);

            $summerMonth = Carbon::create(2024, 6, 1); // June
            $periodStart = $summerMonth->copy()->startOfMonth();
            $periodEnd = $summerMonth->copy()->endOfMonth();

            MeterReading::factory()->create([
                'meter_id' => $heatingMeter->id,
                'reading_date' => $periodStart,
                'value' => 1000.0,
            ]);

            MeterReading::factory()->create([
                'meter_id' => $heatingMeter->id,
                'reading_date' => $periodEnd,
                'value' => 2000.0,
            ]);

            $hotWaterMeter = Meter::factory()->create([
                'property_id' => $property->id,
                'type' => 'water_hot',
            ]);

            MeterReading::factory()->create([
                'meter_id' => $hotWaterMeter->id,
                'reading_date' => $periodStart,
                'value' => 10.0,
            ]);

            MeterReading::factory()->create([
                'meter_id' => $hotWaterMeter->id,
                'reading_date' => $periodEnd,
                'value' => 20.0,
            ]);

            $result = $this->calculator->calculate($building, $summerMonth);

            // Should calculate summer gyvatukas
            expect($result)->toBeGreaterThan(0.0);
        });
    });
});
