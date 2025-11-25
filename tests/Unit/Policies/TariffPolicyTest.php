<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Enums\UserRole;
use App\Models\Tariff;
use App\Models\User;
use App\Policies\TariffPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TariffPolicyTest
 * 
 * Tests authorization rules for tariff operations.
 * 
 * Requirements:
 * - 11.1: Verify user's role using Laravel Policies
 * - 11.2: Admin has full CRUD operations on tariffs
 * - 11.3: Manager cannot modify tariffs (read-only access)
 * - 11.4: Tenant has view-only access to tariffs
 */
class TariffPolicyTest extends TestCase
{
    use RefreshDatabase;

    private TariffPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new TariffPolicy();
    }

    /**
     * Test that all roles can view tariffs.
     * 
     * Requirements: 11.1, 11.4
     */
    public function test_all_roles_can_view_tariffs(): void
    {
        $tariff = Tariff::factory()->create();

        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        $this->assertTrue($this->policy->viewAny($superadmin));
        $this->assertTrue($this->policy->viewAny($admin));
        $this->assertTrue($this->policy->viewAny($manager));
        $this->assertTrue($this->policy->viewAny($tenant));

        $this->assertTrue($this->policy->view($superadmin, $tariff));
        $this->assertTrue($this->policy->view($admin, $tariff));
        $this->assertTrue($this->policy->view($manager, $tariff));
        $this->assertTrue($this->policy->view($tenant, $tariff));
    }

    /**
     * Test that only admins and superadmins can create tariffs.
     * 
     * Requirements: 11.2, 11.3
     */
    public function test_only_admins_can_create_tariffs(): void
    {
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        $this->assertTrue($this->policy->create($superadmin));
        $this->assertTrue($this->policy->create($admin));
        $this->assertFalse($this->policy->create($manager)); // Requirement 11.3
        $this->assertFalse($this->policy->create($tenant));
    }

    /**
     * Test that only admins and superadmins can update tariffs.
     * 
     * Requirements: 11.2, 11.3
     */
    public function test_only_admins_can_update_tariffs(): void
    {
        $tariff = Tariff::factory()->create();

        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        $this->assertTrue($this->policy->update($superadmin, $tariff));
        $this->assertTrue($this->policy->update($admin, $tariff));
        $this->assertFalse($this->policy->update($manager, $tariff)); // Requirement 11.3
        $this->assertFalse($this->policy->update($tenant, $tariff));
    }

    /**
     * Test that only admins and superadmins can delete tariffs.
     * 
     * Requirements: 11.2
     */
    public function test_only_admins_can_delete_tariffs(): void
    {
        $tariff = Tariff::factory()->create();

        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        $this->assertTrue($this->policy->delete($superadmin, $tariff));
        $this->assertTrue($this->policy->delete($admin, $tariff));
        $this->assertFalse($this->policy->delete($manager, $tariff));
        $this->assertFalse($this->policy->delete($tenant, $tariff));
    }

    /**
     * Test that only admins and superadmins can restore tariffs.
     * 
     * Requirements: 11.2
     */
    public function test_only_admins_can_restore_tariffs(): void
    {
        $tariff = Tariff::factory()->create();

        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        $this->assertTrue($this->policy->restore($superadmin, $tariff));
        $this->assertTrue($this->policy->restore($admin, $tariff));
        $this->assertFalse($this->policy->restore($manager, $tariff));
        $this->assertFalse($this->policy->restore($tenant, $tariff));
    }

    /**
     * Test that only superadmins can force delete tariffs.
     * 
     * Requirements: 11.1
     */
    public function test_only_superadmins_can_force_delete_tariffs(): void
    {
        $tariff = Tariff::factory()->create();

        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        $this->assertTrue($this->policy->forceDelete($superadmin, $tariff));
        $this->assertFalse($this->policy->forceDelete($admin, $tariff));
        $this->assertFalse($this->policy->forceDelete($manager, $tariff));
        $this->assertFalse($this->policy->forceDelete($tenant, $tariff));
    }
}
