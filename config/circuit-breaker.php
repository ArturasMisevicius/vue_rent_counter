<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the circuit breaker service that prevents cascading
    | failures by monitoring service calls and temporarily blocking requests
    | when failures exceed configured thresholds.
    |
    */

    'default' => [
        /*
        |--------------------------------------------------------------------------
        | Failure Threshold
        |--------------------------------------------------------------------------
        |
        | Number of consecutive failures before the circuit breaker opens.
        | Once this threshold is reached, the circuit will open and block
        | subsequent requests for the recovery timeout period.
        |
        */
        'failure_threshold' => env('CIRCUIT_BREAKER_FAILURE_THRESHOLD', 5),

        /*
        |--------------------------------------------------------------------------
        | Recovery Timeout (seconds)
        |--------------------------------------------------------------------------
        |
        | Time in seconds to wait before attempting to reset the circuit breaker
        | from OPEN to HALF_OPEN state. During this time, all requests are blocked.
        |
        */
        'recovery_timeout' => env('CIRCUIT_BREAKER_RECOVERY_TIMEOUT', 60),

        /*
        |--------------------------------------------------------------------------
        | Success Threshold
        |--------------------------------------------------------------------------
        |
        | Number of consecutive successful calls required in HALF_OPEN state
        | before the circuit breaker resets to CLOSED state.
        |
        */
        'success_threshold' => env('CIRCUIT_BREAKER_SUCCESS_THRESHOLD', 3),

        /*
        |--------------------------------------------------------------------------
        | Cache TTL (minutes)
        |--------------------------------------------------------------------------
        |
        | Time-to-live for circuit breaker state data in cache.
        | This should be longer than the recovery timeout.
        |
        */
        'cache_ttl' => env('CIRCUIT_BREAKER_CACHE_TTL', 60),

        /*
        |--------------------------------------------------------------------------
        | Service Registry TTL (days)
        |--------------------------------------------------------------------------
        |
        | Time-to-live for the service registry that tracks monitored services.
        |
        */
        'registry_ttl' => env('CIRCUIT_BREAKER_REGISTRY_TTL', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Service-Specific Configurations
    |--------------------------------------------------------------------------
    |
    | Override default settings for specific services. Each service can have
    | its own thresholds and timeouts based on its characteristics.
    |
    */
    'services' => [
        'external-api' => [
            'failure_threshold' => 3,
            'recovery_timeout' => 30,
            'success_threshold' => 2,
        ],
        'payment-gateway' => [
            'failure_threshold' => 2,
            'recovery_timeout' => 120,
            'success_threshold' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure logging behavior for circuit breaker events.
    |
    */
    'logging' => [
        'enabled' => env('CIRCUIT_BREAKER_LOGGING', true),
        'channel' => env('CIRCUIT_BREAKER_LOG_CHANNEL', 'default'),
        'level' => env('CIRCUIT_BREAKER_LOG_LEVEL', 'info'),
    ],
];