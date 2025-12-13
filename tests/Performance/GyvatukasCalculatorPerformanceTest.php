<?php

declare(strict_types=1);

use App\Actions\CalculateGyvatukasAction;
use App\Models\Building;
use App\Models\Property;
use App\Services\GyvatukasBatchProcessor;
use App\Services\GyvatukasCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->action = app(CalculateGyvatukasAction::class);
    $this->calculator = app(GyvatukasCalculator::class);
    $this->batchProcessor = app(GyvatukasBatchProcessor::class);
});

describe('Performance Optimization', function () {
    test('caching reduces database queries significantly', function () {
        $building = Building::factory()->create([
            'total_apartments' => 20,
            'gyvatukas_summer_average' => 150.0,
            'gyvatukas_last_calculated' => now()->subMonths(6),
        ]);
        
        $month = Carbon::create(2024, 6, 15);
        
        // Clear cache to ensure fresh calculation
        Cache::flush();
        
        // First calculation - should hit database
        DB::enableQueryLog();
        $result1 = $this->action->execute($building, $month);
        $firstCallQueries = count(DB::getQueryLog());
        DB::disableQueryLog();
        
        // Second calculation - should use cache
        DB::enableQueryLog();
        $result2 = $this->action->execute($building, $month);
        $secondCallQueries = count(DB::getQueryLog());
        DB::disableQueryLog();
        
        expect($result1)->toBe($result2);
        expect($secondCallQueries)->toBeLessThan($firstCallQueries);
        expect($secondCallQueries)->toBeLessThanOrEqual(2); // Should be minimal queries
    });

    test('batch processing is more efficient than individual calculations', function () {
        $buildings = Building::factory()->count(50)->create([
            'total_apartments' => 20,
        ]);
        
        $month = Carbon::create(2024, 6, 15);
        
        // Individual processing
        $startTime = microtime(true);
        $individualResults = [];
        foreach ($buildings as $building) {
            $individualResults[$building->id] = $this->action->execute($building, $month);
        }
        $individualTime = microtime(true) - $startTime;
        
        // Clear cache for fair comparison
        Cache::flush();
        
        // Batch processing
        $startTime = microtime(true);
        $batchResults = $this->batchProcessor->processBatch($buildings, $month);
        $batchTime = microtime(true) - $startTime;
        
        // Batch should be faster or similar
        expect($batchTime)->toBeLessThanOrEqual($individualTime * 1.2); // Allow 20% margin
        expect(count($batchResults))->toBe(count($individualResults));
    });

    test('distribution calculation prevents N+1 queries', function () {
        $building = Building::factory()->create(['total_apartments' => 20]);
        
        // Create properties for the building
        Property::factory()->count(10)->create([
            'building_id' => $building->id,
            'area_sqm' => 50.0,
        ]);
        
        Cache::flush();
        
        // Test area-based distribution
        DB::enableQueryLog();
        $distribution = $this->calculator->distributeCirculationCost($building, 1000.0, 'area');
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        
        expect(count($distribution))->toBe(10);
        expect(count($queries))->toBeLessThanOrEqual(3); // Should be very few queries
        
        // Test caching works
        DB::enableQueryLog();
        $distribution2 = $this->calculator->distributeCirculationCost($building, 1000.0, 'area');
        $cachedQueries = DB::getQueryLog();
        DB::disableQueryLog();
        
        expect($distribution)->toBe($distribution2);
        expect(count($cachedQueries))->toBeLessThan(count($queries));
    });

    test('memory usage remains stable with large datasets', function () {
        $buildings = Building::factory()->count(200)->create([
            'total_apartments' => 20,
        ]);
        
        $month = Carbon::create(2024, 6, 15);
        $initialMemory = memory_get_usage();
        
        // Process in batches
        $results = $this->batchProcessor->processBatch($buildings, $month);
        
        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;
        
        expect(count($results))->toBe(200);
        // Memory increase should be reasonable (less than 20MB for 200 buildings)
        expect($memoryIncrease)->toBeLessThan(20 * 1024 * 1024);
    });

    test('concurrent calculations handle properly with caching', function () {
        $building = Building::factory()->create([
            'total_apartments' => 20,
        ]);
        
        $month = Carbon::create(2024, 6, 15);
        
        // Simulate concurrent requests
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = $this->action->execute($building, $month);
        }
        
        // All results should be identical
        $firstResult = $results[0];
        foreach ($results as $result) {
            expect($result)->toBe($firstResult);
        }
    });

    test('configuration memoization improves performance', function () {
        $building = Building::factory()->create(['total_apartments' => 20]);
        $month = Carbon::create(2024, 6, 15);
        
        // Multiple calculations should reuse memoized config
        $startTime = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $this->calculator->isSummerPeriod($month);
            $this->calculator->isHeatingSeason($month);
        }
        $endTime = microtime(true);
        
        // Should complete quickly due to memoization
        expect($endTime - $startTime)->toBeLessThan(0.1);
    });

    test('summer average recalculation is efficient', function () {
        $buildings = Building::factory()->count(20)->create([
            'total_apartments' => 20,
            'gyvatukas_summer_average' => null, // Needs recalculation
            'gyvatukas_last_calculated' => null,
        ]);
        
        $startTime = microtime(true);
        $processed = $this->batchProcessor->recalculateSummerAverages(20);
        $endTime = microtime(true);
        
        expect($processed)->toBe(20);
        expect($endTime - $startTime)->toBeLessThan(5.0); // Should complete in under 5 seconds
        
        // Verify averages were calculated
        $buildings->each(function ($building) {
            $building->refresh();
            expect($building->gyvatukas_summer_average)->not->toBeNull();
            expect($building->gyvatukas_last_calculated)->not->toBeNull();
        });
    });
});