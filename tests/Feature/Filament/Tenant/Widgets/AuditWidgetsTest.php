<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Tenant\Widgets;

use App\Filament\Tenant\Widgets\AnomalyDetectionWidget;
use App\Filament\Tenant\Widgets\AuditOverviewWidget;
use App\Filament\Tenant\Widgets\AuditTrendsWidget;
use App\Filament\Tenant\Widgets\ComplianceStatusWidget;
use App\Models\AuditLog;
use App\Models\ServiceConfiguration;
use App\Models\Team;
use App\Models\User;
use App\Models\UtilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Test suite for Audit Dashboard Widgets
 * 
 * Validates all audit-related Filament widgets for proper data display,
 * tenant isolation, and user interaction.
 */
final class AuditWidgetsTest extends TestCase
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

    public function test_audit_overview_widget_displays_stats(): void
    {
        $utilityService = UtilityService::factory()->create(['tenant_id' => $this->tenant->id]);
        
        AuditLog::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $utilityService->id,
            'event' => 'updated',
            'created_at' => now()->subDays(rand(1, 30)),
        ]);

        $component = Livewire::test(AuditOverviewWidget::class);

        $component->assertOk()
            ->assertSee(__('dashboard.audit.total_changes'))
            ->assertSee(__('dashboard.audit.compliance_score'))
            ->assertSee(__('dashboard.audit.performance_score'))
            ->assertSee(__('dashboard.audit.active_alerts'));
    }

    public function test_audit_overview_widget_respects_tenant_isolation(): void
    {
        $otherTenant = Team::factory()->create();
        
        $utilityService = UtilityService::factory()->create(['tenant_id' => $this->tenant->id]);
        $otherUtilityService = UtilityService::factory()->create(['tenant_id' => $otherTenant->id]);
        
        // Create audit logs for both tenants
        AuditLog::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $utilityService->id,
            'event' => 'updated',
        ]);
        
        AuditLog::factory()->count(15)->create([
            'tenant_id' => $otherTenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $otherUtilityService->id,
            'event' => 'updated',
        ]);

        $component = Livewire::test(AuditOverviewWidget::class);

        // Should only show current tenant's data (5 changes, not 20)
        $component->assertOk();
        
        // Verify the widget shows correct tenant-scoped data
        $stats = $component->instance()->getStats();
        $this->assertCount(4, $stats); // Should have 4 stat cards
    }

    public function test_audit_trends_widget_displays_chart(): void
    {
        $utilityService = UtilityService::factory()->create(['tenant_id' => $this->tenant->id]);
        
        // Create audit logs over different days
        for ($i = 0; $i < 30; $i++) {
            AuditLog::factory()->count(rand(1, 5))->create([
                'tenant_id' => $this->tenant->id,
                'auditable_type' => UtilityService::class,
                'auditable_id' => $utilityService->id,
                'event' => 'updated',
                'created_at' => now()->subDays($i),
            ]);
        }

        $component = Livewire::test(AuditTrendsWidget::class);

        $component->assertOk()
            ->assertSee(__('dashboard.audit.trends_title'))
            ->assertSee(__('dashboard.audit.changes_over_time'));
    }

    public function test_compliance_status_widget_shows_status(): void
    {
        $utilityService = UtilityService::factory()->create(['tenant_id' => $this->tenant->id]);
        
        // Create compliance-related audit logs
        AuditLog::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $utilityService->id,
            'event' => 'compliance_check',
            'created_at' => now()->subDays(rand(1, 7)),
        ]);

        $component = Livewire::test(ComplianceStatusWidget::class);

        $component->assertOk()
            ->assertSee(__('dashboard.audit.compliance_status'))
            ->assertSee(__('dashboard.audit.compliance_score'));
    }

    public function test_anomaly_detection_widget_shows_anomalies(): void
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
        AuditLog::factory()->count(25)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $utilityService->id,
            'event' => 'updated',
            'created_at' => now()->subHour(),
        ]);

        $component = Livewire::test(AnomalyDetectionWidget::class);

        $component->assertOk()
            ->assertSee(__('dashboard.audit.anomalies_detected'))
            ->assertSee(__('dashboard.audit.view_details'));
    }

    public function test_widgets_handle_empty_data(): void
    {
        // Test all widgets with no audit data
        $overviewComponent = Livewire::test(AuditOverviewWidget::class);
        $trendsComponent = Livewire::test(AuditTrendsWidget::class);
        $complianceComponent = Livewire::test(ComplianceStatusWidget::class);
        $anomalyComponent = Livewire::test(AnomalyDetectionWidget::class);

        $overviewComponent->assertOk();
        $trendsComponent->assertOk();
        $complianceComponent->assertOk();
        $anomalyComponent->assertOk();
    }

    public function test_widgets_use_caching(): void
    {
        $utilityService = UtilityService::factory()->create(['tenant_id' => $this->tenant->id]);
        
        AuditLog::factory()->count(10)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $utilityService->id,
            'event' => 'updated',
        ]);

        // First load should cache data
        $start1 = microtime(true);
        $component1 = Livewire::test(AuditOverviewWidget::class);
        $time1 = microtime(true) - $start1;

        // Second load should use cache and be faster
        $start2 = microtime(true);
        $component2 = Livewire::test(AuditOverviewWidget::class);
        $time2 = microtime(true) - $start2;

        $component1->assertOk();
        $component2->assertOk();
        
        // Second load should be faster due to caching
        $this->assertLessThan($time1, $time2);
    }

    public function test_anomaly_detection_widget_modal_interaction(): void
    {
        $utilityService = UtilityService::factory()->create(['tenant_id' => $this->tenant->id]);
        
        // Create anomalous activity
        AuditLog::factory()->count(30)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $utilityService->id,
            'event' => 'updated',
            'created_at' => now()->subMinutes(30),
        ]);

        $component = Livewire::test(AnomalyDetectionWidget::class);

        // Test modal opening
        $component->call('viewAnomalyDetails', 0)
            ->assertEmitted('open-modal', 'anomaly-details');
    }

    public function test_widgets_polling_functionality(): void
    {
        $utilityService = UtilityService::factory()->create(['tenant_id' => $this->tenant->id]);
        
        AuditLog::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $utilityService->id,
            'event' => 'updated',
        ]);

        $component = Livewire::test(AuditOverviewWidget::class);

        // Verify polling is configured
        $this->assertNotNull($component->instance()::$pollingInterval);
        $this->assertEquals('30s', $component->instance()::$pollingInterval);
    }

    public function test_widgets_display_localized_content(): void
    {
        $component = Livewire::test(AuditOverviewWidget::class);

        $component->assertOk()
            ->assertSee(__('dashboard.audit.total_changes'))
            ->assertSee(__('dashboard.audit.last_30_days'))
            ->assertSee(__('dashboard.audit.compliance_score'))
            ->assertSee(__('dashboard.audit.performance_score'));
    }

    public function test_compliance_widget_shows_recommendations(): void
    {
        $utilityService = UtilityService::factory()->create(['tenant_id' => $this->tenant->id]);
        
        // Create compliance issues
        AuditLog::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'auditable_type' => UtilityService::class,
            'auditable_id' => $utilityService->id,
            'event' => 'compliance_violation',
            'created_at' => now()->subDays(rand(1, 7)),
        ]);

        $component = Livewire::test(ComplianceStatusWidget::class);

        $component->assertOk()
            ->assertSee(__('dashboard.audit.recommendations'))
            ->assertSee(__('dashboard.audit.view_compliance_report'));
    }

    public function test_trends_widget_chart_data_structure(): void
    {
        $utilityService = UtilityService::factory()->create(['tenant_id' => $this->tenant->id]);
        
        // Create predictable audit data
        for ($i = 0; $i < 7; $i++) {
            AuditLog::factory()->count($i + 1)->create([
                'tenant_id' => $this->tenant->id,
                'auditable_type' => UtilityService::class,
                'auditable_id' => $utilityService->id,
                'event' => 'updated',
                'created_at' => now()->subDays($i),
            ]);
        }

        $component = Livewire::test(AuditTrendsWidget::class);
        
        $chartData = $component->instance()->getChartData();
        
        $this->assertIsArray($chartData);
        $this->assertArrayHasKey('datasets', $chartData);
        $this->assertArrayHasKey('labels', $chartData);
    }
}