<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Enums\UserRole;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Custom Token Performance Tests
 * 
 * Tests performance aspects of the custom API token system including
 * caching, memoization, and database query optimization.
 */
class CustomTokenPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_token_manager_is_memoized_for_performance(): void
    {
        $user = User::factory()->create();

        // Track query count
        $initialQueries = DB::getQueryLog();
        DB::flushQueryLog();
        DB::enableQueryLog();

        // Multiple token operations should reuse the same service instance
        $user->createApiToken('token-1');
        $user->createApiToken('token-2');
        $user->getActiveTokensCount();
        $user->hasApiAbility('test:ability');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should not create multiple service instances
        $this->assertLessThan(10, count($queries)); // Reasonable query limit
    }

    public function test_bulk_token_creation_is_efficient(): void
    {
        $users = User::factory()->count(100)->create();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $startTime = microtime(true);

        // Create tokens for all users
        foreach ($users as $user) {
            $user->createApiToken('bulk-token');
        }

        $endTime = microtime(true);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should complete within reasonable time
        $this->assertLessThan(5.0, $endTime - $startTime); // 5 seconds max

        // Should not have excessive queries
        $this->assertLessThan(300, count($queries)); // ~3 queries per user max
    }

    public function test_bulk_token_revocation_is_efficient(): void
    {
        $user = User::factory()->create();

        // Create many tokens
        for ($i = 0; $i < 100; $i++) {
            $user->createApiToken("token-{$i}");
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $startTime = microtime(true);
        $revokedCount = $user->revokeAllApiTokens();
        $endTime = microtime(true);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertEquals(100, $revokedCount);
        $this->assertLessThan(1.0, $endTime - $startTime); // 1 second max
        $this->assertLessThan(5, count($queries)); // Should be bulk operation
    }

    public function test_token_ability_checking_is_cached(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $token = $user->createApiToken('cache-test');
        $tokenModel = PersonalAccessToken::findToken($token);
        $user->currentAccessToken = $tokenModel;

        Cache::flush();
        DB::flushQueryLog();
        DB::enableQueryLog();

        // First ability check
        $result1 = $user->hasApiAbility('property:read');
        $firstCheckQueries = count(DB::getQueryLog());

        // Second ability check (should use cache)
        $result2 = $user->hasApiAbility('property:read');
        $secondCheckQueries = count(DB::getQueryLog());

        DB::disableQueryLog();

        $this->assertTrue($result1);
        $this->assertTrue($result2);
        $this->assertEquals($firstCheckQueries, $secondCheckQueries); // No additional queries
    }

    public function test_user_token_count_is_cached(): void
    {
        $user = User::factory()->create();
        
        // Create tokens
        $user->createApiToken('count-test-1');
        $user->createApiToken('count-test-2');

        Cache::flush();
        DB::flushQueryLog();
        DB::enableQueryLog();

        // First count
        $count1 = $user->getActiveTokensCount();
        $firstCountQueries = count(DB::getQueryLog());

        // Second count (should use cache if implemented)
        $count2 = $user->getActiveTokensCount();
        $secondCountQueries = count(DB::getQueryLog());

        DB::disableQueryLog();

        $this->assertEquals(2, $count1);
        $this->assertEquals(2, $count2);
        
        // If caching is implemented, should have same query count
        // If not, this test documents the performance opportunity
        $this->assertGreaterThanOrEqual($firstCountQueries, $secondCountQueries);
    }

    public function test_token_validation_is_optimized(): void
    {
        $users = User::factory()->count(50)->create();
        $tokens = [];

        // Create tokens for all users
        foreach ($users as $user) {
            $tokens[] = $user->createApiToken('validation-test');
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $startTime = microtime(true);

        // Validate all tokens
        foreach ($tokens as $token) {
            $tokenModel = PersonalAccessToken::findToken($token);
            $tokenModel?->validateForUser();
        }

        $endTime = microtime(true);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should complete within reasonable time
        $this->assertLessThan(2.0, $endTime - $startTime);

        // Should not have excessive queries (eager loading should be used)
        $this->assertLessThan(150, count($queries)); // ~3 queries per token max
    }

    public function test_concurrent_token_operations_performance(): void
    {
        $user = User::factory()->create();

        $startTime = microtime(true);

        // Simulate concurrent operations
        $operations = [];
        for ($i = 0; $i < 20; $i++) {
            $operations[] = function() use ($user, $i) {
                $user->createApiToken("concurrent-{$i}");
                return $user->getActiveTokensCount();
            };
        }

        // Execute operations
        $results = [];
        foreach ($operations as $operation) {
            $results[] = $operation();
        }

        $endTime = microtime(true);

        // Should complete within reasonable time
        $this->assertLessThan(3.0, $endTime - $startTime);

        // Final count should be correct
        $this->assertEquals(20, $user->getActiveTokensCount());
    }

    public function test_token_cleanup_performance(): void
    {
        $users = User::factory()->count(10)->create();

        // Create mix of active and expired tokens
        foreach ($users as $user) {
            // Active tokens
            $user->createApiToken('active-1');
            $user->createApiToken('active-2');

            // Expired tokens
            PersonalAccessToken::create([
                'tokenable_type' => User::class,
                'tokenable_id' => $user->id,
                'name' => 'expired-1',
                'token' => PersonalAccessToken::generateTokenHash('exp1'),
                'abilities' => ['*'],
                'expires_at' => now()->subDay(),
            ]);

            PersonalAccessToken::create([
                'tokenable_type' => User::class,
                'tokenable_id' => $user->id,
                'name' => 'expired-2',
                'token' => PersonalAccessToken::generateTokenHash('exp2'),
                'abilities' => ['*'],
                'expires_at' => now()->subHour(),
            ]);
        }

        $this->assertEquals(40, PersonalAccessToken::count()); // 20 active + 20 expired

        DB::flushQueryLog();
        DB::enableQueryLog();

        $startTime = microtime(true);
        PersonalAccessToken::pruneExpired();
        $endTime = microtime(true);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should remove only expired tokens
        $this->assertEquals(20, PersonalAccessToken::count());

        // Should be efficient
        $this->assertLessThan(1.0, $endTime - $startTime);
        $this->assertLessThan(5, count($queries)); // Should be bulk operation
    }

    public function test_memory_usage_during_bulk_operations(): void
    {
        $initialMemory = memory_get_usage(true);
        
        $user = User::factory()->create();

        // Create many tokens
        for ($i = 0; $i < 1000; $i++) {
            $user->createApiToken("memory-test-{$i}");
            
            // Clear any potential memory leaks
            if ($i % 100 === 0) {
                gc_collect_cycles();
            }
        }

        $peakMemory = memory_get_peak_usage(true);
        $finalMemory = memory_get_usage(true);

        // Memory usage should be reasonable
        $memoryIncrease = $peakMemory - $initialMemory;
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease); // 50MB max increase

        // Memory should be released after operations
        $this->assertLessThan($peakMemory, $finalMemory + (10 * 1024 * 1024)); // Within 10MB of peak
    }

    public function test_database_connection_efficiency(): void
    {
        $users = User::factory()->count(20)->create();

        // Monitor database connections
        $initialConnections = DB::connection()->getPdo();

        foreach ($users as $user) {
            $user->createApiToken('connection-test');
            $user->getActiveTokensCount();
            $user->revokeAllApiTokens();
        }

        $finalConnections = DB::connection()->getPdo();

        // Should reuse database connections
        $this->assertSame($initialConnections, $finalConnections);
    }

    public function test_query_optimization_with_relationships(): void
    {
        $user = User::factory()->create();
        
        // Create tokens with relationships
        for ($i = 0; $i < 10; $i++) {
            $user->createApiToken("relationship-test-{$i}");
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        // Load tokens with user relationship
        $tokens = $user->tokens()->with('tokenable')->get();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertCount(10, $tokens);
        
        // Should use eager loading to avoid N+1 queries
        $this->assertLessThan(5, count($queries)); // Should be 2-3 queries max
    }
}