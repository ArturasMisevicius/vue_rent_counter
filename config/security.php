<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    |
    | Configure security headers for the application.
    | These headers are applied via the SecurityHeaders middleware.
    |
    */

    'headers' => [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
        
        // Strict-Transport-Security (HSTS)
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
        
        // Content Security Policy
        'Content-Security-Policy' => implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.tailwindcss.com cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' cdn.tailwindcss.com fonts.googleapis.com",
            "font-src 'self' fonts.gstatic.com",
            "img-src 'self' data: https:",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ]),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    |
    | Configure audit logging for security-sensitive operations.
    |
    */

    'audit' => [
        'enabled' => env('SECURITY_AUDIT_ENABLED', true),
        
        'channels' => [
            'security' => 'security',
            'audit' => 'audit',
        ],
        
        'retention_days' => [
            'security' => 90,
            'audit' => 365,
        ],
        
        'events' => [
            'scope_bypass' => true,
            'validation_failure' => true,
            'superadmin_access' => true,
            'missing_tenant_context' => true,
            'tenant_context_switch' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | HierarchicalScope Security
    |--------------------------------------------------------------------------
    |
    | Security configuration for the HierarchicalScope component.
    |
    */

    'hierarchical_scope' => [
        // Enable strict validation
        'strict_validation' => env('HIERARCHICAL_SCOPE_STRICT_VALIDATION', true),
        
        // Maximum allowed tenant_id value
        'max_tenant_id' => 2147483647, // INT max
        
        // Maximum allowed property_id value
        'max_property_id' => 2147483647, // INT max
        
        // Cache configuration
        'cache' => [
            'enabled' => env('HIERARCHICAL_SCOPE_CACHE_ENABLED', true),
            'ttl' => env('HIERARCHICAL_SCOPE_CACHE_TTL', 86400), // 24 hours
            'prefix' => 'hierarchical_scope:columns:',
        ],
        
        // Logging configuration
        'logging' => [
            'scope_bypass' => env('LOG_SCOPE_BYPASS', true),
            'validation_failures' => env('LOG_VALIDATION_FAILURES', true),
            'superadmin_access' => env('LOG_SUPERADMIN_ACCESS', true),
            'missing_context' => env('LOG_MISSING_CONTEXT', true),
        ],
        
        // Rate limiting
        'rate_limiting' => [
            'enabled' => env('HIERARCHICAL_SCOPE_RATE_LIMITING', true),
            'bypass_attempts_per_minute' => 10,
            'validation_failures_per_minute' => 50,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | PII Redaction
    |--------------------------------------------------------------------------
    |
    | Configure PII redaction in logs.
    |
    */

    'pii_redaction' => [
        'enabled' => env('PII_REDACTION_ENABLED', true),
        
        'fields' => [
            'email',
            'password',
            'token',
            'secret',
            'api_key',
            'credit_card',
            'ssn',
        ],
        
        'replacement' => '[REDACTED]',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for security-sensitive operations.
    |
    */

    'rate_limiting' => [
        'enabled' => env('RATE_LIMITING_ENABLED', true),
        
        'limits' => [
            'api' => [
                'per_minute' => 60,
                'per_hour' => 1000,
            ],
            
            'login' => [
                'per_minute' => 5,
                'per_hour' => 20,
            ],
            
            'scope_bypass' => [
                'per_minute' => 10,
                'per_hour' => 100,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IP Blocking
    |--------------------------------------------------------------------------
    |
    | Configure automatic IP blocking for suspicious activity.
    |
    */

    'ip_blocking' => [
        'enabled' => env('IP_BLOCKING_ENABLED', false),
        
        'thresholds' => [
            'validation_failures' => 100, // Block after 100 failures
            'scope_bypass_attempts' => 50, // Block after 50 bypass attempts
        ],
        
        'block_duration' => 3600, // 1 hour in seconds
        
        'whitelist' => explode(',', env('IP_WHITELIST', '')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Monitoring
    |--------------------------------------------------------------------------
    |
    | Configure security monitoring and alerting.
    |
    */

    'monitoring' => [
        'enabled' => env('SECURITY_MONITORING_ENABLED', true),
        
        'alert_thresholds' => [
            'scope_bypass_per_5min' => 10,
            'validation_failures_per_hour' => 50,
            'missing_context_per_10min' => 5,
        ],
        
        'alert_channels' => [
            'email' => env('SECURITY_ALERT_EMAIL', 'security@example.com'),
            'slack' => env('SECURITY_ALERT_SLACK_WEBHOOK'),
            'pagerduty' => env('SECURITY_ALERT_PAGERDUTY_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption
    |--------------------------------------------------------------------------
    |
    | Configure encryption settings.
    |
    */

    'encryption' => [
        // Encrypt sensitive data at rest
        'at_rest' => env('ENCRYPT_AT_REST', true),
        
        // Enforce HTTPS
        'force_https' => env('FORCE_HTTPS', true),
        
        // TLS version
        'min_tls_version' => '1.2',
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    |
    | Additional session security settings.
    |
    */

    'session' => [
        // Regenerate session ID on login
        'regenerate_on_login' => true,
        
        // Session timeout (minutes)
        'timeout' => env('SESSION_TIMEOUT', 120),
        
        // Idle timeout (minutes)
        'idle_timeout' => env('SESSION_IDLE_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | CORS Configuration
    |--------------------------------------------------------------------------
    |
    | Configure CORS settings for API endpoints.
    |
    */

    'cors' => [
        'enabled' => env('CORS_ENABLED', false),
        
        'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', '')),
        
        'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
        
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
        
        'exposed_headers' => [],
        
        'max_age' => 3600,
        
        'supports_credentials' => true,
    ],

];
