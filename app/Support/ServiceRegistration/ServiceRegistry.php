<?php

declare(strict_types=1);

namespace App\Support\ServiceRegistration;

use Illuminate\Contracts\Container\Container;

/**
 * Service Registry for organized service registration
 * 
 * Provides a centralized way to register services with proper
 * lifecycle management and dependency injection patterns.
 */
final readonly class ServiceRegistry
{
    public function __construct(
        private Container $container
    ) {}

    /**
     * Register core application services
     */
    public function registerCoreServices(): void
    {
        $this->registerBillingServices();
        $this->registerSecurityServices();
        $this->registerValidationServices();
        $this->registerTenantServices();
        $this->registerUtilityServices();
    }

    /**
     * Register billing-related services
     */
    private function registerBillingServices(): void
    {
        // Core billing services
        $this->container->singleton(\App\Services\MeterReadingService::class);
        $this->container->singleton(\App\Services\BillingService::class);
        $this->container->singleton(\App\Services\InvoiceSnapshotService::class);
        
        // Billing processors
        $this->container->singleton(\App\Services\Billing\TenantBillingProcessor::class);
        $this->container->singleton(\App\Services\Billing\PropertyBillingProcessor::class);
        $this->container->singleton(\App\Services\Billing\UniversalServiceProcessor::class);
        $this->container->singleton(\App\Services\Billing\HeatingChargeProcessor::class);
        
        // Billing calculators
        $this->container->singleton(\App\Services\AutomatedBillingEngine::class);
        $this->container->singleton(\App\Services\UniversalBillingCalculator::class);
        $this->container->singleton(\App\Services\UniversalReadingCollector::class);
        $this->container->singleton(\App\Services\HeatingCalculatorService::class);
        
        // Tariff services with strategy pattern
        $this->container->singleton(\App\Services\TariffResolver::class, function ($app) {
            return new \App\Services\TariffResolver([
                $app->make(\App\Services\TariffCalculation\FlatRateStrategy::class),
                $app->make(\App\Services\TariffCalculation\TimeOfUseStrategy::class),
            ]);
        });
        
        // Formula evaluator
        $this->container->singleton(\App\Services\FormulaEvaluator::class);
        
        // Shared service cost distributor with interface binding
        $this->container->singleton(
            \App\Contracts\SharedServiceCostDistributor::class,
            \App\Services\SharedServiceCostDistributorService::class
        );
    }

    /**
     * Register security-related services
     */
    private function registerSecurityServices(): void
    {
        // Input sanitization with interface binding
        $this->container->singleton(
            \App\Contracts\InputSanitizerInterface::class,
            \App\Services\InputSanitizer::class
        );
        
        // Security header services
        $this->container->singleton(\App\Services\Security\NonceGeneratorService::class);
        $this->container->singleton(\App\Services\Security\CspHeaderBuilder::class);
        $this->container->singleton(\App\Services\Security\SecurityHeaderFactory::class);
        $this->container->singleton(\App\Services\Security\SecurityHeaderService::class);
        $this->container->singleton(\App\Services\Security\ViteCSPIntegration::class);
        
        // Circuit breaker with interface binding
        $this->container->singleton(
            \App\Contracts\CircuitBreakerInterface::class,
            \App\Services\Integration\CircuitBreakerService::class
        );
    }

    /**
     * Register validation services
     */
    private function registerValidationServices(): void
    {
        $this->container->singleton(\App\Services\TimeRangeValidator::class);
        $this->container->singleton(\App\Services\Validation\ValidationRuleFactory::class);
        $this->container->singleton(\App\Services\ServiceValidationEngine::class);
    }

    /**
     * Register tenant management services
     */
    private function registerTenantServices(): void
    {
        // Tenant initialization services
        $this->container->singleton(\App\Services\TenantInitialization\ServiceDefinitionProvider::class);
        $this->container->singleton(\App\Services\TenantInitialization\MeterConfigurationProvider::class);
        $this->container->singleton(\App\Services\TenantInitialization\PropertyServiceAssigner::class);
        $this->container->singleton(\App\Services\TenantInitialization\TenantValidator::class);
        $this->container->singleton(\App\Services\TenantInitialization\SlugGeneratorService::class);
        $this->container->singleton(\App\Services\TenantInitializationService::class);
        
        // Tenant management with interface bindings
        $this->container->singleton(
            \App\Contracts\TenantManagementInterface::class,
            \App\Services\TenantManagementService::class
        );
    }

    /**
     * Register utility and system services
     */
    private function registerUtilityServices(): void
    {
        // Performance services
        $this->container->singleton(\App\Services\DashboardCacheService::class);
        $this->container->singleton(\App\Services\QueryOptimizationService::class);
        $this->container->singleton(\App\Services\BackgroundJobService::class);
        $this->container->singleton(\App\Services\AssetOptimizationService::class);
        
        // System monitoring services
        $this->container->singleton(\App\Services\SystemHealthService::class);
        $this->container->singleton(
            \App\Contracts\SystemMonitoringInterface::class,
            \App\Services\SystemMonitoringService::class
        );
        
        // User management services
        $this->container->singleton(\App\Services\UserRoleService::class);
        $this->container->singleton(\App\Services\PanelAccessService::class);
        $this->container->singleton(\App\Services\ApiAuthenticationService::class);
        $this->container->singleton(\App\Services\ApiTokenManager::class);
        
        // Super admin services
        $this->container->singleton(
            \App\Contracts\SuperAdminUserInterface::class,
            \App\Services\SuperAdminUserService::class
        );
        
        // Subscription services
        $this->container->singleton(
            \App\Contracts\SubscriptionCheckerInterface::class,
            \App\Services\SubscriptionChecker::class
        );
        
        // Repository services
        $this->container->singleton(\App\Repositories\MeterReadingRepository::class);
        
        // Localization services
        $this->registerLocalizationServices();
    }
    
    /**
     * Register localization and translation services
     */
    private function registerLocalizationServices(): void
    {
        // Localization helper service
        $this->container->singleton(\App\Support\Localization::class);
        
        // Translation cache service for performance
        $this->container->singleton(\App\Services\TranslationCacheService::class, function ($app) {
            return new \App\Services\TranslationCacheService(
                $app['cache.store'],
                config('app.locale'),
                config('locales.available', [])
            );
        });
        
        // Multi-tenant translation service
        $this->container->singleton(\App\Services\TenantTranslationService::class);
    }

    /**
     * Register Laravel 12 compatibility services
     */
    public function registerCompatibilityServices(): void
    {
        // Laravel 12 no longer binds the legacy 'files' service alias; add it for packages (Debugbar)
        if (! $this->container->bound('files')) {
            $this->container->singleton('files', fn () => new \Illuminate\Filesystem\Filesystem());
        }
    }
}