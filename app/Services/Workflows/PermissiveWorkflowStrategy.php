<?php

declare(strict_types=1);

namespace App\Services\Workflows;

use App\Contracts\WorkflowStrategyInterface;
use App\Enums\ValidationStatus;
use App\Models\MeterReading;
use App\Models\User;

/**
 * Permissive workflow strategy for meter readings.
 * 
 * In this workflow:
 * - Tenants can update their own readings if status is 'pending'
 * - Tenants can delete their own readings if status is 'pending'
 * - Tenants cannot approve/reject readings (manager privilege)
 * - Once validated/rejected, only managers+ can modify
 */
final readonly class PermissiveWorkflowStrategy implements WorkflowStrategyInterface
{
    /**
     * {@inheritDoc}
     */
    public function canTenantUpdate(User $tenant, MeterReading $meterReading): bool
    {
        return $this->isTenantOwner($tenant, $meterReading) 
            && $this->isPendingStatus($meterReading);
    }

    /**
     * {@inheritDoc}
     */
    public function canTenantDelete(User $tenant, MeterReading $meterReading): bool
    {
        return $this->isTenantOwner($tenant, $meterReading) 
            && $this->isPendingStatus($meterReading);
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
        return 'permissive';
    }

    /**
     * {@inheritDoc}
     */
    public function getWorkflowDescription(): string
    {
        return 'Allows tenants to edit/delete their own pending readings for self-service corrections';
    }

    /**
     * Check if the tenant is the owner of the meter reading.
     * 
     * @param User $tenant The tenant user
     * @param MeterReading $meterReading The meter reading
     * @return bool True if tenant owns the reading
     */
    private function isTenantOwner(User $tenant, MeterReading $meterReading): bool
    {
        return $tenant->id === $meterReading->entered_by;
    }

    /**
     * Check if the meter reading has pending status.
     * 
     * @param MeterReading $meterReading The meter reading
     * @return bool True if status is pending
     */
    private function isPendingStatus(MeterReading $meterReading): bool
    {
        return $meterReading->validation_status === ValidationStatus::PENDING;
    }
}