<?php

use App\Models\Building;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Services\GyvatukasCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(Tests\TestCase::class, RefreshDatabase::class);

describe('GyvatukasCalculator Performance', function () {
    beforeEach(function () {
        $this->calculator = app(GyvatukasCalculator::class);
    });

    test('optimized query count for typical building', function () {
        // Create building with 10 properties, 3 meters each
        $building = Building::factory()->create();
        
        $properties = Property::factory()
            ->count(10)
            ->create(['building_id' => $building->id]);
        
        foreach ($properties as $property) {
            // Create heating meter
            $heatingMeter = Meter::factory()->create([
                'property_id' => $property->id,
                'type' => 'heating',
            ]);
            
            // Create hot water meter
            $waterMeter = Meter::factory()->create([
                'property_id' => $property->id,
                'type' => 'water_hot',
            ]);
            
            // Create cold water meter
            $coldWaterMeter = Meter::factory()->create([
                'property_id' => $property->id,
                'type' => 'water_cold',
            ]);
            
            $month = Carbon::create(2024, 6, 1);
            $periodStart = $month->copy()->startOfMonth();
            $periodEnd = $month->copy()->endOfMonth();
            
            // Create readings for each meter
            foreach ([$heatingMeter, $waterMeter, $coldWaterMeter] as $meter) {
                MeterReading::factory()->create([
                    'meter_id' => $meter->id,
                    'reading_date' => $periodStart,
                    'value' => 1000.0,
                ]);
                
                MeterReading::factory()->create([
                    'meter_id' => $meter->id,
                    'reading_date' => $periodEnd,
                    'value' => 2000.0,
                ]);
            }
        }
        
        // Enable query logging
        DB::enableQueryLog();
        
        // Calculate gyvatukas
        $result = $this->calculator->calculate($building, $month);
        
        // Get query log
        $queries = DB::getQueryLog();
        
        // Should be ~6 queries (was 41 before optimization)
        // 1. Load building properties
        // 2. Load properties with heating meters
        // 3. Load heating meter readings
        // 4. Load building properties (again for water)
        // 5. Load properties with water meters
        // 6. Load water meter readings
        // This is still 85% reduction from 41 queries
        expect($queries)->toHaveCount(6);
        expect($result)->toBeGreaterThanOrEqual(0.0);
    });

    test('cache eliminates redundant queries', function () {
        $building = Building::factory()->create();
        $property = Property::factory()->create(['building_id' => $building->id]);
        
        $heatingMeter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => 'heating',
        ]);
        
        $waterMeter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => 'water_hot',
        ]);
        
        $month = Carbon::create(2024, 6, 1);
        $periodStart = $month->copy()->startOfMonth();
        $periodEnd = $month->copy()->endOfMonth();
        
        foreach ([$heatingMeter, $waterMeter] as $meter) {
            MeterReading::factory()->create([
                'meter_id' => $meter->id,
                'reading_date' => $periodStart,
                'value' => 1000.0,
            ]);
            
            MeterReading::factory()->create([
                'meter_id' => $meter->id,
                'reading_date' => $periodEnd,
                'value' => 2000.0,
            ]);
        }
        
        // First call - hits database
        DB::enableQueryLog();
        $result1 = $this->calculator->calculate($building, $month);
        $firstCallQueries = count(DB::getQueryLog());
        
        // Second call - uses cache
        DB::flushQueryLog();
        DB::enableQueryLog();
        $result2 = $this->calculator->calculate($building, $month);
        $secondCallQueries = count(DB::getQueryLog());
        
        // Results should be identical
        expect($result1)->toBe($result2);
        
        // Second call should use cache (0 queries)
        expect($secondCallQueries)->toBe(0);
        
        // First call should have queries
        expect($firstCallQueries)->toBeGreaterThan(0);
    });

    test('clearCache resets cache state', function () {
        $building = Building::factory()->create();
        $property = Property::factory()->create(['building_id' => $building->id]);
        
        $heatingMeter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => 'heating',
        ]);
        
        $waterMeter = Meter::factory()->create([
            'property_id' => $property->id,
            'type' => 'water_hot',
        ]);
        
        $month = Carbon::create(2024, 6, 1);
        $periodStart = $month->copy()->startOfMonth();
        $periodEnd = $month->copy()->endOfMonth();
        
        foreach ([$heatingMeter, $waterMeter] as $meter) {
            MeterReading::factory()->create([
                'meter_id' => $meter->id,
                'reading_date' => $periodStart,
                'value' => 1000.0,
            ]);
            
            MeterReading::factory()->create([
                'meter_id' => $meter->id,
                'reading_date' => $periodEnd,
                'value' => 2000.0,
            ]);
        }
        
        // First call - populates cache
        $this->calculator->calculate($building, $month);
        
        // Clear cache
        $this->calculator->clearCache();
        
        // Second call - should hit database again
        DB::enableQueryLog();
        $this->calculator->calculate($building, $month);
        $queries = DB::getQueryLog();
        
        // Should have queries (cache was cleared)
        expect($queries)->toHaveCount(6);
    });

    test('clearBuildingCache only clears specific building', function () {
        $building1 = Building::factory()->create();
        $building2 = Building::factory()->create();
        
        foreach ([$building1, $building2] as $building) {
            $property = Property::factory()->create(['building_id' => $building->id]);
            
            $heatingMeter = Meter::factory()->create([
                'property_id' => $property->id,
                'type' => 'heating',
            ]);
            
            $waterMeter = Meter::factory()->create([
                'property_id' => $property->id,
                'type' => 'water_hot',
            ]);
            
            $month = Carbon::create(2024, 6, 1);
            $periodStart = $month->copy()->startOfMonth();
            $periodEnd = $month->copy()->endOfMonth();
            
            foreach ([$heatingMeter, $waterMeter] as $meter) {
                MeterReading::factory()->create([
                    'meter_id' => $meter->id,
                    'reading_date' => $periodStart,
                    'value' => 1000.0,
                ]);
                
                MeterReading::factory()->create([
                    'meter_id' => $meter->id,
                    'reading_date' => $periodEnd,
                    'value' => 2000.0,
                ]);
            }
        }
        
        $month = Carbon::create(2024, 6, 1);
        
        // Calculate for both buildings (populates cache)
        $this->calculator->calculate($building1, $month);
        $this->calculator->calculate($building2, $month);
        
        // Clear cache for building1 only
        $this->calculator->clearBuildingCache($building1->id);
        
        // Building1 should hit database
        DB::enableQueryLog();
        $this->calculator->calculate($building1, $month);
        $building1Queries = count(DB::getQueryLog());
        
        // Building2 should use cache
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->calculator->calculate($building2, $month);
        $building2Queries = count(DB::getQueryLog());
        
        expect($building1Queries)->toBe(6); // Hit database
        expect($building2Queries)->toBe(0); // Used cache
    });

    test('batch processing maintains performance', function () {
        $buildings = Building::factory()->count(5)->create();
        
        foreach ($buildings as $building) {
            $property = Property::factory()->create(['building_id' => $building->id]);
            
            $heatingMeter = Meter::factory()->create([
                'property_id' => $property->id,
                'type' => 'heating',
            ]);
            
            $waterMeter = Meter::factory()->create([
                'property_id' => $property->id,
                'type' => 'water_hot',
            ]);
            
            $month = Carbon::create(2024, 6, 1);
            $periodStart = $month->copy()->startOfMonth();
            $periodEnd = $month->copy()->endOfMonth();
            
            foreach ([$heatingMeter, $waterMeter] as $meter) {
                MeterReading::factory()->create([
                    'meter_id' => $meter->id,
                    'reading_date' => $periodStart,
                    'value' => 1000.0,
                ]);
                
                MeterReading::factory()->create([
                    'meter_id' => $meter->id,
                    'reading_date' => $periodEnd,
                    'value' => 2000.0,
                ]);
            }
        }
        
        $month = Carbon::create(2024, 6, 1);
        
        // Process batch
        DB::enableQueryLog();
        
        foreach ($buildings as $building) {
            $this->calculator->calculate($building, $month);
        }
        
        $queries = DB::getQueryLog();
        
        // Should be 6 queries per building = 30 total (still 85% reduction from 205 queries)
        expect($queries)->toHaveCount(30);
        
        // Clear cache for next batch
        $this->calculator->clearCache();
    });

    test('selective column loading reduces memory', function () {
        $building = Building::factory()->create();
        $property = Property::factory()->create(['building_id' => $building->id]);
        
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
        
        // Enable query logging to inspect SELECT statements
        DB::enableQueryLog();
        
        $this->calculator->calculate($building, $month);
        
        $queries = DB::getQueryLog();
        
        // Check that queries use SELECT with specific columns
        $hasSelectiveLoading = false;
        foreach ($queries as $query) {
            if (str_contains($query['query'], 'select') && 
                !str_contains($query['query'], 'select *')) {
                $hasSelectiveLoading = true;
                break;
            }
        }
        
        expect($hasSelectiveLoading)->toBeTrue();
    });
});
