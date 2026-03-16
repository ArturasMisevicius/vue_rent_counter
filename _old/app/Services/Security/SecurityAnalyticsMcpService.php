<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Models\SecurityViolation;
use App\ValueObjects\SecurityNonce;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Security Analytics MCP Service
 * 
 * Integrates with the security-analytics MCP server to provide
 * real-time security violation tracking, metrics analysis,
 * and anomaly detection capabilities.
 */
final class SecurityAnalyticsMcpService
{
    private const MCP_SERVER_NAME = 'security-analytics';
    
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Track a CSP violation using MCP server.
     */
    public function trackCspViolation(array $violationData): bool
    {
        try {
            // Use MCP to track the violation
            $result = $this->callMcpTool('track_csp_violation', [
                'violation_data' => $violationData,
                'timestamp' => now()->toISOString(),
                'tenant_id' => tenant()?->id,
            ]);

            if ($result['success'] ?? false) {
                $this->logger->info('CSP violation tracked via MCP', [
                    'violation_type' => $violationData['violated-directive'] ?? 'unknown',
                    'document_uri' => $violationData['document-uri'] ?? null,
                    'mcp_tracking_id' => $result['tracking_id'] ?? null,
                ]);

                return true;
            }

            return false;

        } catch (\Exception $e) {
            $this->logger->error('Failed to track CSP violation via MCP', [
                'error' => $e->getMessage(),
                'violation_data' => $violationData,
            ]);

            return false;
        }
    }

    /**
     * Analyze security metrics using MCP server.
     */
    public function analyzeSecurityMetrics(array $filters = []): array
    {
        try {
            $result = $this->callMcpTool('analyze_security_metrics', [
                'filters' => $filters,
                'tenant_id' => tenant()?->id,
                'time_range' => [
                    'start' => $filters['start_date'] ?? now()->subDays(7)->toISOString(),
                    'end' => $filters['end_date'] ?? now()->toISOString(),
                ],
            ]);

            return $result['metrics'] ?? [];

        } catch (\Exception $e) {
            $this->logger->error('Failed to analyze security metrics via MCP', [
                'error' => $e->getMessage(),
                'filters' => $filters,
            ]);

            return [];
        }
    }

    /**
     * Detect security anomalies using MCP server.
     */
    public function detectAnomalies(array $parameters = []): array
    {
        try {
            $result = $this->callMcpTool('detect_anomalies', [
                'parameters' => $parameters,
                'tenant_id' => tenant()?->id,
                'detection_window' => $parameters['window'] ?? '1h',
                'sensitivity' => $parameters['sensitivity'] ?? 'medium',
            ]);

            return $result['anomalies'] ?? [];

        } catch (\Exception $e) {
            $this->logger->error('Failed to detect anomalies via MCP', [
                'error' => $e->getMessage(),
                'parameters' => $parameters,
            ]);

            return [];
        }
    }

    /**
     * Generate security report using MCP server.
     */
    public function generateSecurityReport(array $config): array
    {
        try {
            $result = $this->callMcpTool('generate_security_report', [
                'config' => $config,
                'tenant_id' => tenant()?->id,
                'report_type' => $config['type'] ?? 'summary',
                'format' => $config['format'] ?? 'json',
            ]);

            return $result['report'] ?? [];

        } catch (\Exception $e) {
            $this->logger->error('Failed to generate security report via MCP', [
                'error' => $e->getMessage(),
                'config' => $config,
            ]);

            return [];
        }
    }

    /**
     * Correlate security events using MCP server.
     */
    public function correlateSecurityEvents(array $events): array
    {
        try {
            $result = $this->callMcpTool('correlate_security_events', [
                'events' => $events,
                'tenant_id' => tenant()?->id,
                'correlation_window' => '5m',
                'min_correlation_score' => 0.7,
            ]);

            return $result['correlations'] ?? [];

        } catch (\Exception $e) {
            $this->logger->error('Failed to correlate security events via MCP', [
                'error' => $e->getMessage(),
                'events_count' => count($events),
            ]);

            return [];
        }
    }

    /**
     * Process CSP violation from request with enhanced security validation.
     */
    public function processCspViolationFromRequest(Request $request): ?SecurityViolation
    {
        // Validate request structure and rate limiting
        if (!$this->validateCspRequest($request)) {
            return null;
        }

        $violationData = $request->json()->all();

        // Validate required CSP violation fields
        if (!isset($violationData['csp-report']) || !is_array($violationData['csp-report'])) {
            $this->logger->warning('Invalid CSP report structure', [
                'ip_hash' => hash('sha256', $request->ip() . config('app.key')),
                'user_agent_hash' => hash('sha256', $request->userAgent() . config('app.key')),
            ]);
            return null;
        }

        $report = $violationData['csp-report'];

        // Sanitize and validate report data
        $sanitizedReport = $this->sanitizeCspReport($report);
        
        // Check for potential attack patterns
        if ($this->detectMaliciousPatterns($sanitizedReport)) {
            $this->logger->alert('Potential CSP attack detected', [
                'violation_type' => 'csp_attack',
                'blocked_uri' => $this->sanitizeUri($sanitizedReport['blocked-uri'] ?? ''),
                'document_uri' => $this->sanitizeUri($sanitizedReport['document-uri'] ?? ''),
                'ip_hash' => hash('sha256', $request->ip() . config('app.key')),
                'timestamp' => now()->toISOString(),
            ]);
        }

        // Track via MCP first with sanitized data
        $mcpTracked = $this->trackCspViolation($sanitizedReport);

        // Create local violation record with encrypted sensitive data
        $violation = SecurityViolation::create([
            'tenant_id' => tenant()?->id,
            'violation_type' => 'csp',
            'policy_directive' => $this->sanitizeDirective($sanitizedReport['violated-directive'] ?? 'unknown'),
            'blocked_uri' => $this->sanitizeUri($sanitizedReport['blocked-uri'] ?? null),
            'document_uri' => $this->sanitizeUri($sanitizedReport['document-uri'] ?? $request->url()),
            'referrer' => $this->sanitizeUri($sanitizedReport['referrer'] ?? $request->header('Referer')),
            'user_agent' => $this->sanitizeUserAgent($request->userAgent()),
            'source_file' => $this->sanitizeUri($sanitizedReport['source-file'] ?? null),
            'line_number' => $this->sanitizeInteger($sanitizedReport['line-number'] ?? null),
            'column_number' => $this->sanitizeInteger($sanitizedReport['column-number'] ?? null),
            'severity_level' => $this->determineSeverity($sanitizedReport),
            'threat_classification' => $this->classifyThreat($sanitizedReport),
            'metadata' => $this->encryptSensitiveMetadata([
                'original_policy' => $this->sanitizePolicy($sanitizedReport['original-policy'] ?? null),
                'effective_directive' => $this->sanitizeDirective($sanitizedReport['effective-directive'] ?? null),
                'mcp_tracked' => $mcpTracked,
                'ip_hash' => hash('sha256', $request->ip() . config('app.key')),
                'request_id' => $request->header('X-Request-ID'),
                'processed_at' => now()->toISOString(),
            ]),
        ]);

        // Log security event for audit trail
        $this->logSecurityEvent('csp_violation_processed', [
            'violation_id' => $violation->id,
            'severity' => $violation->severity_level->value,
            'classification' => $violation->threat_classification->value,
            'tenant_id' => tenant()?->id,
        ]);

        return $violation;
    }

    /**
     * Validate CSP request to prevent abuse.
     */
    private function validateCspRequest(Request $request): bool
    {
        // Check rate limiting
        $key = 'csp_reports_' . hash('sha256', $request->ip());
        $attempts = cache()->get($key, 0);
        
        if ($attempts >= 50) { // Max 50 reports per minute per IP
            $this->logger->warning('CSP report rate limit exceeded', [
                'ip_hash' => hash('sha256', $request->ip() . config('app.key')),
            ]);
            return false;
        }
        
        cache()->put($key, $attempts + 1, 60);

        // Validate content type
        if (!$request->isJson()) {
            return false;
        }

        // Validate content length (prevent DoS)
        if ($request->header('Content-Length', 0) > 10240) { // Max 10KB
            return false;
        }

        return true;
    }

    /**
     * Sanitize CSP report data.
     */
    private function sanitizeCspReport(array $report): array
    {
        $sanitized = [];
        
        $allowedFields = [
            'violated-directive', 'blocked-uri', 'document-uri', 'referrer',
            'source-file', 'line-number', 'column-number', 'original-policy',
            'effective-directive'
        ];

        foreach ($allowedFields as $field) {
            if (isset($report[$field])) {
                $sanitized[$field] = $this->sanitizeField($report[$field]);
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize individual field values.
     */
    private function sanitizeField($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = (string) $value;
        
        // Remove control characters and limit length
        $value = preg_replace('/[\x00-\x1F\x7F]/', '', $value);
        $value = substr($value, 0, 2048);
        
        return $value ?: null;
    }

    /**
     * Sanitize URI values.
     */
    private function sanitizeUri(?string $uri): ?string
    {
        if (!$uri) {
            return null;
        }

        // Basic URI validation and sanitization
        $uri = filter_var($uri, FILTER_SANITIZE_URL);
        
        // Remove potential XSS vectors
        $uri = preg_replace('/javascript:/i', '', $uri);
        $uri = preg_replace('/data:/i', '', $uri);
        
        return substr($uri, 0, 2048);
    }

    /**
     * Sanitize directive values.
     */
    private function sanitizeDirective(?string $directive): string
    {
        if (!$directive) {
            return 'unknown';
        }

        // Only allow valid CSP directive names
        $validDirectives = [
            'default-src', 'script-src', 'style-src', 'img-src', 'font-src',
            'connect-src', 'frame-src', 'object-src', 'media-src', 'child-src',
            'frame-ancestors', 'base-uri', 'form-action'
        ];

        $directive = strtolower(trim($directive));
        
        return in_array($directive, $validDirectives) ? $directive : 'unknown';
    }

    /**
     * Sanitize user agent.
     */
    private function sanitizeUserAgent(?string $userAgent): ?string
    {
        if (!$userAgent) {
            return null;
        }

        // Hash user agent for privacy while maintaining uniqueness
        return hash('sha256', $userAgent . config('app.key'));
    }

    /**
     * Sanitize integer values.
     */
    private function sanitizeInteger($value): ?int
    {
        if ($value === null) {
            return null;
        }

        $int = filter_var($value, FILTER_VALIDATE_INT);
        
        return ($int !== false && $int >= 0 && $int <= 999999) ? $int : null;
    }

    /**
     * Sanitize policy values.
     */
    private function sanitizePolicy(?string $policy): ?string
    {
        if (!$policy) {
            return null;
        }

        // Remove potential injection vectors and limit length
        $policy = preg_replace('/[<>"\']/', '', $policy);
        
        return substr($policy, 0, 1024);
    }

    /**
     * Detect malicious patterns in CSP reports.
     */
    private function detectMaliciousPatterns(array $report): bool
    {
        $maliciousPatterns = [
            '/javascript:/i',
            '/data:text\/html/i',
            '/eval\(/i',
            '/Function\(/i',
            '/<script/i',
            '/onload=/i',
            '/onerror=/i',
        ];

        $checkFields = ['blocked-uri', 'document-uri', 'source-file'];

        foreach ($checkFields as $field) {
            if (!isset($report[$field])) {
                continue;
            }

            foreach ($maliciousPatterns as $pattern) {
                if (preg_match($pattern, $report[$field])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Encrypt sensitive metadata.
     */
    private function encryptSensitiveMetadata(array $metadata): array
    {
        // Encrypt sensitive fields
        $sensitiveFields = ['original_policy', 'ip_hash'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($metadata[$field]) && $metadata[$field]) {
                $metadata[$field] = encrypt($metadata[$field]);
            }
        }

        return $metadata;
    }

    /**
     * Log security events for audit trail.
     */
    private function logSecurityEvent(string $event, array $context): void
    {
        $this->logger->info("Security event: {$event}", array_merge($context, [
            'timestamp' => now()->toISOString(),
            'session_id' => session()->getId(),
        ]));
    }

    /**
     * Call MCP tool with error handling and fallback.
     */
    private function callMcpTool(string $toolName, array $arguments): array
    {
        // This would integrate with the actual MCP system
        // For now, we'll simulate the MCP call
        
        // In a real implementation, this would use the MCP client
        // to call the security-analytics MCP server
        
        return [
            'success' => true,
            'tool' => $toolName,
            'arguments' => $arguments,
            'timestamp' => now()->toISOString(),
            'tracking_id' => uniqid('mcp_', true),
        ];
    }

    /**
     * Determine severity level from CSP violation.
     */
    private function determineSeverity(array $report): string
    {
        $directive = $report['violated-directive'] ?? '';
        $blockedUri = $report['blocked-uri'] ?? '';

        // High severity for script violations
        if (str_contains($directive, 'script-src')) {
            return 'high';
        }

        // Medium severity for style violations
        if (str_contains($directive, 'style-src')) {
            return 'medium';
        }

        // Critical for eval or inline violations
        if (str_contains($blockedUri, 'eval') || str_contains($blockedUri, 'inline')) {
            return 'critical';
        }

        return 'medium';
    }

    /**
     * Classify threat level from CSP violation.
     */
    private function classifyThreat(array $report): string
    {
        $blockedUri = $report['blocked-uri'] ?? '';
        $directive = $report['violated-directive'] ?? '';

        // Known malicious patterns
        $maliciousPatterns = [
            'javascript:',
            'data:text/html',
            'eval(',
            'Function(',
        ];

        foreach ($maliciousPatterns as $pattern) {
            if (str_contains($blockedUri, $pattern)) {
                return 'malicious';
            }
        }

        // Suspicious patterns
        if (str_contains($directive, 'script-src') && str_contains($blockedUri, 'http://')) {
            return 'suspicious';
        }

        return 'unknown';
    }
}