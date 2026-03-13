<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Tariff;
use App\Models\User;

/**
 * TariffPolicy
 * 
 * Authorization policy for tariff configuration operations.
 * 
 * Requirements:
 * - 11.1: Verify user's role using Laravel Policies
 * - 11.2: Admin has full CRUD operations on tariffs
 * - 11.3: Manager cannot modify tariffs (read-only access)
 * - 11.4: Tenant has view-only access to tariffs
 * 
 * @package App\Policies
 */
class TariffPolicy
{
    /**
     * Check if user has admin-level permissions.
     * 
     * @param User $user The authenticated user
     * @return bool True if user is admin or superadmin
     */
    private function isAdmin(User $user): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::SUPERADMIN], true);
    }

    /**
     * Determine whether the user can view any tariffs.
     * 
     * Only SUPERADMIN and ADMIN roles can access tariff configuration.
     * Tariffs are system configuration resources, not operational resources.
     * 
     * Requirements: 11.1, 11.2, 9.2
     * 
     * @param User $user The authenticated user
     * @return bool True if authorized
     */
    public function viewAny(User $user): bool
    {
        // Only admins and superadmins can view tariffs (system configuration)
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can view the tariff.
     * 
     * Only SUPERADMIN and ADMIN roles can view individual tariffs.
     * Tariffs are system configuration resources, not operational resources.
     * 
     * Requirements: 11.1, 11.2, 9.2
     * 
     * @param User $user The authenticated user
     * @param Tariff $tariff The tariff to view
     * @return bool True if authorized
     */
    public function view(User $user, Tariff $tariff): bool
    {
        // Only admins and superadmins can view tariffs (system configuration)
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can create tariffs.
     * 
     * Only Admins and Superadmins can create tariffs.
     * Managers and Tenants have read-only access.
     * 
     * Requirements: 11.1, 11.2, 11.3
     * 
     * @param User $user The authenticated user
     * @return bool True if authorized
     */
    public function create(User $user): bool
    {
        // Only admins and superadmins can create tariffs (Requirement 11.2)
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can update the tariff.
     * 
     * Only Admins and Superadmins can update tariffs.
     * Managers cannot modify tariffs per Requirement 11.3.
     * 
     * Requirements: 11.1, 11.2, 11.3
     * 
     * @param User $user The authenticated user
     * @param Tariff $tariff The tariff to update
     * @return bool True if authorized
     */
    public function update(User $user, Tariff $tariff): bool
    {
        // Only admins and superadmins can update tariffs (Requirement 11.2, 11.3)
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can delete the tariff.
     * 
     * Only Admins and Superadmins can delete tariffs.
     * 
     * Requirements: 11.1, 11.2
     * 
     * @param User $user The authenticated user
     * @param Tariff $tariff The tariff to delete
     * @return bool True if authorized
     */
    public function delete(User $user, Tariff $tariff): bool
    {
        // Only admins and superadmins can delete tariffs (Requirement 11.2)
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can restore the tariff.
     * 
     * Only Admins and Superadmins can restore soft-deleted tariffs.
     * 
     * Requirements: 11.1, 11.2
     * 
     * @param User $user The authenticated user
     * @param Tariff $tariff The tariff to restore
     * @return bool True if authorized
     */
    public function restore(User $user, Tariff $tariff): bool
    {
        // Only admins and superadmins can restore tariffs (Requirement 11.2)
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can permanently delete the tariff.
     * 
     * Only Superadmins can force delete tariffs.
     * 
     * Requirements: 11.1
     * 
     * @param User $user The authenticated user
     * @param Tariff $tariff The tariff to force delete
     * @return bool True if authorized
     */
    public function forceDelete(User $user, Tariff $tariff): bool
    {
        // Only superadmins can force delete tariffs
        return $user->role === UserRole::SUPERADMIN;
    }
}
