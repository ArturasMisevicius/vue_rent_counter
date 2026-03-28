<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;

class OrganizationUserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin() || $this->canManageManagerMemberships($user);
    }

    public function view(User $user, OrganizationUser $organizationUser): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return $this->canAccessManagerMembership($user, $organizationUser);
    }

    public function create(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function update(User $user, OrganizationUser $organizationUser): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return $this->canAccessManagerMembership($user, $organizationUser);
    }

    public function delete(User $user, OrganizationUser $organizationUser): bool
    {
        return $user->isSuperadmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isSuperadmin();
    }

    private function canManageManagerMemberships(User $user): bool
    {
        $organization = $user->currentOrganization();

        if (! $organization instanceof Organization) {
            return false;
        }

        return $user->isAdmin() || $organization->owner_user_id === $user->id;
    }

    private function canAccessManagerMembership(User $user, OrganizationUser $organizationUser): bool
    {
        $organization = $user->currentOrganization();

        if (! $organization instanceof Organization) {
            return false;
        }

        return $this->canManageManagerMemberships($user)
            && $organizationUser->organization_id === $organization->id
            && $organizationUser->role === UserRole::MANAGER->value;
    }
}
