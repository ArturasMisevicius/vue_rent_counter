<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Token Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the custom API token management system.
    |
    */

    'cache' => [
        'ttl' => env('API_TOKEN_CACHE_TTL', 900), // 15 minutes
        'prefix' => env('API_TOKEN_CACHE_PREFIX', 'api_tokens:'),
    ],

    'monitoring' => [
        'enabled' => env('API_TOKEN_MONITORING_ENABLED', true),
        'suspicious_threshold' => env('API_TOKEN_SUSPICIOUS_THRESHOLD', 10),
        'alert_channels' => ['log'], // Can extend to include 'slack', 'email', etc.
    ],

    'pruning' => [
        'enabled' => env('API_TOKEN_PRUNING_ENABLED', true),
        'hours_after_expiration' => env('API_TOKEN_PRUNE_HOURS', 24),
        'schedule' => env('API_TOKEN_PRUNE_SCHEDULE', 'daily'),
    ],

    'security' => [
        'require_active_user' => env('API_TOKEN_REQUIRE_ACTIVE_USER', true),
        'check_suspension' => env('API_TOKEN_CHECK_SUSPENSION', true),
        'log_usage' => env('API_TOKEN_LOG_USAGE', true),
    ],
];