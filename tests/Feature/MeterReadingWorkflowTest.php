<?php

declare(strict_types=1);

namespace Tests\Feature;

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
 * MeterReadingWorkflowTest
 * 
 * Feature tests for meter reading workflow integration.
 * Tests the complete workflow from policy to strategy implementation.
 * 
 * @covers \App\Policies\MeterReadingPolicy
 * @covers \App\Services\Workflows\PermissiveWorkflowStrategy
 * @covers \App\Services\Workflows\TruthButVerifyWorkflowStrategy
 */
final class MeterReadingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private TenantBoundaryService $tenantBoundaryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantBoundaryService = Mockery::mock(TenantBoundaryService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function permissive_workflow_enables_tenant_self_service(): void
    {
        // Arrange: Create tenant and their own pending reading
        $tenant = User::factory()->create([
            'role' => UserRole::TENANT,
            'id' => 1,
            'tenant_id' => 100,
        ]);
        
        $meterReading = MeterReading::factory()->create([
            'entered_by' => 1,
            'validation_status' => ValidationStatus::PENDING,
            'tenant_id' => 100,
        ]);

        $permissiveStrategy = new PermissiveWorkflowStrategy();
        $policy = new MeterReadingPolicy($this->tenantBoundaryService, $permissiveStrategy);

        // Act & Assert: Tenant can update their own pending reading
        $this->assertTrue($policy->update($tenant, $meterReading));
        $this->assertTrue($policy->delete($tenant, $meterReading));
        
        // But cannot approve/reject (manager privilege)
        $this->assertFalse($policy->approve($tenant, $meterReading));
        $this->assertFalse($policy->reject($tenant, $meterReading));
    }

    /** @test */
    public function permissive_workflow_prevents_modification_of_validated_readings(): void
    {
        // Arrange: Create tenant and their own validated reading
        $tenant = User::factory()->create([
            'role' => UserRole::TENANT,
            'id' => 1,
            'tenant_id' => 100,
        ]);
        
        $meterReading = MeterReading::factory()->create([
            'entered_by' => 1,
            'validation_status' => ValidationStatus::VALIDATED,
            'tenant_id' => 100,
        ]);

        $permissiveStrategy = new PermissiveWorkflowStrategy();
        $policy = new MeterReadingPolicy($this->tenantBoundaryService, $permissiveStrategy);

        // Act & Assert: Tenant cannot modify validated readings
        $this->assertFalse($policy->update($tenant, $meterReading));
        $this->assertFalse($policy->delete($tenant, $meterReading));
    }

    /** @test */
    public function permissive_workflow_prevents_cross_tenant_access(): void
    {
        // Arrange: Create tenant and another user's reading
        $tenant = User::factory()->create([
            'role' => UserRole::TENANT,
            'id' => 1,
            'tenant_id' => 100,
        ]);
        
        $meterReading = MeterReading::factory()->create([
            'entered_by' => 2, // Different user
            'validation_status' => ValidationStatus::PENDING,
            'tenant_id' => 100,
        ]);

        $permissiveStrategy = new PermissiveWorkflowStrategy();
        $policy = new MeterReadingPolicy($this->tenantBoundaryService, $permissiveStrategy);

        // Act & Assert: Tenant cannot modify other users' readings
        $this->assertFalse($policy->update($tenant, $meterReading));
        $this->assertFalse($policy->delete($tenant, $meterReading));
    }

    /** @test */
    public function truth_but_verify_workflow_prevents_all_tenant_modifications(): void
    {
        // Arrange: Create tenant and their own pending reading
        $tenant = User::factory()->create([
            'role' => UserRole::TENANT,
            'id' => 1,
            'tenant_id' => 100,
        ]);
        
        $meterReading = MeterReading::factory()->create([
            'entered_by' => 1,
            'validation_status' => ValidationStatus::PENDING,
            'tenant_id' => 100,
        ]);

        $truthButVerifyStrategy = new TruthButVerifyWorkflowStrategy();
        $policy = new MeterReadingPolicy($this->tenantBoundaryService, $truthButVerifyStrategy);

        // Act & Assert: Tenant cannot modify any readings in strict workflow
        $this->assertFalse($policy->update($tenant, $meterReading));
        $this->assertFalse($policy->delete($tenant, $meterReading));
        $this->assertFalse($policy->approve($tenant, $meterReading));
        $this->assertFalse($policy->reject($tenant, $meterReading));
    }

    /** @test */
    public function managers_can_always_modify_readings_in_their_tenant(): void
    {
        // Arrange: Create manager and reading in same tenant
        $manager = User::factory()->create([
            'role' => UserRole::MANAGER,
            'tenant_id' => 100,
        ]);
        
        $meterReading = MeterReading::factory()->create([
            'entered_by' => 999, // Different user
            'validation_status' => ValidationStatus::VALIDATED,
            'tenant_id' => 100,
        ]);

        // Test both workflows
        $permissiveStrategy = new PermissiveWorkflowStrategy();
        $truthButVerifyStrategy = new TruthButVerifyWorkflowStrategy();
        
        $permissivePolicy = new MeterReadingPolicy($this->tenantBoundaryService, $permissiveStrategy);
        $strictPolicy = new MeterReadingPolicy($this->tenantBoundaryService, $truthButVerifyStrategy);

        // Act & Assert: Manager can modify readings regardless of workflow
        $this->assertTrue($permissivePolicy->update($manager, $meterReading));
        $this->assertTrue($strictPolicy->update($manager, $meterReading));
        
        // But only admins can delete (not managers in this implementation)
        $this->assertFalse($permissivePolicy->delete($manager, $meterReading));
        $this->assertFalse($strictPolicy->delete($manager, $meterReading));
    }

    /** @test */
    public function admins_can_always_modify_readings_in_their_tenant(): void
    {
        // Arrange: Create admin and reading in same tenant
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
            'tenant_id' => 100,
        ]);
        
        $meterReading = MeterReading::factory()->create([
            'entered_by' => 999, // Different user
            'validation_status' => ValidationStatus::VALIDATED,
            'tenant_id' => 100,
        ]);

        // Test both workflows
        $permissiveStrategy = new PermissiveWorkflowStrategy();
        $truthButVerifyStrategy = new TruthButVerifyWorkflowStrategy();
        
        $permissivePolicy = new MeterReadingPolicy($this->tenantBoundaryService, $permissiveStrategy);
        $strictPolicy = new MeterReadingPolicy($this->tenantBoundaryService, $truthButVerifyStrategy);

        // Act & Assert: Admin can modify and delete readings regardless of workflow
        $this->assertTrue($permissivePolicy->update($admin, $meterReading));
        $this->assertTrue($strictPolicy->update($admin, $meterReading));
        $this->assertTrue($permissivePolicy->delete($admin, $meterReading));
        $this->assertTrue($strictPolicy->delete($admin, $meterReading));
    }

    /** @test */
    public function superadmins_can_always_modify_any_reading(): void
    {
        // Arrange: Create superadmin and reading in different tenant
        $superadmin = User::factory()->create([
            'role' => UserRole::SUPERADMIN,
            'tenant_id' => 200, // Different tenant
        ]);
        
        $meterReading = MeterReading::factory()->create([
            'entered_by' => 999,
            'validation_status' => ValidationStatus::VALIDATED,
            'tenant_id' => 100, // Different tenant
        ]);

        // Test both workflows
        $permissiveStrategy = new PermissiveWorkflowStrategy();
        $truthButVerifyStrategy = new TruthButVerifyWorkflowStrategy();
        
        $permissivePolicy = new MeterReadingPolicy($this->tenantBoundaryService, $permissiveStrategy);
        $strictPolicy = new MeterReadingPolicy($this->tenantBoundaryService, $truthButVerifyStrategy);

        // Act & Assert: Superadmin can modify any reading regardless of workflow or tenant
        $this->assertTrue($permissivePolicy->update($superadmin, $meterReading));
        $this->assertTrue($strictPolicy->update($superadmin, $meterReading));
        $this->assertTrue($permissivePolicy->delete($superadmin, $meterReading));
        $this->assertTrue($strictPolicy->delete($superadmin, $meterReading));
    }

    /** @test */
    public function workflow_strategy_is_properly_injected_and_used(): void
    {
        // Arrange: Create tenant and reading
        $tenant = User::factory()->create([
            'role' => UserRole::TENANT,
            'id' => 1,
        ]);
        
        $meterReading = MeterReading::factory()->create([
            'entered_by' => 1,
            'validation_status' => ValidationStatus::PENDING,
        ]);

        // Test default strategy (should be Permissive)
        $defaultPolicy = new MeterReadingPolicy($this->tenantBoundaryService);
        $this->assertTrue($defaultPolicy->update($tenant, $meterReading));

        // Test explicit Permissive strategy
        $permissivePolicy = new MeterReadingPolicy(
            $this->tenantBoundaryService, 
            new PermissiveWorkflowStrategy()
        );
        $this->assertTrue($permissivePolicy->update($tenant, $meterReading));

        // Test Truth-but-Verify strategy
        $strictPolicy = new MeterReadingPolicy(
            $this->tenantBoundaryService, 
            new TruthButVerifyWorkflowStrategy()
        );
        $this->assertFalse($strictPolicy->update($tenant, $meterReading));
    }
}