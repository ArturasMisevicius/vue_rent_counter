<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    /**
     * Determine whether the user can view any properties.
     */
    public function viewAny(User $user): bool
    {
        // Superadmin can view all properties
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins and managers can view properties (filtered by tenant scope)
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return true;
        }

        // Tenants can view their assigned property
        if ($user->role === UserRole::TENANT) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the property.
     * Verifies property belongs to admin's tenant_id.
     * Allows tenant to view only their assigned property.
     */
    public function view(User $user, Property $property): bool
    {
        // Superadmin can view any property
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins and managers can view properties within their tenant
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            // Verify property belongs to admin's tenant_id
            return $property->tenant_id === $user->tenant_id;
        }

        // Tenants can only view their assigned property
        if ($user->role === UserRole::TENANT) {
            // Check if this property is assigned to the tenant user
            return $user->property_id === $property->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create properties.
     */
    public function create(User $user): bool
    {
        // Superadmin can create properties
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins and managers can create properties
        return $user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER;
    }

    /**
     * Determine whether the user can update the property.
     */
    public function update(User $user, Property $property): bool
    {
        // Superadmin can update any property
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins and managers can update properties within their tenant
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return $property->tenant_id === $user->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the property.
     */
    public function delete(User $user, Property $property): bool
    {
        // Superadmin can delete any property
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Only admins can delete properties within their tenant
        if ($user->role === UserRole::ADMIN) {
            return $property->tenant_id === $user->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the property.
     */
    public function restore(User $user, Property $property): bool
    {
        // Superadmin can restore any property
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Only admins can restore properties within their tenant
        if ($user->role === UserRole::ADMIN) {
            return $property->tenant_id === $user->tenant_id;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the property.
     */
    public function forceDelete(User $user, Property $property): bool
    {
        // Only superadmin can force delete properties
        return $user->role === UserRole::SUPERADMIN;
    }
}
