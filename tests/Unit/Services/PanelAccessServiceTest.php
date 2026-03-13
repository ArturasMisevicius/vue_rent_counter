<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\PanelAccessService;
use App\Services\UserRoleService;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Panel Access Service Unit Tests
 * 
 * Tests the PanelAccessService implementation to ensure proper
 * Filament panel authorization with caching and performance optimizations.
 */
class PanelAccessServiceTest extends TestCase
{
    use RefreshDatabase;

    private PanelAccessService $panelAccessService;
    private UserRoleService $userRoleService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRoleService = app(UserRoleService::class);
        $this->panelAccessService = new PanelAccessService($this->userRoleService);
    }

    public function test_can_access_admin_panel_returns_true_for_admin_roles(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => true]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'is_active' => true]);
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN, 'is_active' => true]);

        $this->assertTrue($this->panelAccessService->canAccessAdminPanel($admin));
        $this->assertTrue($this->panelAccessService->canAccessAdminPanel($manager));
        $this->assertTrue($this->panelAccessService->canAccessAdminPanel($superadmin));
    }

    public function test_can_access_admin_panel_returns_false_for_tenant(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::TENANT, 'is_active' => true]);

        $result = $this->panelAccessService->canAccessAdminPanel($tenant);

        $this->assertFalse($result);
    }

    public function test_can_access_admin_panel_returns_false_for_inactive_user(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => false]);

        $result = $this->panelAccessService->canAccessAdminPanel($admin);

        $this->assertFalse($result);
    }

    public function test_can_access_admin_panel_returns_false_for_suspended_user(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'is_active' => true,
            'suspended_at' => now(),
        ]);

        $result = $this->panelAccessService->canAccessAdminPanel($admin);

        $this->assertFalse($result);
    }

    public function test_can_access_tenant_panel_returns_true_for_tenant(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::TENANT, 'is_active' => true]);

        $result = $this->panelAccessService->canAccessTenantPanel($tenant);

        $this->assertTrue($result);
    }

    public function test_can_access_tenant_panel_returns_false_for_admin(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => true]);

        $result = $this->panelAccessService->canAccessTenantPanel($admin);

        $this->assertFalse($result);
    }

    public function test_can_access_superadmin_panel_returns_true_only_for_superadmin(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN, 'is_active' => true]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => true]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT, 'is_active' => true]);

        $this->assertTrue($this->panelAccessService->canAccessSuperadminPanel($superadmin));
        $this->assertFalse($this->panelAccessService->canAccessSuperadminPanel($admin));
        $this->assertFalse($this->panelAccessService->canAccessSuperadminPanel($tenant));
    }

    public function test_can_access_panel_with_admin_panel(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => true]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT, 'is_active' => true]);
        
        $adminPanel = $this->createMockPanel('admin');

        $this->assertTrue($this->panelAccessService->canAccessPanel($admin, $adminPanel));
        $this->assertFalse($this->panelAccessService->canAccessPanel($tenant, $adminPanel));
    }

    public function test_can_access_panel_with_tenant_panel(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => true]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT, 'is_active' => true]);
        
        $tenantPanel = $this->createMockPanel('tenant');

        $this->assertFalse($this->panelAccessService->canAccessPanel($admin, $tenantPanel));
        $this->assertTrue($this->panelAccessService->canAccessPanel($tenant, $tenantPanel));
    }

    public function test_can_access_panel_with_other_panel_requires_superadmin(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN, 'is_active' => true]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => true]);
        
        $customPanel = $this->createMockPanel('custom');

        $this->assertTrue($this->panelAccessService->canAccessPanel($superadmin, $customPanel));
        $this->assertFalse($this->panelAccessService->canAccessPanel($admin, $customPanel));
    }

    public function test_get_accessible_panels_returns_correct_panels(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN, 'is_active' => true]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => true]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT, 'is_active' => true]);

        $superadminPanels = $this->panelAccessService->getAccessiblePanels($superadmin);
        $adminPanels = $this->panelAccessService->getAccessiblePanels($admin);
        $tenantPanels = $this->panelAccessService->getAccessiblePanels($tenant);

        $this->assertContains('admin', $superadminPanels);
        $this->assertContains('admin', $adminPanels);
        $this->assertContains('tenant', $tenantPanels);
        $this->assertNotContains('tenant', $adminPanels);
        $this->assertNotContains('admin', $tenantPanels);
    }

    public function test_panel_access_is_cached(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => true]);
        $panel = $this->createMockPanel('admin');
        
        // Clear cache first
        Cache::flush();

        // First call should cache the result
        $result1 = $this->panelAccessService->canAccessPanel($user, $panel);
        
        // Second call should use cached result
        $result2 = $this->panelAccessService->canAccessPanel($user, $panel);

        $this->assertTrue($result1);
        $this->assertTrue($result2);
        
        // Verify cache was used
        $cacheKey = "panel_access:{$user->id}:admin";
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_clear_panel_access_cache_removes_cached_data(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => true]);
        $panel = $this->createMockPanel('admin');
        
        // Cache a panel access check
        $this->panelAccessService->canAccessPanel($user, $panel);
        
        // Clear cache
        $this->panelAccessService->clearPanelAccessCache($user);
        
        // Verify cache was cleared
        $cacheKey = "panel_access:{$user->id}:admin";
        $this->assertFalse(Cache::has($cacheKey));
    }

    /**
     * Create a mock Panel object for testing.
     */
    private function createMockPanel(string $id): Panel
    {
        $panel = $this->createMock(Panel::class);
        $panel->method('getId')->willReturn($id);
        
        return $panel;
    }
}