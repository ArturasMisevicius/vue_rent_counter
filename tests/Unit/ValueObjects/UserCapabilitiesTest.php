<?php

declare(strict_types=1);

namespace Tests\Unit\ValueObjects;

use App\Enums\UserRole;
use App\Models\User;
use App\ValueObjects\UserCapabilities;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * User Capabilities Value Object Tests
 * 
 * Tests the UserCapabilities value object to ensure proper
 * capability checking based on user roles and status.
 */
class UserCapabilitiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_has_all_capabilities(): void
    {
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN, 'is_active' => true]);
        $capabilities = UserCapabilities::fromUser($user);

        $this->assertTrue($capabilities->canManageProperties());
        $this->assertTrue($capabilities->canManageTenants());
        $this->assertTrue($capabilities->canManageBuildings());
        $this->assertTrue($capabilities->canManageInvoices());
        $this->assertTrue($capabilities->canSubmitReadings());
        $this->assertTrue($capabilities->canViewReports());
        $this->assertTrue($capabilities->canAccessAdmin());
        $this->assertTrue($capabilities->canAccessSuperadmin());
        $this->assertTrue($capabilities->canManageSystem());
        $this->assertTrue($capabilities->canImpersonateUsers());
    }

    public function test_admin_has_admin_capabilities_but_not_superadmin(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => true]);
        $capabilities = UserCapabilities::fromUser($user);

        $this->assertTrue($capabilities->canManageProperties());
        $this->assertTrue($capabilities->canManageTenants());
        $this->assertTrue($capabilities->canManageBuildings());
        $this->assertTrue($capabilities->canManageInvoices());
        $this->assertTrue($capabilities->canSubmitReadings());
        $this->assertTrue($capabilities->canViewReports());
        $this->assertTrue($capabilities->canAccessAdmin());
        $this->assertFalse($capabilities->canAccessSuperadmin());
        $this->assertFalse($capabilities->canManageSystem());
        $this->assertFalse($capabilities->canImpersonateUsers());
    }

    public function test_manager_has_same_capabilities_as_admin(): void
    {
        $user = User::factory()->create(['role' => UserRole::MANAGER, 'is_active' => true]);
        $capabilities = UserCapabilities::fromUser($user);

        $this->assertTrue($capabilities->canManageProperties());
        $this->assertTrue($capabilities->canManageTenants());
        $this->assertTrue($capabilities->canManageBuildings());
        $this->assertTrue($capabilities->canManageInvoices());
        $this->assertTrue($capabilities->canSubmitReadings());
        $this->assertTrue($capabilities->canViewReports());
        $this->assertTrue($capabilities->canAccessAdmin());
        $this->assertFalse($capabilities->canAccessSuperadmin());
        $this->assertFalse($capabilities->canManageSystem());
        $this->assertFalse($capabilities->canImpersonateUsers());
    }

    public function test_tenant_has_limited_capabilities(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT, 'is_active' => true]);
        $capabilities = UserCapabilities::fromUser($user);

        $this->assertFalse($capabilities->canManageProperties());
        $this->assertFalse($capabilities->canManageTenants());
        $this->assertFalse($capabilities->canManageBuildings());
        $this->assertFalse($capabilities->canManageInvoices());
        $this->assertTrue($capabilities->canSubmitReadings());
        $this->assertFalse($capabilities->canViewReports());
        $this->assertFalse($capabilities->canAccessAdmin());
        $this->assertFalse($capabilities->canAccessSuperadmin());
        $this->assertFalse($capabilities->canManageSystem());
        $this->assertFalse($capabilities->canImpersonateUsers());
    }

    public function test_inactive_user_has_no_capabilities(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => false]);
        $capabilities = UserCapabilities::fromUser($user);

        $this->assertFalse($capabilities->canManageProperties());
        $this->assertFalse($capabilities->canManageTenants());
        $this->assertFalse($capabilities->canManageBuildings());
        $this->assertFalse($capabilities->canManageInvoices());
        $this->assertFalse($capabilities->canSubmitReadings());
        $this->assertFalse($capabilities->canViewReports());
        $this->assertFalse($capabilities->canAccessAdmin());
        $this->assertFalse($capabilities->canAccessSuperadmin());
        $this->assertFalse($capabilities->canManageSystem());
        $this->assertFalse($capabilities->canImpersonateUsers());
    }

    public function test_to_array_returns_all_capabilities(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => true]);
        $capabilities = UserCapabilities::fromUser($user);

        $array = $capabilities->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('can_manage_properties', $array);
        $this->assertArrayHasKey('can_manage_tenants', $array);
        $this->assertArrayHasKey('can_manage_buildings', $array);
        $this->assertArrayHasKey('can_manage_invoices', $array);
        $this->assertArrayHasKey('can_submit_readings', $array);
        $this->assertArrayHasKey('can_view_reports', $array);
        $this->assertArrayHasKey('can_access_admin', $array);
        $this->assertArrayHasKey('can_access_superadmin', $array);
        $this->assertArrayHasKey('can_manage_system', $array);
        $this->assertArrayHasKey('can_impersonate_users', $array);

        $this->assertTrue($array['can_manage_properties']);
        $this->assertFalse($array['can_access_superadmin']);
    }
}