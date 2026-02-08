<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Models\MeterReading;
use App\Models\ServiceConfiguration;
use App\Models\UtilityService;
use App\Models\Meter;
use App\Services\ServiceValidationEngine;
use App\Services\Validation\ValidationPerformanceMonitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Performance tests for ServiceValidationEngine optimizations.
 * 
 * These tests validate that performance improvements meet expected benchmarks:
 * - Batch validation: <2 queries per reading
 * - Memory usage: <50MB for 100 readings
 * - Throughput: >20 readings per second
 */
class ValidationEnginePerformanceTest extends TestCase
{
    use RefreshDatabase;

    private ServiceValidationEngine $validationEngine;
    private ValidationPerformanceMonitor $performanceMonitor;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->validationEngine = app(ServiceValidationEngine::class);
        $this->performanceMonitor = app(ValidationPerformanceMonitor::class);
        
        // Enable query logging for performance measurement
        DB::enableQueryLog();
    }

    /**
     * Test batch validation performance with optimized queries.
     * 
     * PERFORMANCE TARGETS:
     * - Query count: <2 queries per reading (vs 5+ before optimization)
     * - Duration: <100ms for 50 readings
     * - Memory: <30MB for 50 readings
     */
    public function test_batch_validation_performance_optimized(): void
    {
        // Arrange: Create test data
        $readings = $this->createTestReadings(50);
        
        // Act: Monitor batch validation performance
        $result = $this->performanceMonitor->monitor(
            'batch_validation_optimized',
            fn() => $this->validationEngine->batchValidateReadings($readings),
            ['reading_count' => $readings->count()]
        );
        
        // Assert: Performance targets met
        $metrics = $this->performanceMonitor->getPerformanceSummary();
        $batchMetrics = $metrics['operations']['batch_validation_optimized'];
        
        // Query efficiency: Should be <2 queries per reading (optimized from 5+)
        $queriesPerReading = $batchMetrics['query_count'] / $readings->count();
        $this->assertLessThan(2.0, $queriesPerReading, 
            "Query efficiency target missed: {$queriesPerReading} queries per reading (target: <2)");
        
        // Duration: Should complete in <100ms for 50 readings
        $this->assertLessThan(100, $batchMetrics['duration_ms'],
            "Duration target missed: {$batchMetrics['duration_ms']}ms (target: <100ms)");
        
        // Memory: Should use <30MB for 50 readings
        $this->assertLessThan(30, $batchMetrics['memory_used_mb'],
            "Memory target missed: {$batchMetrics['memory_used_mb']}MB (target: <30MB)");
        
        // Throughput: Should process >20 readings per second
        if (isset($batchMetrics['readings_per_second'])) {
            $this->assertGreaterThan(20, $batchMetrics['readings_per_second'],
                "Throughput target missed: {$batchMetrics['readings_per_second']} readings/sec (target: >20)");
        }
        
        // Validation accuracy: All readings should be processed
        $this->assertEquals(50, $result['total_readings']);
        $this->assertArrayHasKey('results', $result);
        $this->assertCount(50, $result['results']);
    }

    /**
     * Test individual validation performance with caching.
     */
    public function test_individual_validation_performance_with_caching(): void
    {
        // Arrange: Create test reading
        $reading = $this->createTestReadings(1)->first();
        
        // Act: Monitor individual validation (first call - cache miss)
        $firstResult = $this->performanceMonitor->monitor(
            'individual_validation_first',
            fn() => $this->validationEngine->validateMeterReading($reading)
        );
        
        // Act: Monitor individual validation (second call - cache hit)
        $secondResult = $this->performanceMonitor->monitor(
            'individual_validation_cached',
            fn() => $this->validationEngine->validateMeterReading($reading)
        );
        
        // Assert: Second call should be faster due to caching
        $summary = $this->performanceMonitor->getPerformanceSummary();
        $firstDuration = $summary['operations']['individual_validation_first']['duration_ms'];
        $secondDuration = $summary['operations']['individual_validation_cached']['duration_ms'];
        
        $this->assertLessThan($firstDuration, $secondDuration,
            "Caching optimization not working: second call ({$secondDuration}ms) should be faster than first ({$firstDuration}ms)");
        
        // Both results should be identical
        $this->assertEquals($firstResult['is_valid'], $secondResult['is_valid']);
    }

    /**
     * Test memory efficiency with large batch processing.
     */
    public function test_memory_efficiency_large_batch(): void
    {
        // Arrange: Create larger dataset
        $readings = $this->createTestReadings(200);
        
        // Act: Monitor memory usage during large batch processing
        $initialMemory = memory_get_usage(true);
        
        $result = $this->performanceMonitor->monitor(
            'large_batch_validation',
            fn() => $this->validationEngine->batchValidateReadings($readings),
            ['reading_count' => $readings->count()]
        );
        
        $finalMemory = memory_get_usage(true);
        $memoryIncrease = ($finalMemory - $initialMemory) / 1024 / 1024; // MB
        
        // Assert: Memory increase should be reasonable for 200 readings
        $this->assertLessThan(100, $memoryIncrease,
            "Memory efficiency target missed: {$memoryIncrease}MB increase for 200 readings (target: <100MB)");
        
        // Validation should complete successfully
        $this->assertEquals(200, $result['total_readings']);
    }

    /**
     * Test query optimization effectiveness.
     */
    public function test_query_optimization_effectiveness(): void
    {
        // Arrange: Create test data with relationships
        $readings = $this->createTestReadingsWithComplexRelationships(30);
        
        // Clear query log
        DB::flushQueryLog();
        $initialQueryCount = count(DB::getQueryLog());
        
        // Act: Perform batch validation
        $result = $this->validationEngine->batchValidateReadings($readings);
        
        $finalQueryCount = count(DB::getQueryLog());
        $totalQueries = $finalQueryCount - $initialQueryCount;
        
        // Assert: Query optimization targets
        $queriesPerReading = $totalQueries / $readings->count();
        
        // Should use <2 queries per reading (optimized from 5+ before)
        $this->assertLessThan(2.0, $queriesPerReading,
            "Query optimization target missed: {$queriesPerReading} queries per reading (target: <2)");
        
        // Should use <60 total queries for 30 readings (vs 150+ before optimization)
        $this->assertLessThan(60, $totalQueries,
            "Total query optimization target missed: {$totalQueries} queries (target: <60)");
    }

    /**
     * Test performance regression detection.
     */
    public function test_performance_regression_detection(): void
    {
        // Arrange: Create baseline test
        $readings = $this->createTestReadings(25);
        
        // Act: Run validation and check for performance regressions
        $result = $this->performanceMonitor->monitor(
            'regression_test',
            fn() => $this->validationEngine->batchValidateReadings($readings)
        );
        
        $bottlenecks = $this->performanceMonitor->identifyBottlenecks();
        $recommendations = $this->performanceMonitor->getRecommendations();
        
        // Assert: No high-severity performance issues
        $highSeverityIssues = array_filter($bottlenecks, fn($b) => $b['severity'] === 'high');
        $this->assertEmpty($highSeverityIssues,
            'High-severity performance issues detected: ' . json_encode($highSeverityIssues));
        
        // Log recommendations for monitoring
        if (!empty($recommendations)) {
            $this->addWarning('Performance recommendations available: ' . json_encode($recommendations));
        }
    }

    /**
     * Create test meter readings for performance testing.
     */
    private function createTestReadings(int $count): Collection
    {
        // Create utility service
        $utilityService = UtilityService::factory()->create([
            'name' => 'Test Electricity Service',
            'unit_of_measurement' => 'kWh',
            'is_global_template' => false,
        ]);
        
        // Create service configuration
        $serviceConfig = ServiceConfiguration::factory()->create([
            'utility_service_id' => $utilityService->id,
        ]);
        
        // Create meter
        $meter = Meter::factory()->create([
            'service_configuration_id' => $serviceConfig->id,
        ]);
        
        // Create readings
        return MeterReading::factory()
            ->count($count)
            ->create([
                'meter_id' => $meter->id,
            ]);
    }

    /**
     * Create test readings with complex relationships for query optimization testing.
     */
    private function createTestReadingsWithComplexRelationships(int $count): Collection
    {
        $readings = collect();
        
        // Create multiple utility services and configurations
        for ($i = 0; $i < 3; $i++) {
            $utilityService = UtilityService::factory()->create();
            $serviceConfig = ServiceConfiguration::factory()->create([
                'utility_service_id' => $utilityService->id,
            ]);
            
            // Create multiple meters per configuration
            for ($j = 0; $j < 2; $j++) {
                $meter = Meter::factory()->create([
                    'service_configuration_id' => $serviceConfig->id,
                ]);
                
                // Create readings for each meter
                $meterReadings = MeterReading::factory()
                    ->count(intval($count / 6)) // Distribute readings across meters
                    ->create([
                        'meter_id' => $meter->id,
                    ]);
                
                $readings = $readings->merge($meterReadings);
            }
        }
        
        return $readings;
    }
}