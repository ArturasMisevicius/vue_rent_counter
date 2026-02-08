<?php

namespace App\Policies;

use App\Models\OrganizationActivityLog;
use App\Models\User;

class OrganizationActivityLogPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, OrganizationActivityLog $log): bool
    {
        return $user->isSuperadmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false; // Logs are created automatically
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, OrganizationActivityLog $log): bool
    {
        return false; // Logs are immutable
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, OrganizationActivityLog $log): bool
    {
        return $user->isSuperadmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, OrganizationActivityLog $log): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, OrganizationActivityLog $log): bool
    {
        return $user->isSuperadmin();
    }
}
