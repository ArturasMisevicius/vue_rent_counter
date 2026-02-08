<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Superadmin Dashboard Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for the superadmin dashboard enhancement,
    | including rate limiting, security, and performance settings.
    |
    */

    'rate_limits' => [
        /*
        |--------------------------------------------------------------------------
        | Dashboard API Rate Limits
        |--------------------------------------------------------------------------
        |
        | Rate limits for dashboard API endpoints to prevent abuse and ensure
        | system stability. Limits are per superadmin user per time window.
        |
        */
        'dashboard' => [
            'max_attempts' => env('SUPERADMIN_DASHBOARD_RATE_LIMIT', 60),
            'decay_minutes' => env('SUPERADMIN_DASHBOARD_RATE_DECAY', 1),
        ],

        /*
        |--------------------------------------------------------------------------
        | Bulk Operations Rate Limits
        |--------------------------------------------------------------------------
        |
        | Rate limits for bulk operations (suspend, reactivate, plan changes)
        | to prevent system overload and ensure data integrity.
        |
        */
        'bulk_operations' => [
            'max_attempts' => env('SUPERADMIN_BULK_RATE_LIMIT', 10),
            'decay_minutes' => env('SUPERADMIN_BULK_RATE_DECAY', 1),
        ],

        /*
        |--------------------------------------------------------------------------
        | Export Operations Rate Limits
        |--------------------------------------------------------------------------
        |
        | Rate limits for data export operations to prevent resource exhaustion
        | and ensure fair usage across superadmin users.
        |
        */
        'exports' => [
            'max_attempts' => env('SUPERADMIN_EXPORT_RATE_LIMIT', 5),
            'decay_minutes' => env('SUPERADMIN_EXPORT_RATE_DECAY', 1),
        ],

        /*
        |--------------------------------------------------------------------------
        | Password Reset Rate Limits
        |--------------------------------------------------------------------------
        |
        | Rate limits for password reset operations to prevent abuse and
        | protect user accounts from unauthorized access attempts.
        |
        */
        'password_resets' => [
            'max_attempts' => env('SUPERADMIN_PASSWORD_RESET_RATE_LIMIT', 3),
            'decay_minutes' => env('SUPERADMIN_PASSWORD_RESET_RATE_DECAY', 60),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security configuration for superadmin operations including audit
    | logging, session management, and access controls.
    |
    */
    'security' => [
        'audit_log_retention_days' => env('SUPERADMIN_AUDIT_RETENTION', 90),
        'impersonation_timeout_minutes' => env('SUPERADMIN_IMPERSONATION_TIMEOUT', 30),
        'require_confirmation_for_sensitive_operations' => env('SUPERADMIN_REQUIRE_CONFIRMATION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Performance optimization settings for dashboard widgets, caching,
    | and background processing.
    |
    */
    'performance' => [
        'widget_cache_ttl_seconds' => env('SUPERADMIN_WIDGET_CACHE_TTL', 60),
        'dashboard_metrics_cache_ttl_seconds' => env('SUPERADMIN_METRICS_CACHE_TTL', 300),
        'system_health_cache_ttl_seconds' => env('SUPERADMIN_HEALTH_CACHE_TTL', 30),
        'max_bulk_operation_size' => env('SUPERADMIN_MAX_BULK_SIZE', 100),
    ],
];