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
        // Admins and managers can view properties (filtered by tenant scope)
        return $user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER;
    }

    /**
     * Determine whether the user can view the property.
     */
    public function view(User $user, Property $property): bool
    {
        // Admins and managers can view properties within their tenant
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            // Tenant scope will automatically filter, but we verify explicitly
            return $property->tenant_id === $user->tenant_id;
        }

        // Tenants can view their own property
        if ($user->role === UserRole::TENANT) {
            $tenant = $user->tenant;
            if (!$tenant) {
                return false;
            }

            // Check if the tenant is renting this property
            return $property->tenants()->where('id', $tenant->id)->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create properties.
     */
    public function create(User $user): bool
    {
        // Admins and managers can create properties
        return $user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER;
    }

    /**
     * Determine whether the user can update the property.
     */
    public function update(User $user, Property $property): bool
    {
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
        // Only admins can delete properties
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
        // Only admins can restore properties
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can permanently delete the property.
     */
    public function forceDelete(User $user, Property $property): bool
    {
        // Only admins can force delete properties
        return $user->role === UserRole::ADMIN;
    }
}
