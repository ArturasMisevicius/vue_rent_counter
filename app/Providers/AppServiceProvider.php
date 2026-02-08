<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\ServiceRegistration\ErrorHandlingStrategyInterface;
use App\Contracts\ServiceRegistration\PolicyRegistryInterface;
use App\Services\PolicyRegistryMonitoringService;
use App\Services\ServiceRegistration\RegistrationErrorHandler;
use App\Services\ServiceRegistration\ServiceRegistrationOrchestrator;
use App\Support\ServiceRegistration\CompatibilityRegistry;
use App\Support\ServiceRegistration\ObserverRegistry;
use App\Support\ServiceRegistration\PolicyRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Application Service Provider
 * 
 * Handles core application service registration and bootstrapping with improved
 * architecture following SOLID principles and clean code practices.
 * 
 * Key Responsibilities:
 * - Core service registration with proper dependency injection
 * - Service orchestration delegation to specialized services
 * - Laravel 12 compatibility configuration
 * - Translation system setup
 * 
 * Architecture Improvements:
 * - Single Responsibility: Focused only on service registration
 * - Dependency Injection: All dependencies properly injected
 * - Strategy Pattern: Error handling delegated to specialized strategies
 * - Configuration-driven: Externalized configuration management
 * - Monitoring Integration: Built-in performance and health monitoring
 * 
 * @see \App\Services\ServiceRegistration\ServiceRegistrationOrchestrator
 * @see \App\Services\ServiceRegistration\RegistrationErrorHandler
 * @see \App\Services\PolicyRegistryMonitoringService
 */
final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerLaravel12Compatibility();
        $this->registerCoreServices();
        $this->registerServiceRegistrationInfrastructure();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->bootRateLimiters();
        $this->bootObservers();
        $this->bootServiceRegistration();
        $this->bootViewComposers();
    }

    /**
     * Register rate limiters for admin and API routes
     * 
     * This must be called during boot() after facades are initialized.
     * Rate limiters prevent brute force attacks and DoS attempts.
     */
    private function bootRateLimiters(): void
    {
        // Rate limiting for admin routes (120 requests per minute per user)
        \Illuminate\Support\Facades\RateLimiter::for('admin', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(120)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many requests. Please try again later.',
                    ], 429);
                });
        });

        // Rate limiting for API routes (60 requests per minute per user)
        \Illuminate\Support\Facades\RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many requests. Please try again later.',
                    ], 429);
                });
        });
    }

    /**
     * Register view composers for shared view data
     */
    private function bootViewComposers(): void
    {
        \Illuminate\Support\Facades\View::composer(
            'layouts.app',
            \App\View\Composers\NavigationComposer::class
        );
    }

    /**
     * Register model observers required for audit trails and derived updates.
     */
    private function bootObservers(): void
    {
        $observerRegistry = new ObserverRegistry();
        $observerRegistry->registerModelObservers();
        $observerRegistry->registerSuperadminObservers();
        $observerRegistry->registerCacheInvalidationObservers();
    }

    /**
     * Register Laravel 12 compatibility services
     */
    private function registerLaravel12Compatibility(): void
    {
        $compatibilityRegistry = new CompatibilityRegistry();

        // Register Filament v4 action class aliases for legacy resource code.
        $compatibilityRegistry->registerFilamentCompatibility();

        // Register lang path for Laravel 12 compatibility
        $this->app->useLangPath(base_path('lang'));
        
        // Ensure translation loader uses the correct path
        $this->app->singleton('translation.loader', function ($app) {
            return new \Illuminate\Translation\FileLoader($app['files'], base_path('lang'));
        });
    }

    /**
     * Register core application services using configuration-driven approach
     */
    private function registerCoreServices(): void
    {
        $config = config('service-registration.core_services', []);
        
        // Register singleton services
        foreach ($config['singletons'] ?? [] as $service) {
            if (class_exists($service)) {
                $this->app->singleton($service);
            }
        }
        
        // Register interface bindings
        foreach ($config['bindings'] ?? [] as $interface => $implementation) {
            if (interface_exists($interface) && class_exists($implementation)) {
                $this->app->singleton($interface, $implementation);
            }
        }
    }

    /**
     * Register service registration infrastructure
     */
    private function registerServiceRegistrationInfrastructure(): void
    {
        // Register error handling strategy
        $this->app->singleton(ErrorHandlingStrategyInterface::class, RegistrationErrorHandler::class);
        
        // Register policy registry
        $this->app->singleton(PolicyRegistryInterface::class, PolicyRegistry::class);
        
        // Register monitoring service if available
        if (class_exists(PolicyRegistryMonitoringService::class)) {
            $this->app->singleton(PolicyRegistryMonitoringService::class);
        }
        
        // Register orchestrator with all dependencies
        $this->app->singleton(ServiceRegistrationOrchestrator::class, function ($app) {
            return new ServiceRegistrationOrchestrator(
                app: $app,
                errorHandler: $app->make(ErrorHandlingStrategyInterface::class),
                monitoringService: $app->bound(PolicyRegistryMonitoringService::class) 
                    ? $app->make(PolicyRegistryMonitoringService::class) 
                    : null,
            );
        });
    }

    /**
     * Bootstrap service registration using the orchestrator
     */
    private function bootServiceRegistration(): void
    {
        if (!$this->shouldBootServices()) {
            return;
        }

        try {
            $orchestrator = $this->app->make(ServiceRegistrationOrchestrator::class);
            $orchestrator->registerPolicies();
        } catch (\Throwable $e) {
            // Log the error but don't prevent application boot
            logger()->critical('Failed to boot service registration', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            // In development, we might want to see the error
            if ($this->app->environment('local', 'testing')) {
                throw $e;
            }
        }
    }

    /**
     * Determine if services should be booted based on configuration and environment
     */
    private function shouldBootServices(): bool
    {
        // Don't boot during configuration caching or in maintenance mode
        if ($this->app->configurationIsCached() || $this->app->isDownForMaintenance()) {
            return false;
        }

        // Check if monitoring is enabled
        $monitoringEnabled = config('service-registration.monitoring.enabled', true);
        
        return $monitoringEnabled;
    }
}
