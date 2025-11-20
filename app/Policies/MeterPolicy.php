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
     */
    public function view(User $user, Meter $meter): bool
    {
        // Admins and managers can view all meters within their tenant
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return $meter->property->tenant_id === $user->tenant_id;
        }

        // Tenants can view meters for their properties
        if ($user->role === UserRole::TENANT) {
            $tenant = $user->tenant;
            if (!$tenant) {
                return false;
            }

            // Check if the meter belongs to one of the tenant's properties
            return $meter->property->tenants()->where('id', $tenant->id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create meters.
     */
    public function create(User $user): bool
    {
        // Admins and managers can create meters
        return $user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER;
    }

    /**
     * Determine whether the user can update the meter.
     */
    public function update(User $user, Meter $meter): bool
    {
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
        // Only admins can delete meters
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
        // Only admins can restore meters
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can permanently delete the meter.
     */
    public function forceDelete(User $user, Meter $meter): bool
    {
        // Only admins can force delete meters
        return $user->role === UserRole::ADMIN;
    }
}
