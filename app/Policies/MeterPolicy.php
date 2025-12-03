<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Meter;
use App\Models\User;

class MeterPolicy
{
    /**
     * Determine whether the user can view any meters.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view meters (filtered by appropriate scope)
        return true;
    }

    /**
     * Determine whether the user can view the meter.
     * Verifies meter belongs to user's tenant_id.
     */
    public function view(User $user, Meter $meter): bool
    {
        // Superadmin can view any meter
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins can view meters across tenants
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        if ($user->role === UserRole::MANAGER) {
            // Verify meter belongs to manager's tenant_id
            return $meter->tenant_id === $user->tenant_id;
        }

        // Tenants can view meters on their assigned property
        if ($user->role === UserRole::TENANT) {
            if ($user->property_id === $meter->property_id) {
                return true;
            }

            return $user->tenant && $user->tenant->property_id === $meter->property_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create meters.
     */
    public function create(User $user): bool
    {
        // Superadmin can create meters
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins and managers can create meters
        return $user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER;
    }

    /**
     * Determine whether the user can update the meter.
     */
    public function update(User $user, Meter $meter): bool
    {
        // Superadmin can update any meter
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins can update meters across tenants
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        if ($user->role === UserRole::MANAGER) {
            return $meter->tenant_id === $user->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the meter.
     */
    public function delete(User $user, Meter $meter): bool
    {
        // Superadmin can delete any meter
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins can delete meters across tenants
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        if ($user->role === UserRole::MANAGER) {
            return $meter->tenant_id === $user->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the meter.
     */
    public function restore(User $user, Meter $meter): bool
    {
        // Superadmin can restore any meter
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins can restore meters across tenants
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        if ($user->role === UserRole::MANAGER) {
            return $meter->tenant_id === $user->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the meter.
     */
    public function forceDelete(User $user, Meter $meter): bool
    {
        // Only superadmin can force delete meters
        return $user->role === UserRole::SUPERADMIN;
    }
}
