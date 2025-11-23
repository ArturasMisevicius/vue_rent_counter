<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Security Headers
    |--------------------------------------------------------------------------
    |
    | Configure security headers for HTTP responses. These headers help
    | protect against common web vulnerabilities like XSS, clickjacking,
    | and MIME-type sniffing attacks.
    |
    */

    'headers' => [
        'x-frame-options' => env('SECURITY_X_FRAME_OPTIONS', 'DENY'),
        'x-content-type-options' => env('SECURITY_X_CONTENT_TYPE_OPTIONS', 'nosniff'),
        'x-xss-protection' => env('SECURITY_X_XSS_PROTECTION', '1; mode=block'),
        'referrer-policy' => env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        'permissions-policy' => env('SECURITY_PERMISSIONS_POLICY', 'geolocation=(), microphone=(), camera=()'),
        'strict-transport-security' => env('SECURITY_HSTS', 'max-age=31536000; includeSubDomains; preload'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    |
    | Define the Content Security Policy (CSP) for your application.
    | This helps prevent XSS attacks by controlling which resources
    | can be loaded and executed.
    |
    */

    'csp' => [
        'enabled' => env('SECURITY_CSP_ENABLED', true),
        'report_only' => env('SECURITY_CSP_REPORT_ONLY', false),
        'report_uri' => env('SECURITY_CSP_REPORT_URI'),
        
        'directives' => [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'unsafe-inline'", "'unsafe-eval'", 'cdn.jsdelivr.net'],
            'style-src' => ["'self'", "'unsafe-inline'", 'cdn.jsdelivr.net'],
            'img-src' => ["'self'", 'data:', 'https:'],
            'font-src' => ["'self'", 'data:'],
            'connect-src' => ["'self'"],
            'frame-ancestors' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for admin panel access and other sensitive
    | endpoints to prevent brute force attacks.
    |
    */

    'rate_limiting' => [
        'admin_access' => [
            'max_attempts' => env('SECURITY_ADMIN_MAX_ATTEMPTS', 10),
            'decay_seconds' => env('SECURITY_ADMIN_DECAY_SECONDS', 300),
        ],
        'api' => [
            'max_attempts' => env('SECURITY_API_MAX_ATTEMPTS', 60),
            'decay_seconds' => env('SECURITY_API_DECAY_SECONDS', 60),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    |
    | Configure security audit logging for authorization failures,
    | suspicious activities, and security events.
    |
    */

    'audit_logging' => [
        'enabled' => env('SECURITY_AUDIT_LOGGING', true),
        'channel' => env('SECURITY_AUDIT_CHANNEL', 'stack'),
        'mask_pii' => env('SECURITY_MASK_PII_IN_LOGS', false),
        'retention_days' => env('SECURITY_LOG_RETENTION_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | PII Protection
    |--------------------------------------------------------------------------
    |
    | Configure how personally identifiable information (PII) is handled
    | in logs and error messages for GDPR compliance.
    |
    */

    'pii_protection' => [
        'mask_email' => env('SECURITY_MASK_EMAIL', false),
        'mask_ip' => env('SECURITY_MASK_IP', false),
        'redact_user_agent' => env('SECURITY_REDACT_USER_AGENT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    |
    | Additional session security settings beyond Laravel's defaults.
    |
    */

    'session' => [
        'regenerate_on_login' => env('SECURITY_REGENERATE_SESSION_ON_LOGIN', true),
        'regenerate_on_privilege_change' => env('SECURITY_REGENERATE_ON_PRIVILEGE_CHANGE', true),
        'absolute_timeout' => env('SECURITY_SESSION_ABSOLUTE_TIMEOUT', 28800), // 8 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Demo Mode
    |--------------------------------------------------------------------------
    |
    | Enable demo mode to prevent mutations and protect demo data.
    |
    */

    'demo_mode' => [
        'enabled' => env('SECURITY_DEMO_MODE', false),
        'allow_read' => env('SECURITY_DEMO_ALLOW_READ', true),
        'allow_write' => env('SECURITY_DEMO_ALLOW_WRITE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring & Alerting
    |--------------------------------------------------------------------------
    |
    | Configure external monitoring services for security events.
    |
    */

    'monitoring' => [
        'sentry_enabled' => env('SENTRY_LARAVEL_DSN') !== null,
        'alert_on_authorization_failure' => env('SECURITY_ALERT_ON_AUTH_FAILURE', false),
        'alert_threshold' => env('SECURITY_ALERT_THRESHOLD', 10),
    ],

];
