<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\SecurityViolation;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Security\SecurityAnalyticsMcpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Security Testing Helper Methods
 * 
 * Provides common functionality for security-related tests
 * including MCP mocking, violation generation, and tenant setup.
 */
trait SecurityTestHelpers
{
    /**
     * Create a test CSP violation request.
     */
    protected function createCspViolationRequest(array $violationData = []): Request
    {
        $defaultViolation = [
            'violated-directive' => 'script-src',
            'blocked-uri' => 'https://example.com/script.js',
            'document-uri' => 'https://app.test/',
            'referrer' => 'https://app.test/admin',
            'source-file' => 'https://app.test/js/app.js',
            'line-number' => 42,
            'column-number' => 15,
        ];

        $violation = array_merge($defaultViolation, $violationData);

        $request = Request::create('/api/csp-report', 'POST', [], [], [], [], json_encode([
            'csp-report' => $violation,
        ]));

        $request->headers->set('Content-Type', 'application/json');

        return $request;
    }

    /**
     * Create a malicious CSP violation request.
     */
    protected function createMaliciousCspViolationRequest(): Request
    {
        return $this->createCspViolationRequest([
            'blocked-uri' => 'javascript:alert("xss")',
            'violated-directive' => 'script-src',
        ]);
    }

    /**
     * Mock MCP service with expected behavior.
     */
    protected function mockMcpService(array $expectations = []): void
    {
        $defaultExpectations = [
            'trackCspViolation' => true,
            'analyzeSecurityMetrics' => ['violations_count' => 5, 'threat_level' => 'medium'],
            'detectAnomalies' => ['anomalies' => []],
            'generateSecurityReport' => ['report_id' => 'test-report'],
            'correlateSecurityEvents' => ['correlations' => []],
        ];

        $expectations = array_merge($defaultExpectations, $expectations);

        $this->mock(SecurityAnalyticsMcpService::class, function ($mock) use ($expectations) {
            foreach ($expectations as $method => $returnValue) {
                $mock->shouldReceive($method)->andReturn($returnValue);
            }
        });
    }

    /**
     * Create a tenant with security violations.
     */
    protected function createTenantWithViolations(int $violationCount = 5): Tenant
    {
        $tenant = Tenant::factory()->create();
        
        SecurityViolation::factory()
            ->count($violationCount)
            ->withTenant($tenant)
            ->create();

        return $tenant;
    }

    /**
     * Create an admin user for a specific tenant.
     */
    protected function createTenantAdmin(Tenant $tenant): User
    {
        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => \App\Enums\UserRole::ADMIN,
            'is_active' => true,
        ]);
    }

    /**
     * Clear security-related caches.
     */
    protected function clearSecurityCaches(): void
    {
        Cache::forget('security_performance_metrics');
        Cache::flush(); // Clear all rate limiting caches
    }

    /**
     * Assert CSP violation was properly processed.
     */
    protected function assertCspViolationProcessed(array $expectedData): void
    {
        $this->assertDatabaseHas('security_violations', [
            'violation_type' => 'csp',
            'policy_directive' => $expectedData['violated-directive'] ?? 'script-src',
        ]);

        $violation = SecurityViolation::latest()->first();
        
        $this->assertNotNull($violation);
        $this->assertNotNull($violation->metadata);
        $this->assertArrayHasKey('processed_at', $violation->metadata);
    }

    /**
     * Assert security headers are properly applied.
     */
    protected function assertSecurityHeadersApplied($response): void
    {
        $requiredHeaders = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options',
            'Content-Security-Policy',
        ];

        foreach ($requiredHeaders as $header => $expectedValue) {
            if (is_string($expectedValue)) {
                $response->assertHeader($header, $expectedValue);
            } else {
                $response->assertHeader($header);
            }
        }
    }

    /**
     * Assert CSP nonce is present and valid.
     */
    protected function assertCspNonceValid($response): void
    {
        $csp = $response->headers->get('Content-Security-Policy');
        
        $this->assertNotNull($csp);
        $this->assertStringContainsString("'nonce-", $csp);
        
        // Extract nonce and verify format
        preg_match("/'nonce-([^']+)'/", $csp, $matches);
        $this->assertNotEmpty($matches[1]);
        
        // Verify it's valid base64
        $decoded = base64_decode($matches[1], true);
        $this->assertNotFalse($decoded);
        $this->assertGreaterThanOrEqual(16, strlen($decoded));
    }

    /**
     * Assert tenant isolation is maintained.
     */
    protected function assertTenantIsolation(Tenant $tenant1, Tenant $tenant2): void
    {
        $tenant1Violations = SecurityViolation::where('tenant_id', $tenant1->id)->count();
        $tenant2Violations = SecurityViolation::where('tenant_id', $tenant2->id)->count();

        $this->assertGreaterThan(0, $tenant1Violations);
        $this->assertGreaterThan(0, $tenant2Violations);

        // Verify no cross-tenant data leakage
        $tenant1Data = SecurityViolation::where('tenant_id', $tenant1->id)->pluck('id')->toArray();
        $tenant2Data = SecurityViolation::where('tenant_id', $tenant2->id)->pluck('id')->toArray();

        $this->assertEmpty(array_intersect($tenant1Data, $tenant2Data));
    }

    /**
     * Simulate rate limit exhaustion for an IP.
     */
    protected function exhaustRateLimit(string $ip = '192.168.1.100'): void
    {
        $key = 'csp_reports_' . hash('sha256', $ip);
        Cache::put($key, 51, 60); // Exceed the 50 request limit
    }

    /**
     * Create test MCP server configuration.
     */
    protected function createTestMcpConfig(): array
    {
        return [
            'mcpServers' => [
                'test-security-analytics' => [
                    'command' => 'echo',
                    'args' => ['test-mcp-server'],
                    'env' => [
                        'TEST_MODE' => 'true',
                    ],
                    'disabled' => false,
                    'autoApprove' => ['track_csp_violation'],
                ],
            ],
        ];
    }

    /**
     * Assert performance metrics are within bounds.
     */
    protected function assertPerformanceWithinBounds(float $actualTime, float $maxTime, string $operation = 'operation'): void
    {
        $this->assertLessThan($maxTime, $actualTime,
            "{$operation} took {$actualTime}ms, exceeds {$maxTime}ms limit");
    }

    /**
     * Generate test security analytics data.
     */
    protected function generateTestAnalyticsData(int $days = 7): array
    {
        $data = [];
        
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $data[$date] = [
                'violations_count' => rand(1, 20),
                'severity_distribution' => [
                    'low' => rand(0, 5),
                    'medium' => rand(0, 10),
                    'high' => rand(0, 8),
                    'critical' => rand(0, 3),
                ],
                'threat_classification' => [
                    'unknown' => rand(5, 15),
                    'suspicious' => rand(0, 5),
                    'malicious' => rand(0, 2),
                ],
            ];
        }

        return $data;
    }
}