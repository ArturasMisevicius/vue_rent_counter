<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Contracts\WorkflowStrategyInterface;
use App\Enums\UserRole;
use App\Enums\ValidationStatus;
use App\Models\MeterReading;
use App\Models\User;
use App\Policies\MeterReadingPolicy;
use App\Services\TenantBoundaryService;
use App\Services\Workflows\PermissiveWorkflowStrategy;
use App\Services\Workflows\TruthButVerifyWorkflowStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * MeterReadingPolicyTest
 * 
 * Tests the refactored MeterReadingPolicy with configurable workflow support.
 * 
 * @covers \App\Policies\MeterReadingPolicy
 */
final class MeterReadingPolicyTest extends TestCase
{
    use RefreshDatabase;

    private MeterReadingPolicy $policy;
    private TenantBoundaryService $tenantBoundaryService;
    private WorkflowStrategyInterface $workflowStrategy;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenantBoundaryService = Mockery::mock(TenantBoundaryService::class);
        $this->workflowStrategy = Mockery::mock(WorkflowStrategyInterface::class);
        $this->policy = new MeterReadingPolicy($this->tenantBoundaryService, $this->workflowStrategy);
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
    public function all_roles_can_view_any_meter_readings(): void
    {
        $roles = [UserRole::SUPERADMIN, UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT];

        foreach ($roles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertTrue($this->policy->viewAny($user), "Role {$role->value} should be able to view any meter readings");
        }
    }

    /** @test */
    public function superadmin_can_view_specific_meter_reading(): void
    {
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $meterReading = MeterReading::factory()->create();

        $this->assertTrue($this->policy->view($user, $meterReading));
    }

    /** @test */
    public function admin_can_view_meter_reading_in_same_tenant(): void
    {
        $tenantId = 1;
        $user = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $tenantId]);
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
    public function admin_can_update_meter_reading_in_same_tenant(): void
    {
        $tenantId = 1;
        $user = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => $tenantId]);
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
    public function tenant_update_uses_workflow_strategy(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT]);
        $meterReading = MeterReading::factory()->create();

        $this->workflowStrategy
            ->shouldReceive('canTenantUpdate')
            ->once()
            ->with($user, $meterReading)
            ->andReturn(true);

        $this->workflowStrategy
            ->shouldReceive('getWorkflowName')
            ->andReturn('test_workflow');

        $this->assertTrue($this->policy->update($user, $meterReading));
    }

    /** @test */
    public function tenant_cannot_update_when_workflow_denies(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT]);
        $meterReading = MeterReading::factory()->create();

        $this->workflowStrategy
            ->shouldReceive('canTenantUpdate')
            ->once()
            ->with($user, $meterReading)
            ->andReturn(false);

        $this->workflowStrategy
            ->shouldReceive('getWorkflowName')
            ->andReturn('test_workflow');

        $this->assertFalse($this->policy->update($user, $meterReading));
    }

    /** @test */
    public function permissive_workflow_allows_tenant_to_update_own_pending_reading(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT, 'id' => 1]);
        $meterReading = MeterReading::factory()->create([
            'entered_by' => 1,
            'validation_status' => ValidationStatus::PENDING,
        ]);

        $permissiveStrategy = new PermissiveWorkflowStrategy();
        $policy = new MeterReadingPolicy($this->tenantBoundaryService, $permissiveStrategy);

        $this->assertTrue($policy->update($user, $meterReading));
    }

    /** @test */
    public function permissive_workflow_denies_tenant_update_of_validated_reading(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT, 'id' => 1]);
        $meterReading = MeterReading::factory()->create([
            'entered_by' => 1,
            'validation_status' => ValidationStatus::VALIDATED,
        ]);

        $permissiveStrategy = new PermissiveWorkflowStrategy();
        $policy = new MeterReadingPolicy($this->tenantBoundaryService, $permissiveStrategy);

        $this->assertFalse($policy->update($user, $meterReading));
    }

    /** @test */
    public function truth_but_verify_workflow_denies_tenant_updates(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT, 'id' => 1]);
        $meterReading = MeterReading::factory()->create([
            'entered_by' => 1,
            'validation_status' => ValidationStatus::PENDING,
        ]);

        $truthButVerifyStrategy = new TruthButVerifyWorkflowStrategy();
        $policy = new MeterReadingPolicy($this->tenantBoundaryService, $truthButVerifyStrategy);

        $this->assertFalse($policy->update($user, $meterReading));
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
    public function superadmin_and_admin_can_delete_meter_readings(): void
    {
        $meterReading = MeterReading::factory()->create();

        // Superadmin can delete
        $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $this->assertTrue($this->policy->delete($superadmin, $meterReading));

        // Admin can delete
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->assertTrue($this->policy->delete($admin, $meterReading));
    }

    /** @test */
    public function tenant_delete_uses_workflow_strategy(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT]);
        $meterReading = MeterReading::factory()->create();

        $this->workflowStrategy
            ->shouldReceive('canTenantDelete')
            ->once()
            ->with($user, $meterReading)
            ->andReturn(true);

        $this->workflowStrategy
            ->shouldReceive('getWorkflowName')
            ->andReturn('test_workflow');

        $this->assertTrue($this->policy->delete($user, $meterReading));
    }

    /** @test */
    public function permissive_workflow_allows_tenant_to_delete_own_pending_reading(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT, 'id' => 1]);
        $meterReading = MeterReading::factory()->create([
            'entered_by' => 1,
            'validation_status' => ValidationStatus::PENDING,
        ]);

        $permissiveStrategy = new PermissiveWorkflowStrategy();
        $policy = new MeterReadingPolicy($this->tenantBoundaryService, $permissiveStrategy);

        $this->assertTrue($policy->delete($user, $meterReading));
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

    /** @test */
    public function policy_uses_default_permissive_workflow_when_none_provided(): void
    {
        $policy = new MeterReadingPolicy($this->tenantBoundaryService);
        
        $user = User::factory()->create(['role' => UserRole::TENANT, 'id' => 1]);
        $meterReading = MeterReading::factory()->create([
            'entered_by' => 1,
            'validation_status' => ValidationStatus::PENDING,
        ]);

        // Should use default PermissiveWorkflowStrategy
        $this->assertTrue($policy->update($user, $meterReading));
    }
}