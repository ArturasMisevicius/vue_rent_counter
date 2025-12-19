<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * Browser tests for SecurityHeaders MCP Integration
 * 
 * Tests UI interactions with security analytics dashboard
 * and CSP violation reporting functionality.
 */
final class SecurityHeadersMcpIntegrationTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_security_analytics_dashboard_accessibility(): void
    {
        $admin = User::factory()->create([
            'role' => \App\Enums\UserRole::ADMIN,
            'is_active' => true,
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/admin/security/analytics')
                ->assertSee('Security Analytics Dashboard')
                ->assertPresent('[data-testid="security-metrics-chart"]')
                ->assertPresent('[data-testid="violation-summary-table"]')
                
                // Test keyboard navigation
                ->keys('body', ['{tab}'])
                ->assertFocused('[data-testid="filter-severity-select"]')
                
                // Test ARIA labels
                ->assertAttribute('[data-testid="security-metrics-chart"]', 'aria-label', 'Security metrics visualization')
                ->assertAttribute('[data-testid="violation-summary-table"]', 'role', 'table')
                
                // Test screen reader compatibility
                ->assertPresent('[aria-live="polite"]')
                ->assertPresent('[role="status"]');
        });
    }

    public function test_csp_violation_real_time_updates(): void
    {
        $admin = User::factory()->create([
            'role' => \App\Enums\UserRole::ADMIN,
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/admin/security/violations')
                ->assertSee('CSP Violations')
                
                // Wait for initial load
                ->waitFor('[data-testid="violations-table"]', 5)
                
                // Simulate CSP violation via JavaScript
                ->script([
                    'fetch("/api/csp-report", {
                        method: "POST",
                        headers: {"Content-Type": "application/json"},
                        body: JSON.stringify({
                            "csp-report": {
                                "violated-directive": "script-src",
                                "blocked-uri": "https://malicious.example.com/script.js",
                                "document-uri": window.location.href
                            }
                        })
                    });'
                ])
                
                // Wait for real-time update
                ->waitForText('script-src violation detected', 10)
                ->assertSee('malicious.example.com')
                
                // Test filtering functionality
                ->select('[data-testid="severity-filter"]', 'high')
                ->waitFor('[data-testid="filtered-results"]', 3)
                
                // Test export functionality
                ->click('[data-testid="export-violations-btn"]')
                ->waitForDialog(2)
                ->acceptDialog();
        });
    }

    public function test_security_dashboard_performance_monitoring(): void
    {
        $admin = User::factory()->create([
            'role' => \App\Enums\UserRole::ADMIN,
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $startTime = microtime(true);

            $browser->loginAs($admin)
                ->visit('/admin/security/dashboard')
                
                // Measure initial load time
                ->waitFor('[data-testid="dashboard-loaded"]', 10);

            $loadTime = (microtime(true) - $startTime) * 1000;
            
            // Dashboard should load within 3 seconds
            $this->assertLessThan(3000, $loadTime, 
                "Security dashboard took {$loadTime}ms to load, exceeds 3000ms limit");

            $browser
                // Test interactive elements performance
                ->click('[data-testid="refresh-metrics-btn"]')
                ->waitFor('[data-testid="metrics-updated"]', 5)
                
                // Test chart interactions
                ->mouseover('[data-testid="security-chart"] .chart-bar:first-child')
                ->waitFor('[data-testid="chart-tooltip"]', 2)
                ->assertSee('Violation Details')
                
                // Test responsive design
                ->resize(768, 1024) // Tablet view
                ->assertPresent('[data-testid="mobile-navigation"]')
                ->resize(375, 667) // Mobile view
                ->assertPresent('[data-testid="mobile-dashboard"]');
        });
    }

    public function test_mcp_service_status_indicator(): void
    {
        $admin = User::factory()->create([
            'role' => \App\Enums\UserRole::ADMIN,
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/admin/system/status')
                ->assertSee('MCP Services Status')
                
                // Check MCP server status indicators
                ->assertPresent('[data-testid="mcp-security-analytics-status"]')
                ->assertPresent('[data-testid="mcp-compliance-checker-status"]')
                ->assertPresent('[data-testid="mcp-performance-monitor-status"]')
                ->assertPresent('[data-testid="mcp-incident-response-status"]')
                
                // Test status colors and accessibility
                ->assertAttribute('[data-testid="mcp-security-analytics-status"]', 'aria-label', 'Security Analytics MCP Server Status')
                ->assertHasClass('[data-testid="mcp-security-analytics-status"]', 'status-indicator')
                
                // Test refresh functionality
                ->click('[data-testid="refresh-mcp-status-btn"]')
                ->waitFor('[data-testid="status-updated"]', 5)
                
                // Test error state handling
                ->script(['window.mcpTestMode = "error";'])
                ->click('[data-testid="refresh-mcp-status-btn"]')
                ->waitFor('[data-testid="mcp-error-indicator"]', 5)
                ->assertSee('MCP Service Unavailable');
        });
    }

    public function test_csp_policy_builder_interface(): void
    {
        $admin = User::factory()->create([
            'role' => \App\Enums\UserRole::ADMIN,
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/admin/security/csp-builder')
                ->assertSee('CSP Policy Builder')
                
                // Test policy directive inputs
                ->type('[data-testid="default-src-input"]', "'self'")
                ->type('[data-testid="script-src-input"]', "'self' 'unsafe-inline'")
                ->type('[data-testid="style-src-input"]', "'self' fonts.googleapis.com")
                
                // Test live preview
                ->waitFor('[data-testid="csp-preview"]', 2)
                ->assertSeeIn('[data-testid="csp-preview"]', "default-src 'self'")
                ->assertSeeIn('[data-testid="csp-preview"]', "script-src 'self' 'unsafe-inline'")
                
                // Test validation
                ->type('[data-testid="script-src-input"]', 'invalid-directive')
                ->waitFor('[data-testid="validation-error"]', 2)
                ->assertSee('Invalid CSP directive')
                
                // Test save functionality
                ->type('[data-testid="script-src-input"]', "'self'")
                ->click('[data-testid="save-csp-policy-btn"]')
                ->waitFor('[data-testid="save-success"]', 5)
                ->assertSee('CSP Policy Saved Successfully')
                
                // Test accessibility
                ->keys('[data-testid="default-src-input"]', ['{tab}'])
                ->assertFocused('[data-testid="script-src-input"]')
                ->assertAttribute('[data-testid="csp-preview"]', 'aria-live', 'polite');
        });
    }

    public function test_tenant_security_isolation_ui(): void
    {
        $tenant1 = \App\Models\Tenant::factory()->create(['name' => 'Tenant One']);
        $tenant2 = \App\Models\Tenant::factory()->create(['name' => 'Tenant Two']);
        
        $admin1 = User::factory()->create([
            'tenant_id' => $tenant1->id,
            'role' => \App\Enums\UserRole::ADMIN,
        ]);
        
        $admin2 = User::factory()->create([
            'tenant_id' => $tenant2->id,
            'role' => \App\Enums\UserRole::ADMIN,
        ]);

        // Test tenant 1 admin can only see their data
        $this->browse(function (Browser $browser) use ($admin1, $tenant1) {
            $browser->loginAs($admin1)
                ->visit('/admin/security/violations')
                ->assertSee('Tenant One') // Should see their tenant name
                ->assertDontSee('Tenant Two') // Should not see other tenant
                
                // Check data isolation in charts
                ->assertAttribute('[data-testid="tenant-filter"]', 'value', (string)$tenant1->id)
                ->assertAttribute('[data-testid="tenant-filter"]', 'disabled', 'true'); // Should be locked to their tenant
        });

        // Test tenant 2 admin sees different data
        $this->browse(function (Browser $browser) use ($admin2, $tenant2) {
            $browser->loginAs($admin2)
                ->visit('/admin/security/violations')
                ->assertSee('Tenant Two')
                ->assertDontSee('Tenant One')
                
                ->assertAttribute('[data-testid="tenant-filter"]', 'value', (string)$tenant2->id);
        });
    }

    public function test_security_alert_notifications(): void
    {
        $admin = User::factory()->create([
            'role' => \App\Enums\UserRole::ADMIN,
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/admin/dashboard')
                
                // Simulate high-severity security event
                ->script([
                    'window.Echo.channel("security-alerts").listen("SecurityAlert", function(e) {
                        document.querySelector("[data-testid=notification-area]").innerHTML = 
                            "<div class=\"alert alert-danger\" role=\"alert\">" + e.message + "</div>";
                    });',
                    
                    // Trigger test alert
                    'fetch("/api/test/security-alert", {method: "POST"});'
                ])
                
                // Wait for notification to appear
                ->waitFor('[data-testid="notification-area"] .alert', 10)
                ->assertSee('Security Alert')
                
                // Test notification accessibility
                ->assertAttribute('[data-testid="notification-area"] .alert', 'role', 'alert')
                ->assertAttribute('[data-testid="notification-area"] .alert', 'aria-live', 'assertive')
                
                // Test dismiss functionality
                ->click('[data-testid="dismiss-alert-btn"]')
                ->waitUntilMissing('[data-testid="notification-area"] .alert', 3);
        });
    }

    public function test_csp_violation_modal_details(): void
    {
        $admin = User::factory()->create([
            'role' => \App\Enums\UserRole::ADMIN,
        ]);

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/admin/security/violations')
                ->waitFor('[data-testid="violations-table"]', 5)
                
                // Click on first violation to open modal
                ->click('[data-testid="violation-row"]:first-child [data-testid="view-details-btn"]')
                ->waitFor('[data-testid="violation-details-modal"]', 3)
                
                // Test modal content
                ->assertSee('Violation Details')
                ->assertPresent('[data-testid="violation-directive"]')
                ->assertPresent('[data-testid="violation-uri"]')
                ->assertPresent('[data-testid="violation-severity"]')
                ->assertPresent('[data-testid="violation-classification"]')
                
                // Test modal accessibility
                ->assertAttribute('[data-testid="violation-details-modal"]', 'role', 'dialog')
                ->assertAttribute('[data-testid="violation-details-modal"]', 'aria-labelledby', 'modal-title')
                ->assertFocused('[data-testid="modal-close-btn"]') // Focus should be on close button
                
                // Test keyboard navigation
                ->keys('body', ['{escape}'])
                ->waitUntilMissing('[data-testid="violation-details-modal"]', 2)
                
                // Test click outside to close
                ->click('[data-testid="violation-row"]:first-child [data-testid="view-details-btn"]')
                ->waitFor('[data-testid="violation-details-modal"]', 3)
                ->click('[data-testid="modal-backdrop"]')
                ->waitUntilMissing('[data-testid="violation-details-modal"]', 2);
        });
    }
}