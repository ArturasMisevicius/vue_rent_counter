<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Workflows;

use App\Enums\UserRole;
use App\Enums\ValidationStatus;
use App\Models\MeterReading;
use App\Models\User;
use App\Services\Workflows\TruthButVerifyWorkflowStrategy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TruthButVerifyWorkflowStrategyTest
 * 
 * Tests the Truth-but-Verify workflow strategy implementation.
 * 
 * @covers \App\Services\Workflows\TruthButVerifyWorkflowStrategy
 */
final class TruthButVerifyWorkflowStrategyTest extends TestCase
{
    use RefreshDatabase;

    private TruthButVerifyWorkflowStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new TruthButVerifyWorkflowStrategy();
    }

    /** @test */
    public function returns_correct_workflow_name(): void
    {
        $this->assertEquals('truth_but_verify', $this->strategy->getWorkflowName());
    }

    /** @test */
    public function returns_workflow_description(): void
    {
        $description = $this->strategy->getWorkflowDescription();
        
        $this->assertIsString($description);
        $this->assertStringContainsString('strict', $description);
        $this->assertStringContainsString('cannot modify', $description);
    }

    /** @test */
    public function denies_tenant_update_of_any_reading(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT, 'id' => 1]);
        $meterReading = MeterReading::factory()->create([
            'entered_by' => 1,
            'validation_status' => ValidationStatus::PENDING,
        ]);

        $this->assertFalse($this->strategy->canTenantUpdate($user, $meterReading));
    }

    /** @test */
    public function denies_tenant_update_even_for_own_pending_reading(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT, 'id' => 1]);
        $meterReading = MeterReading::factory()->create([
            'entered_by' => 1,
            'validation_status' => ValidationStatus::PENDING,
        ]);

        $this->assertFalse($this->strategy->canTenantUpdate($user, $meterReading));
    }

    /** @test */
    public function denies_tenant_delete_of_any_reading(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT, 'id' => 1]);
        $meterReading = MeterReading::factory()->create([
            'entered_by' => 1,
            'validation_status' => ValidationStatus::PENDING,
        ]);

        $this->assertFalse($this->strategy->canTenantDelete($user, $meterReading));
    }

    /** @test */
    public function denies_tenant_delete_even_for_own_pending_reading(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT, 'id' => 1]);
        $meterReading = MeterReading::factory()->create([
            'entered_by' => 1,
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