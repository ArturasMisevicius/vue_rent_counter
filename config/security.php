<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Security Headers Configuration
    |--------------------------------------------------------------------------
    |
    | Base security headers applied to all responses. CSP is handled separately
    | by the SecurityHeaderFactory based on context.
    |
    */
    'headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for monitoring and optimizing security header performance.
    |
    */
    'performance' => [
        'enabled' => env('SECURITY_PERFORMANCE_MONITORING', true),
        'thresholds' => [
            'warning_ms' => env('SECURITY_WARNING_THRESHOLD_MS', 15),
            'error_ms' => env('SECURITY_ERROR_THRESHOLD_MS', 50),
        ],
        'log_throttle_seconds' => env('SECURITY_LOG_THROTTLE_SECONDS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | CSP Configuration
    |--------------------------------------------------------------------------
    |
    | Content Security Policy settings and reporting configuration.
    |
    */
    'csp' => [
        'nonce_enabled' => env('CSP_NONCE_ENABLED', true),
        'report_uri' => env('CSP_REPORT_URI'),
        'report_only' => env('CSP_REPORT_ONLY', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment-Specific Settings
    |--------------------------------------------------------------------------
    |
    | Different security policies for different environments.
    |
    */
    'environments' => [
        'production' => [
            'strict_transport_security' => true,
            'cross_origin_policies' => true,
            'permissions_policy' => true,
        ],
        'development' => [
            'allow_localhost' => true,
            'allow_hmr' => true,
            'debug_headers' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for caching security headers and CSP templates.
    |
    */
    'cache' => [
        'enabled' => env('SECURITY_CACHE_ENABLED', true),
        'ttl' => env('SECURITY_CACHE_TTL', 3600), // 1 hour
        'max_templates' => env('SECURITY_CACHE_MAX_TEMPLATES', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | MCP Integration Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for Model Context Protocol server integration.
    | Enhanced with security controls and validation.
    |
    */
    'mcp' => [
        'analytics_enabled' => env('SECURITY_MCP_ANALYTICS_ENABLED', true),
        'compliance_enabled' => env('SECURITY_MCP_COMPLIANCE_ENABLED', true),
        'performance_enabled' => env('SECURITY_MCP_PERFORMANCE_ENABLED', true),
        'incident_response_enabled' => env('SECURITY_MCP_INCIDENT_RESPONSE_ENABLED', true),
        
        'fallback_on_failure' => env('SECURITY_MCP_FALLBACK_ENABLED', true),
        'timeout_seconds' => env('SECURITY_MCP_TIMEOUT', 5),
        'retry_attempts' => env('SECURITY_MCP_RETRY_ATTEMPTS', 3),
        
        // Enhanced security controls
        'require_authentication' => env('SECURITY_MCP_REQUIRE_AUTH', true),
        'validate_tenant_access' => env('SECURITY_MCP_VALIDATE_TENANT', true),
        'encrypt_sensitive_data' => env('SECURITY_MCP_ENCRYPT_DATA', true),
        'audit_all_calls' => env('SECURITY_MCP_AUDIT_CALLS', true),
        
        // Rate limiting for MCP calls
        'rate_limit' => [
            'enabled' => env('SECURITY_MCP_RATE_LIMIT_ENABLED', true),
            'max_calls_per_minute' => env('SECURITY_MCP_MAX_CALLS_PER_MINUTE', 100),
            'max_calls_per_hour' => env('SECURITY_MCP_MAX_CALLS_PER_HOUR', 1000),
        ],
        
        // Data retention and privacy
        'data_retention_days' => env('SECURITY_MCP_DATA_RETENTION_DAYS', 90),
        'anonymize_pii' => env('SECURITY_MCP_ANONYMIZE_PII', true),
        'redact_sensitive_fields' => env('SECURITY_MCP_REDACT_SENSITIVE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for security analytics and violation tracking.
    | Enhanced with privacy and security controls.
    |
    */
    'analytics' => [
        'violation_retention_days' => env('SECURITY_VIOLATION_RETENTION_DAYS', 90),
        'metrics_retention_days' => env('SECURITY_METRICS_RETENTION_DAYS', 365),
        'real_time_enabled' => env('SECURITY_REAL_TIME_ENABLED', true),
        'batch_size' => env('SECURITY_ANALYTICS_BATCH_SIZE', 1000),
        
        // Enhanced privacy controls
        'anonymize_ips' => env('SECURITY_ANONYMIZE_IPS', true),
        'hash_user_agents' => env('SECURITY_HASH_USER_AGENTS', true),
        'encrypt_sensitive_data' => env('SECURITY_ENCRYPT_SENSITIVE_DATA', true),
        'redact_pii' => env('SECURITY_REDACT_PII', true),
        
        // Rate limiting for analytics
        'rate_limiting' => [
            'enabled' => env('SECURITY_ANALYTICS_RATE_LIMIT_ENABLED', true),
            'max_violations_per_ip_per_minute' => env('SECURITY_MAX_VIOLATIONS_PER_IP_PER_MINUTE', 50),
            'max_analytics_requests_per_user_per_minute' => env('SECURITY_MAX_ANALYTICS_REQUESTS_PER_USER_PER_MINUTE', 60),
        ],
        
        'anomaly_detection' => [
            'enabled' => env('SECURITY_ANOMALY_DETECTION_ENABLED', true),
            'sensitivity' => env('SECURITY_ANOMALY_SENSITIVITY', 'medium'),
            'window_minutes' => env('SECURITY_ANOMALY_WINDOW', 60),
            'auto_block_threshold' => env('SECURITY_AUTO_BLOCK_THRESHOLD', 10),
            'notification_threshold' => env('SECURITY_NOTIFICATION_THRESHOLD', 5),
        ],
        
        // Audit and compliance
        'audit_trail' => [
            'enabled' => env('SECURITY_AUDIT_TRAIL_ENABLED', true),
            'log_all_access' => env('SECURITY_LOG_ALL_ACCESS', true),
            'log_data_exports' => env('SECURITY_LOG_DATA_EXPORTS', true),
            'retention_days' => env('SECURITY_AUDIT_RETENTION_DAYS', 2555), // 7 years
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Compliance Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for automated compliance checking.
    |
    */
    'compliance' => [
        'frameworks' => [
            'owasp' => env('SECURITY_OWASP_ENABLED', true),
            'soc2' => env('SECURITY_SOC2_ENABLED', true),
            'gdpr' => env('SECURITY_GDPR_ENABLED', true),
            'iso27001' => env('SECURITY_ISO27001_ENABLED', false),
            'nist' => env('SECURITY_NIST_ENABLED', false),
        ],
        
        'check_interval_hours' => env('SECURITY_COMPLIANCE_CHECK_INTERVAL', 24),
        'auto_remediation' => env('SECURITY_AUTO_REMEDIATION_ENABLED', false),
        'notification_channels' => explode(',', env('SECURITY_COMPLIANCE_NOTIFICATIONS', 'email')),
    ],
];