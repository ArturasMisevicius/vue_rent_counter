<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    /**
     * Determine whether the user can view any properties.
     * 
     * Requirements: 4.3, 8.2
     */
    public function viewAny(User $user): bool
    {
        // Superadmin can view all properties
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins and managers can view properties (filtered by tenant scope) (Requirement 4.3)
        return $user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER;
    }

    /**
     * Determine whether the user can view the property.
     * Verifies property belongs to admin's tenant_id.
     * Allows tenant to view only their assigned property.
     *
     * Requirements: 4.3, 8.2
     */
    public function view(User $user, Property $property): bool
    {
        // Superadmin can view any property
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins and managers can view properties within their tenant scope
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return $property->tenant_id === $user->tenant_id;
        }

        // Tenants can only view their assigned property (Requirement 8.2)
        if ($user->role === UserRole::TENANT) {
            // Check if this property is assigned to the tenant user or linked tenant record
            if ($user->property_id === $property->id) {
                return true;
            }

            return $user->tenant && $user->tenant->property_id === $property->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create properties.
     * 
     * Requirements: 4.1, 13.2
     */
    public function create(User $user): bool
    {
        // Superadmin can create properties
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins and managers can create properties (Requirement 4.1, 13.2)
        return $user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER;
    }

    /**
     * Determine whether the user can update the property.
     *
     * Requirements: 4.3, 13.3
     */
    public function update(User $user, Property $property): bool
    {
        // Superadmin can update any property
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins and managers can update properties within their tenant scope (Requirement 4.3, 13.3)
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return $property->tenant_id === $user->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the property.
     *
     * Requirements: 4.3, 13.3
     */
    public function delete(User $user, Property $property): bool
    {
        // Superadmin can delete any property
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins and managers can delete properties within their tenant scope (Requirement 4.3, 13.3)
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return $property->tenant_id === $user->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the property.
     *
     * Requirements: 4.3, 13.3
     */
    public function restore(User $user, Property $property): bool
    {
        // Superadmin can restore any property
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins and managers can restore properties within their tenant scope (Requirement 4.3, 13.3)
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return $property->tenant_id === $user->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the property.
     * 
     * Requirements: 13.1
     */
    public function forceDelete(User $user, Property $property): bool
    {
        // Only superadmin can force delete properties (Requirement 13.1)
        return $user->role === UserRole::SUPERADMIN;
    }
}
