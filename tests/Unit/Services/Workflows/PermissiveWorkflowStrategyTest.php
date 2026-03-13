<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Workflows;

use App\Enums\UserRole;
use App\Enums\ValidationStatus;
use App\Models\MeterReading;
use App\Models\User;
use App\Services\Workflows\PermissiveWorkflowStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PermissiveWorkflowStrategyTest
 * 
 * Tests the Permissive workflow strategy implementation.
 * 
 * @covers \App\Services\Workflows\PermissiveWorkflowStrategy
 */
final class PermissiveWorkflowStrategyTest extends TestCase
{
    use RefreshDatabase;

    private PermissiveWorkflowStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new PermissiveWorkflowStrategy();
    }

    /** @test */
    public function returns_correct_workflow_name(): void
    {
        $this->assertEquals('permissive', $this->strategy->getWorkflowName());
    }

    /** @test */
    public function returns_workflow_description(): void
    {
        $description = $this->strategy->getWorkflowDescription();
        
        $this->assertIsString($description);
        $this->assertStringContainsString('pending', $description);
        $this->assertStringContainsString('self-service', $description);
    }

    /** @test */
    public function allows_tenant_to_update_own_pending_reading(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT, 'id' => 1]);
        $meterReading = MeterReading::factory()->create([
            'entered_by' => 1,
            'validation_status' => ValidationStatus::PENDING,
        ]);

        $this->assertTrue($this->strategy->canTenantUpdate($user, $meterReading));
    }

    /** @test */
    public function denies_tenant_update_of_validated_reading(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT, 'id' => 1]);
        $meterReading = MeterReading::factory()->create([
            'entered_by' => 1,
            'validation_status' => ValidationStatus::VALIDATED,
        ]);

        $this->assertFalse($this->strategy->canTenantUpdate($user, $meterReading));
    }

    /** @test */
    public function denies_tenant_update_of_rejected_reading(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT, 'id' => 1]);
        $meterReading = MeterReading::factory()->create([
            'entered_by' => 1,
            'validation_status' => ValidationStatus::REJECTED,
        ]);

        $this->assertFalse($this->strategy->canTenantUpdate($user, $meterReading));
    }

    /** @test */
    public function denies_tenant_update_of_other_users_reading(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT, 'id' => 1]);
        $meterReading = MeterReading::factory()->create([
            'entered_by' => 2, // Different user
            'validation_status' => ValidationStatus::PENDING,
        ]);

        $this->assertFalse($this->strategy->canTenantUpdate($user, $meterReading));
    }

    /** @test */
    public function allows_tenant_to_delete_own_pending_reading(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT, 'id' => 1]);
        $meterReading = MeterReading::factory()->create([
            'entered_by' => 1,
            'validation_status' => ValidationStatus::PENDING,
        ]);

        $this->assertTrue($this->strategy->canTenantDelete($user, $meterReading));
    }

    /** @test */
    public function denies_tenant_delete_of_validated_reading(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT, 'id' => 1]);
        $meterReading = MeterReading::factory()->create([
            'entered_by' => 1,
            'validation_status' => ValidationStatus::VALIDATED,
        ]);

        $this->assertFalse($this->strategy->canTenantDelete($user, $meterReading));
    }

    /** @test */
    public function denies_tenant_delete_of_other_users_reading(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT, 'id' => 1]);
        $meterReading = MeterReading::factory()->create([
            'entered_by' => 2, // Different user
            'validation_status' => ValidationStatus::PENDING,
        ]);

        $this->assertFalse($this->strategy->canTenantDelete($user, $meterReading));
    }

    /** @test */
    public function denies_tenant_approval_of_any_reading(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT, 'id' => 1]);
        $meterReading = MeterReading::factory()->create([
            'entered_by' => 1,
            'validation_status' => ValidationStatus::PENDING,
        ]);

        $this->assertFalse($this->strategy->canTenantApprove($user, $meterReading));
    }

    /** @test */
    public function denies_tenant_rejection_of_any_reading(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT, 'id' => 1]);
        $meterReading = MeterReading::factory()->create([
            'entered_by' => 1,
            'validation_status' => ValidationStatus::PENDING,
        ]);

        $this->assertFalse($this->strategy->canTenantReject($user, $meterReading));
    }
}