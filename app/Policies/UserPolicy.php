<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        // Only admins can view the user list
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can view the user.
     */
    public function view(User $user, User $model): bool
    {
        // Admins can view any user
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        // Users can view their own profile
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        // Only admins can create users
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can update the user.
     */
    public function update(User $user, User $model): bool
    {
        // Admins can update any user
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        // Users can update their own profile
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the user.
     */
    public function delete(User $user, User $model): bool
    {
        // Only admins can delete users
        // Cannot delete yourself
        return $user->role === UserRole::ADMIN && $user->id !== $model->id;
    }

    /**
     * Determine whether the user can restore the user.
     */
    public function restore(User $user, User $model): bool
    {
        // Only admins can restore users
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can permanently delete the user.
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Only admins can force delete users
        return $user->role === UserRole::ADMIN;
    }
}
