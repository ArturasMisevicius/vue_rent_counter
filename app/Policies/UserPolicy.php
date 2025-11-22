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

        // Admins and Managers can view users within their tenant (their created tenants)
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return true;
        }

        return false;
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

        // Admins and Managers can view users within their tenant
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            // Can view themselves
            if ($user->id === $model->id) {
                return true;
            }

            // Can view their child users (tenants they created)
            if ($model->parent_user_id === $user->id) {
                return true;
            }

            // Can view other users in their tenant (for admin viewing other admins' data)
            // But not modify them (Requirement 13.3)
            if ($model->tenant_id === $user->tenant_id) {
                return true;
            }

            return false;
        }

        // Users can view their own profile
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

        // Admins and Managers can create tenant accounts (Requirement 13.2)
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            return true;
        }

        // Tenants cannot create users (Requirement 13.4)
        return false;
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

        // Admins and Managers can update users within their hierarchy
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            // Can update themselves
            if ($user->id === $model->id) {
                return true;
            }

            // Can update their child users (tenants they created)
            if ($model->parent_user_id === $user->id) {
                return true;
            }

            // Cannot update other admins' data (Requirement 13.3)
            return false;
        }

        // Tenants can update their own profile only (Requirement 13.4)
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

        // Admins and Managers can delete their child users (tenants they created)
        if ($user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER) {
            // Can only delete users they created (Requirement 13.3)
            return $model->parent_user_id === $user->id;
        }

        // Tenants cannot delete users (Requirement 13.4)
        return false;
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
