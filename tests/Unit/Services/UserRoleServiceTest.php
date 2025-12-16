<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\UserRoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * User Role Service Unit Tests
 * 
 * Tests the UserRoleService implementation to ensure all role-related
 * operations work correctly with proper caching and performance optimizations.
 */
class UserRoleServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserRoleService $userRoleService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRoleService = app(UserRoleService::class);
    }

    public function test_has_role_returns_true_for_matching_role(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);

        $result = $this->userRoleService->hasRole($user, UserRole::ADMIN);

        $this->assertTrue($result);
    }

    public function test_has_role_returns_false_for_non_matching_role(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT]);

        $result = $this->userRoleService->hasRole($user, UserRole::ADMIN);

        $this->assertFalse($result);
    }

    public function test_has_role_works_with_array_of_roles(): void
    {
        $user = User::factory()->create(['role' => UserRole::MANAGER]);

        $result = $this->userRoleService->hasRole($user, [UserRole::ADMIN, UserRole::MANAGER]);

        $this->assertTrue($result);
    }

    public function test_can_access_admin_returns_true_for_admin_roles(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        $this->assertTrue($this->userRoleService->canAccessAdmin($admin));
        $this->assertTrue($this->userRoleService->canAccessAdmin($manager));
        $this->assertTrue($this->userRoleService->canAccessAdmin($superadmin));
    }

    public function test_can_access_admin_returns_false_for_tenant(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        $result = $this->userRoleService->canAccessAdmin($tenant);

        $this->assertFalse($result);
    }

    public function test_role_check_methods_work_correctly(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        // Test isSuperadmin
        $this->assertTrue($this->userRoleService->isSuperadmin($superadmin));
        $this->assertFalse($this->userRoleService->isSuperadmin($admin));

        // Test isAdmin
        $this->assertTrue($this->userRoleService->isAdmin($admin));
        $this->assertFalse($this->userRoleService->isAdmin($tenant));

        // Test isManager
        $this->assertTrue($this->userRoleService->isManager($manager));
        $this->assertFalse($this->userRoleService->isManager($admin));

        // Test isTenant
        $this->assertTrue($this->userRoleService->isTenant($tenant));
        $this->assertFalse($this->userRoleService->isTenant($admin));
    }

    public function test_get_role_priority_returns_correct_values(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        $this->assertEquals(1, $this->userRoleService->getRolePriority($superadmin));
        $this->assertEquals(2, $this->userRoleService->getRolePriority($admin));
        $this->assertEquals(3, $this->userRoleService->getRolePriority($manager));
        $this->assertEquals(4, $this->userRoleService->getRolePriority($tenant));
    }

    public function test_has_administrative_privileges_works_correctly(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        $this->assertTrue($this->userRoleService->hasAdministrativePrivileges($superadmin));
        $this->assertTrue($this->userRoleService->hasAdministrativePrivileges($admin));
        $this->assertTrue($this->userRoleService->hasAdministrativePrivileges($manager));
        $this->assertFalse($this->userRoleService->hasAdministrativePrivileges($tenant));
    }

    public function test_role_checks_are_cached(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Clear cache first
        Cache::flush();

        // First call should cache the result
        $result1 = $this->userRoleService->hasRole($user, UserRole::ADMIN);
        
        // Second call should use cached result
        $result2 = $this->userRoleService->hasRole($user, UserRole::ADMIN);

        $this->assertTrue($result1);
        $this->assertTrue($result2);
        
        // Verify cache was used by checking cache directly
        $cacheKey = "user_role:{$user->id}:has_role:" . UserRole::ADMIN->value . ":default";
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_clear_role_cache_removes_cached_data(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Cache a role check
        $this->userRoleService->hasRole($user, UserRole::ADMIN);
        
        // Clear cache
        $this->userRoleService->clearRoleCache($user);
        
        // Verify cache was cleared
        $cacheKey = "user_role:{$user->id}:has_role:" . UserRole::ADMIN->value . ":default";
        $this->assertFalse(Cache::has($cacheKey));
    }
}