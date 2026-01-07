<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Providers\AppServiceProvider;
use Tests\TestCase;

/**
 * Performance tests for AppServiceProvider
 * 
 * Ensures that service registration and policy bootstrapping
 * complete within acceptable time limits.
 */
final class AppServiceProviderPerformanceTest extends TestCase
{
    public function test_register_method_completes_within_time_limit(): void
    {
        $provider = new AppServiceProvider($this->app);
        
        $startTime = microtime(true);
        $provider->register();
        $duration = microtime(true) - $startTime;
        
        // Registration should complete within 50ms
        $this->assertLessThan(
            0.05,
            $duration,
            'Service registration should complete within 50ms'
        );
    }

    public function test_boot_method_completes_within_time_limit(): void
    {
        $provider = new AppServiceProvider($this->app);
        $provider->register();
        
        $startTime = microtime(true);
        $provider->boot();
        $duration = microtime(true) - $startTime;
        
        // Boot should complete within 100ms (includes policy registration)
        $this->assertLessThan(
            0.1,
            $duration,
            'Service boot should complete within 100ms'
        );
    }

    public function test_multiple_service_resolutions_are_fast(): void
    {
        $provider = new AppServiceProvider($this->app);
        $provider->register();
        
        $startTime = microtime(true);
        
        // Resolve services multiple times to test singleton performance
        for ($i = 0; $i < 100; $i++) {
            $this->app->make(\App\Services\TenantContext::class);
            $this->app->make(\App\Services\TenantBoundaryService::class);
            $this->app->make(\App\Contracts\ServiceRegistration\PolicyRegistryInterface::class);
        }
        
        $duration = microtime(true) - $startTime;
        
        // 100 resolutions should complete within 10ms (singleton caching)
        $this->assertLessThan(
            0.01,
            $duration,
            '100 service resolutions should complete within 10ms'
        );
    }

    public function test_memory_usage_is_reasonable(): void
    {
        $memoryBefore = memory_get_usage(true);
        
        $provider = new AppServiceProvider($this->app);
        $provider->register();
        $provider->boot();
        
        $memoryAfter = memory_get_usage(true);
        $memoryUsed = $memoryAfter - $memoryBefore;
        
        // Should use less than 1MB of memory
        $this->assertLessThan(
            1024 * 1024,
            $memoryUsed,
            'Provider should use less than 1MB of memory'
        );
    }
}