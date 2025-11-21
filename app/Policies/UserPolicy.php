<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any users.
     * Respects role hierarchy: superadmin sees all, admin sees their tenants.
     */
    public function viewAny(User $user): bool
    {
        // Superadmin can view all users
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins can view users within their tenant (their created tenants)
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view the user.
     * Checks parent-child relationships in hierarchy.
     */
    public function view(User $user, User $model): bool
    {
        // Superadmin can view any user
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins can view users within their tenant
        if ($user->role === UserRole::ADMIN) {
            // Can view themselves
            if ($user->id === $model->id) {
                return true;
            }

            // Can view their child users (tenants they created)
            if ($model->parent_user_id === $user->id) {
                return true;
            }

            // Can view other users in their tenant (for admin viewing other admins' data)
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
     */
    public function create(User $user): bool
    {
        // Superadmin can create admin accounts
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins can create tenant accounts
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can update the user.
     * Includes ownership checks based on hierarchy.
     */
    public function update(User $user, User $model): bool
    {
        // Superadmin can update any user
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins can update users within their hierarchy
        if ($user->role === UserRole::ADMIN) {
            // Can update themselves
            if ($user->id === $model->id) {
                return true;
            }

            // Can update their child users (tenants they created)
            if ($model->parent_user_id === $user->id) {
                return true;
            }

            return false;
        }

        // Users can update their own profile
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the user.
     * Includes ownership checks and prevents self-deletion.
     */
    public function delete(User $user, User $model): bool
    {
        // Cannot delete yourself
        if ($user->id === $model->id) {
            return false;
        }

        // Superadmin can delete any user (except themselves)
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins can delete their child users (tenants they created)
        if ($user->role === UserRole::ADMIN) {
            return $model->parent_user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the user.
     */
    public function restore(User $user, User $model): bool
    {
        // Superadmin can restore any user
        if ($user->role === UserRole::SUPERADMIN) {
            return true;
        }

        // Admins can restore their child users
        if ($user->role === UserRole::ADMIN) {
            return $model->parent_user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the user.
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Only superadmin can force delete users
        return $user->role === UserRole::SUPERADMIN;
    }
}
