<?php

declare(strict_types=1);

namespace Tests\Property;

use App\Models\SecurityViolation;
use App\Services\Security\SecurityAnalyticsMcpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Property-based tests for SecurityHeaders MCP Integration
 * 
 * Tests security properties that must hold across all scenarios
 * with MCP server integration and enhanced security analytics.
 */
final class SecurityHeadersMcpIntegrationPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property: All CSP violations must be properly sanitized and classified
     * 
     * @test
     */
    public function csp_violation_sanitization_and_classification_property(): void
    {
        $service = app(SecurityAnalyticsMcpService::class);
        
        // Generate various CSP violation patterns
        $violationPatterns = [
            // Normal violations
            ['script-src', 'https://cdn.example.com/script.js', 'unknown', 'high'],
            ['style-src', 'https://fonts.googleapis.com/css', 'unknown', 'medium'],
            ['img-src', 'data:image/png;base64,iVBOR...', 'unknown', 'medium'],
            
            // Suspicious violations
            ['script-src', 'http://suspicious.com/script.js', 'suspicious', 'high'],
            ['script-src', 'https://unknown-cdn.ru/malware.js', 'unknown', 'high'],
            
            // Malicious violations
            ['script-src', 'javascript:alert("xss")', 'malicious', 'critical'],
            ['script-src', 'data:text/html,<script>alert("xss")</script>', 'malicious', 'critical'],
            ['script-src', 'eval(atob("YWxlcnQoJ1hTUycpOw=="))', 'malicious', 'critical'],
        ];

        foreach ($violationPatterns as [$directive, $blockedUri, $expectedThreat, $expectedSeverity]) {
            $request = Request::create('/api/csp-report', 'POST', [], [], [], [], json_encode([
                'csp-report' => [
                    'violated-directive' => $directive,
                    'blocked-uri' => $blockedUri,
                    'document-uri' => 'https://app.test/',
                ],
            ]));

            $request->headers->set('Content-Type', 'application/json');

            $violation = $service->processCspViolationFromRequest($request);

            // Property: All violations must be processed and stored
            $this->assertInstanceOf(SecurityViolation::class, $violation,
                "Failed to process violation: {$directive} -> {$blockedUri}");

            // Property: Threat classification must be consistent
            $this->assertEquals($expectedThreat, $violation->threat_classification->value,
                "Incorrect threat classification for: {$blockedUri}");

            // Property: Severity determination must be consistent
            $this->assertEquals($expectedSeverity, $violation->severity_level->value,
                "Incorrect severity for: {$directive} -> {$blockedUri}");

            // Property: Sensitive data must be sanitized
            $this->assertStringNotContainsString('javascript:', $violation->blocked_uri ?? '');
            $this->assertStringNotContainsString('<script>', $violation->blocked_uri ?? '');
            $this->assertStringNotContainsString('eval(', $violation->blocked_uri ?? '');
        }
    }

    /**
     * Property: Rate limiting must be consistently enforced across all IPs
     * 
     * @test
     */
    public function rate_limiting_consistency_property(): void
    {
        $service = app(SecurityAnalyticsMcpService::class);
        $testIps = ['192.168.1.1', '10.0.0.1', '172.16.0.1'];

        foreach ($testIps as $ip) {
            $rateLimitHit = false;
            
            for ($i = 0; $i < 55; $i++) {
                $request = Request::create('/api/csp-report', 'POST', [], [], [], [
                    'REMOTE_ADDR' => $ip,
                ], json_encode([
                    'csp-report' => [
                        'violated-directive' => 'script-src',
                        'blocked-uri' => "https://example.com/script-{$i}.js",
                        'document-uri' => 'https://app.test/',
                    ],
                ]));

                $request->headers->set('Content-Type', 'application/json');

                $result = $service->processCspViolationFromRequest($request);

                if ($result === null && $i >= 50) {
                    $rateLimitHit = true;
                    break;
                }
            }

            $this->assertTrue($rateLimitHit, "Rate limiting not enforced for IP: {$ip}");
        }
    }

    /**
     * Property: MCP integration must be resilient to service failures
     * 
     * @test
     */
    public function mcp_service_resilience_property(): void
    {
        $service = app(SecurityAnalyticsMcpService::class);
        
        // Test various failure scenarios
        $testScenarios = [
            'empty_violation_data' => [],
            'invalid_violation_data' => ['invalid' => 'data'],
            'malformed_json' => null,
        ];

        foreach ($testScenarios as $scenario => $data) {
            // Should not throw exceptions, should handle gracefully
            try {
                if ($data !== null) {
                    $result = $service->trackCspViolation($data);
                    $this->assertIsBool($result, "trackCspViolation should return boolean for scenario: {$scenario}");
                }

                $metrics = $service->analyzeSecurityMetrics($data ?? []);
                $this->assertIsArray($metrics, "analyzeSecurityMetrics should return array for scenario: {$scenario}");

                $anomalies = $service->detectAnomalies($data ?? []);
                $this->assertIsArray($anomalies, "detectAnomalies should return array for scenario: {$scenario}");

            } catch (\Exception $e) {
                $this->fail("MCP service should handle failures gracefully, but threw: " . $e->getMessage());
            }
        }
    }

    /**
     * Property: Tenant isolation must be maintained across all MCP operations
     * 
     * @test
     */
    public function tenant_isolation_property(): void
    {
        $tenant1 = \App\Models\Tenant::factory()->create();
        $tenant2 = \App\Models\Tenant::factory()->create();
        
        $service = app(SecurityAnalyticsMcpService::class);

        // Create violations for different tenants
        foreach ([$tenant1, $tenant2] as $tenant) {
            app()->instance('tenant', $tenant);

            $request = Request::create('/api/csp-report', 'POST', [], [], [], [], json_encode([
                'csp-report' => [
                    'violated-directive' => 'script-src',
                    'blocked-uri' => "https://tenant-{$tenant->id}.example.com/script.js",
                    'document-uri' => 'https://app.test/',
                ],
            ]));

            $request->headers->set('Content-Type', 'application/json');

            $violation = $service->processCspViolationFromRequest($request);

            // Property: Violations must be associated with correct tenant
            $this->assertEquals($tenant->id, $violation->tenant_id,
                "Violation not properly associated with tenant {$tenant->id}");
        }

        // Verify tenant isolation in database
        $tenant1Violations = SecurityViolation::where('tenant_id', $tenant1->id)->count();
        $tenant2Violations = SecurityViolation::where('tenant_id', $tenant2->id)->count();

        $this->assertEquals(1, $tenant1Violations);
        $this->assertEquals(1, $tenant2Violations);

        // Property: Cross-tenant data leakage must not occur
        app()->instance('tenant', $tenant1);
        $tenant1Data = $service->analyzeSecurityMetrics(['tenant_id' => $tenant1->id]);
        
        app()->instance('tenant', $tenant2);
        $tenant2Data = $service->analyzeSecurityMetrics(['tenant_id' => $tenant2->id]);

        // Data should be different for different tenants
        $this->assertNotEquals($tenant1Data, $tenant2Data,
            "Tenant data isolation violated - same data returned for different tenants");
    }

    /**
     * Property: Security metadata encryption must be consistent and reversible
     * 
     * @test
     */
    public function metadata_encryption_property(): void
    {
        $service = app(SecurityAnalyticsMcpService::class);
        
        $sensitiveData = [
            "default-src 'self'; script-src 'self' 'unsafe-inline'",
            "script-src 'self' https://cdn.example.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
        ];

        foreach ($sensitiveData as $originalPolicy) {
            $request = Request::create('/api/csp-report', 'POST', [], [], [], [], json_encode([
                'csp-report' => [
                    'violated-directive' => 'script-src',
                    'blocked-uri' => 'https://example.com/script.js',
                    'document-uri' => 'https://app.test/',
                    'original-policy' => $originalPolicy,
                ],
            ]));

            $request->headers->set('Content-Type', 'application/json');

            $violation = $service->processCspViolationFromRequest($request);
            $metadata = $violation->metadata;

            // Property: Sensitive data must be encrypted
            $this->assertNotEquals($originalPolicy, $metadata['original_policy'],
                "Sensitive data not encrypted: {$originalPolicy}");

            // Property: Encrypted data must be decryptable
            $decrypted = decrypt($metadata['original_policy']);
            $this->assertEquals($originalPolicy, $decrypted,
                "Encrypted data not properly decryptable: {$originalPolicy}");
        }
    }

    /**
     * Property: Performance must remain within bounds regardless of load
     * 
     * @test
     */
    public function performance_bounds_property(): void
    {
        $service = app(SecurityAnalyticsMcpService::class);
        $maxProcessingTime = 100; // milliseconds
        
        // Test with various load patterns
        $loadPatterns = [
            'single_violation' => 1,
            'moderate_load' => 10,
            'high_load' => 50,
        ];

        foreach ($loadPatterns as $pattern => $count) {
            $startTime = microtime(true);

            for ($i = 0; $i < $count; $i++) {
                $request = Request::create('/api/csp-report', 'POST', [], [], [], [], json_encode([
                    'csp-report' => [
                        'violated-directive' => 'script-src',
                        'blocked-uri' => "https://example.com/script-{$i}.js",
                        'document-uri' => 'https://app.test/',
                    ],
                ]));

                $request->headers->set('Content-Type', 'application/json');

                $violation = $service->processCspViolationFromRequest($request);
                $this->assertInstanceOf(SecurityViolation::class, $violation);
            }

            $processingTime = (microtime(true) - $startTime) * 1000;
            $avgTimePerViolation = $processingTime / $count;

            $this->assertLessThan($maxProcessingTime, $avgTimePerViolation,
                "Processing time exceeded limit for {$pattern}: {$avgTimePerViolation}ms > {$maxProcessingTime}ms");
        }
    }

    /**
     * Property: All security events must be properly logged for audit trail
     * 
     * @test
     */
    public function audit_trail_completeness_property(): void
    {
        $service = app(SecurityAnalyticsMcpService::class);
        
        // Track expected log entries
        $expectedLogs = [];
        
        Log::shouldReceive('info')
            ->atLeast()
            ->times(3)
            ->with('Security event: csp_violation_processed', \Mockery::type('array'))
            ->andReturnUsing(function ($message, $context) use (&$expectedLogs) {
                $expectedLogs[] = $context;
            });

        // Process multiple violations
        for ($i = 0; $i < 3; $i++) {
            $request = Request::create('/api/csp-report', 'POST', [], [], [], [], json_encode([
                'csp-report' => [
                    'violated-directive' => 'script-src',
                    'blocked-uri' => "https://example.com/script-{$i}.js",
                    'document-uri' => 'https://app.test/',
                ],
            ]));

            $request->headers->set('Content-Type', 'application/json');

            $violation = $service->processCspViolationFromRequest($request);
            $this->assertInstanceOf(SecurityViolation::class, $violation);
        }

        // Property: All violations must have corresponding audit logs
        $this->assertCount(3, $expectedLogs);

        foreach ($expectedLogs as $logEntry) {
            $this->assertArrayHasKey('violation_id', $logEntry);
            $this->assertArrayHasKey('severity', $logEntry);
            $this->assertArrayHasKey('classification', $logEntry);
            $this->assertArrayHasKey('timestamp', $logEntry);
        }
    }
}