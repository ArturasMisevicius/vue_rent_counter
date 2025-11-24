<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Provider;
use App\Models\User;

class ProviderPolicy
{
    /**
     * Determine whether the user can view any providers.
     */
    public function viewAny(User $user): bool
    {
        // Providers are readable by all authenticated roles
        return in_array($user->role, [
            UserRole::SUPERADMIN,
            UserRole::ADMIN,
            UserRole::MANAGER,
            UserRole::TENANT,
        ], true);
    }

    /**
     * Determine whether the user can view the provider.
     */
    public function view(User $user, Provider $provider): bool
    {
        // Providers are readable by all authenticated roles
        return in_array($user->role, [
            UserRole::SUPERADMIN,
            UserRole::ADMIN,
            UserRole::MANAGER,
            UserRole::TENANT,
        ], true);
    }

    /**
     * Determine whether the user can create providers.
     */
    public function create(User $user): bool
    {
        // Only admins can create providers
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can update the provider.
     */
    public function update(User $user, Provider $provider): bool
    {
        // Only admins can update providers
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can delete the provider.
     */
    public function delete(User $user, Provider $provider): bool
    {
        // Only admins can delete providers
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can restore the provider.
     */
    public function restore(User $user, Provider $provider): bool
    {
        // Only admins can restore providers
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can permanently delete the provider.
     */
    public function forceDelete(User $user, Provider $provider): bool
    {
        // Only admins can force delete providers
        return $user->role === UserRole::ADMIN;
    }
}
