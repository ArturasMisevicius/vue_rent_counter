<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Audit;

use App\Models\AuditLog;
use App\Models\ServiceConfiguration;
use App\Models\Team;
use App\Models\User;
use App\Models\UtilityService;
use App\Services\Audit\UniversalServiceAuditReporter;
use App\ValueObjects\Audit\AuditReportData;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite for UniversalServiceAuditReporter
 * 
 * Validates comprehensive audit reporting functionality for universal services
 * including change tracking, performance metrics, and compliance reporting.
 */
final class UniversalServiceAuditReporterTest extends TestCase
{
    use RefreshDatabase;

    private UniversalServiceAuditReporter $auditReporter;
    private Team $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Team::factory()->create();
        $this->user = User::factory()->create(['current_team_id' => $this->tenant->id]);
        $this->actingAs($this->user);
        
        $this->auditReporter = app(UniversalServiceAuditReporter::class);
    }

    public function test_generates_comprehensive_audit_report(): void
    {
        // Create test data
        $utilityService = UtilityService::factory()->create(['tenant_id' => $this->tenant->id]);
        $serviceConfig = ServiceConfiguration::factory()->create([
            'tenant_id' => $this->tenant->id,
            'utility_service_id' => $utilityService->id,
        ]);
        
        // Create audit logs
        AuditLog::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => ServiceConfiguration::class,
            'auditable_id' => $serviceConfig->id,
            'event' => 'updated',
            'created_at' => now()->subDays(15),
        ]);
        
        AuditLog::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $utilityService->id,
            'event' => 'created',
            'created_at' => now()->subDays(10),
        ]);

        // Generate report
        $report = $this->auditReporter->generateReport(
            tenantId: $this->tenant->id,
            startDate: now()->subDays(30),
            endDate: now(),
            serviceTypes: ['electricity', 'water'],
        );

        // Assertions
        $this->assertInstanceOf(AuditReportData::class, $report);
        $this->assertNotNull($report->summary);
        $this->assertNotNull($report->configurationChanges);
        $this->assertNotNull($report->performanceMetrics);
        $this->assertNotNull($report->complianceStatus);
        $this->assertIsArray($report->anomalies);
        $this->assertIsArray($report->trends);
        
        // Verify summary contains expected data
        $this->assertGreaterThan(0, $report->summary->totalChanges);
        $this->assertIsFloat($report->summary->getChangesPerDay());
    }

    public function test_filters_report_by_date_range(): void
    {
        $utilityService = UtilityService::factory()->create(['tenant_id' => $this->tenant->id]);
        
        // Create audit logs in different time periods
        AuditLog::factory()->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $utilityService->id,
            'event' => 'updated',
            'created_at' => now()->subDays(45), // Outside range
        ]);
        
        AuditLog::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $utilityService->id,
            'event' => 'updated',
            'created_at' => now()->subDays(15), // Within range
        ]);

        $report = $this->auditReporter->generateReport(
            tenantId: $this->tenant->id,
            startDate: now()->subDays(30),
            endDate: now(),
        );

        // Should only include logs within the date range
        $this->assertEquals(3, $report->summary->totalChanges);
    }

    public function test_filters_report_by_service_types(): void
    {
        $electricityService = UtilityService::factory()->create([
            'tenant_id' => $this->tenant->id,
            'service_type' => 'electricity',
        ]);
        
        $waterService = UtilityService::factory()->create([
            'tenant_id' => $this->tenant->id,
            'service_type' => 'water',
        ]);
        
        $heatingService = UtilityService::factory()->create([
            'tenant_id' => $this->tenant->id,
            'service_type' => 'heating',
        ]);

        // Create audit logs for different service types
        AuditLog::factory()->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $electricityService->id,
            'event' => 'updated',
        ]);
        
        AuditLog::factory()->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $waterService->id,
            'event' => 'updated',
        ]);
        
        AuditLog::factory()->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $heatingService->id,
            'event' => 'updated',
        ]);

        // Filter by specific service types
        $report = $this->auditReporter->generateReport(
            tenantId: $this->tenant->id,
            serviceTypes: ['electricity', 'water'],
        );

        // Should only include electricity and water services
        $this->assertEquals(2, $report->summary->totalChanges);
    }

    public function test_respects_tenant_isolation(): void
    {
        $otherTenant = Team::factory()->create();
        
        $utilityService = UtilityService::factory()->create(['tenant_id' => $this->tenant->id]);
        $otherUtilityService = UtilityService::factory()->create(['tenant_id' => $otherTenant->id]);
        
        // Create audit logs for both tenants
        AuditLog::factory()->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $utilityService->id,
            'event' => 'updated',
        ]);
        
        AuditLog::factory()->create([
            'tenant_id' => $otherTenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $otherUtilityService->id,
            'event' => 'updated',
        ]);

        $report = $this->auditReporter->generateReport(tenantId: $this->tenant->id);

        // Should only include current tenant's data
        $this->assertEquals(1, $report->summary->totalChanges);
    }

    public function test_caches_report_results(): void
    {
        $utilityService = UtilityService::factory()->create(['tenant_id' => $this->tenant->id]);
        
        AuditLog::factory()->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $utilityService->id,
            'event' => 'updated',
        ]);

        // First call should generate and cache
        $report1 = $this->auditReporter->generateReport(tenantId: $this->tenant->id);
        
        // Second call should use cache
        $report2 = $this->auditReporter->generateReport(tenantId: $this->tenant->id);

        $this->assertEquals($report1->summary->totalChanges, $report2->summary->totalChanges);
        $this->assertEquals($report1->summary->getChangesPerDay(), $report2->summary->getChangesPerDay());
    }

    public function test_handles_empty_data_gracefully(): void
    {
        // Generate report with no audit data
        $report = $this->auditReporter->generateReport(tenantId: $this->tenant->id);

        $this->assertInstanceOf(AuditReportData::class, $report);
        $this->assertEquals(0, $report->summary->totalChanges);
        $this->assertEquals(0.0, $report->summary->getChangesPerDay());
        $this->assertEmpty($report->anomalies);
        $this->assertEmpty($report->trends);
    }

    public function test_generates_performance_metrics(): void
    {
        $utilityService = UtilityService::factory()->create(['tenant_id' => $this->tenant->id]);
        
        AuditLog::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $utilityService->id,
            'event' => 'billing_calculated',
            'created_at' => now()->subHours(rand(1, 24)),
        ]);

        $report = $this->auditReporter->generateReport(tenantId: $this->tenant->id);

        $this->assertNotNull($report->performanceMetrics);
        $this->assertIsFloat($report->performanceMetrics->averageCalculationTime);
        $this->assertIsInt($report->performanceMetrics->totalCalculations);
        $this->assertIsFloat($report->performanceMetrics->errorRate);
    }

    public function test_detects_anomalies(): void
    {
        $utilityService = UtilityService::factory()->create(['tenant_id' => $this->tenant->id]);
        
        // Create normal activity
        AuditLog::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $utilityService->id,
            'event' => 'updated',
            'created_at' => now()->subDays(rand(1, 30)),
        ]);
        
        // Create anomalous activity (many changes in short time)
        AuditLog::factory()->count(20)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $utilityService->id,
            'event' => 'updated',
            'created_at' => now()->subHour(),
        ]);

        $report = $this->auditReporter->generateReport(tenantId: $this->tenant->id);

        $this->assertNotEmpty($report->anomalies);
        $this->assertArrayHasKey('type', $report->anomalies[0]);
        $this->assertArrayHasKey('severity', $report->anomalies[0]);
        $this->assertArrayHasKey('description', $report->anomalies[0]);
    }

    public function test_generates_compliance_status(): void
    {
        $utilityService = UtilityService::factory()->create(['tenant_id' => $this->tenant->id]);
        
        AuditLog::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $utilityService->id,
            'event' => 'compliance_check',
        ]);

        $report = $this->auditReporter->generateReport(tenantId: $this->tenant->id);

        $this->assertNotNull($report->complianceStatus);
        $this->assertIsBool($report->complianceStatus->isCompliant);
        $this->assertIsFloat($report->complianceStatus->complianceScore);
        $this->assertIsArray($report->complianceStatus->issues);
        $this->assertIsArray($report->complianceStatus->recommendations);
    }
}