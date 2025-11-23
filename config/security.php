<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Security Headers Configuration
    |--------------------------------------------------------------------------
    |
    | Configure security headers for the application.
    | These headers help protect against common web vulnerabilities.
    |
    */

    'headers' => [
        /**
         * X-Frame-Options: Prevents clickjacking attacks
         * Options: DENY, SAMEORIGIN, ALLOW-FROM uri
         */
        'x-frame-options' => env('SECURITY_X_FRAME_OPTIONS', 'SAMEORIGIN'),

        /**
         * X-Content-Type-Options: Prevents MIME type sniffing
         * Should always be 'nosniff'
         */
        'x-content-type-options' => 'nosniff',

        /**
         * X-XSS-Protection: Legacy XSS protection (for older browsers)
         * Modern browsers use CSP instead
         */
        'x-xss-protection' => '1; mode=block',

        /**
         * Referrer-Policy: Controls referrer information
         * Options: no-referrer, no-referrer-when-downgrade, origin, origin-when-cross-origin,
         *          same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
         */
        'referrer-policy' => env('SECURITY_REFERRER_POLICY', 'strict-origin-when-cross-origin'),

        /**
         * Permissions-Policy: Controls browser features
         * Replaces Feature-Policy
         */
        'permissions-policy' => env('SECURITY_PERMISSIONS_POLICY', 'geolocation=(), microphone=(), camera=()'),

        /**
         * Strict-Transport-Security (HSTS): Forces HTTPS
         * Only enable in production with valid SSL certificate
         */
        'strict-transport-security' => env('SECURITY_HSTS_ENABLED', false)
            ? 'max-age=31536000; includeSubDomains; preload'
            : null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy (CSP)
    |--------------------------------------------------------------------------
    |
    | Configure Content Security Policy directives.
    | CSP helps prevent XSS, clickjacking, and other code injection attacks.
    |
    */

    'csp' => [
        /**
         * Enable/disable CSP
         */
        'enabled' => env('SECURITY_CSP_ENABLED', true),

        /**
         * Report-only mode (for testing)
         * Set to true to report violations without blocking
         */
        'report_only' => env('SECURITY_CSP_REPORT_ONLY', false),

        /**
         * CSP directives
         */
        'directives' => [
            'default-src' => ["'self'"],
            'script-src' => [
                "'self'",
                "'unsafe-inline'", // Required for Filament/Alpine
                "'unsafe-eval'", // Required for Alpine
                'cdn.jsdelivr.net', // Tailwind/Alpine CDN
                'unpkg.com', // Alpine CDN fallback
            ],
            'style-src' => [
                "'self'",
                "'unsafe-inline'", // Required for Tailwind
                'cdn.jsdelivr.net',
                'fonts.googleapis.com',
            ],
            'img-src' => [
                "'self'",
                'data:', // For inline images
                'blob:', // For generated images
            ],
            'font-src' => [
                "'self'",
                'fonts.gstatic.com',
                'data:',
            ],
            'connect-src' => [
                "'self'",
            ],
            'frame-ancestors' => ["'self'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
        ],

        /**
         * CSP violation reporting endpoint
         */
        'report_uri' => env('SECURITY_CSP_REPORT_URI'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for sensitive operations.
    | See config/throttle.php for detailed throttle settings.
    |
    */

    'rate_limiting' => [
        /**
         * Invoice finalization rate limit
         */
        'invoice_finalization' => [
            'attempts' => (int) env('SECURITY_INVOICE_FINALIZATION_ATTEMPTS', 10),
            'decay_minutes' => (int) env('SECURITY_INVOICE_FINALIZATION_DECAY', 1),
        ],

        /**
         * Login attempts rate limit
         */
        'login' => [
            'attempts' => (int) env('SECURITY_LOGIN_ATTEMPTS', 5),
            'decay_minutes' => (int) env('SECURITY_LOGIN_DECAY', 1),
        ],

        /**
         * Password reset rate limit
         */
        'password_reset' => [
            'attempts' => (int) env('SECURITY_PASSWORD_RESET_ATTEMPTS', 3),
            'decay_minutes' => (int) env('SECURITY_PASSWORD_RESET_DECAY', 1),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure audit logging for security-sensitive operations.
    |
    */

    'audit_logging' => [
        /**
         * Enable/disable audit logging
         */
        'enabled' => env('SECURITY_AUDIT_LOGGING_ENABLED', true),

        /**
         * Log channel for audit logs
         */
        'channel' => env('SECURITY_AUDIT_LOG_CHANNEL', 'stack'),

        /**
         * Events to audit log
         */
        'events' => [
            'invoice_finalization_attempt',
            'invoice_finalization_success',
            'invoice_finalization_failure',
            'login_attempt',
            'login_success',
            'login_failure',
            'password_reset_request',
            'password_reset_success',
            'tenant_switch',
            'user_created',
            'user_deleted',
            'role_changed',
        ],

        /**
         * PII fields to redact in logs
         */
        'redact_fields' => [
            'password',
            'password_confirmation',
            'token',
            'api_token',
            'remember_token',
            'credit_card',
            'ssn',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security Configuration
    |--------------------------------------------------------------------------
    |
    | Additional session security settings.
    | See config/session.php for core session configuration.
    |
    */

    'session' => [
        /**
         * Regenerate session ID on login
         */
        'regenerate_on_login' => true,

        /**
         * Session timeout warning (minutes before expiry)
         */
        'timeout_warning' => (int) env('SECURITY_SESSION_TIMEOUT_WARNING', 5),

        /**
         * Require password confirmation for sensitive actions
         */
        'password_confirmation_timeout' => (int) env('AUTH_PASSWORD_TIMEOUT', 10800),
    ],

    /*
    |--------------------------------------------------------------------------
    | Demo Mode Configuration
    |--------------------------------------------------------------------------
    |
    | Configure demo mode restrictions for testing environments.
    |
    */

    'demo_mode' => [
        /**
         * Enable/disable demo mode
         */
        'enabled' => env('SECURITY_DEMO_MODE_ENABLED', false),

        /**
         * Disable demo accounts in production
         */
        'disable_in_production' => true,

        /**
         * Demo user email patterns
         */
        'email_patterns' => [
            'demo+*@example.com',
            'test+*@example.com',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption Configuration
    |--------------------------------------------------------------------------
    |
    | Configure encryption settings for sensitive data.
    |
    */

    'encryption' => [
        /**
         * Encrypt session data
         */
        'encrypt_session' => env('SESSION_ENCRYPT', false),

        /**
         * Encrypt cookies
         */
        'encrypt_cookies' => true,

        /**
         * Fields to encrypt in database
         */
        'encrypted_fields' => [
            // Add fields that should be encrypted at rest
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | CORS Configuration
    |--------------------------------------------------------------------------
    |
    | Configure Cross-Origin Resource Sharing.
    | See config/cors.php for detailed CORS settings.
    |
    */

    'cors' => [
        /**
         * Default-deny policy
         */
        'default_deny' => true,

        /**
         * Allowed origins (whitelist)
         */
        'allowed_origins' => [
            env('APP_URL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Production Security Checklist
    |--------------------------------------------------------------------------
    |
    | Verify these settings before deploying to production.
    |
    */

    'production_checklist' => [
        'APP_DEBUG' => false,
        'APP_ENV' => 'production',
        'SESSION_SECURE_COOKIE' => true,
        'SECURITY_HSTS_ENABLED' => true,
        'SECURITY_CSP_ENABLED' => true,
        'SECURITY_AUDIT_LOGGING_ENABLED' => true,
        'SECURITY_DEMO_MODE_ENABLED' => false,
    ],

];
