<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Models\User;
use App\Services\ApiTokenManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * API Token Performance Tests
 * 
 * Ensures token operations meet performance requirements.
 */
class ApiTokenPerformanceTest extends TestCase
{
    use RefreshDatabase;

    private ApiTokenManager $tokenManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenManager = app(ApiTokenManager::class);
    }

    public function test_token_creation_performance(): void
    {
        $user = User::factory()->create();
        
        $startTime = microtime(true);
        
        for ($i = 0; $i < 10; $i++) {
            $this->tokenManager->createToken($user, "token-{$i}");
        }
        
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        // Should create 10 tokens in less than 500ms
        $this->assertLessThan(500, $duration, 'Token creation took too long: ' . $duration . 'ms');
    }

    public function test_token_lookup_performance(): void
    {
        $user = User::factory()->create();
        $tokens = [];
        
        // Create 100 tokens
        for ($i = 0; $i < 100; $i++) {
            $tokens[] = $this->tokenManager->createToken($user, "token-{$i}");
        }
        
        $startTime = microtime(true);
        
        // Look up 10 random tokens
        for ($i = 0; $i < 10; $i++) {
            $randomToken = $tokens[array_rand($tokens)];
            $this->tokenManager->findToken($randomToken);
        }
        
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;
        
        // Should lookup 10 tokens in less than 100ms
        $this->assertLessThan(100, $duration, 'Token lookup took too long: ' . $duration . 'ms');
    }

    public function test_token_count_performance(): void
    {
        $user = User::factory()->create();
        
        // Create 50 tokens
        for ($i = 0; $i < 50; $i++) {
            $this->tokenManager->createToken($user, "token-{$i}");
        }
        
        $startTime = microtime(true);
        
        // Get count 10 times
        for ($i = 0; $i < 10; $i++) {
            $this->tokenManager->getActiveTokenCount($user);
        }
        
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;
        
        // Should get count 10 times in less than 50ms (caching should help)
        $this->assertLessThan(50, $duration, 'Token count took too long: ' . $duration . 'ms');
    }

    public function test_bulk_token_revocation_performance(): void
    {
        $user = User::factory()->create();
        
        // Create 100 tokens
        for ($i = 0; $i < 100; $i++) {
            $this->tokenManager->createToken($user, "token-{$i}");
        }
        
        $startTime = microtime(true);
        
        $this->tokenManager->revokeAllTokens($user);
        
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;
        
        // Should revoke 100 tokens in less than 200ms
        $this->assertLessThan(200, $duration, 'Bulk revocation took too long: ' . $duration . 'ms');
    }

    public function test_token_statistics_performance(): void
    {
        // Create multiple users with tokens
        for ($u = 0; $u < 10; $u++) {
            $user = User::factory()->create();
            for ($t = 0; $t < 5; $t++) {
                $this->tokenManager->createToken($user, "user-{$u}-token-{$t}");
            }
        }
        
        $startTime = microtime(true);
        
        $this->tokenManager->getTokenStatistics();
        
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;
        
        // Should get statistics in less than 100ms
        $this->assertLessThan(100, $duration, 'Statistics generation took too long: ' . $duration . 'ms');
    }

    public function test_concurrent_token_operations_performance(): void
    {
        $users = User::factory()->count(5)->create();
        
        $startTime = microtime(true);
        
        // Simulate concurrent operations
        foreach ($users as $user) {
            $this->tokenManager->createToken($user, 'concurrent-token');
            $this->tokenManager->getActiveTokenCount($user);
            $this->tokenManager->getUserTokens($user);
        }
        
        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;
        
        // Should handle 15 operations (3 per user) in less than 300ms
        $this->assertLessThan(300, $duration, 'Concurrent operations took too long: ' . $duration . 'ms');
    }
}