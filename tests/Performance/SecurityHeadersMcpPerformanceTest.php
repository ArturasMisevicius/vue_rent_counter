<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Services\Security\SecurityAnalyticsMcpService;
use App\Services\Security\SecurityPerformanceMonitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Performance tests for SecurityHeaders with MCP Integration
 * 
 * Validates that MCP integration maintains performance targets
 * and doesn't introduce significant overhead.
 */
final class SecurityHeadersMcpPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_mcp_csp_violation_processing_performance(): void
    {
        $service = app(SecurityAnalyticsMcpService::class);
        $iterations = 100;
        $maxTimePerViolation = 50; // milliseconds
        
        $totalTime = 0;
        
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            
            $request = Request::create('/api/csp-report', 'POST', [], [], [], [], json_encode([
                'csp-report' => [
                    'violated-directive' => 'script-src',
                    'blocked-uri' => "https://example.com/script-{$i}.js",
                    'document-uri' => 'https://app.test/',
                    'referrer' => 'https://app.test/admin',
                    'source-file' => 'https://app.test/js/app.js',
                    'line-number' => rand(1, 1000),
                    'column-number' => rand(1, 100),
                ],
            ]));

            $request->headers->set('Content-Type', 'application/json');

            $violation = $service->processCspViolationFromRequest($request);
            
            $endTime = microtime(true);
            $processingTime = ($endTime - $startTime) * 1000;
            $totalTime += $processingTime;
            
            $this->assertNotNull($violation);
            $this->assertLessThan($maxTimePerViolation, $processingTime,
                "CSP violation processing took {$processingTime}ms, exceeds {$maxTimePerViolation}ms limit");
        }
        
        $averageTime = $totalTime / $iterations;
        $this->assertLessThan($maxTimePerViolation * 0.8, $averageTime,
            "Average CSP violation processing time ({$averageTime}ms) should be well under limit");
    }

    public function test_mcp_security_analytics_performance(): void
    {
        $service = app(SecurityAnalyticsMcpService::class);
        $maxAnalyticsTime = 200; // milliseconds
        
        $analyticsOperations = [
            'analyzeSecurityMetrics' => [
                'start_date' => now()->subDays(7)->toISOString(),
                'end_date' => now()->toISOString(),
                'severity' => 'high',
            ],
            'detectAnomalies' => [
                'window' => '1h',
                'sensitivity' => 'medium',
            ],
            'generateSecurityReport' => [
                'type' => 'summary',
                'format' => 'json',
            ],
            'correlateSecurityEvents' => [
                ['type' => 'csp_violation', 'timestamp' => now()->toISOString()],
                ['type' => 'failed_login', 'timestamp' => now()->subMinutes(2)->toISOString()],
            ],
        ];

        foreach ($analyticsOperations as $operation => $params) {
            $startTime = microtime(true);
            
            $result = match ($operation) {
                'analyzeSecurityMetrics' => $service->analyzeSecurityMetrics($params),
                'detectAnomalies' => $service->detectAnomalies($params),
                'generateSecurityReport' => $service->generateSecurityReport($params),
                'correlateSecurityEvents' => $service->correlateSecurityEvents($params),
            };
            
            $processingTime = (microtime(true) - $startTime) * 1000;
            
            $this->assertIsArray($result);
            $this->assertLessThan($maxAnalyticsTime, $processingTime,
                "MCP {$operation} took {$processingTime}ms, exceeds {$maxAnalyticsTime}ms limit");
        }
    }

    public function test_concurrent_mcp_operations_performance(): void
    {
        $service = app(SecurityAnalyticsMcpService::class);
        $concurrentRequests = 20;
        $maxTotalTime = 1000; // milliseconds for all concurrent operations
        
        $startTime = microtime(true);
        
        // Simulate concurrent CSP violation processing
        $violations = [];
        for ($i = 0; $i < $concurrentRequests; $i++) {
            $request = Request::create('/api/csp-report', 'POST', [], [], [], [], json_encode([
                'csp-report' => [
                    'violated-directive' => 'script-src',
                    'blocked-uri' => "https://concurrent-{$i}.example.com/script.js",
                    'document-uri' => 'https://app.test/',
                ],
            ]));

            $request->headers->set('Content-Type', 'application/json');

            $violations[] = $service->processCspViolationFromRequest($request);
        }
        
        $totalTime = (microtime(true) - $startTime) * 1000;
        
        // Verify all violations were processed
        foreach ($violations as $violation) {
            $this->assertNotNull($violation);
        }
        
        $this->assertLessThan($maxTotalTime, $totalTime,
            "Concurrent MCP operations took {$totalTime}ms, exceeds {$maxTotalTime}ms limit");
        
        $averageTimePerRequest = $totalTime / $concurrentRequests;
        $this->assertLessThan(100, $averageTimePerRequest,
            "Average time per concurrent request ({$averageTimePerRequest}ms) too high");
    }

    public function test_mcp_rate_limiting_performance_impact(): void
    {
        $service = app(SecurityAnalyticsMcpService::class);
        
        // Test performance with rate limiting active
        $ip = '192.168.1.100';
        $timesBeforeLimit = [];
        $timesAfterLimit = [];
        
        // Process requests up to rate limit
        for ($i = 0; $i < 55; $i++) {
            $startTime = microtime(true);
            
            $request = Request::create('/api/csp-report', 'POST', [], [], [], [
                'REMOTE_ADDR' => $ip,
            ], json_encode([
                'csp-report' => [
                    'violated-directive' => 'script-src',
                    'blocked-uri' => "https://example.com/script-{$i}.js",
                    'document-uri' => 'https://app.test/',
                ],
            ]));

            $request->headers->set('Content-Type', 'application/json');

            $result = $service->processCspViolationFromRequest($request);
            
            $processingTime = (microtime(true) - $startTime) * 1000;
            
            if ($i < 50) {
                $timesBeforeLimit[] = $processingTime;
                $this->assertNotNull($result);
            } else {
                $timesAfterLimit[] = $processingTime;
                $this->assertNull($result); // Should be rate limited
            }
        }
        
        $avgTimeBeforeLimit = array_sum($timesBeforeLimit) / count($timesBeforeLimit);
        $avgTimeAfterLimit = array_sum($timesAfterLimit) / count($timesAfterLimit);
        
        // Rate limiting should be faster (just rejection)
        $this->assertLessThan($avgTimeBeforeLimit, $avgTimeAfterLimit,
            "Rate limiting should be faster than processing");
        
        // Both should be reasonably fast
        $this->assertLessThan(50, $avgTimeBeforeLimit);
        $this->assertLessThan(10, $avgTimeAfterLimit);
    }

    public function test_security_performance_monitor_overhead(): void
    {
        $monitor = app(SecurityPerformanceMonitor::class);
        $iterations = 1000;
        $maxOverheadPerMetric = 1; // milliseconds
        
        $totalOverhead = 0;
        
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            
            $monitor->recordMetric('test_operation', rand(1, 100), [
                'context' => 'test',
                'iteration' => $i,
            ]);
            
            $overhead = (microtime(true) - $startTime) * 1000;
            $totalOverhead += $overhead;
            
            $this->assertLessThan($maxOverheadPerMetric, $overhead,
                "Performance monitoring overhead ({$overhead}ms) exceeds limit");
        }
        
        $averageOverhead = $totalOverhead / $iterations;
        $this->assertLessThan($maxOverheadPerMetric * 0.5, $averageOverhead,
            "Average monitoring overhead ({$averageOverhead}ms) too high");
    }

    public function test_mcp_cache_performance_impact(): void
    {
        $service = app(SecurityAnalyticsMcpService::class);
        
        // Test cache hit vs miss performance
        $cacheHitTimes = [];
        $cacheMissTimes = [];
        
        // First request (cache miss)
        for ($i = 0; $i < 5; $i++) {
            $startTime = microtime(true);
            
            $result = $service->analyzeSecurityMetrics([
                'cache_key' => "unique_key_{$i}",
                'start_date' => now()->subDays(1)->toISOString(),
            ]);
            
            $cacheMissTimes[] = (microtime(true) - $startTime) * 1000;
            $this->assertIsArray($result);
        }
        
        // Subsequent requests (potential cache hits)
        for ($i = 0; $i < 5; $i++) {
            $startTime = microtime(true);
            
            $result = $service->analyzeSecurityMetrics([
                'cache_key' => 'repeated_key',
                'start_date' => now()->subDays(1)->toISOString(),
            ]);
            
            $cacheHitTimes[] = (microtime(true) - $startTime) * 1000;
            $this->assertIsArray($result);
        }
        
        $avgCacheMissTime = array_sum($cacheMissTimes) / count($cacheMissTimes);
        $avgCacheHitTime = array_sum($cacheHitTimes) / count($cacheHitTimes);
        
        // Cache hits should be faster or at least not significantly slower
        $this->assertLessThanOrEqual($avgCacheMissTime * 1.2, $avgCacheHitTime,
            "Cache hits should not be significantly slower than misses");
    }

    public function test_memory_usage_with_mcp_operations(): void
    {
        $service = app(SecurityAnalyticsMcpService::class);
        $initialMemory = memory_get_usage(true);
        $maxMemoryIncrease = 10 * 1024 * 1024; // 10MB
        
        // Perform various MCP operations
        for ($i = 0; $i < 100; $i++) {
            // CSP violation processing
            $request = Request::create('/api/csp-report', 'POST', [], [], [], [], json_encode([
                'csp-report' => [
                    'violated-directive' => 'script-src',
                    'blocked-uri' => "https://memory-test-{$i}.example.com/script.js",
                    'document-uri' => 'https://app.test/',
                ],
            ]));

            $request->headers->set('Content-Type', 'application/json');

            $violation = $service->processCspViolationFromRequest($request);
            $this->assertNotNull($violation);
            
            // Analytics operations
            if ($i % 10 === 0) {
                $service->analyzeSecurityMetrics(['iteration' => $i]);
                $service->detectAnomalies(['iteration' => $i]);
            }
        }
        
        $finalMemory = memory_get_usage(true);
        $memoryIncrease = $finalMemory - $initialMemory;
        
        $this->assertLessThan($maxMemoryIncrease, $memoryIncrease,
            "Memory usage increased by " . number_format($memoryIncrease / 1024 / 1024, 2) . 
            "MB, exceeds " . ($maxMemoryIncrease / 1024 / 1024) . "MB limit");
    }
}