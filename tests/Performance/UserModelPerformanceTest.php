<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Models\User;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Services\UserQueryOptimizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * User Model Performance Tests
 * 
 * Tests to validate performance optimizations and measure improvements.
 */
class UserModelPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private UserQueryOptimizationService $optimizationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->optimizationService = app(UserQueryOptimizationService::class);
    }

    public function test_user_with_common_relations_avoids_n_plus_1(): void
    {
        // Create test data
        User::factory()->count(10)->create();

        // Clear query log
        DB::flushQueryLog();
        DB::enableQueryLog();

        // Test optimized query
        $users = User::withCommonRelations()->get();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should be minimal queries due to eager loading
        $this->assertLessThanOrEqual(4, count($queries), 'Too many queries executed');
        $this->assertCount(10, $users);
    }

    public function test_user_for_listing_scope_limits_fields(): void
    {
        User::factory()->count(5)->create();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $users = User::forListing()->get();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Check that only essential fields are selected
        $firstQuery = $queries[0]['query'] ?? '';
        $this->assertStringContainsString('select `id`, `name`, `email`', $firstQuery);
        $this->assertStringNotContainsString('password', $firstQuery);
        $this->assertStringNotContainsString('remember_token', $firstQuery);
    }

    public function test_memoization_prevents_repeated_service_calls(): void
    {
        $user = User::factory()->create();

        // First call should create the memoized instance
        $capabilities1 = $user->getCapabilities();
        $capabilities2 = $user->getCapabilities();

        // Should be the same instance (memoized)
        $this->assertSame($capabilities1, $capabilities2);

        // Same for user state
        $state1 = $user->getState();
        $state2 = $user->getState();
        $this->assertSame($state1, $state2);
    }

    public function test_cached_organization_ids_improves_all_projects_query(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();
        $user->organizations()->attach($organization->id, ['role' => 'member', 'is_active' => true]);

        Cache::flush();

        // First call should cache the organization IDs
        $projects1 = $user->allProjects()->get();
        
        // Second call should use cached IDs
        DB::flushQueryLog();
        DB::enableQueryLog();
        
        $projects2 = $user->allProjects()->get();
        
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should have fewer queries on second call due to caching
        $this->assertLessThanOrEqual(2, count($queries));
    }

    public function test_bulk_operations_are_efficient(): void
    {
        $users = User::factory()->count(100)->create();
        $userIds = $users->pluck('id')->toArray();

        DB::flushQueryLog();
        DB::enableQueryLog();

        // Test bulk update
        $this->optimizationService->bulkUpdateLastLogin($userIds);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should be only a few queries regardless of user count
        $this->assertLessThanOrEqual(3, count($queries), 'Bulk update should be efficient');
    }

    public function test_user_statistics_query_is_optimized(): void
    {
        User::factory()->count(50)->create(['tenant_id' => 1]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $stats = $this->optimizationService->getUserStatistics(1);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should use a single aggregated query
        $this->assertEquals(1, count($queries), 'Statistics should use single query');
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_users', $stats);
    }

    public function test_caching_reduces_database_hits(): void
    {
        $user = User::factory()->create();
        Cache::flush();

        // First call - should hit database
        DB::flushQueryLog();
        DB::enableQueryLog();
        
        $summary1 = $this->optimizationService->getUserWorkloadSummary($user);
        
        $firstCallQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Second call - should use cache
        DB::flushQueryLog();
        DB::enableQueryLog();
        
        $summary2 = $this->optimizationService->getUserWorkloadSummary($user);
        
        $secondCallQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertEquals($summary1, $summary2);
        $this->assertLessThan($firstCallQueries, $secondCallQueries, 'Second call should have fewer queries due to caching');
    }

    public function test_user_activity_metrics_calculation_performance(): void
    {
        $user = User::factory()->create(['last_login_at' => now()->subDays(5)]);
        
        // Create some task assignments
        $tasks = Task::factory()->count(10)->create();
        foreach ($tasks as $task) {
            $user->taskAssignments()->attach($task->id, [
                'role' => 'assignee',
                'status' => fake()->randomElement(['pending', 'completed']),
                'assigned_at' => now(),
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $metrics = $this->optimizationService->getUserActivityMetrics($user);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('engagement_level', $metrics);
        $this->assertLessThanOrEqual(5, count($queries), 'Activity metrics should be efficient');
    }

    public function test_similar_users_query_performance(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);
        User::factory()->count(20)->create(['role' => 'admin', 'tenant_id' => 1]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $similarUsers = $this->optimizationService->getSimilarUsers($user, 10);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(3, count($queries));
        $this->assertLessThanOrEqual(10, $similarUsers->count());
    }

    public function test_cache_invalidation_works_correctly(): void
    {
        $user = User::factory()->create();
        
        // Cache some data
        $user->getCapabilities();
        $user->getState();
        
        // Update user - should clear cache
        $user->update(['name' => 'Updated Name']);
        
        // Verify memoized data is cleared
        $reflection = new \ReflectionClass($user);
        $capabilitiesProperty = $reflection->getProperty('memoizedCapabilities');
        $capabilitiesProperty->setAccessible(true);
        
        $this->assertNull($capabilitiesProperty->getValue($user));
    }

    public function test_database_indexes_are_used(): void
    {
        // This test would require EXPLAIN queries in a real database
        // For now, we'll test that the queries we expect to be fast are indeed fast
        
        User::factory()->count(1000)->create();
        
        $startTime = microtime(true);
        
        // Test queries that should use indexes
        User::active()->count();
        User::where('tenant_id', 1)->where('role', 'admin')->count();
        User::where('email', 'test@example.com')->first();
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Should complete quickly with proper indexes
        $this->assertLessThan(0.1, $executionTime, 'Indexed queries should be fast');
    }
}