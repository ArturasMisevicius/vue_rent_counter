<?php

declare(strict_types=1);

namespace App\Support\ServiceRegistration;

use App\Contracts\CircuitBreakerInterface;
use App\Contracts\InputSanitizerInterface;
use App\Contracts\SharedServiceCostDistributor;
use App\Contracts\SubscriptionCheckerInterface;
use App\Contracts\SuperAdminUserInterface;
use App\Contracts\TenantAuditLoggerInterface;
use App\Contracts\TenantAuthorizationServiceInterface;
use App\Contracts\TenantContextInterface;
use App\Contracts\TenantManagementInterface;
use App\Repositories\Eloquent\EloquentTenantRepository;
use App\Repositories\MeterReadingRepository;
use App\Repositories\TenantRepositoryInterface;
use App\Services\ApiAuthenticationService;
use App\Services\ApiTokenManager;
use App\Services\AssetOptimizationService;
use App\Services\Audit\ComplianceReportGenerator;
use App\Services\Audit\ConfigurationChangeAuditor;
use App\Services\Audit\ConfigurationRollbackService;
use App\Services\Audit\PerformanceMetricsCollector;
use App\Services\Audit\UniversalServiceAuditReporter;
use App\Services\Audit\UniversalServiceChangeTracker;
use App\Services\AutomatedBillingEngine;
use App\Services\BackgroundJobService;
use App\Services\Billing\HeatingChargeProcessor;
use App\Services\Billing\PropertyBillingProcessor;
use App\Services\Billing\TenantBillingProcessor;
use App\Services\Billing\UniversalServiceProcessor;
use App\Services\BillingService;
use App\Services\DashboardCacheService;
use App\Services\FormulaEvaluator;
use App\Services\HeatingCalculatorService;
use App\Services\InputSanitizer;
use App\Services\Integration\CircuitBreakerService;
use App\Services\Integration\IntegrationResilienceHandler;
use App\Services\InvoiceSnapshotService;
use App\Services\MeterReadingService;
use App\Services\PanelAccessService;
use App\Services\QueryOptimizationService;
use App\Services\Security\CspHeaderBuilder;
use App\Services\Security\NonceGeneratorService;
use App\Services\Security\SecurityHeaderFactory;
use App\Services\Security\SecurityHeaderService;
use App\Services\Security\ViteCSPIntegration;
use App\Services\ServiceValidationEngine;
use App\Services\SharedServiceCostDistributorService;
use App\Services\SubscriptionChecker;
use App\Services\SuperAdminUserService;
use App\Services\TariffCalculation\FlatRateStrategy;
use App\Services\TariffCalculation\TimeOfUseStrategy;
use App\Services\TariffResolver;
use App\Services\TenantAuditLogger;
use App\Services\TenantAuthorizationService;
use App\Services\TenantContext;
use App\Services\TenantInitialization\MeterConfigurationProvider;
use App\Services\TenantInitialization\PropertyServiceAssigner;
use App\Services\TenantInitialization\ServiceDefinitionProvider;
use App\Services\TenantInitialization\SlugGeneratorService;
use App\Services\TenantInitialization\TenantValidator;
use App\Services\TenantInitializationService;
use App\Services\TenantManagementService;
use App\Services\TenantTranslationService;
use App\Services\TimeRangeValidator;
use App\Services\TranslationCacheService;
use App\Services\UniversalBillingCalculator;
use App\Services\UniversalReadingCollector;
use App\Services\UserRoleService;
use App\Services\Validation\ValidationRuleFactory;
use App\Support\Localization;
use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\Filesystem;

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
        $this->registerAuditServices();
        $this->registerIntegrationServices();
    }

    /**
     * Register billing-related services
     */
    private function registerBillingServices(): void
    {
        // Core billing services
        $this->container->singleton(MeterReadingService::class);
        $this->container->singleton(BillingService::class);
        $this->container->singleton(InvoiceSnapshotService::class);

        // Billing processors
        $this->container->singleton(TenantBillingProcessor::class);
        $this->container->singleton(PropertyBillingProcessor::class);
        $this->container->singleton(UniversalServiceProcessor::class);
        $this->container->singleton(HeatingChargeProcessor::class);

        // Billing calculators
        $this->container->singleton(AutomatedBillingEngine::class);
        $this->container->singleton(UniversalBillingCalculator::class);
        $this->container->singleton(UniversalReadingCollector::class);
        $this->container->singleton(HeatingCalculatorService::class);

        // Tariff services with strategy pattern
        $this->container->singleton(TariffResolver::class, function ($app) {
            return new TariffResolver([
                $app->make(FlatRateStrategy::class),
                $app->make(TimeOfUseStrategy::class),
            ]);
        });

        // Formula evaluator
        $this->container->singleton(FormulaEvaluator::class);

        // Shared service cost distributor with interface binding
        $this->container->singleton(
            SharedServiceCostDistributor::class,
            SharedServiceCostDistributorService::class
        );
    }

    /**
     * Register security-related services
     */
    private function registerSecurityServices(): void
    {
        // Input sanitization with interface binding
        $this->container->singleton(
            InputSanitizerInterface::class,
            InputSanitizer::class
        );

        // Security header services
        $this->container->singleton(NonceGeneratorService::class);
        $this->container->singleton(CspHeaderBuilder::class);
        $this->container->singleton(SecurityHeaderFactory::class);
        $this->container->singleton(SecurityHeaderService::class);
        $this->container->singleton(ViteCSPIntegration::class);

        // Circuit breaker with interface binding
        $this->container->singleton(
            CircuitBreakerInterface::class,
            CircuitBreakerService::class
        );
    }

    /**
     * Register validation services
     */
    private function registerValidationServices(): void
    {
        $this->container->singleton(TimeRangeValidator::class);
        $this->container->singleton(ValidationRuleFactory::class);
        $this->container->singleton(ServiceValidationEngine::class);
    }

    /**
     * Register tenant management services
     */
    private function registerTenantServices(): void
    {
        // Tenant context services with interface bindings
        $this->container->singleton(
            TenantRepositoryInterface::class,
            EloquentTenantRepository::class
        );

        $this->container->singleton(
            TenantAuditLoggerInterface::class,
            TenantAuditLogger::class
        );

        $this->container->singleton(
            TenantAuthorizationServiceInterface::class,
            TenantAuthorizationService::class
        );

        $this->container->singleton(
            TenantContextInterface::class,
            TenantContext::class
        );

        // Tenant initialization services
        $this->container->singleton(ServiceDefinitionProvider::class);
        $this->container->singleton(MeterConfigurationProvider::class);
        $this->container->singleton(PropertyServiceAssigner::class);
        $this->container->singleton(TenantValidator::class);
        $this->container->singleton(SlugGeneratorService::class);
        $this->container->singleton(TenantInitializationService::class);

        // Tenant management with interface bindings
        $this->container->singleton(
            TenantManagementInterface::class,
            TenantManagementService::class
        );
    }

    /**
     * Register utility and system services
     */
    private function registerUtilityServices(): void
    {
        // Performance services
        $this->container->singleton(DashboardCacheService::class);
        $this->container->singleton(QueryOptimizationService::class);
        $this->container->singleton(BackgroundJobService::class);
        $this->container->singleton(AssetOptimizationService::class);

        // User management services
        $this->container->singleton(UserRoleService::class);
        $this->container->singleton(PanelAccessService::class);
        $this->container->singleton(ApiAuthenticationService::class);
        $this->container->singleton(ApiTokenManager::class);

        // Super admin services
        $this->container->singleton(
            SuperAdminUserInterface::class,
            SuperAdminUserService::class
        );

        // Subscription services
        $this->container->singleton(
            SubscriptionCheckerInterface::class,
            SubscriptionChecker::class
        );

        // Repository services
        $this->container->singleton(MeterReadingRepository::class);

        // Localization services
        $this->registerLocalizationServices();
    }

    /**
     * Register localization and translation services
     */
    private function registerLocalizationServices(): void
    {
        // Localization helper service
        $this->container->singleton(Localization::class);

        // Translation cache service for performance
        $this->container->singleton(TranslationCacheService::class, function ($app) {
            return new TranslationCacheService(
                $app['cache.store'],
                config('app.locale'),
                config('locales.available', [])
            );
        });

        // Multi-tenant translation service
        $this->container->singleton(TenantTranslationService::class);
    }

    /**
     * Register Laravel 12 compatibility services
     */
    public function registerCompatibilityServices(): void
    {
        // Laravel 12 no longer binds the legacy 'files' service alias; restore it for packages that still expect it.
        if (! $this->container->bound('files')) {
            $this->container->singleton('files', fn () => new Filesystem);
        }
    }

    /**
     * Register audit and tracking services
     */
    private function registerAuditServices(): void
    {
        // Audit dependencies first
        $this->container->singleton(ConfigurationChangeAuditor::class);
        $this->container->singleton(PerformanceMetricsCollector::class);
        $this->container->singleton(ComplianceReportGenerator::class);

        // Main audit services
        $this->container->singleton(UniversalServiceAuditReporter::class);
        $this->container->singleton(UniversalServiceChangeTracker::class);
        $this->container->singleton(ConfigurationRollbackService::class);
    }

    /**
     * Register integration and resilience services
     */
    private function registerIntegrationServices(): void
    {
        $this->container->singleton(IntegrationResilienceHandler::class);
    }
}
