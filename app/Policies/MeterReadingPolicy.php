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
        // All authenticated users can view meter readings (filtered by tenant scope)
        return true;
    }

    /**
     * Determine whether the user can view the meter reading.
     */
    public function view(User $user, MeterReading $meterReading): bool
    {
        // Admins and managers can view all meter readings (within their tenant)
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return true;
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
        // Admins and managers can create meter readings
        return $user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER;
    }

    /**
     * Determine whether the user can update the meter reading.
     */
    public function update(User $user, MeterReading $meterReading): bool
    {
        // Admins and managers can update meter readings
        return $user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER;
    }

    /**
     * Determine whether the user can delete the meter reading.
     */
    public function delete(User $user, MeterReading $meterReading): bool
    {
        // Only admins can delete meter readings
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can restore the meter reading.
     */
    public function restore(User $user, MeterReading $meterReading): bool
    {
        // Only admins can restore meter readings
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can permanently delete the meter reading.
     */
    public function forceDelete(User $user, MeterReading $meterReading): bool
    {
        // Only admins can force delete meter readings
        return $user->role === UserRole::ADMIN;
    }
}
