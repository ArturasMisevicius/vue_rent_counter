<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\ServiceRegistration\CompatibilityRegistry;
use App\Support\ServiceRegistration\EventRegistry;
use App\Support\ServiceRegistration\ObserverRegistry;
use App\Support\ServiceRegistration\PolicyRegistry;
use App\Support\ServiceRegistration\ServiceRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Application Service Provider
 * 
 * Refactored to use registry pattern for better organization
 * and maintainability. Follows Laravel 12 best practices.
 */
final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register lang path for Laravel 12 compatibility
        // Ensures translation files in base_path('lang') are properly loaded
        $this->app->instance('path.lang', base_path('lang'));

        $this->registerCoreServices();
        $this->registerCompatibilityServices();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->bootRegistries();
    }

    /**
     * Register core application services using registry pattern
     */
    private function registerCoreServices(): void
    {
        $serviceRegistry = new ServiceRegistry($this->app);
        $serviceRegistry->registerCoreServices();
    }

    /**
     * Register compatibility services for Laravel 12 and Filament v4
     */
    private function registerCompatibilityServices(): void
    {
        $serviceRegistry = new ServiceRegistry($this->app);
        $serviceRegistry->registerCompatibilityServices();
    }

    /**
     * Bootstrap all registries in proper order
     */
    private function bootRegistries(): void
    {
        $this->bootCompatibility();
        $this->bootObservers();
        $this->bootPolicies();
        $this->bootEvents();
    }

    /**
     * Boot compatibility registry
     */
    private function bootCompatibility(): void
    {
        $compatibilityRegistry = new CompatibilityRegistry();
        $compatibilityRegistry->registerTranslationCompatibility();
        $compatibilityRegistry->registerFilamentCompatibility();
    }

    /**
     * Boot observer registry
     */
    private function bootObservers(): void
    {
        $observerRegistry = new ObserverRegistry();
        $observerRegistry->registerModelObservers();
        $observerRegistry->registerSuperadminObservers();
        $observerRegistry->registerCacheInvalidationObservers();
    }

    /**
     * Boot policy registry with performance monitoring
     */
    private function bootPolicies(): void
    {
        $policyRegistry = new PolicyRegistry();
        
        $policyResults = $policyRegistry->registerModelPolicies();
        $gateResults = $policyRegistry->registerSettingsGates();
        
        // Log results in development and testing
        if (app()->environment('local', 'testing')) {
            logger()->info('Policy registration completed', [
                'policies' => $policyResults,
                'gates' => $gateResults,
            ]);
        }
        
        // Alert on production issues
        if (app()->environment('production')) {
            if ($policyResults['errors'] || $gateResults['errors']) {
                logger()->warning('Policy registration issues detected', [
                    'policy_errors' => $policyResults['errors'],
                    'gate_errors' => $gateResults['errors'],
                ]);
            }
        }
        
        // Track performance metrics
        if (class_exists(\App\Services\PerformanceMonitoringService::class)) {
            try {
                $performanceService = app(\App\Services\PerformanceMonitoringService::class);
                $performanceService->monitorPolicyRegistration($policyResults, $gateResults);
            } catch (\Throwable $e) {
                // Silently continue if performance monitoring fails
                logger()->debug('Performance monitoring unavailable for policy registration', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Boot event registry
     */
    private function bootEvents(): void
    {
        $eventRegistry = new EventRegistry();
        $eventRegistry->registerSecurityEvents();
        $eventRegistry->registerAuthenticationEvents();
        $eventRegistry->registerViewComposers();
        $eventRegistry->registerRateLimiters();
        $eventRegistry->registerCollectionMacros();
    }
}