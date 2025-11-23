<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any users.
     * Respects role hierarchy: superadmin sees all, admin sees their tenants.
     * 
     * Requirements: 13.1
     */
    public function viewAny(User $user): bool
    {
        // Superadmin can view all users (Requirement 13.1)
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Only admins can view users list (their created tenants)
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can view the user.
     * Checks parent-child relationships in hierarchy.
     * 
     * Requirements: 13.1, 13.3
     */
    public function view(User $user, User $model): bool
    {
        // Superadmin can view any user (Requirement 13.1)
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins can view any user
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        // Other users can view only themselves
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can create users.
     * Allows superadmin→admin, admin→tenant creation.
     * 
     * Requirements: 13.1, 13.2
     */
    public function create(User $user): bool
    {
        // Superadmin can create admin accounts (Requirement 13.1)
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Only admins can create users (Requirement 13.2)
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can update the user.
     * Includes ownership checks based on hierarchy.
     * 
     * Requirements: 13.1, 13.3, 13.4
     */
    public function update(User $user, User $model): bool
    {
        // Superadmin can update any user (Requirement 13.1)
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins can update any user
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        // Tenants and managers can update their own profile only (Requirement 13.4)
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the user.
     * Includes ownership checks and prevents self-deletion.
     * 
     * Requirements: 13.1, 13.3, 13.4
     */
    public function delete(User $user, User $model): bool
    {
        // Cannot delete yourself
        if ($user->id === $model->id) {
            return false;
        }

        // Superadmin can delete any user (except themselves) (Requirement 13.1)
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Only admins can delete users they did not create (Requirement 13.3)
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can restore the user.
     * 
     * Requirements: 13.1, 13.3
     */
    public function restore(User $user, User $model): bool
    {
        // Superadmin can restore any user (Requirement 13.1)
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins and Managers can restore their child users
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return $model->parent_user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the user.
     * 
     * Requirements: 13.1
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Only superadmin can force delete users (Requirement 13.1)
        return $user->role === UserRole::SUPERADMIN;
    }
}
