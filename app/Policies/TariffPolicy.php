<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Tariff;
use App\Models\User;

class TariffPolicy
{
    /**
     * Determine whether the user can view any tariffs.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view tariffs
        return true;
    }

    /**
     * Determine whether the user can view the tariff.
     */
    public function view(User $user, Tariff $tariff): bool
    {
        // All authenticated users can view individual tariffs
        return true;
    }

    /**
     * Determine whether the user can create tariffs.
     */
    public function create(User $user): bool
    {
        // Only admins can create tariffs
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can update the tariff.
     */
    public function update(User $user, Tariff $tariff): bool
    {
        // Only admins can update tariffs
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can delete the tariff.
     */
    public function delete(User $user, Tariff $tariff): bool
    {
        // Only admins can delete tariffs
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can restore the tariff.
     */
    public function restore(User $user, Tariff $tariff): bool
    {
        // Only admins can restore tariffs
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can permanently delete the tariff.
     */
    public function forceDelete(User $user, Tariff $tariff): bool
    {
        // Only admins can force delete tariffs
        return $user->role === UserRole::ADMIN;
    }
}
