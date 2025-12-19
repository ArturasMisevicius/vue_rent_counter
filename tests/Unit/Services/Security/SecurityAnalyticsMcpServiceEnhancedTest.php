<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Security;

use App\Models\SecurityViolation;
use App\Services\Security\SecurityAnalyticsMcpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Enhanced SecurityAnalyticsMcpService Unit Tests
 * 
 * Comprehensive tests for MCP integration, security analytics,
 * and violation processing with enhanced security features.
 */
final class SecurityAnalyticsMcpServiceEnhancedTest extends TestCase
{
    use RefreshDatabase;

    private SecurityAnalyticsMcpService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SecurityAnalyticsMcpService::class);
    }

    public function test_tracks_csp_violation_via_mcp(): void
    {
        $violationData = [
            'violated-directive' => 'script-src',
            'blocked-uri' => 'https://malicious.example.com/script.js',
            'document-uri' => 'https://app.test/admin',
        ];

        Log::shouldReceive('info')
            ->once()
            ->with('CSP violation tracked via MCP', \Mockery::type('array'));

        $result = $this->service->trackCspViolation($violationData);

        $this->assertTrue($result);
    }

    public function test_analyzes_security_metrics_via_mcp(): void
    {
        $filters = [
            'start_date' => now()->subDays(7)->toISOString(),
            'end_date' => now()->toISOString(),
            'severity' => 'high',
        ];

        $result = $this->service->analyzeSecurityMetrics($filters);

        $this->assertIsArray($result);
    }

    public function test_detects_anomalies_via_mcp(): void
    {
        $parameters = [
            'window' => '1h',
            'sensitivity' => 'high',
        ];

        $result = $this->service->detectAnomalies($parameters);

        $this->assertIsArray($result);
    }

    public function test_generates_security_report_via_mcp(): void
    {
        $config = [
            'type' => 'detailed',
            'format' => 'json',
            'include_trends' => true,
        ];

        $result = $this->service->generateSecurityReport($config);

        $this->assertIsArray($result);
    }

    public function test_correlates_security_events_via_mcp(): void
    {
        $events = [
            ['type' => 'csp_violation', 'timestamp' => now()->toISOString()],
            ['type' => 'failed_login', 'timestamp' => now()->subMinutes(2)->toISOString()],
        ];

        $result = $this->service->correlateSecurityEvents($events);

        $this->assertIsArray($result);
    }

    public function test_processes_csp_violation_from_request(): void
    {
        $tenant = \App\Models\Tenant::factory()->create();
        $this->mockTenantContext($tenant);

        $request = Request::create('/api/csp-report', 'POST', [], [], [], [], json_encode([
            'csp-report' => [
                'violated-directive' => 'script-src',
                'blocked-uri' => 'https://example.com/script.js',
                'document-uri' => 'https://app.test/admin',
                'referrer' => 'https://app.test/',
                'source-file' => 'https://app.test/admin/script.js',
                'line-number' => 42,
                'column-number' => 15,
            ],
        ]));

        $request->headers->set('Content-Type', 'application/json');

        $violation = $this->service->processCspViolationFromRequest($request);

        $this->assertInstanceOf(SecurityViolation::class, $violation);
        $this->assertEquals('csp', $violation->violation_type);
        $this->assertEquals('script-src', $violation->policy_directive);
        $this->assertEquals($tenant->id, $violation->tenant_id);
    }

    public function test_validates_csp_request_rate_limiting(): void
    {
        $request = Request::create('/api/csp-report', 'POST', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.100',
        ], json_encode(['csp-report' => []]));

        $request->headers->set('Content-Type', 'application/json');

        // First 50 requests should pass
        for ($i = 0; $i < 50; $i++) {
            $result = $this->service->processCspViolationFromRequest($request);
            $this->assertNotNull($result);
        }

        // 51st request should be rate limited
        Log::shouldReceive('warning')
            ->once()
            ->with('CSP report rate limit exceeded', \Mockery::type('array'));

        $result = $this->service->processCspViolationFromRequest($request);
        $this->assertNull($result);
    }

    public function test_sanitizes_csp_report_data(): void
    {
        $request = Request::create('/api/csp-report', 'POST', [], [], [], [], json_encode([
            'csp-report' => [
                'violated-directive' => 'script-src',
                'blocked-uri' => "javascript:alert('xss')\x00\x01malicious",
                'document-uri' => 'https://app.test/admin<script>alert("xss")</script>',
                'line-number' => 'not-a-number',
                'column-number' => -1,
            ],
        ]));

        $request->headers->set('Content-Type', 'application/json');

        $violation = $this->service->processCspViolationFromRequest($request);

        $this->assertInstanceOf(SecurityViolation::class, $violation);
        
        // Verify sanitization
        $this->assertStringNotContainsString('javascript:', $violation->blocked_uri);
        $this->assertStringNotContainsString('<script>', $violation->document_uri);
        $this->assertNull($violation->line_number); // Invalid number should be null
        $this->assertNull($violation->column_number); // Negative number should be null
    }

    public function test_detects_malicious_patterns(): void
    {
        $maliciousPatterns = [
            'javascript:alert("xss")',
            'data:text/html,<script>alert("xss")</script>',
            'eval(atob("YWxlcnQoJ1hTUycpOw=="))',
        ];

        foreach ($maliciousPatterns as $pattern) {
            $request = Request::create('/api/csp-report', 'POST', [], [], [], [], json_encode([
                'csp-report' => [
                    'violated-directive' => 'script-src',
                    'blocked-uri' => $pattern,
                    'document-uri' => 'https://app.test/',
                ],
            ]));

            $request->headers->set('Content-Type', 'application/json');

            Log::shouldReceive('alert')
                ->once()
                ->with('Potential CSP attack detected', \Mockery::type('array'));

            $violation = $this->service->processCspViolationFromRequest($request);
            $this->assertInstanceOf(SecurityViolation::class, $violation);
        }
    }

    public function test_classifies_threat_levels_correctly(): void
    {
        $testCases = [
            [
                'blocked-uri' => 'javascript:alert("xss")',
                'expected' => 'malicious',
            ],
            [
                'blocked-uri' => 'http://suspicious.example.com/script.js',
                'violated-directive' => 'script-src',
                'expected' => 'suspicious',
            ],
            [
                'blocked-uri' => 'https://cdn.example.com/script.js',
                'expected' => 'unknown',
            ],
        ];

        foreach ($testCases as $testCase) {
            $request = Request::create('/api/csp-report', 'POST', [], [], [], [], json_encode([
                'csp-report' => $testCase,
            ]));

            $request->headers->set('Content-Type', 'application/json');

            $violation = $this->service->processCspViolationFromRequest($request);
            
            $this->assertEquals(
                $testCase['expected'],
                $violation->threat_classification->value,
                "Failed to classify threat for: " . $testCase['blocked-uri']
            );
        }
    }

    public function test_determines_severity_levels_correctly(): void
    {
        $testCases = [
            [
                'violated-directive' => 'script-src',
                'blocked-uri' => 'eval(code)',
                'expected' => 'critical',
            ],
            [
                'violated-directive' => 'script-src',
                'blocked-uri' => 'https://example.com/script.js',
                'expected' => 'high',
            ],
            [
                'violated-directive' => 'style-src',
                'blocked-uri' => 'https://example.com/style.css',
                'expected' => 'medium',
            ],
            [
                'violated-directive' => 'img-src',
                'blocked-uri' => 'https://example.com/image.jpg',
                'expected' => 'medium',
            ],
        ];

        foreach ($testCases as $testCase) {
            $request = Request::create('/api/csp-report', 'POST', [], [], [], [], json_encode([
                'csp-report' => $testCase,
            ]));

            $request->headers->set('Content-Type', 'application/json');

            $violation = $this->service->processCspViolationFromRequest($request);
            
            $this->assertEquals(
                $testCase['expected'],
                $violation->severity_level->value,
                "Failed to determine severity for: " . $testCase['violated-directive']
            );
        }
    }

    public function test_encrypts_sensitive_metadata(): void
    {
        $request = Request::create('/api/csp-report', 'POST', [], [], [], [], json_encode([
            'csp-report' => [
                'violated-directive' => 'script-src',
                'blocked-uri' => 'https://example.com/script.js',
                'document-uri' => 'https://app.test/',
                'original-policy' => "default-src 'self'; script-src 'self'",
            ],
        ]));

        $request->headers->set('Content-Type', 'application/json');

        $violation = $this->service->processCspViolationFromRequest($request);

        $metadata = $violation->metadata;
        
        // Verify sensitive fields are encrypted
        $this->assertNotEquals(
            "default-src 'self'; script-src 'self'",
            $metadata['original_policy']
        );
        
        // Should be able to decrypt
        $decrypted = decrypt($metadata['original_policy']);
        $this->assertEquals("default-src 'self'; script-src 'self'", $decrypted);
    }

    public function test_handles_mcp_service_failures_gracefully(): void
    {
        // Mock logger to expect error logging
        Log::shouldReceive('error')
            ->times(5) // One for each MCP method
            ->with(\Mockery::pattern('/Failed to .* via MCP/'), \Mockery::type('array'));

        // Test all MCP methods handle failures gracefully
        $this->assertFalse($this->service->trackCspViolation([]));
        $this->assertEquals([], $this->service->analyzeSecurityMetrics([]));
        $this->assertEquals([], $this->service->detectAnomalies([]));
        $this->assertEquals([], $this->service->generateSecurityReport([]));
        $this->assertEquals([], $this->service->correlateSecurityEvents([]));
    }

    public function test_validates_content_length_limits(): void
    {
        $largePayload = str_repeat('x', 20480); // 20KB payload

        $request = Request::create('/api/csp-report', 'POST', [], [], [], [
            'CONTENT_LENGTH' => strlen($largePayload),
        ], $largePayload);

        $request->headers->set('Content-Type', 'application/json');

        $result = $this->service->processCspViolationFromRequest($request);

        $this->assertNull($result); // Should reject large payloads
    }

    public function test_rejects_non_json_requests(): void
    {
        $request = Request::create('/api/csp-report', 'POST', [], [], [], [], 'not-json-data');
        $request->headers->set('Content-Type', 'text/plain');

        $result = $this->service->processCspViolationFromRequest($request);

        $this->assertNull($result);
    }

    public function test_logs_security_events_for_audit_trail(): void
    {
        $request = Request::create('/api/csp-report', 'POST', [], [], [], [], json_encode([
            'csp-report' => [
                'violated-directive' => 'script-src',
                'blocked-uri' => 'https://example.com/script.js',
                'document-uri' => 'https://app.test/',
            ],
        ]));

        $request->headers->set('Content-Type', 'application/json');

        Log::shouldReceive('info')
            ->once()
            ->with('Security event: csp_violation_processed', \Mockery::type('array'));

        $violation = $this->service->processCspViolationFromRequest($request);

        $this->assertInstanceOf(SecurityViolation::class, $violation);
    }

    protected function mockTenantContext(\App\Models\Tenant $tenant): void
    {
        // Mock tenant context
        app()->instance('tenant', $tenant);
    }
}