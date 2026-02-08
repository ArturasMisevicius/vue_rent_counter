<?php

declare(strict_types=1);

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
            \App\Services\TenantContext::class,
            \App\Services\TenantBoundaryService::class,
        ],
        
        'bindings' => [
            \App\Contracts\ServiceRegistration\PolicyRegistryInterface::class => \App\Support\ServiceRegistration\PolicyRegistry::class,
            \App\Contracts\ServiceRegistration\ErrorHandlingStrategyInterface::class => \App\Services\ServiceRegistration\RegistrationErrorHandler::class,
            \App\Contracts\SubscriptionCheckerInterface::class => \App\Services\SubscriptionChecker::class,
        ],
    ],
];
