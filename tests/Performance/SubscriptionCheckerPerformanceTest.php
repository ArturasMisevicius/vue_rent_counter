<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Contracts\SubscriptionCheckerInterface;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Performance tests for SubscriptionChecker optimizations.
 * 
 * These tests validate the performance improvements from:
 * - Request-level memoization
 * - Batch loading for multiple users
 * - Optimized cache usage
 */
class SubscriptionCheckerPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_request_cache_eliminates_repeated_lookups(): void
    {
        $user = User::factory()->create();
        Subscription::factory()->create(['user_id' => $user->id]);
        
        $checker = app(SubscriptionCheckerInterface::class);
        
        // First call - cache miss (will hit Laravel cache or DB)
        $start = microtime(true);
        $checker->getSubscription($user);
        $firstCallTime = (microtime(true) - $start) * 1000;
        
        // Second call - request cache hit (should be much faster)
        $start = microtime(true);
        $checker->getSubscription($user);
        $secondCallTime = (microtime(true) - $start) * 1000;
        
        // Third call - also request cache hit
        $start = microtime(true);
        $checker->getSubscription($user);
        $thirdCallTime = (microtime(true) - $start) * 1000;
        
        // Request cache should be at least 10x faster than first call
        $this->assertLessThan($firstCallTime / 10, $secondCallTime, 
            "Second call ({$secondCallTime}ms) should be at least 10x faster than first call ({$firstCallTime}ms)");
        
        $this->assertLessThan($firstCallTime / 10, $thirdCallTime,
            "Third call ({$thirdCallTime}ms) should be at least 10x faster than first call ({$firstCallTime}ms)");
    }

    public function test_batch_loading_avoids_n_plus_1_queries(): void
    {
        // Create 50 users with subscriptions
        $users = User::factory()->count(50)->create();
        foreach ($users as $user) {
            Subscription::factory()->create(['user_id' => $user->id]);
        }
        
        $checker = app(SubscriptionCheckerInterface::class);
        
        // Clear all caches to force DB queries
        Cache::flush();
        
        // Count queries for batch loading
        DB::enableQueryLog();
        $checker->getSubscriptionsForUsers($users->all());
        $batchQueries = DB::getQueryLog();
        DB::disableQueryLog();
        
        // Should be 1 query (whereIn), not 50 queries
        $this->assertLessThanOrEqual(2, count($batchQueries), 
            'Batch loading should use 1-2 queries, not N queries');
    }

    public function test_batch_loading_performance_vs_individual_calls(): void
    {
        // Create 20 users with subscriptions
        $users = User::factory()->count(20)->create();
        foreach ($users as $user) {
            Subscription::factory()->create(['user_id' => $user->id]);
        }
        
        $checker = app(SubscriptionCheckerInterface::class);
        
        // Test individual calls (cold cache)
        Cache::flush();
        $start = microtime(true);
        foreach ($users as $user) {
            $checker->getSubscription($user);
        }
        $individualTime = (microtime(true) - $start) * 1000;
        
        // Test batch loading (cold cache)
        Cache::flush();
        $start = microtime(true);
        $checker->getSubscriptionsForUsers($users->all());
        $batchTime = (microtime(true) - $start) * 1000;
        
        // Batch loading should be significantly faster
        $this->assertLessThan($individualTime / 2, $batchTime,
            "Batch loading ({$batchTime}ms) should be at least 2x faster than individual calls ({$individualTime}ms)");
    }

    public function test_is_active_reuses_get_subscription_result(): void
    {
        $user = User::factory()->create();
        Subscription::factory()->active()->create(['user_id' => $user->id]);
        
        $checker = app(SubscriptionCheckerInterface::class);
        
        // Clear cache
        Cache::flush();
        
        // Call getSubscription first
        $checker->getSubscription($user);
        
        // Now call isActive - should use request cache, not hit cache/DB again
        DB::enableQueryLog();
        $isActive = $checker->isActive($user);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        
        // Should be 0 queries because request cache is used
        $this->assertCount(0, $queries, 
            'isActive() should reuse getSubscription() result from request cache');
        
        $this->assertTrue($isActive);
    }

    public function test_multiple_method_calls_use_request_cache(): void
    {
        $user = User::factory()->create();
        Subscription::factory()->active()->create(['user_id' => $user->id]);
        
        $checker = app(SubscriptionCheckerInterface::class);
        
        // Clear cache
        Cache::flush();
        
        // First call populates request cache
        $checker->getSubscription($user);
        
        // Subsequent calls should not hit DB
        DB::enableQueryLog();
        $checker->isActive($user);
        $checker->isExpired($user);
        $checker->daysUntilExpiry($user);
        $checker->getSubscription($user); // Call again
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        
        // Should be 0 queries - all use request cache
        $this->assertCount(0, $queries,
            'Multiple method calls should reuse request cache without additional queries');
    }

    public function test_batch_loading_with_mixed_cache_states(): void
    {
        // Create 30 users
        $users = User::factory()->count(30)->create();
        foreach ($users as $user) {
            Subscription::factory()->create(['user_id' => $user->id]);
        }
        
        $checker = app(SubscriptionCheckerInterface::class);
        
        // Warm cache for first 10 users
        foreach ($users->take(10) as $user) {
            $checker->getSubscription($user);
        }
        
        // Now batch load all 30 users (10 cached, 20 uncached)
        DB::enableQueryLog();
        $results = $checker->getSubscriptionsForUsers($users->all());
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        
        // Should only query for the 20 uncached users (1 query with whereIn)
        $this->assertLessThanOrEqual(1, count($queries),
            'Batch loading should only query for uncached users');
        
        // Should return all 30 subscriptions
        $this->assertCount(30, $results);
    }

    public function test_cache_invalidation_clears_request_cache(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $user->id]);
        
        $checker = app(SubscriptionCheckerInterface::class);
        
        // Populate request cache
        $result1 = $checker->getSubscription($user);
        $this->assertEquals($subscription->id, $result1->id);
        
        // Invalidate cache
        $checker->invalidateCache($user);
        
        // Update subscription in database
        $subscription->update(['plan_type' => 'enterprise']);
        
        // Next call should fetch fresh data from DB (not request cache)
        $result2 = $checker->getSubscription($user);
        $this->assertEquals('enterprise', $result2->plan_type);
    }
}
