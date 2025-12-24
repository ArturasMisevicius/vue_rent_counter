<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\TenantInitialization\Contracts\ServiceCreationStrategyInterface;
use App\Services\TenantInitialization\Strategies\TemplateServiceCreationStrategy;
use App\Services\TenantInitialization\Strategies\DefaultServiceCreationStrategy;
use App\Services\TenantInitialization\Factories\UtilityServiceFactory;
use App\Services\TenantInitialization\Repositories\UtilityServiceRepositoryInterface;
use App\Services\TenantInitialization\Repositories\EloquentUtilityServiceRepository;
use App\Services\TenantInitializationService;
use App\Services\TenantInitialization\TenantInitializationOrchestrator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Collection;

/**
 * Service provider for tenant initialization services.
 * 
 * Registers all dependencies and configures the service container
 * for tenant initialization operations.
 */
final class TenantInitializationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register repository
        $this->app->bind(
            UtilityServiceRepositoryInterface::class,
            EloquentUtilityServiceRepository::class
        );

        // Register strategies
        $this->app->when(UtilityServiceFactory::class)
            ->needs('$strategies')
            ->give(function ($app) {
                return new Collection([
                    $app->make(TemplateServiceCreationStrategy::class),
                    $app->make(DefaultServiceCreationStrategy::class),
                ]);
            });

        // Register factory
        $this->app->singleton(UtilityServiceFactory::class);

        // Register orchestrator
        $this->app->singleton(TenantInitializationOrchestrator::class);

        // Bind old service to new orchestrator for backward compatibility
        $this->app->bind(TenantInitializationService::class, function ($app) {
            return new class($app->make(TenantInitializationOrchestrator::class)) {
                public function __construct(
                    private readonly TenantInitializationOrchestrator $orchestrator
                ) {}

                public function initializeUniversalServices($tenant)
                {
                    return $this->orchestrator->initializeUniversalServices($tenant);
                }

                public function initializePropertyServiceAssignments($tenant, $utilityServices)
                {
                    return $this->orchestrator->initializePropertyServiceAssignments($tenant, $utilityServices);
                }

                public function ensureHeatingCompatibility($tenant)
                {
                    return $this->orchestrator->ensureHeatingCompatibility($tenant);
                }
            };
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}