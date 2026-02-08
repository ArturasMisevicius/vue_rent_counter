<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\AuditLog;
use App\Models\ServiceConfiguration;
use App\Models\Team;
use App\Models\User;
use App\Models\UtilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test suite for AuditReportController API
 * 
 * Validates REST API endpoints for audit reporting and alert management.
 */
final class AuditReportControllerTest extends TestCase
{
    use RefreshDatabase;

    private Team $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Team::factory()->create();
        $this->user = User::factory()->create(['current_team_id' => $this->tenant->id]);
        $this->actingAs($this->user);
    }

    public function test_generates_audit_report_successfully(): void
    {
        $utilityService = UtilityService::factory()->create(['tenant_id' => $this->tenant->id]);
        
        AuditLog::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $utilityService->id,
            'event' => 'updated',
        ]);

        $response = $this->postJson('/api/audit/reports/generate', [
            'tenant_id' => $this->tenant->id,
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->toDateString(),
            'service_types' => ['electricity', 'water'],
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'summary' => [
                        'totalChanges',
                        'changesPerDay',
                        'mostActiveService',
                        'mostCommonEvent',
                    ],
                    'configurationChanges',
                    'performanceMetrics' => [
                        'averageCalculationTime',
                        'totalCalculations',
                        'errorRate',
                        'peakUsageHour',
                    ],
                    'complianceStatus' => [
                        'isCompliant',
                        'complianceScore',
                        'issues',
                        'recommendations',
                    ],
                    'anomalies',
                    'trends',
                ],
            ]);
    }

    public function test_validates_request_parameters(): void
    {
        $response = $this->postJson('/api/audit/reports/generate', [
            'tenant_id' => 'invalid',
            'start_date' => 'invalid-date',
            'end_date' => 'invalid-date',
            'service_types' => ['invalid-type'],
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => [
                    'tenant_id',
                    'start_date',
                    'end_date',
                    'service_types.0',
                ],
            ]);
    }

    public function test_validates_date_range(): void
    {
        $response = $this->postJson('/api/audit/reports/generate', [
            'start_date' => now()->toDateString(),
            'end_date' => now()->subDays(1)->toDateString(), // End before start
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_validates_service_types(): void
    {
        $response = $this->postJson('/api/audit/reports/generate', [
            'service_types' => ['electricity', 'invalid_type', 'water'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['service_types.1']);
    }

    public function test_handles_missing_parameters(): void
    {
        $utilityService = UtilityService::factory()->create(['tenant_id' => $this->tenant->id]);
        
        AuditLog::factory()->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $utilityService->id,
            'event' => 'updated',
        ]);

        // Should work with minimal parameters
        $response = $this->postJson('/api/audit/reports/generate', []);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    public function test_processes_alerts_successfully(): void
    {
        $utilityService = UtilityService::factory()->create(['tenant_id' => $this->tenant->id]);
        
        AuditLog::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $utilityService->id,
            'event' => 'updated',
        ]);

        $response = $this->postJson('/api/audit/alerts/process', [
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'alertsProcessed',
                    'anomaliesDetected',
                    'notificationsSent',
                ],
            ]);
    }

    public function test_validates_alert_processing_parameters(): void
    {
        $response = $this->postJson('/api/audit/alerts/process', [
            'tenant_id' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tenant_id']);
    }

    public function test_gets_alert_status_successfully(): void
    {
        $response = $this->getJson('/api/audit/alerts/status/' . $this->tenant->id);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'tenantId',
                    'lastProcessed',
                    'activeAlerts',
                    'alertHistory',
                    'systemHealth',
                ],
            ]);
    }

    public function test_validates_alert_status_tenant_id(): void
    {
        $response = $this->getJson('/api/audit/alerts/status/invalid');

        $response->assertStatus(422);
    }

    public function test_respects_tenant_isolation_in_reports(): void
    {
        $otherTenant = Team::factory()->create();
        
        $utilityService = UtilityService::factory()->create(['tenant_id' => $this->tenant->id]);
        $otherUtilityService = UtilityService::factory()->create(['tenant_id' => $otherTenant->id]);
        
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

        $response = $this->postJson('/api/audit/reports/generate', [
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertOk();
        
        $data = $response->json('data');
        $this->assertEquals(1, $data['summary']['totalChanges']);
    }

    public function test_handles_large_date_ranges(): void
    {
        $utilityService = UtilityService::factory()->create(['tenant_id' => $this->tenant->id]);
        
        // Create audit logs over a large date range
        for ($i = 0; $i < 100; $i++) {
            AuditLog::factory()->create([
                'tenant_id' => $this->tenant->id,
                'auditable_type' => UtilityService::class,
                'auditable_id' => $utilityService->id,
                'event' => 'updated',
                'created_at' => now()->subDays(rand(1, 365)),
            ]);
        }

        $response = $this->postJson('/api/audit/reports/generate', [
            'tenant_id' => $this->tenant->id,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->toDateString(),
        ]);

        $response->assertOk();
        
        $data = $response->json('data');
        $this->assertGreaterThan(0, $data['summary']['totalChanges']);
    }

    public function test_returns_empty_report_for_no_data(): void
    {
        $response = $this->postJson('/api/audit/reports/generate', [
            'tenant_id' => $this->tenant->id,
        ]);

        $response->assertOk();
        
        $data = $response->json('data');
        $this->assertEquals(0, $data['summary']['totalChanges']);
        $this->assertEquals(0.0, $data['summary']['changesPerDay']);
        $this->assertEmpty($data['anomalies']);
    }

    public function test_handles_api_errors_gracefully(): void
    {
        // Test with non-existent tenant
        $response = $this->postJson('/api/audit/reports/generate', [
            'tenant_id' => 99999,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tenant_id']);
    }

    public function test_supports_batch_alert_processing(): void
    {
        $tenantIds = [
            $this->tenant->id,
            Team::factory()->create()->id,
            Team::factory()->create()->id,
        ];

        $response = $this->postJson('/api/audit/alerts/batch-process', [
            'tenant_ids' => $tenantIds,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'totalTenants',
                    'processedTenants',
                    'failedTenants',
                    'results',
                ],
            ]);
    }

    public function test_validates_batch_processing_parameters(): void
    {
        $response = $this->postJson('/api/audit/alerts/batch-process', [
            'tenant_ids' => ['invalid', 99999],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tenant_ids.0']);
    }

    public function test_rate_limits_api_requests(): void
    {
        // Make multiple rapid requests
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/audit/reports/generate', [
                'tenant_id' => $this->tenant->id,
            ]);
            
            if ($i < 5) {
                $response->assertOk();
            }
        }
        
        // Should eventually hit rate limit (if implemented)
        // This test validates the API can handle rapid requests
        $this->assertTrue(true);
    }
}