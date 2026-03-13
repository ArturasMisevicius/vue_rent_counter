<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * UserMeterReadingCapabilitiesTest
 * 
 * Tests the User model's meter reading capability methods.
 * 
 * @covers \App\Models\User
 */
final class UserMeterReadingCapabilitiesTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function all_active_roles_can_create_meter_readings(): void
    {
        $roles = [UserRole::SUPERADMIN, UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role, 'is_active' => true]);
            $this->assertTrue(
                $user->canCreateMeterReadings(),
                "Active {$role->value} should be able to create meter readings"
            );
        }
    }

    /** @test */
    public function inactive_users_cannot_create_meter_readings(): void
    {
        $roles = [UserRole::SUPERADMIN, UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role, 'is_active' => false]);
            $this->assertFalse(
                $user->canCreateMeterReadings(),
                "Inactive {$role->value} should not be able to create meter readings"
            );
        }
    }

    /** @test */
    public function only_managers_and_above_can_manage_meter_readings(): void
    {
        // Can manage
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN, 'is_active' => true]);
        $this->assertTrue($superadmin->canManageMeterReadings());

        $admin = User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => true]);
        $this->assertTrue($admin->canManageMeterReadings());

        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'is_active' => true]);
        $this->assertTrue($manager->canManageMeterReadings());

        // Cannot manage
        $tenant = User::factory()->create(['role' => UserRole::TENANT, 'is_active' => true]);
        $this->assertFalse($tenant->canManageMeterReadings());
    }

    /** @test */
    public function inactive_managers_cannot_manage_meter_readings(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'is_active' => false]);
        $this->assertFalse($manager->canManageMeterReadings());
    }

    /** @test */
    public function can_validate_meter_readings_is_alias_for_can_manage(): void
    {
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'is_active' => true]);
        
        $this->assertEquals(
            $manager->canManageMeterReadings(),
            $manager->canValidateMeterReadings()
        );
    }

    /** @test */
    public function only_tenant_submissions_require_validation(): void
    {
        // Tenant submissions require validation
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        $this->assertTrue($tenant->submissionsRequireValidation());

        // Other roles don't require validation
        $roles = [UserRole::SUPERADMIN, UserRole::ADMIN, UserRole::MANAGER];
        
        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertFalse(
                $user->submissionsRequireValidation(),
                "{$role->value} submissions should not require validation"
            );
        }
    }
}