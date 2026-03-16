<?php

declare(strict_types=1);

namespace App\Providers\Enhanced;

use App\Services\Enhanced\BillingService;
use App\Services\Enhanced\UserManagementService;
use App\Services\Enhanced\ConsumptionCalculationService;
use App\Actions\Enhanced\ProcessPaymentAction;
use App\Actions\Enhanced\ValidateMeterReadingAction;
use App\Actions\CreateUserAction;
use App\Actions\AssignRoleAction;
use App\Actions\SendWelcomeEmailAction;
use App\Actions\GenerateInvoiceAction;
use App\Contracts\BillingServiceInterface;
use App\Contracts\UserManagementServiceInterface;
use App\Contracts\ConsumptionCalculationServiceInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Service Layer Service Provider
 * 
 * Registers all service layer components with proper dependency injection:
 * - Service classes with interface bindings
 * - Action classes as singletons
 * - DTO factories and validators
 * - Performance monitoring and logging
 * 
 * @package App\Providers\Enhanced
 */
final class ServiceLayerServiceProvider extends ServiceProvider
{
    /**
     * All of the container bindings that should be registered.
     *
     * @var array
     */
    public array $bindings = [
        BillingServiceInterface::class => BillingService::class,
        UserManagementServiceInterface::class => UserManagementService::class,
        ConsumptionCalculationServiceInterface::class => ConsumptionCalculationService::class,
    ];

    /**
     * All of the container singletons that should be registered.
     *
     * @var array
     */
    public array $singletons = [
        // Actions are stateless and can be singletons
        ProcessPaymentAction::class => ProcessPaymentAction::class,
        ValidateMeterReadingAction::class => ValidateMeterReadingAction::class,
        CreateUserAction::class => CreateUserAction::class,
        AssignRoleAction::class => AssignRoleAction::class,
        SendWelcomeEmailAction::class => SendWelcomeEmailAction::class,
        GenerateInvoiceAction::class => GenerateInvoiceAction::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        // Register service interfaces for testability
        $this->app->bind(BillingServiceInterface::class, function ($app) {
            return new BillingService(
                $app->make(GenerateInvoiceAction::class),
                $app->make(\App\Services\UniversalBillingCalculator::class),
                $app->make(\App\Services\MeterReadingService::class),
                $app->make(ConsumptionCalculationService::class)
            );
        });

        $this->app->bind(UserManagementServiceInterface::class, function ($app) {
            return new UserManagementService(
                $app->make(CreateUserAction::class),
                $app->make(AssignRoleAction::class),
                $app->make(SendWelcomeEmailAction::class)
            );
        });

        $this->app->bind(ConsumptionCalculationServiceInterface::class, function ($app) {
            return new ConsumptionCalculationService(
                $app->make(\App\Services\MeterReadingService::class)
            );
        });

        // Register enhanced actions with dependencies
        $this->app->bind(ProcessPaymentAction::class, function ($app) {
            return new ProcessPaymentAction();
        });

        $this->app->bind(ValidateMeterReadingAction::class, function ($app) {
            return new ValidateMeterReadingAction(
                $app->make(\App\Services\ServiceValidationEngine::class)
            );
        });

        // Register performance monitoring
        $this->registerPerformanceMonitoring();

        // Register error handling
        $this->registerErrorHandling();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configure service layer logging
        $this->configureServiceLogging();

        // Register service layer middleware
        $this->registerServiceMiddleware();

        // Configure performance thresholds
        $this->configurePerformanceThresholds();
    }

    /**
     * Register performance monitoring services.
     */
    private function registerPerformanceMonitoring(): void
    {
        $this->app->singleton('service.performance.monitor', function ($app) {
            return new class {
                private array $metrics = [];

                public function record(string $service, string $operation, float $duration, array $metadata = []): void
                {
                    $this->metrics[] = [
                        'service' => $service,
                        'operation' => $operation,
                        'duration_ms' => round($duration * 1000, 2),
                        'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                        'timestamp' => microtime(true),
                        'metadata' => $metadata,
                    ];

                    // Log slow operations
                    if ($duration > 1.0) {
                        \Log::warning('Slow service operation detected', [
                            'service' => $service,
                            'operation' => $operation,
                            'duration_seconds' => $duration,
                            'metadata' => $metadata,
                        ]);
                    }
                }

                public function getMetrics(): array
                {
                    return $this->metrics;
                }

                public function clearMetrics(): void
                {
                    $this->metrics = [];
                }
            };
        });
    }

    /**
     * Register error handling services.
     */
    private function registerErrorHandling(): void
    {
        $this->app->singleton('service.error.handler', function ($app) {
            return new class {
                public function handle(\Throwable $e, array $context = []): void
                {
                    $errorContext = array_merge([
                        'exception_class' => get_class($e),
                        'exception_code' => $e->getCode(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                        'timestamp' => now()->toISOString(),
                    ], $context);

                    \Log::error($e->getMessage(), $errorContext);

                    // Trigger notification for critical errors
                    if ($this->isCriticalError($e)) {
                        $this->notifyCriticalError($e, $errorContext);
                    }
                }

                private function isCriticalError(\Throwable $e): bool
                {
                    return $e instanceof \Error ||
                           $e instanceof \PDOException ||
                           str_contains($e->getMessage(), 'memory') ||
                           str_contains($e->getMessage(), 'timeout');
                }

                private function notifyCriticalError(\Throwable $e, array $context): void
                {
                    \Log::critical('Critical service error detected', array_merge($context, [
                        'requires_immediate_attention' => true,
                        'error_classification' => 'critical',
                    ]));
                }
            };
        });
    }

    /**
     * Configure service layer logging.
     */
    private function configureServiceLogging(): void
    {
        config([
            'logging.channels.services' => [
                'driver' => 'daily',
                'path' => storage_path('logs/services.log'),
                'level' => env('LOG_LEVEL', 'debug'),
                'days' => 14,
                'replace_placeholders' => true,
            ],
        ]);
    }

    /**
     * Register service layer middleware.
     */
    private function registerServiceMiddleware(): void
    {
        // This would register middleware for service layer operations
        // such as performance monitoring, error handling, etc.
    }

    /**
     * Configure performance thresholds.
     */
    private function configurePerformanceThresholds(): void
    {
        config([
            'services.performance.thresholds' => [
                'slow_operation_seconds' => 1.0,
                'memory_warning_mb' => 128,
                'query_warning_count' => 50,
            ],
        ]);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return [
            BillingServiceInterface::class,
            UserManagementServiceInterface::class,
            ConsumptionCalculationServiceInterface::class,
            ProcessPaymentAction::class,
            ValidateMeterReadingAction::class,
            'service.performance.monitor',
            'service.error.handler',
        ];
    }
}