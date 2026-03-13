<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Security Test Configuration
 * 
 * Centralized configuration for security-related tests
 * including performance thresholds, test data, and MCP settings.
 */
final class SecurityTestConfig
{
    /**
     * Performance thresholds for various operations (in milliseconds).
     */
    public const PERFORMANCE_THRESHOLDS = [
        'middleware_processing' => 50,
        'csp_violation_processing' => 100,
        'mcp_analytics_call' => 200,
        'security_header_application' => 10,
        'nonce_generation' => 1,
        'dashboard_load' => 3000,
        'concurrent_operations' => 1000,
    ];

    /**
     * Rate limiting configuration for tests.
     */
    public const RATE_LIMITS = [
        'csp_reports_per_ip_per_minute' => 50,
        'analytics_requests_per_user_per_minute' => 60,
        'mcp_calls_per_minute' => 100,
    ];

    /**
     * Test data patterns for CSP violations.
     */
    public const CSP_VIOLATION_PATTERNS = [
        'normal' => [
            'script-src' => 'https://cdn.example.com/script.js',
            'style-src' => 'https://fonts.googleapis.com/css',
            'img-src' => 'data:image/png;base64,iVBOR...',
        ],
        'suspicious' => [
            'script-src' => 'http://suspicious.com/script.js',
            'connect-src' => 'ws://unknown-websocket.ru:8080',
        ],
        'malicious' => [
            'script-src' => [
                'javascript:alert("xss")',
                'data:text/html,<script>alert("xss")</script>',
                'eval(atob("YWxlcnQoJ1hTUycpOw=="))',
                'vbscript:msgbox("xss")',
            ],
        ],
    ];

    /**
     * Expected threat classifications for test patterns.
     */
    public const THREAT_CLASSIFICATIONS = [
        'javascript:alert("xss")' => 'malicious',
        'data:text/html,<script>' => 'malicious',
        'eval(' => 'malicious',
        'http://suspicious.com' => 'suspicious',
        'https://cdn.example.com' => 'unknown',
    ];

    /**
     * Expected severity levels for different directives.
     */
    public const SEVERITY_LEVELS = [
        'script-src' => 'high',
        'style-src' => 'medium',
        'img-src' => 'medium',
        'font-src' => 'low',
        'connect-src' => 'medium',
    ];

    /**
     * MCP server test configuration.
     */
    public const MCP_TEST_CONFIG = [
        'security-analytics' => [
            'tools' => [
                'track_csp_violation',
                'analyze_security_metrics',
                'detect_anomalies',
                'generate_security_report',
                'correlate_security_events',
            ],
            'timeout' => 5000, // milliseconds
            'retry_attempts' => 3,
        ],
        'compliance-checker' => [
            'tools' => [
                'validate_owasp_compliance',
                'check_soc2_controls',
                'verify_gdpr_headers',
            ],
            'frameworks' => ['owasp', 'soc2', 'gdpr'],
        ],
        'performance-monitor' => [
            'tools' => [
                'monitor_header_performance',
                'analyze_performance_trends',
            ],
            'thresholds' => [
                'warning_ms' => 15,
                'error_ms' => 50,
            ],
        ],
    ];

    /**
     * Security headers that must be present in responses.
     */
    public const REQUIRED_SECURITY_HEADERS = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => ['DENY', 'SAMEORIGIN'],
        'Content-Security-Policy' => null, // Must be present but value varies
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
    ];

    /**
     * Additional headers for production environment.
     */
    public const PRODUCTION_SECURITY_HEADERS = [
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
        'Cross-Origin-Embedder-Policy' => 'require-corp',
        'Cross-Origin-Opener-Policy' => 'same-origin',
        'Cross-Origin-Resource-Policy' => 'same-origin',
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
    ];

    /**
     * Test user roles and their expected access levels.
     */
    public const USER_ROLES_ACCESS = [
        'superadmin' => [
            'can_access_all_tenants' => true,
            'can_view_security_analytics' => true,
            'can_modify_csp_policies' => true,
            'can_access_mcp_status' => true,
        ],
        'admin' => [
            'can_access_all_tenants' => false,
            'can_view_security_analytics' => true,
            'can_modify_csp_policies' => true,
            'can_access_mcp_status' => false,
        ],
        'manager' => [
            'can_access_all_tenants' => false,
            'can_view_security_analytics' => true,
            'can_modify_csp_policies' => false,
            'can_access_mcp_status' => false,
        ],
        'tenant' => [
            'can_access_all_tenants' => false,
            'can_view_security_analytics' => false,
            'can_modify_csp_policies' => false,
            'can_access_mcp_status' => false,
        ],
    ];

    /**
     * Memory usage limits for performance tests.
     */
    public const MEMORY_LIMITS = [
        'single_operation' => 1024 * 1024, // 1MB
        'batch_operations' => 10 * 1024 * 1024, // 10MB
        'concurrent_operations' => 50 * 1024 * 1024, // 50MB
    ];

    /**
     * Test data sizes for various scenarios.
     */
    public const TEST_DATA_SIZES = [
        'small_payload' => 1024, // 1KB
        'medium_payload' => 5 * 1024, // 5KB
        'large_payload' => 10 * 1024, // 10KB (should be rejected)
        'violation_batch_size' => 100,
        'analytics_data_points' => 1000,
    ];

    /**
     * Accessibility test requirements.
     */
    public const ACCESSIBILITY_REQUIREMENTS = [
        'wcag_level' => 'AA',
        'color_contrast_ratio' => 4.5,
        'required_aria_attributes' => [
            'role',
            'aria-label',
            'aria-labelledby',
            'aria-describedby',
            'aria-live',
        ],
        'keyboard_navigation' => true,
        'screen_reader_support' => true,
    ];

    /**
     * Browser test configuration.
     */
    public const BROWSER_TEST_CONFIG = [
        'default_timeout' => 10000, // milliseconds
        'page_load_timeout' => 30000,
        'element_wait_timeout' => 5000,
        'supported_browsers' => ['chrome', 'firefox', 'safari', 'edge'],
        'viewport_sizes' => [
            'desktop' => ['width' => 1920, 'height' => 1080],
            'tablet' => ['width' => 768, 'height' => 1024],
            'mobile' => ['width' => 375, 'height' => 667],
        ],
    ];

    /**
     * Get performance threshold for a specific operation.
     */
    public static function getPerformanceThreshold(string $operation): int
    {
        return self::PERFORMANCE_THRESHOLDS[$operation] ?? 1000;
    }

    /**
     * Get rate limit for a specific operation.
     */
    public static function getRateLimit(string $operation): int
    {
        return self::RATE_LIMITS[$operation] ?? 100;
    }

    /**
     * Get expected threat classification for a URI.
     */
    public static function getExpectedThreatClassification(string $uri): string
    {
        foreach (self::THREAT_CLASSIFICATIONS as $pattern => $classification) {
            if (str_contains($uri, $pattern)) {
                return $classification;
            }
        }
        
        return 'unknown';
    }

    /**
     * Get expected severity level for a directive.
     */
    public static function getExpectedSeverityLevel(string $directive): string
    {
        return self::SEVERITY_LEVELS[$directive] ?? 'medium';
    }

    /**
     * Check if user role has specific access.
     */
    public static function userRoleHasAccess(string $role, string $permission): bool
    {
        return self::USER_ROLES_ACCESS[$role][$permission] ?? false;
    }
}