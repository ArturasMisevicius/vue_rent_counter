<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\User;
use App\Services\TenantContext;

/**
 * GyvatukasCalculator Authorization Policy
 *
 * Enforces authorization for gyvatukas (circulation fee) calculations.
 * Ensures only authorized users can calculate billing for buildings
 * within their tenant scope.
 *
 * ## Authorization Rules
 * - Superadmin: Can calculate for any building
 * - Admin: Can calculate for buildings in their tenant
 * - Manager: Can calculate for buildings in their tenant
 * - Tenant: Cannot calculate (view-only access)
 *
 * ## Security Requirements
 * - Requirement 7.1: Session-based tenant identification
 * - Requirement 7.2: Automatic query filtering by tenant_id
 * - Requirement 7.3: Cross-tenant access prevention
 * - Requirement 11.1: Role-based access control
 *
 * @package App\Policies
 */
final class GyvatukasCalculatorPolicy
{
    /**
     * Determine if the user can calculate gyvatukas for a building.
     *
     * @param  User  $user  The authenticated user
     * @param  Building  $building  The building to calculate for
     * @return bool True if authorized, false otherwise
     */
    public function calculate(User $user, Building $building): bool
    {
        // Superadmin can calculate for any building
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Tenants cannot perform calculations (view-only)
        if ($user->role === UserRole::TENANT) {
            return false;
        }

        // Admin and Manager must be in same tenant as building
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            // Verify building belongs to user's tenant
            if ($building->tenant_id !== $user->tenant_id) {
                return false;
            }

            // Verify building belongs to current tenant context
            $currentTenantId = TenantContext::id();
            if ($currentTenantId && $building->tenant_id !== $currentTenantId) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Determine if the user can distribute circulation costs.
     *
     * Same authorization rules as calculate().
     *
     * @param  User  $user  The authenticated user
     * @param  Building  $building  The building to distribute costs for
     * @return bool True if authorized, false otherwise
     */
    public function distribute(User $user, Building $building): bool
    {
        return $this->calculate($user, $building);
    }

    /**
     * Determine if the user can view calculation audit logs.
     *
     * @param  User  $user  The authenticated user
     * @param  Building  $building  The building to view audits for
     * @return bool True if authorized, false otherwise
     */
    public function viewAudit(User $user, Building $building): bool
    {
        // Superadmin can view all audits
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admin and Manager can view audits for their tenant
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return $building->tenant_id === $user->tenant_id;
        }

        // Tenants can view audits for their property's building
        if ($user->role === UserRole::TENANT && $user->property_id) {
            $property = $user->property;
            if ($property) {
                return $property->building_id === $building->id;
            }
        }

        return false;
    }
}
