<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\MeterReading;
use App\Models\User;

class MeterReadingPolicy
{
    /**
     * Determine whether the user can view any meter readings.
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
     */
    public function view(User $user, MeterReading $meterReading): bool
    {
        // Superadmin can view any meter reading
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins and managers can view meter readings within their tenant
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return $meterReading->tenant_id === $user->tenant_id;
        }

        // Tenants can view meter readings for their properties
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
     */
    public function create(User $user): bool
    {
        // Admins, managers, and superadmins can create meter readings
        return in_array($user->role, [
            UserRole::SUPERADMIN,
            UserRole::ADMIN,
            UserRole::MANAGER,
        ], true);
    }

    /**
     * Determine whether the user can update the meter reading.
     */
    public function update(User $user, MeterReading $meterReading): bool
    {
        // Superadmin can update any meter reading
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins and managers can update meter readings within their tenant
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return $meterReading->tenant_id === $user->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the meter reading.
     */
    public function delete(User $user, MeterReading $meterReading): bool
    {
        // Superadmin can delete any meter reading
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Only admins can delete meter readings within their tenant
        if ($user->role === UserRole::ADMIN) {
            return $meterReading->tenant_id === $user->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the meter reading.
     */
    public function restore(User $user, MeterReading $meterReading): bool
    {
        // Superadmin can restore any meter reading
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Only admins can restore meter readings within their tenant
        if ($user->role === UserRole::ADMIN) {
            return $meterReading->tenant_id === $user->tenant_id;
        }

        return false;
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
