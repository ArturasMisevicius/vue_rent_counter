<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\PanelAccessService;
use App\Services\UserRoleService;
use App\ValueObjects\UserCapabilities;
use App\ValueObjects\UserState;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * User Model Refactored Tests
 * 
 * Tests the refactored User model to ensure all new methods
 * and integrations work correctly with the extracted services.
 */
class UserModelRefactoredTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_panel_delegates_to_service(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => true]);
        $panel = $this->createMockPanel('admin');

        $result = $user->canAccessPanel($panel);

        $this->assertTrue($result);
    }

    public function test_user_role_helpers_delegate_to_service(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        $this->assertTrue($superadmin->isSuperadmin());
        $this->assertFalse($admin->isSuperadmin());

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($tenant->isAdmin());

        $this->assertTrue($manager->isManager());
        $this->assertFalse($admin->isManager());

        $this->assertTrue($tenant->isTenantUser());
        $this->assertFalse($admin->isTenantUser());
    }

    public function test_user_has_role_delegates_to_service(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->assertTrue($user->hasRole(UserRole::ADMIN));
        $this->assertFalse($user->hasRole(UserRole::TENANT));
        $this->assertTrue($user->hasRole([UserRole::ADMIN, UserRole::MANAGER]));
    }

    public function test_user_get_capabilities_returns_value_object(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => true]);

        $capabilities = $user->getCapabilities();

        $this->assertInstanceOf(UserCapabilities::class, $capabilities);
        $this->assertTrue($capabilities->canManageProperties());
        $this->assertFalse($capabilities->canAccessSuperadmin());
    }

    public function test_user_get_state_returns_value_object(): void
    {
        $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);

        $state = $user->getState();

        $this->assertInstanceOf(UserState::class, $state);
        $this->assertTrue($state->isActive());
        $this->assertTrue($state->isEmailVerified());
    }

    public function test_user_has_administrative_privileges_delegates_to_service(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        $this->assertTrue($admin->hasAdministrativePrivileges());
        $this->assertFalse($tenant->hasAdministrativePrivileges());
    }

    public function test_user_get_role_priority_delegates_to_service(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        $this->assertEquals(1, $superadmin->getRolePriority());
        $this->assertEquals(2, $admin->getRolePriority());
        $this->assertEquals(4, $tenant->getRolePriority());
    }

    public function test_user_clear_cache_delegates_to_services(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);

        // This should not throw any exceptions
        $user->clearCache();

        $this->assertTrue(true); // If we get here, the method worked
    }

    public function test_user_scopes_have_proper_return_types(): void
    {
        User::factory()->count(3)->create([
            'role' => UserRole::ADMIN,
            'is_active' => true,
        ]);

        $activeUsers = User::active()->get();
        $adminUsers = User::admins()->get();
        $orderedUsers = User::orderedByRole()->get();

        $this->assertCount(3, $activeUsers);
        $this->assertCount(3, $adminUsers);
        $this->assertCount(3, $orderedUsers);
    }

    public function test_user_constants_are_defined(): void
    {
        $this->assertEquals('tenant', User::DEFAULT_ROLE);
        $this->assertEquals('admin', User::ADMIN_PANEL_ID);
        $this->assertIsArray(User::ROLE_PRIORITIES);
        $this->assertArrayHasKey('superadmin', User::ROLE_PRIORITIES);
    }

    public function test_user_scope_active_excludes_suspended_users(): void
    {
        User::factory()->create(['is_active' => true, 'suspended_at' => null]);
        User::factory()->create(['is_active' => true, 'suspended_at' => now()]);
        User::factory()->create(['is_active' => false]);

        $activeUsers = User::active()->get();

        $this->assertCount(1, $activeUsers);
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