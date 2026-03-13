<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains security-related configuration options for the
    | application. These settings help protect against common vulnerabilities.
    |
    */

    'policy_registration' => [
        /*
        |--------------------------------------------------------------------------
        | Policy Registration Security
        |--------------------------------------------------------------------------
        |
        | Controls who can register policies and gates in the application.
        |
        */
        'allowed_environments' => ['local', 'testing'],
        'require_super_admin' => env('POLICY_REGISTRATION_REQUIRE_SUPER_ADMIN', true),
        'rate_limit' => [
            'max_attempts' => 5,
            'decay_minutes' => 5,
        ],
        'cache_ttl' => 3600, // 1 hour
    ],

    'logging' => [
        /*
        |--------------------------------------------------------------------------
        | Security Logging
        |--------------------------------------------------------------------------
        |
        | Configuration for security-related logging and monitoring.
        |
        */
        'redact_sensitive_data' => env('LOG_REDACT_SENSITIVE', true),
        'hash_pii' => env('LOG_HASH_PII', true),
        'security_channel' => env('SECURITY_LOG_CHANNEL', 'security'),
        'alert_on_violations' => env('ALERT_ON_SECURITY_VIOLATIONS', true),
    ],

    'headers' => [
        /*
        |--------------------------------------------------------------------------
        | Security Headers
        |--------------------------------------------------------------------------
        |
        | HTTP security headers to protect against common attacks.
        |
        */
        'csp' => [
            'enabled' => env('CSP_ENABLED', true),
            'report_only' => env('CSP_REPORT_ONLY', false),
            'report_uri' => env('CSP_REPORT_URI', '/csp-report'),
        ],
        'hsts' => [
            'enabled' => env('HSTS_ENABLED', true),
            'max_age' => env('HSTS_MAX_AGE', 31536000), // 1 year
            'include_subdomains' => env('HSTS_INCLUDE_SUBDOMAINS', true),
        ],
        'x_frame_options' => env('X_FRAME_OPTIONS', 'DENY'),
        'x_content_type_options' => env('X_CONTENT_TYPE_OPTIONS', 'nosniff'),
        'referrer_policy' => env('REFERRER_POLICY', 'strict-origin-when-cross-origin'),
    ],

    'rate_limiting' => [
        /*
        |--------------------------------------------------------------------------
        | Rate Limiting
        |--------------------------------------------------------------------------
        |
        | Rate limiting configuration for various endpoints.
        |
        */
        'translation_requests' => [
            'max_attempts' => 100,
            'decay_minutes' => 1,
        ],
        'policy_registration' => [
            'max_attempts' => 5,
            'decay_minutes' => 5,
        ],
        'api_requests' => [
            'max_attempts' => 60,
            'decay_minutes' => 1,
        ],
    ],

    'validation' => [
        /*
        |--------------------------------------------------------------------------
        | Input Validation
        |--------------------------------------------------------------------------
        |
        | Validation rules and patterns for security-sensitive inputs.
        |
        */
        'locale_pattern' => '/^[a-z]{2}(_[A-Z]{2})?$/',
        'translation_key_pattern' => '/^[a-zA-Z0-9._-]+$/',
        'max_translation_key_length' => 255,
        'suspicious_patterns' => [
            '/\.\.\//i',           // Path traversal
            '/<script/i',          // XSS
            '/javascript:/i',      // XSS
            '/on\w+\s*=/i',       // Event handlers
            '/union\s+select/i',   // SQL injection
            '/drop\s+table/i',     // SQL injection
            '/exec\s*\(/i',       // Code execution
            '/eval\s*\(/i',       // Code execution
            '/system\s*\(/i',     // System calls
        ],
    ],

    'monitoring' => [
        /*
        |--------------------------------------------------------------------------
        | Security Monitoring
        |--------------------------------------------------------------------------
        |
        | Configuration for security monitoring and alerting.
        |
        */
        'enabled' => env('SECURITY_MONITORING_ENABLED', true),
        'alert_channels' => ['slack', 'email'],
        'thresholds' => [
            'failed_logins' => 10,
            'policy_registration_failures' => 5,
            'suspicious_requests' => 20,
        ],
    ],
];