<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\User;

class BuildingPolicy
{
    /**
     * Determine whether the user can view any buildings.
     */
    public function viewAny(User $user): bool
    {
        // Admins and managers can view buildings (filtered by tenant scope)
        return $user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER;
    }

    /**
     * Determine whether the user can view the building.
     */
    public function view(User $user, Building $building): bool
    {
        // Admins and managers can view buildings within their tenant
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            // Tenant scope will automatically filter, but we verify explicitly
            return $building->tenant_id === $user->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create buildings.
     */
    public function create(User $user): bool
    {
        // Admins and managers can create buildings
        return $user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER;
    }

    /**
     * Determine whether the user can update the building.
     */
    public function update(User $user, Building $building): bool
    {
        // Admins and managers can update buildings within their tenant
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return $building->tenant_id === $user->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the building.
     */
    public function delete(User $user, Building $building): bool
    {
        // Only admins can delete buildings
        if ($user->role === UserRole::ADMIN) {
            return $building->tenant_id === $user->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the building.
     */
    public function restore(User $user, Building $building): bool
    {
        // Only admins can restore buildings
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can permanently delete the building.
     */
    public function forceDelete(User $user, Building $building): bool
    {
        // Only admins can force delete buildings
        return $user->role === UserRole::ADMIN;
    }
}
