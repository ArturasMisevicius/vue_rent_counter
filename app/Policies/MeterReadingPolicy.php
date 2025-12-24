<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Enums\ValidationStatus;
use App\Models\MeterReading;
use App\Models\User;
use App\Services\TenantBoundaryService;

/**
 * MeterReadingPolicy
 * 
 * Authorization policy for meter reading operations with Truth-but-Verify workflow support.
 * 
 * Requirements:
 * - 11.1: Verify user's role using Laravel Policies
 * - 11.3: Manager can create and update meter readings
 * - 11.4: Tenant can only view their own meter readings
 * - 7.3: Cross-tenant access prevention
 * - Gold Master v7.0: Tenant Input -> Manager Approval workflow
 * 
 * @package App\Policies
 */
final class MeterReadingPolicy extends BasePolicy
{
    public function __construct(
        private readonly TenantBoundaryService $tenantBoundaryService
    ) {}

    /**
     * Role groups specific to meter reading operations.
     */
    private const READING_CREATORS = [UserRole::SUPERADMIN, UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT];
    private const READING_MANAGERS = [UserRole::SUPERADMIN, UserRole::ADMIN, UserRole::MANAGER];

    /**
     * Determine whether the user can view any meter readings.
     * 
     * All authenticated roles can view meter readings (filtered by tenant scope).
     * 
     * Requirements: 11.1, 11.4
     * 
     * @param User $user The authenticated user
     * @return bool True if authorized
     */
    public function viewAny(User $user): bool
    {
        // All authenticated roles can view meter readings
        return in_array($user->role, self::ALL_ROLES, true);
    }

    /**
     * Determine whether the user can view the meter reading.
     * 
     * Tenants can only view meter readings for their properties.
     * Managers can view readings within their tenant.
     * 
     * Requirements: 11.1, 11.4, 7.3
     * 
     * @param User $user The authenticated user
     * @param MeterReading $meterReading The meter reading to view
     * @return bool True if authorized
     */
    public function view(User $user, MeterReading $meterReading): bool
    {
        // Superadmin can view any meter reading
        if ($this->isSuperadmin($user)) {
            return true;
        }

        // Admins can view meter readings across all tenants
        if ($this->isAdmin($user)) {
            return true;
        }

        // Managers can view readings within their tenant (Requirement 7.3)
        if ($user->role === UserRole::MANAGER) {
            return $this->belongsToUserTenant($user, $meterReading);
        }

        // Tenants can view meter readings for their properties (Requirement 11.4)
        if ($user->role === UserRole::TENANT) {
            return $this->tenantBoundaryService->canTenantAccessMeterReading($user, $meterReading);
        }

        return false;
    }

    /**
     * Determine whether the user can create meter readings.
     * 
     * Admins, Managers, and Tenants can create meter readings.
     * Tenants can submit readings for manager approval (Truth-but-Verify workflow).
     * 
     * Requirements: 11.1, 11.3, Gold Master v7.0
     * 
     * @param User $user The authenticated user
     * @return bool True if authorized
     */
    public function create(User $user): bool
    {
        // All authenticated roles can create meter readings (Gold Master v7.0: Tenant Input -> Manager Approval)
        $canCreate = in_array($user->role, self::READING_CREATORS, true);
        
        if ($canCreate && $user->role === UserRole::TENANT) {
            $this->logSensitiveOperation('create_attempt', $user, null, [
                'workflow' => 'truth_but_verify',
                'requires_approval' => true,
            ]);
        }
        
        return $canCreate;
    }

    /**
     * Determine whether the user can create a meter reading for a specific meter.
     * 
     * This method provides additional validation for tenant submissions.
     * 
     * @param User $user The authenticated user
     * @param int $meterId The meter ID
     * @return bool True if authorized
     */
    public function createForMeter(User $user, int $meterId): bool
    {
        if (!$this->create($user)) {
            return false;
        }

        // Additional validation for tenants
        if ($user->role === UserRole::TENANT) {
            return $this->tenantBoundaryService->canTenantSubmitReadingForMeter($user, $meterId);
        }

        return true;
    }

    /**
     * Determine whether the user can update the meter reading.
     * 
     * Admins and Managers can update meter readings.
     * Managers are restricted to their tenant scope.
     * Tenants cannot update readings once submitted (Truth-but-Verify workflow).
     * 
     * Requirements: 11.1, 11.3, 7.3, Gold Master v7.0
     * 
     * @param User $user The authenticated user
     * @param MeterReading $meterReading The meter reading to update
     * @return bool True if authorized
     */
    public function update(User $user, MeterReading $meterReading): bool
    {
        // Superadmin can update any meter reading
        if ($this->isSuperadmin($user)) {
            $this->logSensitiveOperation('update', $user, $meterReading);
            return true;
        }

        // Admins can update any meter reading
        if ($this->isAdmin($user)) {
            $this->logSensitiveOperation('update', $user, $meterReading);
            return true;
        }

        // Managers can update readings within their tenant (Requirement 11.3, 7.3)
        if ($user->role === UserRole::MANAGER) {
            $canUpdate = $this->belongsToUserTenant($user, $meterReading);
            if ($canUpdate) {
                $this->logSensitiveOperation('update', $user, $meterReading);
            }
            return $canUpdate;
        }

        // Tenants cannot update readings once submitted (Truth-but-Verify workflow)
        return false;
    }

    /**
     * Determine whether the user can approve/validate the meter reading.
     * 
     * Only managers and above can approve tenant-submitted readings.
     * 
     * Requirements: Gold Master v7.0 Truth-but-Verify workflow
     * 
     * @param User $user The authenticated user
     * @param MeterReading $meterReading The meter reading to approve
     * @return bool True if authorized
     */
    public function approve(User $user, MeterReading $meterReading): bool
    {
        // Only managers and above can approve readings
        if (!in_array($user->role, self::READING_MANAGERS, true)) {
            return false;
        }

        // Must be within tenant scope for managers
        if ($user->role === UserRole::MANAGER && !$this->belongsToUserTenant($user, $meterReading)) {
            return false;
        }

        // Can only approve readings that require validation
        if (!$meterReading->requiresValidation()) {
            return false;
        }

        // Can only approve pending readings
        if ($meterReading->validation_status !== ValidationStatus::PENDING) {
            return false;
        }

        $this->logSensitiveOperation('approve', $user, $meterReading, [
            'validation_status' => $meterReading->validation_status->value,
            'input_method' => $meterReading->input_method->value,
        ]);

        return true;
    }

    /**
     * Determine whether the user can reject the meter reading.
     * 
     * Only managers and above can reject tenant-submitted readings.
     * 
     * Requirements: Gold Master v7.0 Truth-but-Verify workflow
     * 
     * @param User $user The authenticated user
     * @param MeterReading $meterReading The meter reading to reject
     * @return bool True if authorized
     */
    public function reject(User $user, MeterReading $meterReading): bool
    {
        // Same logic as approve for now
        return $this->approve($user, $meterReading);
    }

    /**
     * Determine whether the user can delete the meter reading.
     * 
     * Only admins and superadmins can delete meter readings.
     * Tenant-submitted readings should be rejected rather than deleted.
     * 
     * Requirements: 11.1, Gold Master v7.0
     * 
     * @param User $user The authenticated user
     * @param MeterReading $meterReading The meter reading to delete
     * @return bool True if authorized
     */
    public function delete(User $user, MeterReading $meterReading): bool
    {
        // Only admins and superadmins can delete meter readings
        $canDelete = $this->isAdmin($user);
        
        if ($canDelete) {
            $this->logSensitiveOperation('delete', $user, $meterReading, [
                'validation_status' => $meterReading->validation_status->value,
                'input_method' => $meterReading->input_method->value,
                'entered_by' => $meterReading->entered_by,
            ]);
        }
        
        return $canDelete;
    }

    /**
     * Determine whether the user can restore the meter reading.
     * 
     * Only admins and superadmins can restore meter readings.
     * 
     * Requirements: 11.1
     * 
     * @param User $user The authenticated user
     * @param MeterReading $meterReading The meter reading to restore
     * @return bool True if authorized
     */
    public function restore(User $user, MeterReading $meterReading): bool
    {
        $canRestore = $this->isAdmin($user);
        
        if ($canRestore) {
            $this->logSensitiveOperation('restore', $user, $meterReading);
        }
        
        return $canRestore;
    }

    /**
     * Determine whether the user can permanently delete the meter reading.
     * 
     * Only superadmins can force delete meter readings.
     * 
     * Requirements: 11.1
     * 
     * @param User $user The authenticated user
     * @param MeterReading $meterReading The meter reading to force delete
     * @return bool True if authorized
     */
    public function forceDelete(User $user, MeterReading $meterReading): bool
    {
        $canForceDelete = $this->isSuperadmin($user);
        
        if ($canForceDelete) {
            $this->logSensitiveOperation('forceDelete', $user, $meterReading, [
                'validation_status' => $meterReading->validation_status->value,
                'input_method' => $meterReading->input_method->value,
                'entered_by' => $meterReading->entered_by,
            ]);
        }
        
        return $canForceDelete;
    }

    /**
     * Determine whether the user can replicate the meter reading.
     * 
     * Used by Filament for record duplication.
     * Only managers and above can replicate readings.
     * 
     * @param User $user The authenticated user
     * @param MeterReading $meterReading The meter reading to replicate
     * @return bool True if authorized
     */
    public function replicate(User $user, MeterReading $meterReading): bool
    {
        return $this->isManagerOrHigher($user) && 
               ($this->isSuperadmin($user) || $this->belongsToUserTenant($user, $meterReading));
    }

    /**
     * Determine whether the user can export meter readings.
     * 
     * All roles can export readings within their scope.
     * 
     * @param User $user The authenticated user
     * @return bool True if authorized
     */
    public function export(User $user): bool
    {
        return in_array($user->role, self::ALL_ROLES, true);
    }

    /**
     * Determine whether the user can import meter readings.
     * 
     * Only managers and above can import readings.
     * 
     * @param User $user The authenticated user
     * @return bool True if authorized
     */
    public function import(User $user): bool
    {
        return $this->isManagerOrHigher($user);
    }
}
