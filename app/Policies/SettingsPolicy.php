<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class SettingsPolicy
{
    /**
     * Determine whether the user can view settings.
     */
    public function viewSettings(User $user): bool
    {
        // Only admins can view system settings
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can update settings.
     */
    public function updateSettings(User $user): bool
    {
        // Only admins can update system settings
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can run backups.
     */
    public function runBackup(User $user): bool
    {
        // Only admins can run backups
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can clear cache.
     */
    public function clearCache(User $user): bool
    {
        // Only admins can clear cache
        return $user->role === UserRole::ADMIN;
    }
}
