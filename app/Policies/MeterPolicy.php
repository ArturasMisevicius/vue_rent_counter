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
        // All authenticated roles can view meters
        return in_array($user->role, [
            UserRole::SUPERADMIN,
            UserRole::ADMIN,
            UserRole::MANAGER,
            UserRole::TENANT,
        ], true);
    }

    /**
     * Determine whether the user can view the meter.
     * Adds tenant_id ownership checks.
     * Ensures tenant can only access their property's meters.
     * 
     * Requirements: 9.1, 13.3
     */
    public function view(User $user, Meter $meter): bool
    {
        // Superadmin can view any meter
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins can view meters across tenants; managers are tenant-scoped
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        if ($user->role === UserRole::MANAGER) {
            return $meter->property->tenant_id === $user->tenant_id;
        }

        // Tenants can only view meters for their assigned property
        if ($user->role === UserRole::TENANT) {
            // Check if the meter belongs to the tenant's assigned property
            if ($user->property_id !== null) {
                return $meter->property_id === $user->property_id;
            }

            // Fallback to tenant model relation when property_id is not set on user
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

        // Admins can update meters across tenants; managers are tenant-scoped
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        if ($user->role === UserRole::MANAGER) {
            return $meter->property->tenant_id === $user->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the meter.
     * 
     * Requirements: 9.1, 13.3
     */
    public function delete(User $user, Meter $meter): bool
    {
        // Superadmin can delete any meter
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins can delete meters across tenants (Requirement 9.1, 13.3)
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the meter.
     * 
     * Requirements: 9.1, 13.3
     */
    public function restore(User $user, Meter $meter): bool
    {
        // Superadmin can restore any meter
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins can restore any meter; managers are tenant-scoped (Requirement 9.1, 13.3)
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        if ($user->role === UserRole::MANAGER) {
            return $meter->property->tenant_id === $user->tenant_id;
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
