<?php

declare(strict_types=1);
use App\Contracts\CircuitBreakerInterface;
use App\Contracts\ServiceRegistration\ErrorHandlingStrategyInterface;
use App\Contracts\ServiceRegistration\PolicyRegistryInterface;
use App\Contracts\SubscriptionCheckerInterface;
use App\Contracts\TenantAuditLoggerInterface;
use App\Contracts\TenantAuthorizationServiceInterface;
use App\Contracts\TenantContextInterface;
use App\Repositories\Eloquent\EloquentTenantRepository;
use App\Repositories\TenantRepositoryInterface;
use App\Services\Integration\CircuitBreakerService;
use App\Services\ServiceRegistration\RegistrationErrorHandler;
use App\Services\SubscriptionChecker;
use App\Services\TenantAuditLogger;
use App\Services\TenantAuthorizationService;
use App\Services\TenantBoundaryService;
use App\Services\TenantContext;
use App\Support\ServiceRegistration\PolicyRegistry;

return [
    /*
    |--------------------------------------------------------------------------
    | Service Registration Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for service registration behavior, error handling,
    | and monitoring in the application.
    |
    */

    'error_handling' => [
        /*
        |--------------------------------------------------------------------------
        | Environment-Specific Behavior
        |--------------------------------------------------------------------------
        |
        | Configure how errors are handled in different environments.
        |
        */
        'fail_fast_environments' => ['local', 'testing'],
        'detailed_logging_environments' => ['local', 'testing'],
        'production_alert_environments' => ['production', 'staging'],
    ],

    'monitoring' => [
        /*
        |--------------------------------------------------------------------------
        | Performance Monitoring
        |--------------------------------------------------------------------------
        |
        | Configuration for monitoring service registration performance.
        |
        */
        'enabled' => env('SERVICE_REGISTRATION_MONITORING', true),
        'record_metrics' => env('SERVICE_REGISTRATION_RECORD_METRICS', true),
        'cache_ttl' => env('SERVICE_REGISTRATION_CACHE_TTL', 3600),
    ],

    'logging' => [
        /*
        |--------------------------------------------------------------------------
        | Logging Configuration
        |--------------------------------------------------------------------------
        |
        | Configure logging behavior for service registration.
        |
        */
        'contexts' => [
            'app_boot' => 'application_boot',
            'policy_registration' => 'policy_registration',
            'gate_registration' => 'gate_registration',
            'service_registration' => 'service_registration',
        ],

        'log_levels' => [
            'success' => 'info',
            'warning' => 'warning',
            'error' => 'error',
            'critical' => 'critical',
        ],
    ],

    'core_services' => [
        /*
        |--------------------------------------------------------------------------
        | Core Service Definitions
        |--------------------------------------------------------------------------
        |
        | Define which services should be registered as singletons.
        |
        */
        'singletons' => [
            TenantContext::class,
            TenantBoundaryService::class,
            EloquentTenantRepository::class,
            TenantAuditLogger::class,
            TenantAuthorizationService::class,
        ],

        'bindings' => [
            PolicyRegistryInterface::class => PolicyRegistry::class,
            ErrorHandlingStrategyInterface::class => RegistrationErrorHandler::class,
            CircuitBreakerInterface::class => CircuitBreakerService::class,
            SubscriptionCheckerInterface::class => SubscriptionChecker::class,
            TenantRepositoryInterface::class => EloquentTenantRepository::class,
            TenantAuditLoggerInterface::class => TenantAuditLogger::class,
            TenantAuthorizationServiceInterface::class => TenantAuthorizationService::class,
            TenantContextInterface::class => TenantContext::class,
        ],
    ],
];
