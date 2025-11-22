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
        // Superadmin can view all buildings
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins and managers can view buildings (filtered by tenant scope)
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return true;
        }

        // Tenants can view buildings (filtered to their property's building)
        if ($user->role === UserRole::TENANT) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the building.
     * Adds tenant_id ownership checks.
     * Ensures tenant can only access their property's building.
     * 
     * Requirements: 4.5, 9.1
     */
    public function view(User $user, Building $building): bool
    {
        // Superadmin can view any building
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins and managers can view buildings within their tenant
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            // Verify building belongs to their tenant_id
            return $building->tenant_id === $user->tenant_id;
        }

        // Tenants can only view their property's building
        if ($user->role === UserRole::TENANT && $user->property_id) {
            $property = $user->property;
            if ($property) {
                return $property->building_id === $building->id;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can create buildings.
     */
    public function create(User $user): bool
    {
        // Superadmin can create buildings
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins and managers can create buildings
        return $user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER;
    }

    /**
     * Determine whether the user can update the building.
     */
    public function update(User $user, Building $building): bool
    {
        // Superadmin can update any building
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins and managers can update buildings within their tenant
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return $building->tenant_id === $user->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the building.
     * 
     * Requirements: 4.5, 13.3
     */
    public function delete(User $user, Building $building): bool
    {
        // Superadmin can delete any building
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins and managers can delete buildings within their tenant (Requirement 4.5, 13.3)
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return $building->tenant_id === $user->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the building.
     * 
     * Requirements: 4.5, 13.3
     */
    public function restore(User $user, Building $building): bool
    {
        // Superadmin can restore any building
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins and managers can restore buildings within their tenant (Requirement 4.5, 13.3)
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return $building->tenant_id === $user->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the building.
     */
    public function forceDelete(User $user, Building $building): bool
    {
        // Only superadmin can force delete buildings
        return $user->role === UserRole::SUPERADMIN;
    }
}
