<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MeterReading;
use App\Models\User;
use App\Services\TenantBoundaryService;
use Illuminate\Auth\Access\Response;

/**
 * Authorization policy for MeterReading model with tenant boundary validation.
 */
final readonly class MeterReadingPolicy
{
    public function __construct(
        private TenantBoundaryService $tenantBoundaryService
    ) {}

    /**
     * Determine whether the user can view any meter readings.
     */
    public function viewAny(User $user): bool
    {
        // Managers and above can view meter readings
        return $this->tenantBoundaryService->canPerformManagerOperations($user);
    }

    /**
     * Determine whether the user can view the meter reading.
     */
    public function view(User $user, MeterReading $meterReading): bool
    {
        // Must be able to access the meter reading's tenant
        if (!$this->tenantBoundaryService->canAccessModel($user, $meterReading)) {
            return false;
        }

        // Managers and above can view all readings in their tenant
        if ($this->tenantBoundaryService->canPerformManagerOperations($user)) {
            return true;
        }

        // Tenants can only view readings for their own properties
        if ($user->hasRole('tenant')) {
            return $this->userOwnsProperty($user, $meterReading);
        }

        return false;
    }

    /**
     * Determine whether the user can create meter readings.
     */
    public function create(User $user): bool
    {
        // Must have a tenant_id and be manager or above
        if ($user->tenant_id === null) {
            return false;
        }

        // Managers and above can create readings
        return $this->tenantBoundaryService->canPerformManagerOperations($user);
    }

    /**
     * Determine whether the user can update the meter reading.
     */
    public function update(User $user, MeterReading $meterReading): Response
    {
        // Must be able to access the meter reading's tenant
        if (!$this->tenantBoundaryService->canAccessModel($user, $meterReading)) {
            return Response::deny('You do not have access to this meter reading.');
        }

        // Cannot update validated readings
        if ($meterReading->validation_status === \App\Enums\ValidationStatus::VALIDATED) {
            return Response::deny('Cannot update validated meter readings.');
        }

        // Managers and above can update readings in their tenant
        if ($this->tenantBoundaryService->canPerformManagerOperations($user)) {
            return Response::allow();
        }

        return Response::deny('Insufficient permissions to update meter readings.');
    }

    /**
     * Determine whether the user can delete the meter reading.
     */
    public function delete(User $user, MeterReading $meterReading): Response
    {
        // Must be able to access the meter reading's tenant
        if (!$this->tenantBoundaryService->canAccessModel($user, $meterReading)) {
            return Response::deny('You do not have access to this meter reading.');
        }

        // Cannot delete validated readings
        if ($meterReading->validation_status === \App\Enums\ValidationStatus::VALIDATED) {
            return Response::deny('Cannot delete validated meter readings.');
        }

        // Only admins and above can delete readings
        if ($this->tenantBoundaryService->canPerformAdminOperations($user)) {
            return Response::allow();
        }

        return Response::deny('Insufficient permissions to delete meter readings.');
    }

    /**
     * Determine whether the user can restore the meter reading.
     */
    public function restore(User $user, MeterReading $meterReading): bool
    {
        // Must be able to access the meter reading's tenant
        if (!$this->tenantBoundaryService->canAccessModel($user, $meterReading)) {
            return false;
        }

        // Only admins and above can restore readings
        return $this->tenantBoundaryService->canPerformAdminOperations($user);
    }

    /**
     * Determine whether the user can permanently delete the meter reading.
     */
    public function forceDelete(User $user, MeterReading $meterReading): bool
    {
        // Must be able to access the meter reading's tenant
        if (!$this->tenantBoundaryService->canAccessModel($user, $meterReading)) {
            return false;
        }

        // Only superadmins can force delete
        return $user->hasRole('superadmin');
    }

    /**
     * Determine whether the user can finalize the meter reading.
     */
    public function finalize(User $user, MeterReading $meterReading): Response
    {
        // Must be able to access the meter reading's tenant
        if (!$this->tenantBoundaryService->canAccessModel($user, $meterReading)) {
            return Response::deny('You do not have access to this meter reading.');
        }

        // Cannot finalize already validated readings
        if ($meterReading->validation_status === \App\Enums\ValidationStatus::VALIDATED) {
            return Response::deny('Meter reading is already validated.');
        }

        // Only managers and above can finalize readings
        if ($this->tenantBoundaryService->canPerformManagerOperations($user)) {
            return Response::allow();
        }

        return Response::deny('Insufficient permissions to finalize meter readings.');
    }

    /**
     * Determine whether the user can bulk update meter readings.
     */
    public function bulkUpdate(User $user): bool
    {
        // Must have a tenant_id and be manager or above
        if ($user->tenant_id === null) {
            return false;
        }

        return $this->tenantBoundaryService->canPerformManagerOperations($user);
    }

    /**
     * Check if user owns the property associated with the meter reading.
     */
    private function userOwnsProperty(User $user, MeterReading $meterReading): bool
    {
        // Load the meter and property relationships if not already loaded
        $meterReading->loadMissing('meter.property');
        
        $property = $meterReading->meter?->property;
        
        if (!$property) {
            return false;
        }

        // Check if user is assigned to this property
        return $property->tenant_id === $user->id;
    }
}