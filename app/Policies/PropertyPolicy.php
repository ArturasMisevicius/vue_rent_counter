<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Property;
use App\Models\User;
use App\Services\TenantBoundaryService;

final readonly class PropertyPolicy
{
    public function __construct(
        private TenantBoundaryService $tenantBoundaryService
    ) {}

    /**
     * Determine whether the user can view any properties.
     * 
     * Requirements: 4.3, 8.2
     */
    public function viewAny(User $user): bool
    {
        // Managers and above can view properties
        // TenantScope handles data filtering automatically
        return $this->tenantBoundaryService->canPerformManagerOperations($user);
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
        // Must be able to access the property's tenant
        if (!$this->tenantBoundaryService->canAccessModel($user, $property)) {
            return false;
        }

        // Managers and above can view all properties in their tenant
        if ($this->tenantBoundaryService->canPerformManagerOperations($user)) {
            return true;
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
        // User must have a tenant_id and have appropriate role
        if ($user->tenant_id === null) {
            return false;
        }

        return $this->tenantBoundaryService->canPerformManagerOperations($user);
    }

    /**
     * Determine whether the user can update the property.
     *
     * Requirements: 4.3, 13.3
     */
    public function update(User $user, Property $property): bool
    {
        // Must be able to access the property's tenant
        if (!$this->tenantBoundaryService->canAccessModel($user, $property)) {
            return false;
        }

        // Managers and above can update properties in their tenant
        return $this->tenantBoundaryService->canPerformManagerOperations($user);
    }

    /**
     * Determine whether the user can delete the property.
     *
     * Requirements: 4.3, 13.3
     */
    public function delete(User $user, Property $property): bool
    {
        // Must be able to access the property's tenant
        if (!$this->tenantBoundaryService->canAccessModel($user, $property)) {
            return false;
        }

        // Managers and above can delete properties in their tenant
        return $this->tenantBoundaryService->canPerformManagerOperations($user);
    }

    /**
     * Determine whether the user can restore the property.
     *
     * Requirements: 4.3, 13.3
     */
    public function restore(User $user, Property $property): bool
    {
        // Must be able to access the property's tenant
        if (!$this->tenantBoundaryService->canAccessModel($user, $property)) {
            return false;
        }

        // Managers and above can restore properties in their tenant
        return $this->tenantBoundaryService->canPerformManagerOperations($user);
    }

    /**
     * Determine whether the user can permanently delete the property.
     * 
     * Requirements: 13.1
     */
    public function forceDelete(User $user, Property $property): bool
    {
        // Must be able to access the property's tenant
        if (!$this->tenantBoundaryService->canAccessModel($user, $property)) {
            return false;
        }

        // Only superadmin can force delete properties (Requirement 13.1)
        return $user->hasRole('superadmin');
    }
}
