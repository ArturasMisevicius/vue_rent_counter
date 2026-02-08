<?php

declare(strict_types=1);

namespace App\Services\Workflows;

use App\Contracts\WorkflowStrategyInterface;
use App\Models\MeterReading;
use App\Models\User;

/**
 * Truth-but-Verify workflow strategy for meter readings.
 * 
 * In this workflow:
 * - Tenants cannot update readings once submitted
 * - Tenants cannot delete readings once submitted
 * - Tenants cannot approve/reject readings
 * - Only managers+ can modify readings after submission
 */
final readonly class TruthButVerifyWorkflowStrategy implements WorkflowStrategyInterface
{
    /**
     * {@inheritDoc}
     */
    public function canTenantUpdate(User $tenant, MeterReading $meterReading): bool
    {
        // Tenants cannot update readings once submitted (Truth-but-Verify workflow)
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function canTenantDelete(User $tenant, MeterReading $meterReading): bool
    {
        // Tenants cannot delete readings once submitted (Truth-but-Verify workflow)
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function canTenantApprove(User $tenant, MeterReading $meterReading): bool
    {
        // Tenants cannot approve readings in any workflow
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function canTenantReject(User $tenant, MeterReading $meterReading): bool
    {
        // Tenants cannot reject readings in any workflow
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getWorkflowName(): string
    {
        return 'truth_but_verify';
    }

    /**
     * {@inheritDoc}
     */
    public function getWorkflowDescription(): string
    {
        return 'Strict workflow where tenants cannot modify readings after submission';
    }
}