<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Panel Access Authorization Test
 * 
 * Critical security tests to ensure role-based access control is enforced
 * for Filament admin panels. These tests prevent regression of the security
 * vulnerability where `canAccessPanel()` was temporarily set to return true.
 * 
 * Requirements: 9.1, 9.2, 9.3, 12.5, 13.3
 */
class PanelAccessAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that SUPERADMIN can access admin panel
     * 
     * @test
     */
    public function superadmin_can_access_admin_panel(): void
    {
        $user = User::factory()->superadmin()->create();
        $panel = Filament::getPanel('admin');

        $this->assertTrue($user->canAccessPanel($panel));
    }

    /**
     * Test that ADMIN can access admin panel
     * 
     * @test
     */
    public function admin_can_access_admin_panel(): void
    {
        $user = User::factory()->admin(1)->create();
        $panel = Filament::getPanel('admin');

        $this->assertTrue($user->canAccessPanel($panel));
    }

    /**
     * Test that MANAGER can access admin panel
     * 
     * @test
     */
    public function manager_can_access_admin_panel(): void
    {
        $user = User::factory()->manager(1)->create();
        $panel = Filament::getPanel('admin');

        $this->assertTrue($user->canAccessPanel($panel));
    }

    /**
     * Test that TENANT cannot access admin panel (CRITICAL)
     * 
     * This is the most critical test - TENANT role must NEVER access admin panel
     * 
     * @test
     */
    public function tenant_cannot_access_admin_panel(): void
    {
        $user = User::factory()->tenant(1, 1, 1)->create();
        $panel = Filament::getPanel('admin');

        $this->assertFalse($user->canAccessPanel($panel));
    }

    /**
     * Test that inactive users cannot access admin panel
     * 
     * @test
     */
    public function inactive_user_cannot_access_admin_panel(): void
    {
        $user = User::factory()->admin(1)->inactive()->create();
        $panel = Filament::getPanel('admin');

        $this->assertFalse($user->canAccessPanel($panel));
    }

    /**
     * Test that inactive SUPERADMIN cannot access admin panel
     * 
     * @test
     */
    public function inactive_superadmin_cannot_access_admin_panel(): void
    {
        $user = User::factory()->superadmin()->inactive()->create();
        $panel = Filament::getPanel('admin');

        $this->assertFalse($user->canAccessPanel($panel));
    }

    /**
     * Test that only SUPERADMIN can access non-admin panels
     * 
     * @test
     */
    public function only_superadmin_can_access_other_panels(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $admin = User::factory()->admin(1)->create();
        $manager = User::factory()->manager(1)->create();
        $tenant = User::factory()->tenant(1, 1, 1)->create();

        // Create a mock panel with different ID
        $mockPanel = new class {
            public function getId(): string {
                return 'other-panel';
            }
        };

        $this->assertTrue($superadmin->canAccessPanel($mockPanel));
        $this->assertFalse($admin->canAccessPanel($mockPanel));
        $this->assertFalse($manager->canAccessPanel($mockPanel));
        $this->assertFalse($tenant->canAccessPanel($mockPanel));
    }

    /**
     * Test that EnsureUserIsAdminOrManager middleware blocks TENANT
     * 
     * @test
     */
    public function middleware_blocks_tenant_from_admin_routes(): void
    {
        $tenant = User::factory()->tenant(1, 1, 1)->create();

        $this->actingAs($tenant)
            ->get('/admin')
            ->assertForbidden();
    }

    /**
     * Test that ADMIN can access admin routes
     * 
     * @test
     */
    public function middleware_allows_admin_to_access_admin_routes(): void
    {
        $admin = User::factory()->admin(1)->create();

        $this->actingAs($admin)
            ->get('/admin')
            ->assertSuccessful();
    }

    /**
     * Test that MANAGER can access admin routes
     * 
     * @test
     */
    public function middleware_allows_manager_to_access_admin_routes(): void
    {
        $manager = User::factory()->manager(1)->create();

        $this->actingAs($manager)
            ->get('/admin')
            ->assertSuccessful();
    }

    /**
     * Test that SUPERADMIN can access admin routes
     * 
     * @test
     */
    public function middleware_allows_superadmin_to_access_admin_routes(): void
    {
        $superadmin = User::factory()->superadmin()->create();

        $this->actingAs($superadmin)
            ->get('/admin')
            ->assertSuccessful();
    }

    /**
     * Test that unauthenticated users cannot access admin panel
     * 
     * @test
     */
    public function unauthenticated_users_cannot_access_admin_panel(): void
    {
        $this->get('/admin')
            ->assertRedirect('/admin/login');
    }

    /**
     * Test role helper methods for consistency
     * 
     * @test
     */
    public function role_helper_methods_work_correctly(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $admin = User::factory()->admin(1)->create();
        $manager = User::factory()->manager(1)->create();
        $tenant = User::factory()->tenant(1, 1, 1)->create();

        // Superadmin checks
        $this->assertTrue($superadmin->isSuperadmin());
        $this->assertFalse($superadmin->isAdmin());
        $this->assertFalse($superadmin->isManager());
        $this->assertFalse($superadmin->isTenantUser());

        // Admin checks
        $this->assertFalse($admin->isSuperadmin());
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isManager());
        $this->assertFalse($admin->isTenantUser());

        // Manager checks
        $this->assertFalse($manager->isSuperadmin());
        $this->assertFalse($manager->isAdmin());
        $this->assertTrue($manager->isManager());
        $this->assertFalse($manager->isTenantUser());

        // Tenant checks
        $this->assertFalse($tenant->isSuperadmin());
        $this->assertFalse($tenant->isAdmin());
        $this->assertFalse($tenant->isManager());
        $this->assertTrue($tenant->isTenantUser());
    }
}
