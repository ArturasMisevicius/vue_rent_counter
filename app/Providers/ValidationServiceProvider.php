<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\ServiceValidationEngine;
use App\Services\Validation\ValidationCacheService;
use App\Services\Validation\ValidationPerformanceMonitor;
use App\Services\Validation\ValidationRuleFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

/**
 * Service provider for validation system components.
 * 
 * Registers and configures validation services with performance optimizations.
 */
class ValidationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register validation cache service
        $this->app->singleton(ValidationCacheService::class, function ($app) {
            return new ValidationCacheService(
                $app->make(CacheRepository::class)
            );
        });

        // Register performance monitor
        $this->app->singleton(ValidationPerformanceMonitor::class, function ($app) {
            return new ValidationPerformanceMonitor(
                $app->make(LoggerInterface::class)
            );
        });

        // Register validation rule factory
        $this->app->singleton(ValidationRuleFactory::class, function ($app) {
            return new ValidationRuleFactory($app);
        });

        // Register main validation engine with dependencies
        $this->app->singleton(ServiceValidationEngine::class, function ($app) {
            return new ServiceValidationEngine(
                $app->make(CacheRepository::class),
                $app->make(ConfigRepository::class),
                $app->make(LoggerInterface::class),
                $app->make(\App\Services\MeterReadingService::class),
                $app->make(\App\Services\GyvatukasCalculator::class),
                $app->make(ValidationRuleFactory::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Warm validation cache on startup if enabled
        if (config('service_validation.performance.cache_warm_on_startup', false)) {
            $this->warmValidationCache();
        }

        // Register performance monitoring middleware if enabled
        if (config('service_validation.performance.enable_performance_monitoring', true)) {
            $this->registerPerformanceMonitoring();
        }
    }

    /**
     * Warm validation cache with commonly used configurations.
     */
    private function warmValidationCache(): void
    {
        try {
            $cacheService = $this->app->make(ValidationCacheService::class);
            
            // Get active service configurations for cache warming
            $serviceConfigs = \App\Models\ServiceConfiguration::with(['utilityService'])
                ->active()
                ->limit(config('service_validation.performance.cache_warm_batch_size', 20))
                ->get();
            
            if ($serviceConfigs->isNotEmpty()) {
                $cacheService->warmValidationCache($serviceConfigs);
                
                \Log::info('Validation cache warmed on startup', [
                    'configurations_cached' => $serviceConfigs->count(),
                ]);
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to warm validation cache on startup', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Register performance monitoring for validation operations.
     */
    private function registerPerformanceMonitoring(): void
    {
        // This could be extended to register middleware or event listeners
        // for automatic performance monitoring of validation operations
        
        logger()->info('Validation performance monitoring enabled');
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            ServiceValidationEngine::class,
            ValidationCacheService::class,
            ValidationPerformanceMonitor::class,
            ValidationRuleFactory::class,
        ];
    }
}