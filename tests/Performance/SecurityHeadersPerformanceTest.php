<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Http\Middleware\SecurityHeaders;
use App\Services\Security\SecurityHeaderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

/**
 * Performance tests for SecurityHeaders system
 * 
 * Validates that optimizations achieve target performance metrics.
 */
final class SecurityHeadersPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_middleware_performance_under_target(): void
    {
        $iterations = 100;
        $totalTime = 0;
        
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            
            $response = $this->get('/');
            
            $endTime = microtime(true);
            $totalTime += ($endTime - $startTime) * 1000; // Convert to milliseconds
            
            $response->assertStatus(200);
            $response->assertHeader('X-Content-Type-Options');
        }
        
        $averageTime = $totalTime / $iterations;
        
        // Target: Average processing time should be under 5ms
        $this->assertLessThan(5, $averageTime, 
            "Average security header processing time ({$averageTime}ms) exceeds 5ms target");
    }

    public function test_api_route_performance(): void
    {
        $user = \App\Models\User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $token = $user->createApiToken('test');
        
        $iterations = 50;
        $totalTime = 0;
        
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            
            $response = $this->withToken($token)->getJson('/api/user');
            
            $endTime = microtime(true);
            $totalTime += ($endTime - $startTime) * 1000;
            
            $response->assertStatus(200);
        }
        
        $averageTime = $totalTime / $iterations;
        
        // API routes should be even faster (no CSP generation)
        $this->assertLessThan(3, $averageTime,
            "Average API security header processing time ({$averageTime}ms) exceeds 3ms target");
    }

    public function test_header_factory_caching_performance(): void
    {
        $factory = app(\App\Services\Security\SecurityHeaderFactory::class);
        $nonce = \App\ValueObjects\SecurityNonce::generate();
        
        // First call (cache miss)
        $startTime = microtime(true);
        $headers1 = $factory->createForContextOptimized('production', $nonce);
        $firstCallTime = (microtime(true) - $startTime) * 1000;
        
        // Subsequent calls (cache hit)
        $startTime = microtime(true);
        for ($i = 0; $i < 10; $i++) {
            $headers2 = $factory->createForContextOptimized('production', $nonce);
        }
        $cachedCallsTime = (microtime(true) - $startTime) * 1000 / 10;
        
        // Cached calls should be significantly faster
        $this->assertLessThan($firstCallTime * 0.5, $cachedCallsTime,
            "Cached header creation not showing expected performance improvement");
        
        // Cached calls should be under 1ms
        $this->assertLessThan(1, $cachedCallsTime,
            "Cached header creation ({$cachedCallsTime}ms) exceeds 1ms target");
    }

    public function test_nonce_generation_performance(): void
    {
        $service = app(\App\Services\Security\NonceGeneratorService::class);
        
        $iterations = 100;
        $startTime = microtime(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            $nonce = $service->generateNonce();
            $this->assertNotEmpty($nonce->base64Encoded);
        }
        
        $totalTime = (microtime(true) - $startTime) * 1000;
        $averageTime = $totalTime / $iterations;
        
        // Nonce generation should be under 0.5ms per nonce
        $this->assertLessThan(0.5, $averageTime,
            "Average nonce generation time ({$averageTime}ms) exceeds 0.5ms target");
    }

    public function test_memory_usage_optimization(): void
    {
        $initialMemory = memory_get_usage(true);
        
        // Process multiple requests to test memory efficiency
        for ($i = 0; $i < 50; $i++) {
            $response = $this->get('/test-' . $i);
            $response->assertStatus(200);
        }
        
        $finalMemory = memory_get_usage(true);
        $memoryIncrease = $finalMemory - $initialMemory;
        
        // Memory increase should be minimal (under 5MB for 50 requests)
        $this->assertLessThan(5 * 1024 * 1024, $memoryIncrease,
            "Memory usage increased by " . number_format($memoryIncrease / 1024 / 1024, 2) . "MB, exceeds 5MB limit");
    }

    public function test_concurrent_request_performance(): void
    {
        // Simulate concurrent requests by rapidly making multiple requests
        $requests = 20;
        $startTime = microtime(true);
        
        $responses = [];
        for ($i = 0; $i < $requests; $i++) {
            $responses[] = $this->get('/concurrent-test-' . $i);
        }
        
        $totalTime = (microtime(true) - $startTime) * 1000;
        $averageTime = $totalTime / $requests;
        
        // Verify all responses are successful
        foreach ($responses as $response) {
            $response->assertStatus(200);
            $response->assertHeader('X-Content-Type-Options');
        }
        
        // Average time per request should still be under target
        $this->assertLessThan(10, $averageTime,
            "Average concurrent request processing time ({$averageTime}ms) exceeds 10ms target");
    }
}