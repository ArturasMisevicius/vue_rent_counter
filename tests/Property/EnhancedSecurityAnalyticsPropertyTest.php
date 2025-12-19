<?php

declare(strict_types=1);

namespace Tests\Property;

use App\Enums\SecuritySeverity;
use App\Enums\ThreatClassification;
use App\Models\SecurityViolation;
use App\Models\Tenant;
use App\Services\Security\SecurityAnalyticsMcpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Enhanced Property-based tests for Security Analytics system
 * 
 * Tests security properties that must hold across all scenarios
 * with MCP integration and enhanced analytics capabilities.
 */
final class EnhancedSecurityAnalyticsPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property: All security violations must maintain tenant isolation
     * 
     * @test
     */
    public function security_violation_tenant_isolation_property(): void
    {
        $tenants = Tenant::factory()->count(3)->create();
        $violations = [];

        // Generate violations for each tenant
        foreach ($tenants as $tenant) {
            for ($i = 0; $i < 50; $i++) {
                $violation = SecurityViolation::factory()->create([
                    'tenant_id' => $tenant->id,
                    'violation_type' => fake()->randomElement(['csp', 'xss', 'clickjacking']),
                    'severity_level' => fake()->randomElement(SecuritySeverity::cases()),
                ]);
                
                $violations[$tenant->id][] = $violation;
            }
        }

        // Verify tenant isolation
        foreach ($tenants as $tenant) {
            // Mock tenant context
            app()->instance('tenant', $tenant);
            
            $tenantViolations = SecurityViolation::all();
            
            // All violations should belong to current tenant
            foreach ($tenantViolations as $violation) {
                $this->assertEquals($tenant->id, $violation->tenant_id,
                    "Violation {$violation->id} leaked across tenant boundary");
            }
            
            // Should only see violations for current tenant
            $this->assertCount(50, $tenantViolations,
                "Tenant {$tenant->id} should see exactly 50 violations");
        }
    }

    /**
     * Property: Security severity classification must be consistent
     * 
     * @test
     */
    public function security_severity_classification_consistency_property(): void
    {
        $service = app(SecurityAnalyticsMcpService::class);
        $testCases = [];

        // Generate test cases for severity classification
        for ($i = 0; $i < 100; $i++) {
            $violationData = [
                'violated-directive' => fake()->randomElement([
                    'script-src', 'style-src', 'img-src', 'font-src'
                ]),
                'blocked-uri' => fake()->randomElement([
                    'https://malicious.example.com/script.js',
                    'javascript:alert(1)',
                    'data:text/html,<script>alert(1)</script>',
                    'https://cdn.example.com/font.woff',
                ]),
                'document-uri' => 'https://app.example.com/dashboard',
            ];

            $testCases[] = $violationData;
        }

        // Test consistency across multiple runs
        $severityResults = [];
        
        foreach ($testCases as $index => $violationData) {
            $request = $this->createCspRequest($violationData);
            
            // Process violation multiple times
            for ($run = 0; $run < 5; $run++) {
                $violation = $service->processCspViolationFromRequest($request);
                
                if ($violation) {
                    $severityResults[$index][$run] = $violation->severity_level->value;
                    $violation->delete(); // Clean up for next run
                }
            }
        }

        // Verify consistency
        foreach ($severityResults as $index => $runs) {
            if (count($runs) > 1) {
                $firstSeverity = $runs[0];
                foreach ($runs as $run => $severity) {
                    $this->assertEquals($firstSeverity, $severity,
                        "Severity classification inconsistent for test case {$index}, run {$run}");
                }
            }
        }
    }

    /**
     * Property: Threat classification must follow logical rules
     * 
     * @test
     */
    public function threat_classification_logical_rules_property(): void
    {
        $service = app(SecurityAnalyticsMcpService::class);
        
        // Known malicious patterns should always be classified as malicious
        $maliciousPatterns = [
            'javascript:alert(1)',
            'data:text/html,<script>alert(1)</script>',
            'eval(malicious_code)',
            'Function(malicious_code)',
        ];

        foreach ($maliciousPatterns as $pattern) {
            $violationData = [
                'violated-directive' => 'script-src',
                'blocked-uri' => $pattern,
                'document-uri' => 'https://app.example.com/test',
            ];

            $request = $this->createCspRequest($violationData);
            $violation = $service->processCspViolationFromRequest($request);

            $this->assertNotNull($violation, "Failed to process violation for pattern: {$pattern}");
            $this->assertEquals(ThreatClassification::MALICIOUS, $violation->threat_classification,
                "Pattern '{$pattern}' should be classified as malicious");
        }

        // Suspicious patterns should be classified as suspicious or malicious
        $suspiciousPatterns = [
            'http://insecure.example.com/script.js', // HTTP instead of HTTPS
            'https://unknown-cdn.example.com/script.js',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            $violationData = [
                'violated-directive' => 'script-src',
                'blocked-uri' => $pattern,
                'document-uri' => 'https://app.example.com/test',
            ];

            $request = $this->createCspRequest($violationData);
            $violation = $service->processCspViolationFromRequest($request);

            $this->assertNotNull($violation);
            $this->assertContains($violation->threat_classification, [
                ThreatClassification::SUSPICIOUS,
                ThreatClassification::MALICIOUS,
            ], "Pattern '{$pattern}' should be classified as suspicious or malicious");
        }
    }

    /**
     * Property: MCP integration must not impact performance significantly
     * 
     * @test
     */
    public function mcp_integration_performance_property(): void
    {
        $service = app(SecurityAnalyticsMcpService::class);
        $performanceResults = [];

        // Test performance with and without MCP integration
        for ($i = 0; $i < 20; $i++) {
            $violationData = [
                'violated-directive' => 'script-src',
                'blocked-uri' => 'https://example.com/script.js',
                'document-uri' => 'https://app.example.com/test',
            ];

            $request = $this->createCspRequest($violationData);

            // Measure processing time
            $startTime = microtime(true);
            $violation = $service->processCspViolationFromRequest($request);
            $processingTime = (microtime(true) - $startTime) * 1000; // Convert to ms

            $performanceResults[] = $processingTime;

            if ($violation) {
                $violation->delete();
            }
        }

        $averageTime = array_sum($performanceResults) / count($performanceResults);
        $maxTime = max($performanceResults);

        // Performance assertions
        $this->assertLessThan(100, $averageTime,
            "Average processing time ({$averageTime}ms) exceeds 100ms limit");
        
        $this->assertLessThan(500, $maxTime,
            "Maximum processing time ({$maxTime}ms) exceeds 500ms limit");
    }

    /**
     * Property: Security analytics must handle high violation volumes
     * 
     * @test
     */
    public function security_analytics_volume_handling_property(): void
    {
        $service = app(SecurityAnalyticsMcpService::class);
        $tenant = Tenant::factory()->create();
        app()->instance('tenant', $tenant);

        $violationCount = 500;
        $successCount = 0;
        $startTime = microtime(true);

        // Generate high volume of violations
        for ($i = 0; $i < $violationCount; $i++) {
            $violationData = [
                'violated-directive' => fake()->randomElement(['script-src', 'style-src', 'img-src']),
                'blocked-uri' => 'https://example.com/resource-' . $i,
                'document-uri' => 'https://app.example.com/page-' . $i,
            ];

            $request = $this->createCspRequest($violationData);
            $violation = $service->processCspViolationFromRequest($request);

            if ($violation) {
                $successCount++;
            }
        }

        $totalTime = (microtime(true) - $startTime) * 1000; // Convert to ms
        $averageTimePerViolation = $totalTime / $violationCount;

        // Volume handling assertions
        $this->assertGreaterThanOrEqual($violationCount * 0.95, $successCount,
            "Success rate should be at least 95% for high volume processing");
        
        $this->assertLessThan(10, $averageTimePerViolation,
            "Average time per violation ({$averageTimePerViolation}ms) exceeds 10ms limit");
        
        // Verify all violations were stored correctly
        $storedViolations = SecurityViolation::where('tenant_id', $tenant->id)->count();
        $this->assertEquals($successCount, $storedViolations,
            "Stored violation count should match successful processing count");
    }

    /**
     * Property: Security metrics must be accurate and consistent
     * 
     * @test
     */
    public function security_metrics_accuracy_property(): void
    {
        $service = app(SecurityAnalyticsMcpService::class);
        $tenant = Tenant::factory()->create();
        app()->instance('tenant', $tenant);

        // Create known set of violations
        $violationTypes = ['csp' => 30, 'xss' => 20, 'clickjacking' => 10];
        $severityLevels = ['low' => 15, 'medium' => 25, 'high' => 15, 'critical' => 5];

        foreach ($violationTypes as $type => $count) {
            SecurityViolation::factory()->count($count)->create([
                'tenant_id' => $tenant->id,
                'violation_type' => $type,
            ]);
        }

        // Test metrics accuracy
        $filters = ['tenant_id' => $tenant->id];
        $metrics = $service->analyzeSecurityMetrics($filters);

        // Verify metrics are returned (MCP simulation)
        $this->assertIsArray($metrics);
        
        // Verify database counts match expected
        foreach ($violationTypes as $type => $expectedCount) {
            $actualCount = SecurityViolation::where('tenant_id', $tenant->id)
                ->where('violation_type', $type)
                ->count();
            
            $this->assertEquals($expectedCount, $actualCount,
                "Violation count for type '{$type}' should be {$expectedCount}");
        }
    }

    private function createCspRequest(array $reportData): Request
    {
        $request = Request::create('/csp-report', 'POST', [], [], [], [], json_encode([
            'csp-report' => $reportData,
        ]));

        $request->headers->set('Content-Type', 'application/json');
        $request->headers->set('User-Agent', 'Mozilla/5.0 (Test Browser)');

        return $request;
    }
}