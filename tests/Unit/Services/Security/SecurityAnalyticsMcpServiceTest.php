<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Security;

use App\Models\SecurityViolation;
use App\Models\Tenant;
use App\Services\Security\SecurityAnalyticsMcpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * @covers \App\Services\Security\SecurityAnalyticsMcpService
 */
final class SecurityAnalyticsMcpServiceTest extends TestCase
{
    use RefreshDatabase;

    private SecurityAnalyticsMcpService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SecurityAnalyticsMcpService::class);
    }

    public function test_tracks_csp_violation_successfully(): void
    {
        $violationData = [
            'violated-directive' => 'script-src',
            'blocked-uri' => 'https://malicious.example.com/script.js',
            'document-uri' => 'https://app.example.com/dashboard',
            'referrer' => 'https://app.example.com/',
        ];

        $result = $this->service->trackCspViolation($violationData);

        $this->assertTrue($result);
    }

    public function test_handles_mcp_service_failure_gracefully(): void
    {
        Log::shouldReceive('error')->once();

        // Test with invalid data to trigger error handling
        $result = $this->service->trackCspViolation([]);

        $this->assertFalse($result);
    }

    public function test_analyzes_security_metrics(): void
    {
        $filters = [
            'start_date' => now()->subDays(7)->toISOString(),
            'end_date' => now()->toISOString(),
            'violation_type' => 'csp',
        ];

        $metrics = $this->service->analyzeSecurityMetrics($filters);

        $this->assertIsArray($metrics);
    }

    public function test_detects_anomalies(): void
    {
        $parameters = [
            'window' => '1h',
            'sensitivity' => 'medium',
        ];

        $anomalies = $this->service->detectAnomalies($parameters);

        $this->assertIsArray($anomalies);
    }

    public function test_generates_security_report(): void
    {
        $config = [
            'type' => 'summary',
            'format' => 'json',
            'include_charts' => true,
        ];

        $report = $this->service->generateSecurityReport($config);

        $this->assertIsArray($report);
    }

    public function test_correlates_security_events(): void
    {
        $events = [
            [
                'type' => 'csp_violation',
                'timestamp' => now()->toISOString(),
                'severity' => 'high',
            ],
            [
                'type' => 'xss_attempt',
                'timestamp' => now()->addMinutes(2)->toISOString(),
                'severity' => 'critical',
            ],
        ];

        $correlations = $this->service->correlateSecurityEvents($events);

        $this->assertIsArray($correlations);
    }

    public function test_processes_csp_violation_from_request(): void
    {
        $tenant = Tenant::factory()->create();
        $this->mockTenantContext($tenant);

        $request = Request::create('/csp-report', 'POST', [], [], [], [], json_encode([
            'csp-report' => [
                'violated-directive' => 'script-src \'self\'',
                'blocked-uri' => 'https://evil.example.com/malware.js',
                'document-uri' => 'https://app.example.com/dashboard',
                'referrer' => 'https://app.example.com/',
                'source-file' => 'https://app.example.com/js/app.js',
                'line-number' => 42,
                'column-number' => 15,
            ],
        ]));

        $request->headers->set('Content-Type', 'application/json');

        $violation = $this->service->processCspViolationFromRequest($request);

        $this->assertInstanceOf(SecurityViolation::class, $violation);
        $this->assertEquals('csp', $violation->violation_type);
        $this->assertEquals('script-src \'self\'', $violation->policy_directive);
        $this->assertEquals('https://evil.example.com/malware.js', $violation->blocked_uri);
        $this->assertEquals($tenant->id, $violation->tenant_id);
        $this->assertEquals(42, $violation->line_number);
        $this->assertEquals(15, $violation->column_number);
    }

    public function test_determines_severity_correctly(): void
    {
        $scriptViolation = [
            'violated-directive' => 'script-src',
            'blocked-uri' => 'https://example.com/script.js',
        ];

        $violation = $this->service->processCspViolationFromRequest(
            $this->createCspRequest($scriptViolation)
        );

        $this->assertEquals('high', $violation->severity_level->value);
    }

    public function test_classifies_threats_correctly(): void
    {
        $maliciousViolation = [
            'violated-directive' => 'script-src',
            'blocked-uri' => 'javascript:alert(1)',
        ];

        $violation = $this->service->processCspViolationFromRequest(
            $this->createCspRequest($maliciousViolation)
        );

        $this->assertEquals('malicious', $violation->threat_classification->value);
    }

    public function test_handles_invalid_csp_report_format(): void
    {
        $request = Request::create('/csp-report', 'POST', [], [], [], [], json_encode([
            'invalid' => 'format',
        ]));

        $violation = $this->service->processCspViolationFromRequest($request);

        $this->assertNull($violation);
    }

    public function test_includes_metadata_in_violation(): void
    {
        $tenant = Tenant::factory()->create();
        $this->mockTenantContext($tenant);

        $request = $this->createCspRequest([
            'violated-directive' => 'script-src',
            'blocked-uri' => 'https://example.com/script.js',
            'original-policy' => 'script-src \'self\' \'unsafe-inline\'',
        ]);

        $request->headers->set('X-Request-ID', 'test-request-123');

        $violation = $this->service->processCspViolationFromRequest($request);

        $this->assertArrayHasKey('original_policy', $violation->metadata);
        $this->assertArrayHasKey('mcp_tracked', $violation->metadata);
        $this->assertArrayHasKey('request_ip', $violation->metadata);
        $this->assertArrayHasKey('request_id', $violation->metadata);
        $this->assertEquals('test-request-123', $violation->metadata['request_id']);
    }

    private function createCspRequest(array $reportData): Request
    {
        $request = Request::create('/csp-report', 'POST', [], [], [], [], json_encode([
            'csp-report' => $reportData,
        ]));

        $request->headers->set('Content-Type', 'application/json');

        return $request;
    }

    private function mockTenantContext(Tenant $tenant): void
    {
        // Mock tenant context
        app()->instance('tenant', $tenant);
    }
}