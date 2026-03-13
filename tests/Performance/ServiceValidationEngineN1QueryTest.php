<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Models\MeterReading;
use App\Models\Meter;
use App\Models\ServiceConfiguration;
use App\Models\UtilityService;
use App\Models\Tariff;
use App\Models\Provider;
use App\Models\User;
use App\Services\ServiceValidationEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Automated N+1 Query Detection Tests for ServiceValidationEngine
 * 
 * These tests automatically detect N+1 query problems and ensure
 * performance optimizations are working correctly.
 */
class ServiceValidationEngineN1QueryTest extends TestCase
{
    use RefreshDatabase;

    private ServiceValidationEngine $validationEngine;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->validationEngine = app(ServiceValidationEngine::class);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        
        // Enable query logging for N+1 detection
        DB::enableQueryLog();
    }

    protected function tearDown(): void
    {
        DB::disableQueryLog();
        parent::tearDown();
    }

    /**
     * Test N+1 query detection for single reading validation
     * 
     * @test
     */
    public function it_validates_single_reading_without_n1_queries(): void
    {
        // Arrange: Create test data
        $serviceConfig = $this->createServiceConfigurationWithRelationships();
        $meter = Meter::factory()->create(['service_configuration_id' => $serviceConfig->id]);
        $reading = MeterReading::factory()->create(['meter_id' => $meter->id]);

        // Act: Clear query log and validate
        DB::flushQueryLog();
        $result = $this->validationEngine->validateMeterReading($reading);

        // Assert: Check query count
        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        // Should use maximum 5 queries for single reading validation
        $this->assertLessThanOrEqual(5, $queryCount, 
            "Single reading validation should use ≤5 queries, used {$queryCount}. Queries: " . 
            $this->formatQueries($queries)
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('is_valid', $result);
    }

    /**
     * Test N+1 query detection for batch validation
     * 
     * @test
     */
    public function it_validates_batch_readings_without_n1_queries(): void
    {
        // Arrange: Create test data with multiple readings
        $readingCount = 50;
        $readings = $this->createTestReadingsWithRelationships($readingCount);

        // Act: Clear query log and validate batch
        DB::flushQueryLog();
        $result = $this->validationEngine->batchValidateReadings($readings);

        // Assert: Check query count
        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        // Should use maximum 15 queries regardless of reading count
        $maxAllowedQueries = 15;
        $this->assertLessThanOrEqual($maxAllowedQueries, $queryCount,
            "Batch validation of {$readingCount} readings should use ≤{$maxAllowedQueries} queries, used {$queryCount}. " .
            "This indicates N+1 query problem. Queries: " . $this->formatQueries($queries)
        );

        // Verify results structure
        $this->assertIsArray($result);
        $this->assertEquals($readingCount, $result['total_readings']);
        $this->assertArrayHasKey('performance_metrics', $result);
    }

    /**
     * Test N+1 query scaling - query count should not increase linearly
     * 
     * @test
     */
    public function it_maintains_constant_query_count_regardless_of_batch_size(): void
    {
        $testSizes = [10, 25, 50];
        $queryCountsBySize = [];

        foreach ($testSizes as $size) {
            // Create readings for this test size
            $readings = $this->createTestReadingsWithRelationships($size);

            // Measure query count
            DB::flushQueryLog();
            $this->validationEngine->batchValidateReadings($readings);
            $queryCountsBySize[$size] = count(DB::getQueryLog());
        }

        // Assert: Query count should not scale linearly with batch size
        $smallBatchQueries = $queryCountsBySize[10];
        $largeBatchQueries = $queryCountsBySize[50];
        
        // Large batch should not use more than 2x queries of small batch
        $maxAllowedIncrease = $smallBatchQueries * 2;
        
        $this->assertLessThanOrEqual($maxAllowedIncrease, $largeBatchQueries,
            "Query count should not scale linearly. " .
            "10 readings: {$smallBatchQueries} queries, " .
            "50 readings: {$largeBatchQueries} queries. " .
            "This indicates N+1 query problem."
        );

        // Log results for analysis
        $this->addToAssertionCount(1); // Prevent risky test warning
        dump("Query scaling analysis:", $queryCountsBySize);
    }

    /**
     * Test specific N+1 scenarios that were problematic
     * 
     * @test
     */
    public function it_avoids_n1_queries_in_service_configuration_loading(): void
    {
        // Arrange: Create readings with different service configurations
        $readings = collect();
        for ($i = 0; $i < 20; $i++) {
            $serviceConfig = $this->createServiceConfigurationWithRelationships();
            $meter = Meter::factory()->create(['service_configuration_id' => $serviceConfig->id]);
            $readings->push(MeterReading::factory()->create(['meter_id' => $meter->id]));
        }

        // Act: Validate batch and monitor service configuration queries
        DB::flushQueryLog();
        $this->validationEngine->batchValidateReadings($readings);
        
        $queries = DB::getQueryLog();
        
        // Count queries to service_configurations table
        $serviceConfigQueries = collect($queries)->filter(function ($query) {
            return str_contains(strtolower($query['query']), 'service_configurations');
        })->count();

        // Should not have one query per reading for service configurations
        $this->assertLessThanOrEqual(3, $serviceConfigQueries,
            "Should not have N+1 queries for service configurations. " .
            "Found {$serviceConfigQueries} service_configuration queries for {$readings->count()} readings."
        );
    }

    /**
     * Test N+1 detection in previous reading lookups
     * 
     * @test
     */
    public function it_avoids_n1_queries_in_previous_reading_lookups(): void
    {
        // Arrange: Create readings with previous readings
        $meter = Meter::factory()->create();
        $readings = collect();
        
        // Create a chain of readings for the same meter
        for ($i = 0; $i < 15; $i++) {
            $readings->push(MeterReading::factory()->create([
                'meter_id' => $meter->id,
                'reading_date' => now()->subDays($i),
                'value' => 100 + $i
            ]));
        }

        // Act: Validate batch and monitor previous reading queries
        DB::flushQueryLog();
        $this->validationEngine->batchValidateReadings($readings);
        
        $queries = DB::getQueryLog();
        
        // Count queries that look like previous reading lookups
        $previousReadingQueries = collect($queries)->filter(function ($query) {
            $sql = strtolower($query['query']);
            return str_contains($sql, 'meter_readings') && 
                   str_contains($sql, 'reading_date') && 
                   str_contains($sql, '<');
        })->count();

        // Should not have one query per reading for previous readings
        $this->assertLessThanOrEqual(5, $previousReadingQueries,
            "Should not have N+1 queries for previous readings. " .
            "Found {$previousReadingQueries} previous reading queries for {$readings->count()} readings."
        );
    }

    /**
     * Test memory usage doesn't grow linearly with batch size
     * 
     * @test
     */
    public function it_maintains_reasonable_memory_usage_for_large_batches(): void
    {
        $memoryUsageBySize = [];
        
        foreach ([25, 50, 100] as $size) {
            // Clear memory
            gc_collect_cycles();
            $startMemory = memory_get_usage(true);
            
            // Create and process batch
            $readings = $this->createTestReadingsWithRelationships($size);
            $this->validationEngine->batchValidateReadings($readings);
            
            $endMemory = memory_get_usage(true);
            $memoryUsageBySize[$size] = $endMemory - $startMemory;
            
            // Clean up
            unset($readings);
            gc_collect_cycles();
        }

        // Memory usage should not grow exponentially
        $memoryFor25 = $memoryUsageBySize[25];
        $memoryFor100 = $memoryUsageBySize[100];
        
        // 4x the readings should not use more than 8x the memory
        $maxAllowedMemoryIncrease = $memoryFor25 * 8;
        
        $this->assertLessThanOrEqual($maxAllowedMemoryIncrease, $memoryFor100,
            "Memory usage should not grow exponentially. " .
            "25 readings: " . $this->formatBytes($memoryFor25) . ", " .
            "100 readings: " . $this->formatBytes($memoryFor100)
        );

        dump("Memory usage analysis:", array_map([$this, 'formatBytes'], $memoryUsageBySize));
    }

    /**
     * Test performance regression detection
     * 
     * @test
     */
    public function it_completes_batch_validation_within_performance_threshold(): void
    {
        // Arrange: Create realistic batch size
        $readings = $this->createTestReadingsWithRelationships(100);

        // Act: Measure execution time
        $startTime = microtime(true);
        $result = $this->validationEngine->batchValidateReadings($readings);
        $duration = microtime(true) - $startTime;

        // Assert: Should complete within reasonable time
        $maxAllowedDuration = 2.0; // 2 seconds for 100 readings
        
        $this->assertLessThan($maxAllowedDuration, $duration,
            "Batch validation of 100 readings should complete in under {$maxAllowedDuration}s, " .
            "took {$duration}s. This may indicate performance regression."
        );

        // Verify performance metrics are tracked
        $this->assertArrayHasKey('performance_metrics', $result);
        $this->assertArrayHasKey('duration_seconds', $result['performance_metrics']);
        $this->assertArrayHasKey('queries_per_reading', $result['performance_metrics']);
    }

    /**
     * Test cache effectiveness in reducing queries
     * 
     * @test
     */
    public function it_uses_caching_to_reduce_repeated_queries(): void
    {
        // Arrange: Create readings that would benefit from caching
        $serviceConfig = $this->createServiceConfigurationWithRelationships();
        $meter = Meter::factory()->create(['service_configuration_id' => $serviceConfig->id]);
        
        $readings = collect();
        for ($i = 0; $i < 10; $i++) {
            $readings->push(MeterReading::factory()->create(['meter_id' => $meter->id]));
        }

        // Act: First validation (cache miss)
        DB::flushQueryLog();
        $this->validationEngine->batchValidateReadings($readings);
        $firstRunQueries = count(DB::getQueryLog());

        // Act: Second validation (cache hit)
        DB::flushQueryLog();
        $this->validationEngine->batchValidateReadings($readings);
        $secondRunQueries = count(DB::getQueryLog());

        // Assert: Second run should use fewer queries due to caching
        $this->assertLessThanOrEqual($firstRunQueries, $secondRunQueries,
            "Second validation run should use same or fewer queries due to caching. " .
            "First run: {$firstRunQueries}, Second run: {$secondRunQueries}"
        );
    }

    /**
     * Helper: Create service configuration with all relationships
     */
    private function createServiceConfigurationWithRelationships(): ServiceConfiguration
    {
        $utilityService = UtilityService::factory()->create();
        $tariff = Tariff::factory()->create();
        $provider = Provider::factory()->create();
        
        return ServiceConfiguration::factory()->create([
            'utility_service_id' => $utilityService->id,
            'tariff_id' => $tariff->id,
            'provider_id' => $provider->id,
        ]);
    }

    /**
     * Helper: Create test readings with relationships
     */
    private function createTestReadingsWithRelationships(int $count): Collection
    {
        $readings = collect();
        
        // Create some shared service configurations to test bulk loading
        $serviceConfigs = collect();
        for ($i = 0; $i < min(5, $count); $i++) {
            $serviceConfigs->push($this->createServiceConfigurationWithRelationships());
        }
        
        for ($i = 0; $i < $count; $i++) {
            $serviceConfig = $serviceConfigs->random();
            $meter = Meter::factory()->create(['service_configuration_id' => $serviceConfig->id]);
            $readings->push(MeterReading::factory()->create(['meter_id' => $meter->id]));
        }
        
        return $readings;
    }

    /**
     * Helper: Format queries for debugging
     */
    private function formatQueries(array $queries): string
    {
        return collect($queries)->map(function ($query, $index) {
            return ($index + 1) . ". " . $query['query'] . " ({$query['time']}ms)";
        })->take(10)->implode("\n") . (count($queries) > 10 ? "\n... and " . (count($queries) - 10) . " more" : "");
    }

    /**
     * Helper: Format bytes for human reading
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}