<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Enums\UserRole;
use App\Enums\ValidationStatus;
use App\Models\MeterReading;
use App\Models\User;
use App\Policies\MeterReadingPolicy;
use App\Services\TenantBoundaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * MeterReadingPolicyTest
 * 
 * Tests the refactored MeterReadingPolicy with Truth-but-Verify workflow support.
 * 
 * @covers \App\Policies\MeterReadingPolicy
 */
final class MeterReadingPolicyTest extends TestCase
{
    use RefreshDatabase;

    private MeterReadingPolicy $policy;
    private TenantBoundaryService $tenantBoundaryService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenantBoundaryService = Mockery::mock(TenantBoundaryService::class);
        $this->policy = new MeterReadingPolicy($this->tenantBoundaryService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function superadmin_can_view_any_meter_readings(): void
    {
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function admin_can_view_any_meter_readings(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function manager_can_view_any_meter_readings(): void
    {
        $user = User::factory()->create(['role' => UserRole::MANAGER]);

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function tenant_can_view_any_meter_readings(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT]);

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function superadmin_can_view_specific_meter_reading(): void
    {
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $meterReading = MeterReading::factory()->create();

        $this->assertTrue($this->policy->view($user, $meterReading));
    }

    /** @test */
    public function admin_can_view_specific_meter_reading(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $meterReading = MeterReading::factory()->create();

        $this->assertTrue($this->policy->view($user, $meterReading));
    }

    /** @test */
    public function manager_can_view_meter_reading_in_same_tenant(): void
    {
        $tenantId = 1;
        $user = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $tenantId]);
        $meterReading = MeterReading::factory()->create(['tenant_id' => $tenantId]);

        $this->assertTrue($this->policy->view($user, $meterReading));
    }

    /** @test */
    public function manager_cannot_view_meter_reading_in_different_tenant(): void
    {
        $user = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 1]);
        $meterReading = MeterReading::factory()->create(['tenant_id' => 2]);

        $this->assertFalse($this->policy->view($user, $meterReading));
    }

    /** @test */
    public function tenant_can_view_meter_reading_for_their_property(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT]);
        $meterReading = MeterReading::factory()->create();

        $this->tenantBoundaryService
            ->shouldReceive('canTenantAccessMeterReading')
            ->once()
            ->with($user, $meterReading)
            ->andReturn(true);

        $this->assertTrue($this->policy->view($user, $meterReading));
    }

    /** @test */
    public function tenant_cannot_view_meter_reading_for_other_property(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT]);
        $meterReading = MeterReading::factory()->create();

        $this->tenantBoundaryService
            ->shouldReceive('canTenantAccessMeterReading')
            ->once()
            ->with($user, $meterReading)
            ->andReturn(false);

        $this->assertFalse($this->policy->view($user, $meterReading));
    }

    /** @test */
    public function all_roles_can_create_meter_readings(): void
    {
        $roles = [UserRole::SUPERADMIN, UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertTrue($this->policy->create($user), "Role {$role->value} should be able to create meter readings");
        }
    }

    /** @test */
    public function tenant_can_create_reading_for_accessible_meter(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT]);
        $meterId = 123;

        $this->tenantBoundaryService
            ->shouldReceive('canTenantSubmitReadingForMeter')
            ->once()
            ->with($user, $meterId)
            ->andReturn(true);

        $this->assertTrue($this->policy->createForMeter($user, $meterId));
    }

    /** @test */
    public function tenant_cannot_create_reading_for_inaccessible_meter(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT]);
        $meterId = 123;

        $this->tenantBoundaryService
            ->shouldReceive('canTenantSubmitReadingForMeter')
            ->once()
            ->with($user, $meterId)
            ->andReturn(false);

        $this->assertFalse($this->policy->createForMeter($user, $meterId));
    }

    /** @test */
    public function superadmin_can_update_any_meter_reading(): void
    {
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $meterReading = MeterReading::factory()->create();

        $this->assertTrue($this->policy->update($user, $meterReading));
    }

    /** @test */
    public function admin_can_update_any_meter_reading(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $meterReading = MeterReading::factory()->create();

        $this->assertTrue($this->policy->update($user, $meterReading));
    }

    /** @test */
    public function manager_can_update_meter_reading_in_same_tenant(): void
    {
        $tenantId = 1;
        $user = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $tenantId]);
        $meterReading = MeterReading::factory()->create(['tenant_id' => $tenantId]);

        $this->assertTrue($this->policy->update($user, $meterReading));
    }

    /** @test */
    public function manager_cannot_update_meter_reading_in_different_tenant(): void
    {
        $user = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 1]);
        $meterReading = MeterReading::factory()->create(['tenant_id' => 2]);

        $this->assertFalse($this->policy->update($user, $meterReading));
    }

    /** @test */
    public function tenant_cannot_update_meter_reading(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT]);
        $meterReading = MeterReading::factory()->create();

        $this->assertFalse($this->policy->update($user, $meterReading));
    }

    /** @test */
    public function manager_can_approve_pending_reading_in_same_tenant(): void
    {
        $tenantId = 1;
        $user = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $tenantId]);
        $meterReading = MeterReading::factory()->create([
            'tenant_id' => $tenantId,
            'validation_status' => ValidationStatus::PENDING,
        ]);

        // Mock the requiresValidation method
        $meterReading = Mockery::mock($meterReading)->makePartial();
        $meterReading->shouldReceive('requiresValidation')->andReturn(true);

        $this->assertTrue($this->policy->approve($user, $meterReading));
    }

    /** @test */
    public function manager_cannot_approve_reading_in_different_tenant(): void
    {
        $user = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 1]);
        $meterReading = MeterReading::factory()->create([
            'tenant_id' => 2,
            'validation_status' => ValidationStatus::PENDING,
        ]);

        $this->assertFalse($this->policy->approve($user, $meterReading));
    }

    /** @test */
    public function tenant_cannot_approve_meter_reading(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT]);
        $meterReading = MeterReading::factory()->create([
            'validation_status' => ValidationStatus::PENDING,
        ]);

        $this->assertFalse($this->policy->approve($user, $meterReading));
    }

    /** @test */
    public function cannot_approve_already_validated_reading(): void
    {
        $user = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 1]);
        $meterReading = MeterReading::factory()->create([
            'tenant_id' => 1,
            'validation_status' => ValidationStatus::VALIDATED,
        ]);

        $this->assertFalse($this->policy->approve($user, $meterReading));
    }

    /** @test */
    public function only_admins_can_delete_meter_readings(): void
    {
        $meterReading = MeterReading::factory()->create();

        // Admins can delete
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->assertTrue($this->policy->delete($admin, $meterReading));

        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $this->assertTrue($this->policy->delete($superadmin, $meterReading));

        // Others cannot delete
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $this->assertFalse($this->policy->delete($manager, $meterReading));

        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        $this->assertFalse($this->policy->delete($tenant, $meterReading));
    }

    /** @test */
    public function only_superadmin_can_force_delete_meter_readings(): void
    {
        $meterReading = MeterReading::factory()->create();

        // Only superadmin can force delete
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $this->assertTrue($this->policy->forceDelete($superadmin, $meterReading));

        // Others cannot force delete
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->assertFalse($this->policy->forceDelete($admin, $meterReading));

        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $this->assertFalse($this->policy->forceDelete($manager, $meterReading));

        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        $this->assertFalse($this->policy->forceDelete($tenant, $meterReading));
    }

    /** @test */
    public function managers_and_above_can_replicate_readings_in_scope(): void
    {
        $tenantId = 1;
        $meterReading = MeterReading::factory()->create(['tenant_id' => $tenantId]);

        // Superadmin can replicate any reading
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $this->assertTrue($this->policy->replicate($superadmin, $meterReading));

        // Admin can replicate any reading
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->assertTrue($this->policy->replicate($admin, $meterReading));

        // Manager can replicate readings in same tenant
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => $tenantId]);
        $this->assertTrue($this->policy->replicate($manager, $meterReading));

        // Manager cannot replicate readings in different tenant
        $managerOtherTenant = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 2]);
        $this->assertFalse($this->policy->replicate($managerOtherTenant, $meterReading));

        // Tenant cannot replicate
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        $this->assertFalse($this->policy->replicate($tenant, $meterReading));
    }

    /** @test */
    public function all_roles_can_export_meter_readings(): void
    {
        $roles = [UserRole::SUPERADMIN, UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertTrue($this->policy->export($user), "Role {$role->value} should be able to export meter readings");
        }
    }

    /** @test */
    public function only_managers_and_above_can_import_meter_readings(): void
    {
        // Managers and above can import
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $this->assertTrue($this->policy->import($superadmin));

        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->assertTrue($this->policy->import($admin));

        $manager = User::factory()->create(['role' => UserRole::MANAGER]);
        $this->assertTrue($this->policy->import($manager));

        // Tenants cannot import
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        $this->assertFalse($this->policy->import($tenant));
    }
}