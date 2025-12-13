<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\SystemHealthMetric;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SuperadminDashboardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'tenant_id' => null,
        ]);
        
        Cache::flush();
    }

    /** @test */
    public function superadmin_can_access_dashboard_page(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Superadmin Dashboard');
    }

    /** @test */
    public function non_superadmin_cannot_access_dashboard(): void
    {
        $regularUser = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->actingAs($regularUser)
            ->get('/superadmin/dashboard');

        $response->assertStatus(403);
    }

    /** @test */
    public function dashboard_displays_subscription_statistics(): void
    {
        // Create test data
        Subscription::factory()->count(5)->create(['status' => SubscriptionStatus::ACTIVE]);
        Subscription::factory()->count(3)->create(['status' => SubscriptionStatus::EXPIRED]);
        Subscription::factory()->count(2)->create(['status' => SubscriptionStatus::SUSPENDED]);

        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/dashboard');

        $response->assertStatus(200);
        
        // Check that subscription stats are displayed
        $response->assertSee('Total Subscriptions');
        $response->assertSee('10'); // Total count
        $response->assertSee('Active');
        $response->assertSee('5'); // Active count
        $response->assertSee('Expired');
        $response->assertSee('3'); // Expired count
    }

    /** @test */
    public function dashboard_displays_organization_statistics(): void
    {
        // Create test organizations
        Organization::factory()->count(8)->create(['is_active' => true]);
        Organization::factory()->count(2)->create(['is_active' => false]);

        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/dashboard');

        $response->assertStatus(200);
        
        // Check that organization stats are displayed
        $response->assertSee('Total Organizations');
        $response->assertSee('10'); // Total count
        $response->assertSee('Active Organizations');
        $response->assertSee('8'); // Active count
    }

    /** @test */
    public function dashboard_displays_system_health_indicators(): void
    {
        // Create system health metrics
        SystemHealthMetric::factory()->create([
            'metric_type' => 'database',
            'metric_name' => 'connection_status',
            'status' => 'healthy',
            'checked_at' => now(),
        ]);

        SystemHealthMetric::factory()->create([
            'metric_type' => 'storage',
            'metric_name' => 'disk_usage',
            'status' => 'warning',
            'checked_at' => now(),
        ]);

        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/dashboard');

        $response->assertStatus(200);
        
        // Check that system health is displayed
        $response->assertSee('System Health');
        $response->assertSee('Database');
        $response->assertSee('Storage');
    }

    /** @test */
    public function dashboard_displays_expiring_subscriptions(): void
    {
        $user = User::factory()->create();
        
        // Create subscription expiring in 10 days
        Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE,
            'expires_at' => now()->addDays(10),
        ]);

        // Create subscription expiring in 20 days (should not show in urgent list)
        Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE,
            'expires_at' => now()->addDays(20),
        ]);

        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/dashboard');

        $response->assertStatus(200);
        
        // Check that expiring subscriptions widget is displayed
        $response->assertSee('Expiring Subscriptions');
        $response->assertSee('10 days'); // Should show the subscription expiring in 10 days
    }

    /** @test */
    public function dashboard_displays_recent_activity(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['tenant_id' => $organization->id]);

        // Create some recent activity
        \App\Models\OrganizationActivityLog::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'action' => 'property_created',
            'resource_type' => 'Property',
            'created_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/dashboard');

        $response->assertStatus(200);
        
        // Check that recent activity is displayed
        $response->assertSee('Recent Activity');
        $response->assertSee('property_created');
    }

    /** @test */
    public function dashboard_displays_top_organizations_chart(): void
    {
        $org1 = Organization::factory()->create(['name' => 'Top Organization']);
        $org2 = Organization::factory()->create(['name' => 'Small Organization']);

        // Create properties for organizations
        Property::factory()->count(15)->create(['tenant_id' => $org1->id]);
        Property::factory()->count(3)->create(['tenant_id' => $org2->id]);

        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/dashboard');

        $response->assertStatus(200);
        
        // Check that top organizations chart is displayed
        $response->assertSee('Top Organizations');
        $response->assertSee('Top Organization');
    }

    /** @test */
    public function dashboard_quick_actions_are_accessible(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/dashboard');

        $response->assertStatus(200);
        
        // Check that quick action buttons are present
        $response->assertSee('Create Organization');
        $response->assertSee('Create Subscription');
        $response->assertSee('View All Activity');
    }

    /** @test */
    public function dashboard_export_functionality_works(): void
    {
        // Create some test data
        Organization::factory()->count(3)->create();
        Subscription::factory()->count(5)->create();

        $response = $this->actingAs($this->superadmin)
            ->post('/superadmin/dashboard/export', [
                'format' => 'pdf',
                'include_charts' => true,
            ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    /** @test */
    public function dashboard_widgets_cache_data_properly(): void
    {
        // Create test data
        Organization::factory()->count(5)->create();
        
        // First request should cache the data
        $response1 = $this->actingAs($this->superadmin)
            ->get('/superadmin/dashboard');
        
        $response1->assertStatus(200);
        
        // Verify cache keys exist
        $this->assertTrue(Cache::has('superadmin_dashboard_organizations_stats'));
        
        // Second request should use cached data
        $response2 = $this->actingAs($this->superadmin)
            ->get('/superadmin/dashboard');
        
        $response2->assertStatus(200);
    }

    /** @test */
    public function dashboard_handles_empty_data_gracefully(): void
    {
        // No test data created - dashboard should handle empty state
        
        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/dashboard');

        $response->assertStatus(200);
        
        // Should show zero counts
        $response->assertSee('0'); // Should appear in various stat widgets
        $response->assertSee('No data available'); // Empty state message
    }

    /** @test */
    public function dashboard_widgets_refresh_automatically(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/dashboard');

        $response->assertStatus(200);
        
        // Check that Livewire polling is set up for widgets
        $response->assertSee('wire:poll');
    }

    /** @test */
    public function dashboard_responsive_layout_works(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/dashboard');

        $response->assertStatus(200);
        
        // Check for responsive grid classes
        $response->assertSee('grid');
        $response->assertSee('md:grid-cols-2');
        $response->assertSee('lg:grid-cols-3');
    }

    /** @test */
    public function dashboard_system_health_check_action_works(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->post('/superadmin/dashboard/health-check');

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
        
        // Verify health metrics were created/updated
        $this->assertDatabaseHas('system_health_metrics', [
            'metric_type' => 'database',
        ]);
    }

    /** @test */
    public function dashboard_navigation_links_work(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/dashboard');

        $response->assertStatus(200);
        
        // Check navigation links to other superadmin pages
        $response->assertSee('Organizations');
        $response->assertSee('Subscriptions');
        $response->assertSee('System Health');
        $response->assertSee('Analytics');
    }
}