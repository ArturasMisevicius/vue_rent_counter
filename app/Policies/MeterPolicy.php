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
        // All authenticated users can view meters (filtered by tenant scope)
        return true;
    }

    /**
     * Determine whether the user can view the meter.
     * Adds tenant_id ownership checks.
     * Ensures tenant can only access their property's meters.
     */
    public function view(User $user, Meter $meter): bool
    {
        // Superadmin can view any meter
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins and managers can view all meters within their tenant
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return $meter->property->tenant_id === $user->tenant_id;
        }

        // Tenants can only view meters for their assigned property
        if ($user->role === UserRole::TENANT) {
            // Check if the meter belongs to the tenant's assigned property
            return $meter->property_id === $user->property_id;
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

        // Admins and managers can update meters within their tenant
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return $meter->property->tenant_id === $user->tenant_id;
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

        // Only admins can delete meters within their tenant
        if ($user->role === UserRole::ADMIN) {
            return $meter->property->tenant_id === $user->tenant_id;
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

        // Only admins can restore meters within their tenant
        if ($user->role === UserRole::ADMIN) {
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
