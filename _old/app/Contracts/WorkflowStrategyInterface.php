<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\MeterReading;
use App\Models\User;

/**
 * Interface for meter reading workflow strategies.
 * 
 * This interface defines the contract for different workflow types
 * (Truth-but-Verify, Permissive, etc.) to handle tenant permissions
 * for meter reading operations.
 */
interface WorkflowStrategyInterface
{
    /**
     * Determine if a tenant can update a meter reading.
     * 
     * @param User $tenant The tenant user
     * @param MeterReading $meterReading The meter reading to update
     * @return bool True if the tenant can update the reading
     */
    public function canTenantUpdate(User $tenant, MeterReading $meterReading): bool;

    /**
     * Determine if a tenant can delete a meter reading.
     * 
     * @param User $tenant The tenant user
     * @param MeterReading $meterReading The meter reading to delete
     * @return bool True if the tenant can delete the reading
     */
    public function canTenantDelete(User $tenant, MeterReading $meterReading): bool;

    /**
     * Determine if a tenant can approve a meter reading.
     * 
     * @param User $tenant The tenant user
     * @param MeterReading $meterReading The meter reading to approve
     * @return bool True if the tenant can approve the reading
     */
    public function canTenantApprove(User $tenant, MeterReading $meterReading): bool;

    /**
     * Determine if a tenant can reject a meter reading.
     * 
     * @param User $tenant The tenant user
     * @param MeterReading $meterReading The meter reading to reject
     * @return bool True if the tenant can reject the reading
     */
    public function canTenantReject(User $tenant, MeterReading $meterReading): bool;

    /**
     * Get the workflow name for logging purposes.
     * 
     * @return string The workflow name
     */
    public function getWorkflowName(): string;

    /**
     * Get a description of the workflow's behavior.
     * 
     * @return string The workflow description
     */
    public function getWorkflowDescription(): string;
}