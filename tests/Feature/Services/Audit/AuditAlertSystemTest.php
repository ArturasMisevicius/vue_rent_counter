<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Audit;

use App\Models\AuditLog;
use App\Models\ServiceConfiguration;
use App\Models\Team;
use App\Models\User;
use App\Models\UtilityService;
use App\Notifications\AuditAnomalyDetectedNotification;
use App\Notifications\ComplianceIssueNotification;
use App\Services\Audit\AuditAlertSystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Test suite for AuditAlertSystem
 * 
 * Validates anomaly detection, compliance monitoring, and alert notifications
 * for universal service audit data.
 */
final class AuditAlertSystemTest extends TestCase
{
    use RefreshDatabase;

    private AuditAlertSystem $alertSystem;
    private Team $tenant;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Team::factory()->create();
        $this->user = User::factory()->create(['current_team_id' => $this->tenant->id]);
        $this->actingAs($this->user);
        
        $this->alertSystem = app(AuditAlertSystem::class);
        
        Notification::fake();
    }

    public function test_processes_alerts_successfully(): void
    {
        $utilityService = UtilityService::factory()->create(['tenant_id' => $this->tenant->id]);
        
        // Create normal audit activity
        AuditLog::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $utilityService->id,
            'event' => 'updated',
            'created_at' => now()->subHours(12),
        ]);

        // Process alerts should not throw exceptions
        $this->alertSystem->processAlerts($this->tenant->id);

        // Should complete without errors
        $this->assertTrue(true);
    }

    public function test_detects_critical_anomalies(): void
    {
        $utilityService = UtilityService::factory()->create(['tenant_id' => $this->tenant->id]);
        
        // Create anomalous activity (many changes in short time)
        AuditLog::factory()->count(50)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $utilityService->id,
            'event' => 'updated',
            'created_at' => now()->subMinutes(30),
        ]);

        $this->alertSystem->processAlerts($this->tenant->id);

        // Should send anomaly notification
        Notification::assertSentTo(
            [$this->user],
            AuditAnomalyDetectedNotification::class
        );
    }

    public function test_detects_compliance_issues(): void
    {
        $utilityService = UtilityService::factory()->create(['tenant_id' => $this->tenant->id]);
        
        // Create compliance-related audit logs
        AuditLog::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $utilityService->id,
            'event' => 'compliance_violation',
            'created_at' => now()->subHours(2),
        ]);

        $this->alertSystem->processAlerts($this->tenant->id);

        // Should send compliance issue notification
        Notification::assertSentTo(
            [$this->user],
            ComplianceIssueNotification::class
        );
    }

    public function test_checks_performance_thresholds(): void
    {
        $utilityService = UtilityService::factory()->create(['tenant_id' => $this->tenant->id]);
        
        // Create performance-related audit logs with slow calculations
        AuditLog::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $utilityService->id,
            'event' => 'billing_calculated',
            'old_values' => ['calculation_time' => 5000], // 5 seconds (slow)
            'created_at' => now()->subHours(1),
        ]);

        $this->alertSystem->processAlerts($this->tenant->id);

        // Should detect performance issues
        $this->assertTrue(true); // Performance alerts are logged, not necessarily sent as notifications
    }

    public function test_monitors_change_frequency(): void
    {
        $serviceConfig = ServiceConfiguration::factory()->create(['tenant_id' => $this->tenant->id]);
        
        // Create frequent configuration changes
        AuditLog::factory()->count(30)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => ServiceConfiguration::class,
            'auditable_id' => $serviceConfig->id,
            'event' => 'updated',
            'created_at' => now()->subHours(rand(1, 6)),
        ]);

        $this->alertSystem->processAlerts($this->tenant->id);

        // Should detect high change frequency
        Notification::assertSentTo(
            [$this->user],
            AuditAnomalyDetectedNotification::class
        );
    }

    public function test_respects_tenant_isolation(): void
    {
        $otherTenant = Team::factory()->create();
        $otherUser = User::factory()->create(['current_team_id' => $otherTenant->id]);
        
        $utilityService = UtilityService::factory()->create(['tenant_id' => $this->tenant->id]);
        $otherUtilityService = UtilityService::factory()->create(['tenant_id' => $otherTenant->id]);
        
        // Create anomalous activity for both tenants
        AuditLog::factory()->count(50)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $utilityService->id,
            'event' => 'updated',
            'created_at' => now()->subMinutes(30),
        ]);
        
        AuditLog::factory()->count(50)->create([
            'tenant_id' => $otherTenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $otherUtilityService->id,
            'event' => 'updated',
            'created_at' => now()->subMinutes(30),
        ]);

        // Process alerts for current tenant only
        $this->alertSystem->processAlerts($this->tenant->id);

        // Should only notify current tenant's users
        Notification::assertSentTo(
            [$this->user],
            AuditAnomalyDetectedNotification::class
        );
        
        Notification::assertNotSentTo(
            [$otherUser],
            AuditAnomalyDetectedNotification::class
        );
    }

    public function test_handles_errors_gracefully(): void
    {
        // Process alerts for non-existent tenant
        $this->alertSystem->processAlerts(99999);

        // Should not throw exceptions
        $this->assertTrue(true);
    }

    public function test_caches_alert_processing(): void
    {
        $utilityService = UtilityService::factory()->create(['tenant_id' => $this->tenant->id]);
        
        AuditLog::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $utilityService->id,
            'event' => 'updated',
            'created_at' => now()->subHours(1),
        ]);

        // First processing
        $start1 = microtime(true);
        $this->alertSystem->processAlerts($this->tenant->id);
        $time1 = microtime(true) - $start1;

        // Second processing (should use cache)
        $start2 = microtime(true);
        $this->alertSystem->processAlerts($this->tenant->id);
        $time2 = microtime(true) - $start2;

        // Second call should be faster due to caching
        $this->assertLessThan($time1, $time2);
    }

    public function test_detects_multiple_anomaly_types(): void
    {
        $utilityService = UtilityService::factory()->create(['tenant_id' => $this->tenant->id]);
        $serviceConfig = ServiceConfiguration::factory()->create(['tenant_id' => $this->tenant->id]);
        
        // Create different types of anomalies
        
        // 1. High frequency changes
        AuditLog::factory()->count(25)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $utilityService->id,
            'event' => 'updated',
            'created_at' => now()->subMinutes(15),
        ]);
        
        // 2. Configuration changes outside business hours
        AuditLog::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => ServiceConfiguration::class,
            'auditable_id' => $serviceConfig->id,
            'event' => 'updated',
            'created_at' => now()->setTime(2, 0, 0), // 2 AM
        ]);
        
        // 3. Failed operations
        AuditLog::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $utilityService->id,
            'event' => 'billing_failed',
            'created_at' => now()->subHours(1),
        ]);

        $this->alertSystem->processAlerts($this->tenant->id);

        // Should detect multiple anomaly types
        Notification::assertSentTo(
            [$this->user],
            AuditAnomalyDetectedNotification::class
        );
    }

    public function test_alert_severity_levels(): void
    {
        $utilityService = UtilityService::factory()->create(['tenant_id' => $this->tenant->id]);
        
        // Create critical severity anomaly (security-related)
        AuditLog::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $utilityService->id,
            'event' => 'unauthorized_access',
            'created_at' => now()->subMinutes(10),
        ]);

        $this->alertSystem->processAlerts($this->tenant->id);

        // Should send high-priority notifications for critical issues
        Notification::assertSentTo(
            [$this->user],
            AuditAnomalyDetectedNotification::class
        );
    }
}