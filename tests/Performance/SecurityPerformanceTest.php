<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Services\InputSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Security Performance Tests
 * 
 * Ensures security operations perform within acceptable limits
 * and that security indexes are working effectively.
 */
class SecurityPerformanceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function token_validation_performs_within_limits(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        
        $token = $user->createApiToken('test');
        
        $startTime = microtime(true);
        
        // Perform 100 token validations
        for ($i = 0; $i < 100; $i++) {
            PersonalAccessToken::findToken($token);
        }
        
        $duration = microtime(true) - $startTime;
        
        // Should complete 100 validations in under 1 second
        $this->assertLessThan(1.0, $duration, 'Token validation should be fast');
    }

    /** @test */
    public function database_indexes_optimize_security_queries(): void
    {
        // Create test data
        User::factory()->count(1000)->create();
        
        // Enable query logging
        DB::enableQueryLog();
        
        $startTime = microtime(true);
        
        // Query that should use security indexes
        $count = User::where('is_active', true)
            ->whereNotNull('email_verified_at')
            ->whereNull('suspended_at')
            ->count();
            
        $duration = microtime(true) - $startTime;
        $queries = DB::getQueryLog();
        
        // Should complete in under 100ms with proper indexes
        $this->assertLessThan(0.1, $duration, 'Security queries should be fast with indexes');
        
        // Should use only one query
        $this->assertCount(1, $queries, 'Should use single optimized query');
        
        DB::disableQueryLog();
    }

    /** @test */
    public function token_cleanup_query_performance(): void
    {
        // Create many expired tokens
        $users = User::factory()->count(100)->create();
        
        foreach ($users as $user) {
            for ($i = 0; $i < 10; $i++) {
                PersonalAccessToken::create([
                    'tokenable_type' => User::class,
                    'tokenable_id' => $user->id,
                    'name' => "token-{$i}",
                    'token' => hash('sha256', "token-{$user->id}-{$i}"),
                    'abilities' => ['*'],
                    'expires_at' => now()->subDays(rand(1, 30)),
                ]);
            }
        }
        
        $startTime = microtime(true);
        
        // Query expired tokens (should use cleanup index)
        $expiredCount = PersonalAccessToken::expired()
            ->where('created_at', '<', now()->subHours(24))
            ->count();
            
        $duration = microtime(true) - $startTime;
        
        // Should complete quickly even with many tokens
        $this->assertLessThan(0.5, $duration, 'Token cleanup queries should be fast');
        $this->assertGreaterThan(0, $expiredCount, 'Should find expired tokens');
    }

    /** @test */
    public function input_sanitization_performance(): void
    {
        $sanitizer = app(InputSanitizer::class);
        
        $testInputs = [
            'normal-identifier',
            'system.id.12345',
            'provider-name-with-dashes',
            str_repeat('a', 200), // Long input
            'unicode-test-ñáéíóú',
        ];
        
        $startTime = microtime(true);
        
        // Sanitize 1000 inputs
        for ($i = 0; $i < 1000; $i++) {
            $input = $testInputs[$i % count($testInputs)];
            $sanitizer->sanitizeIdentifier($input);
        }
        
        $duration = microtime(true) - $startTime;
        
        // Should complete 1000 sanitizations in under 1 second
        $this->assertLessThan(1.0, $duration, 'Input sanitization should be fast');
    }

    /** @test */
    public function authentication_query_performance(): void
    {
        // Create test users
        User::factory()->count(1000)->create();
        
        $testUser = User::factory()->create([
            'email' => 'test@example.com',
            'is_active' => true,
            'email_verified_at' => now(),
            'suspended_at' => null,
        ]);
        
        DB::enableQueryLog();
        $startTime = microtime(true);
        
        // Authentication query (should use auth index)
        $user = User::where('email', 'test@example.com')
            ->where('is_active', true)
            ->whereNull('suspended_at')
            ->first();
            
        $duration = microtime(true) - $startTime;
        $queries = DB::getQueryLog();
        
        // Should be very fast with proper indexing
        $this->assertLessThan(0.05, $duration, 'Authentication queries should be very fast');
        $this->assertCount(1, $queries, 'Should use single query');
        $this->assertNotNull($user, 'Should find the user');
        
        DB::disableQueryLog();
    }

    /** @test */
    public function rate_limiting_check_performance(): void
    {
        $startTime = microtime(true);
        
        // Simulate rate limiting checks
        for ($i = 0; $i < 100; $i++) {
            \Illuminate\Support\Facades\RateLimiter::tooManyAttempts("test-key-{$i}", 60);
        }
        
        $duration = microtime(true) - $startTime;
        
        // Rate limiting checks should be fast
        $this->assertLessThan(0.5, $duration, 'Rate limiting checks should be fast');
    }

    /** @test */
    public function security_monitoring_query_performance(): void
    {
        // Create test data for monitoring queries
        $users = User::factory()->count(100)->create();
        
        foreach ($users as $user) {
            PersonalAccessToken::factory()->count(5)->create([
                'tokenable_type' => User::class,
                'tokenable_id' => $user->id,
            ]);
        }
        
        $startTime = microtime(true);
        
        // Monitoring queries
        $recentTokens = PersonalAccessToken::where('created_at', '>', now()->subHour())->count();
        $superadminTokens = PersonalAccessToken::whereHasMorph('tokenable', [User::class], function ($query) {
            $query->where('role', 'superadmin');
        })->count();
        $unverifiedWithTokens = User::whereNull('email_verified_at')
            ->whereHas('tokens')
            ->count();
            
        $duration = microtime(true) - $startTime;
        
        // Monitoring queries should complete quickly
        $this->assertLessThan(1.0, $duration, 'Security monitoring queries should be fast');
    }

    /** @test */
    public function bulk_token_operations_performance(): void
    {
        $user = User::factory()->create();
        
        $startTime = microtime(true);
        
        // Create many tokens
        for ($i = 0; $i < 50; $i++) {
            $user->createApiToken("token-{$i}");
        }
        
        $creationDuration = microtime(true) - $startTime;
        
        $startTime = microtime(true);
        
        // Revoke all tokens
        $revokedCount = $user->revokeAllApiTokens();
        
        $revocationDuration = microtime(true) - $startTime;
        
        // Operations should be reasonably fast
        $this->assertLessThan(5.0, $creationDuration, 'Token creation should be reasonably fast');
        $this->assertLessThan(1.0, $revocationDuration, 'Token revocation should be fast');
        $this->assertEquals(50, $revokedCount, 'Should revoke all tokens');
    }
}