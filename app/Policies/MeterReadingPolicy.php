<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\MeterReading;
use App\Models\User;

/**
 * MeterReadingPolicy
 * 
 * Authorization policy for meter reading operations.
 * 
 * Requirements:
 * - 11.1: Verify user's role using Laravel Policies
 * - 11.3: Manager can create and update meter readings
 * - 11.4: Tenant can only view their own meter readings
 * - 7.3: Cross-tenant access prevention
 * 
 * @package App\Policies
 */
class MeterReadingPolicy
{
    /**
     * Check if user has admin-level permissions.
     * 
     * @param User $user The authenticated user
     * @return bool True if user is admin or superadmin
     */
    private function isAdmin(User $user): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::SUPERADMIN], true);
    }

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
        return in_array($user->role, [
            UserRole::SUPERADMIN,
            UserRole::ADMIN,
            UserRole::MANAGER,
            UserRole::TENANT,
        ], true);
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
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins and superadmins can view meter readings across all tenants
        if ($this->isAdmin($user)) {
            return true;
        }

        // Managers can view readings within their tenant (Requirement 7.3)
        if ($user->role === UserRole::MANAGER) {
            return $meterReading->tenant_id === $user->tenant_id;
        }

        // Tenants can view meter readings for their properties (Requirement 11.4)
        if ($user->role === UserRole::TENANT) {
            $tenant = $user->tenant;
            if (!$tenant) {
                return false;
            }

            // Check if the meter belongs to one of the tenant's properties
            return $meterReading->meter->property->tenants()
                ->where('tenants.id', $tenant->id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create meter readings.
     * 
     * Admins and Managers can create meter readings.
     * Tenants have read-only access.
     * 
     * Requirements: 11.1, 11.3
     * 
     * @param User $user The authenticated user
     * @return bool True if authorized
     */
    public function create(User $user): bool
    {
        // Admins, managers, and superadmins can create meter readings (Requirement 11.3)
        return in_array($user->role, [
            UserRole::SUPERADMIN,
            UserRole::ADMIN,
            UserRole::MANAGER,
        ], true);
    }

    /**
     * Determine whether the user can update the meter reading.
     * 
     * Admins and Managers can update meter readings.
     * Managers are restricted to their tenant scope.
     * 
     * Requirements: 11.1, 11.3, 7.3
     * 
     * @param User $user The authenticated user
     * @param MeterReading $meterReading The meter reading to update
     * @return bool True if authorized
     */
    public function update(User $user, MeterReading $meterReading): bool
    {
        // Superadmin can update any meter reading
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins and superadmins can update any meter reading; managers are tenant-scoped (Requirement 11.3, 7.3)
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($user->role === UserRole::MANAGER) {
            return $meterReading->tenant_id === $user->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the meter reading.
     */
    public function delete(User $user, MeterReading $meterReading): bool
    {
        // Only admins and superadmins can delete meter readings
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can restore the meter reading.
     */
    public function restore(User $user, MeterReading $meterReading): bool
    {
        // Only admins and superadmins can restore meter readings
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can permanently delete the meter reading.
     */
    public function forceDelete(User $user, MeterReading $meterReading): bool
    {
        // Only superadmin can force delete meter readings
        return $user->role === UserRole::SUPERADMIN;
    }
}
