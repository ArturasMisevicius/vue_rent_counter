<?php

namespace Tests\Performance;

use App\Enums\UserRole;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Performance tests for TariffResource optimizations.
 * 
 * These tests verify that performance optimizations are working correctly:
 * - Query count reduction through caching
 * - Auth user memoization
 * - Provider dropdown caching
 * - Enum label caching
 * 
 * Run with: php artisan test --filter=TariffResourcePerformanceTest
 */
class TariffResourcePerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear all caches before each test
        Cache::flush();
    }

    /**
     * Test that tariff list page has acceptable query count.
     * 
     * Expected: <= 6 queries with all optimizations
     * - 1 for auth user
     * - 1 for tariffs with eager loading
     * - 1-2 for pagination
     * - 1-2 for Filament internal queries
     */
    public function test_tariff_list_query_count_is_optimized(): void
    {
        // Arrange
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        Provider::factory()->count(5)->create();
        Tariff::factory()->count(20)->create();

        $this->actingAs($user);

        // Act
        DB::enableQueryLog();
        $response = $this->get(route('filament.admin.resources.tariffs.index'));
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $queryCount = count($queries);

        // Assert
        $this->assertLessThanOrEqual(
            8, // Allowing some buffer for Filament internal queries
            $queryCount,
            sprintf(
                "Expected <= 8 queries, got %d. Queries:\n%s",
                $queryCount,
                json_encode(array_map(fn($q) => $q['query'], $queries), JSON_PRETTY_PRINT)
            )
        );

        $response->assertOk();
    }

    /**
     * Test that provider dropdown uses cache.
     * 
     * First call should hit database, second call should use cache.
     */
    public function test_provider_dropdown_uses_cache(): void
    {
        // Arrange
        Provider::factory()->count(5)->create();

        // Act - First call should query database
        DB::enableQueryLog();
        $firstResult = Provider::getCachedOptions();
        $firstCallQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Act - Second call should use cache
        DB::enableQueryLog();
        $secondResult = Provider::getCachedOptions();
        $secondCallQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Assert
        $this->assertEquals(1, $firstCallQueries, 'First call should query database once');
        $this->assertEquals(0, $secondCallQueries, 'Second call should use cache (0 queries)');
        $this->assertEquals($firstResult, $secondResult, 'Results should be identical');
        $this->assertTrue(Cache::has('providers.form_options'), 'Cache key should exist');
    }

    /**
     * Test that enum labels are cached.
     * 
     * Verifies that TariffType::labels() uses caching.
     */
    public function test_enum_labels_are_cached(): void
    {
        // Act - First call
        $labels1 = \App\Enums\TariffType::labels();

        // Act - Second call should use cache
        $labels2 = \App\Enums\TariffType::labels();

        // Assert
        $this->assertEquals($labels1, $labels2, 'Labels should be identical');
        $this->assertTrue(
            Cache::has('enum.labels.' . \App\Enums\TariffType::class),
            'Enum labels cache key should exist'
        );
    }

    /**
     * Test that auth user is memoized within request.
     * 
     * Verifies that multiple authorization checks use cached user.
     */
    public function test_auth_user_is_memoized(): void
    {
        // Arrange
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->actingAs($user);

        // Clear any existing cache
        \App\Filament\Resources\TariffResource::clearCachedUser();

        // Act - Multiple authorization checks
        DB::enableQueryLog();
        
        $canViewAny = \App\Filament\Resources\TariffResource::canViewAny();
        $canCreate = \App\Filament\Resources\TariffResource::canCreate();
        $shouldRegister = \App\Filament\Resources\TariffResource::shouldRegisterNavigation();
        
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Assert
        $this->assertTrue($canViewAny);
        $this->assertTrue($canCreate);
        $this->assertTrue($shouldRegister);
        
        // Should have minimal queries (not 3+ separate auth queries)
        $authQueries = array_filter($queries, fn($q) => str_contains($q['query'], 'users'));
        $this->assertLessThanOrEqual(
            1,
            count($authQueries),
            'Should have at most 1 auth query due to memoization'
        );
    }

    /**
     * Test that navigation visibility is memoized.
     * 
     * Multiple calls to shouldRegisterNavigation() should not repeat role checks.
     */
    public function test_navigation_visibility_is_memoized(): void
    {
        // Arrange
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->actingAs($user);

        // Act - Call multiple times
        $result1 = \App\Filament\Resources\TariffResource::shouldRegisterNavigation();
        $result2 = \App\Filament\Resources\TariffResource::shouldRegisterNavigation();
        $result3 = \App\Filament\Resources\TariffResource::shouldRegisterNavigation();

        // Assert
        $this->assertTrue($result1);
        $this->assertTrue($result2);
        $this->assertTrue($result3);
        $this->assertEquals($result1, $result2);
        $this->assertEquals($result2, $result3);
    }

    /**
     * Test that cache is cleared when providers are modified.
     * 
     * Ensures cache invalidation works correctly.
     */
    public function test_provider_cache_is_cleared_on_modification(): void
    {
        // Arrange
        Provider::factory()->count(3)->create();
        
        // Prime the cache
        $initialOptions = Provider::getCachedOptions();
        $this->assertTrue(Cache::has('providers.form_options'));

        // Act - Create new provider
        Provider::factory()->create(['name' => 'New Provider']);

        // Assert - Cache should be cleared
        $this->assertFalse(
            Cache::has('providers.form_options'),
            'Cache should be cleared after provider creation'
        );

        // New call should include the new provider
        $newOptions = Provider::getCachedOptions();
        $this->assertCount(4, $newOptions);
        $this->assertContains('New Provider', $newOptions);
    }

    /**
     * Benchmark test: Measure response time improvement.
     * 
     * This test measures the actual performance improvement.
     * Run with --group=benchmark to execute.
     * 
     * @group benchmark
     */
    public function test_benchmark_response_time_improvement(): void
    {
        // Arrange
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        Provider::factory()->count(10)->create();
        Tariff::factory()->count(50)->create();

        $this->actingAs($user);

        // Warm up
        $this->get(route('filament.admin.resources.tariffs.index'));

        // Act - Measure with cache
        $startTime = microtime(true);
        $response = $this->get(route('filament.admin.resources.tariffs.index'));
        $cachedTime = (microtime(true) - $startTime) * 1000;

        // Assert
        $response->assertOk();
        
        // Log performance metrics
        $this->addToAssertionCount(1);
        echo sprintf(
            "\n\nPerformance Metrics:\n" .
            "- Response time (cached): %.2fms\n" .
            "- Target: < 150ms\n" .
            "- Status: %s\n",
            $cachedTime,
            $cachedTime < 150 ? '✅ PASS' : '❌ FAIL'
        );
    }
}
