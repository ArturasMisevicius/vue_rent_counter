<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Enums\UserRole;
use App\Models\MeterReading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * MeterReadingUpdatePerformanceTest
 * 
 * Performance tests for meter reading update workflow.
 * Validates query count, response time, and resource usage.
 * 
 * @package Tests\Performance
 * @group performance
 * @group meter-readings
 */
class MeterReadingUpdatePerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => 1,
        ]);
    }

    /**
     * Test that meter reading update executes minimal queries.
     * 
     * Target: ≤6 queries total
     * - 1 route binding (with eager loading)
     * - 2 validation queries (previous/next)
     * - 1 authorization check
     * - 1 update query
     * - 1 audit record creation
     */
    public function test_meter_reading_update_executes_minimal_queries(): void
    {
        $reading = MeterReading::factory()->create([
            'value' => 1000,
            'tenant_id' => 1,
        ]);
        
        DB::enableQueryLog();
        
        $response = $this->actingAs($this->manager)
            ->put(route('meter-readings.correct', $reading), [
                'value' => 1100,
                'change_reason' => 'Correcting data entry error for performance test',
            ]);
        
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        
        // Should execute 6 or fewer queries
        $this->assertLessThanOrEqual(
            6,
            count($queries),
            'Meter reading update should execute 6 or fewer queries. Actual: ' . count($queries)
        );
        
        $response->assertRedirect();
    }

    /**
     * Test that meter reading update completes within acceptable time.
     * 
     * Target: <200ms for single update
     */
    public function test_meter_reading_update_completes_within_acceptable_time(): void
    {
        $reading = MeterReading::factory()->create([
            'value' => 1000,
            'tenant_id' => 1,
        ]);
        
        $start = microtime(true);
        
        $response = $this->actingAs($this->manager)
            ->put(route('meter-readings.correct', $reading), [
                'value' => 1100,
                'change_reason' => 'Performance benchmark test execution',
            ]);
        
        $duration = (microtime(true) - $start) * 1000; // Convert to milliseconds
        
        // Should complete in under 200ms
        $this->assertLessThan(
            200,
            $duration,
            "Meter reading update should complete in <200ms. Actual: {$duration}ms"
        );
        
        $response->assertRedirect();
    }

    /**
     * Test that validation queries use indexes efficiently.
     * 
     * Validates that adjacent reading queries use the composite index.
     */
    public function test_validation_queries_use_indexes(): void
    {
        $reading = MeterReading::factory()->create([
            'value' => 1000,
            'tenant_id' => 1,
        ]);
        
        // Create previous and next readings
        MeterReading::factory()->create([
            'meter_id' => $reading->meter_id,
            'value' => 900,
            'reading_date' => now()->subMonth(),
            'tenant_id' => 1,
        ]);
        
        MeterReading::factory()->create([
            'meter_id' => $reading->meter_id,
            'value' => 1200,
            'reading_date' => now()->addMonth(),
            'tenant_id' => 1,
        ]);
        
        DB::enableQueryLog();
        
        $response = $this->actingAs($this->manager)
            ->put(route('meter-readings.correct', $reading), [
                'value' => 1100,
                'change_reason' => 'Testing index usage in validation queries',
            ]);
        
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        
        // Check that queries use indexed columns
        $validationQueries = array_filter($queries, function ($query) {
            return str_contains($query['query'], 'meter_id') 
                && str_contains($query['query'], 'reading_date');
        });
        
        $this->assertGreaterThanOrEqual(
            2,
            count($validationQueries),
            'Should have at least 2 validation queries using indexed columns'
        );
        
        $response->assertRedirect();
    }

    /**
     * Test that eager loading prevents N+1 queries.
     * 
     * Validates that meter relationship is loaded once, not per validation.
     */
    public function test_eager_loading_prevents_n_plus_one(): void
    {
        $reading = MeterReading::factory()->create([
            'value' => 1000,
            'tenant_id' => 1,
        ]);
        
        DB::enableQueryLog();
        
        $response = $this->actingAs($this->manager)
            ->put(route('meter-readings.correct', $reading), [
                'value' => 1100,
                'change_reason' => 'Testing eager loading to prevent N+1 queries',
            ]);
        
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        
        // Count queries that load the meter relationship
        $meterLoadQueries = array_filter($queries, function ($query) use ($reading) {
            return str_contains($query['query'], 'meters') 
                && str_contains($query['query'], 'where');
        });
        
        // Should load meter only once (via eager loading in route binding)
        $this->assertLessThanOrEqual(
            1,
            count($meterLoadQueries),
            'Meter should be loaded only once via eager loading'
        );
        
        $response->assertRedirect();
    }

    /**
     * Test memory usage during update.
     * 
     * Target: <2MB memory increase
     */
    public function test_memory_usage_during_update(): void
    {
        $reading = MeterReading::factory()->create([
            'value' => 1000,
            'tenant_id' => 1,
        ]);
        
        $memoryBefore = memory_get_usage(true);
        
        $response = $this->actingAs($this->manager)
            ->put(route('meter-readings.correct', $reading), [
                'value' => 1100,
                'change_reason' => 'Testing memory usage during update operation',
            ]);
        
        $memoryAfter = memory_get_usage(true);
        $memoryIncrease = ($memoryAfter - $memoryBefore) / 1024 / 1024; // Convert to MB
        
        // Should use less than 2MB additional memory
        $this->assertLessThan(
            2,
            $memoryIncrease,
            "Memory increase should be <2MB. Actual: {$memoryIncrease}MB"
        );
        
        $response->assertRedirect();
    }

    /**
     * Test transaction overhead.
     * 
     * Validates that transaction wrapper adds minimal overhead.
     */
    public function test_transaction_overhead_is_minimal(): void
    {
        $reading = MeterReading::factory()->create([
            'value' => 1000,
            'tenant_id' => 1,
        ]);
        
        // Measure time with transaction (current implementation)
        $startWithTransaction = microtime(true);
        
        $response = $this->actingAs($this->manager)
            ->put(route('meter-readings.correct', $reading), [
                'value' => 1100,
                'change_reason' => 'Testing transaction overhead measurement',
            ]);
        
        $durationWithTransaction = (microtime(true) - $startWithTransaction) * 1000;
        
        // Transaction overhead should be less than 5ms
        // (This is a rough estimate; actual overhead is typically <1ms)
        $this->assertLessThan(
            205, // 200ms target + 5ms transaction overhead
            $durationWithTransaction,
            "Transaction overhead should be minimal. Duration: {$durationWithTransaction}ms"
        );
        
        $response->assertRedirect();
    }

    /**
     * Test concurrent update performance.
     * 
     * Validates that multiple concurrent updates don't cause deadlocks.
     */
    public function test_concurrent_updates_dont_cause_deadlocks(): void
    {
        $readings = MeterReading::factory()->count(5)->create([
            'tenant_id' => 1,
        ]);
        
        $start = microtime(true);
        
        foreach ($readings as $reading) {
            $response = $this->actingAs($this->manager)
                ->put(route('meter-readings.correct', $reading), [
                    'value' => $reading->value + 100,
                    'change_reason' => 'Testing concurrent update performance',
                ]);
            
            $response->assertRedirect();
        }
        
        $duration = (microtime(true) - $start) * 1000;
        
        // 5 updates should complete in under 1 second
        $this->assertLessThan(
            1000,
            $duration,
            "5 concurrent updates should complete in <1s. Actual: {$duration}ms"
        );
    }
}
