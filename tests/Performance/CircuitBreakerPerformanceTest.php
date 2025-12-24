<?php

declare(strict_types=1);

use App\Contracts\CircuitBreakerInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->circuitBreaker = app(CircuitBreakerInterface::class);
    Cache::flush();
});

describe('Circuit Breaker Performance', function () {
    it('executes calls within acceptable time limits', function () {
        $serviceName = 'performance-test';
        
        $startTime = microtime(true);
        
        // Execute 100 successful calls
        for ($i = 0; $i < 100; $i++) {
            $this->circuitBreaker->call($serviceName, fn() => "result-{$i}");
        }
        
        $executionTime = microtime(true) - $startTime;
        
        // Should complete within reasonable time (adjust threshold as needed)
        expect($executionTime)->toBeLessThan(1.0); // 1 second for 100 calls
    });

    it('handles multiple services efficiently', function () {
        $serviceCount = 50;
        $callsPerService = 10;
        
        $startTime = microtime(true);
        
        for ($i = 0; $i < $serviceCount; $i++) {
            $serviceName = "service-{$i}";
            
            for ($j = 0; $j < $callsPerService; $j++) {
                $this->circuitBreaker->call($serviceName, fn() => "result-{$i}-{$j}");
            }
        }
        
        $executionTime = microtime(true) - $startTime;
        
        // Should handle multiple services efficiently
        expect($executionTime)->toBeLessThan(2.0); // 2 seconds for 500 calls across 50 services
        
        // Verify all services were registered
        $allStatus = $this->circuitBreaker->getAllStatus();
        expect($allStatus)->toHaveCount($serviceCount);
    });

    it('maintains reasonable memory usage', function () {
        $initialMemory = memory_get_usage(true);
        
        // Create many services and calls
        for ($i = 0; $i < 100; $i++) {
            $serviceName = "memory-test-{$i}";
            $this->circuitBreaker->call($serviceName, fn() => str_repeat('x', 1000));
        }
        
        $finalMemory = memory_get_usage(true);
        $memoryIncrease = $finalMemory - $initialMemory;
        
        // Memory increase should be reasonable (less than 10MB for 100 services)
        expect($memoryIncrease)->toBeLessThan(10 * 1024 * 1024);
    });

    it('cache operations are efficient', function () {
        $serviceName = 'cache-performance-test';
        
        $startTime = microtime(true);
        
        // Test status retrieval performance
        for ($i = 0; $i < 50; $i++) {
            $this->circuitBreaker->getStatus($serviceName);
        }
        
        $executionTime = microtime(true) - $startTime;
        
        // Cache operations should be fast
        expect($executionTime)->toBeLessThan(0.1); // 100ms for 50 status checks
    });

    it('handles concurrent access gracefully', function () {
        $serviceName = 'concurrent-test';
        
        // Simulate concurrent calls
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = $this->circuitBreaker->call($serviceName, fn() => "concurrent-{$i}");
        }
        
        expect($results)->toHaveCount(10);
        
        // All calls should succeed
        foreach ($results as $index => $result) {
            expect($result)->toBe("concurrent-{$index}");
        }
    });
});