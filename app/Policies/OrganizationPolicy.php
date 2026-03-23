<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function view(User $user, Organization $organization): bool
    {
        return $user->isSuperadmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->isSuperadmin();
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $user->isSuperadmin();
    }

    public function suspend(User $user, Organization $organization): bool
    {
        return $user->isSuperadmin();
    }

    public function reinstate(User $user, Organization $organization): bool
    {
        return $user->isSuperadmin();
    }

    public function impersonate(User $user, Organization $organization): bool
    {
        return $user->isSuperadmin();
    }
}
