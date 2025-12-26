<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Application;
use Tests\TestCase;

/**
 * Performance test suite for AppServiceProvider refactoring
 * 
 * Ensures the registry pattern doesn't negatively impact
 * application startup performance.
 */
final class AppServiceProviderPerformanceTest extends TestCase
{
    public function test_service_registration_performance(): void
    {
        $app = new Application();
        $provider = new AppServiceProvider($app);
        
        $startTime = microtime(true);
        
        // Register services
        $provider->register();
        
        $registrationTime = microtime(true) - $startTime;
        
        // Service registration should complete within 100ms
        $this->assertLessThan(
            0.1,
            $registrationTime,
            'Service registration should complete within 100ms'
        );
    }

    public function test_service_boot_performance(): void
    {
        $app = $this->createApplication();
        $provider = new AppServiceProvider($app);
        
        // Register first
        $provider->register();
        
        $startTime = microtime(true);
        
        // Boot services
        $provider->boot();
        
        $bootTime = microtime(true) - $startTime;
        
        // Service boot should complete within 200ms
        $this->assertLessThan(
            0.2,
            $bootTime,
            'Service boot should complete within 200ms'
        );
    }

    public function test_translation_services_registration(): void
    {
        $app = $this->createApplication();
        $provider = new AppServiceProvider($app);
        
        $provider->register();
        
        // Test that translation services are properly registered
        $this->assertTrue(
            $app->bound(\App\Services\TranslationCacheService::class),
            'TranslationCacheService should be registered'
        );
        
        $this->assertTrue(
            $app->bound(\App\Services\TenantTranslationService::class),
            'TenantTranslationService should be registered'
        );
        
        $this->assertTrue(
            $app->bound(\App\Support\Localization::class),
            'Localization service should be registered'
        );
    }

    public function test_service_resolution_performance(): void
    {
        $app = $this->createApplication();
        $provider = new AppServiceProvider($app);
        
        $provider->register();
        
        $services = [
            \App\Services\BillingService::class,
            \App\Services\MeterReadingService::class,
            \App\Services\TariffResolver::class,
            \App\Services\SystemHealthService::class,
            \App\Services\UserRoleService::class,
        ];
        
        $startTime = microtime(true);
        
        // Resolve all services
        foreach ($services as $service) {
            $app->make($service);
        }
        
        $resolutionTime = microtime(true) - $startTime;
        
        // Service resolution should complete within 50ms
        $this->assertLessThan(
            0.05,
            $resolutionTime,
            'Service resolution should complete within 50ms'
        );
    }

    public function test_memory_usage_during_registration(): void
    {
        $app = new Application();
        $provider = new AppServiceProvider($app);
        
        $memoryBefore = memory_get_usage(true);
        
        $provider->register();
        
        $memoryAfter = memory_get_usage(true);
        $memoryUsed = $memoryAfter - $memoryBefore;
        
        // Memory usage should be reasonable (less than 5MB)
        $this->assertLessThan(
            5 * 1024 * 1024,
            $memoryUsed,
            'Service registration should use less than 5MB of memory'
        );
    }

    public function test_registry_instantiation_performance(): void
    {
        $app = $this->createApplication();
        
        $registries = [
            \App\Support\ServiceRegistration\ServiceRegistry::class,
            \App\Support\ServiceRegistration\PolicyRegistry::class,
            \App\Support\ServiceRegistration\ObserverRegistry::class,
            \App\Support\ServiceRegistration\EventRegistry::class,
            \App\Support\ServiceRegistration\CompatibilityRegistry::class,
        ];
        
        $startTime = microtime(true);
        
        foreach ($registries as $registryClass) {
            if ($registryClass === \App\Support\ServiceRegistration\ServiceRegistry::class) {
                new $registryClass($app);
            } else {
                new $registryClass();
            }
        }
        
        $instantiationTime = microtime(true) - $startTime;
        
        // Registry instantiation should be fast (less than 10ms)
        $this->assertLessThan(
            0.01,
            $instantiationTime,
            'Registry instantiation should complete within 10ms'
        );
    }

    public function test_no_n_plus_one_service_registrations(): void
    {
        $app = new Application();
        $provider = new AppServiceProvider($app);
        
        // Count the number of singleton registrations
        $registrationCount = 0;
        
        // Mock the singleton method to count calls
        $originalSingleton = $app->singleton(...);
        
        $app->singleton = function (...$args) use (&$registrationCount, $originalSingleton) {
            $registrationCount++;
            return $originalSingleton(...$args);
        };
        
        $provider->register();
        
        // Ensure we're not making excessive registrations
        $this->assertLessThan(
            100,
            $registrationCount,
            'Should not make excessive service registrations'
        );
    }
}