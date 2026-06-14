<?php

namespace App\Policies;

use App\Enums\ManagerMembershipStatus;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\OrganizationUser;
use App\Models\User;

class OrganizationUserPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->viewTeamMembers($user);
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
        return $this->createManager($user);
    }

    public function update(User $user, OrganizationUser $organizationUser): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return $this->updateManagerPermissions($user, $organizationUser);
    }

    public function delete(User $user, OrganizationUser $organizationUser): bool
    {
        return $user->isSuperadmin();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function viewTeamMembers(User $user): bool
    {
        return $user->isSuperadmin() || $this->canManageManagerMemberships($user);
    }

    public function createManager(User $user, ?Organization $organization = null): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        $currentOrganization = $user->currentOrganization();

        if (! $currentOrganization instanceof Organization) {
            return false;
        }

        if ($organization instanceof Organization && $organization->isNot($currentOrganization)) {
            return false;
        }

        return $this->canManageManagerMemberships($user);
    }

    public function updateManagerPermissions(User $user, OrganizationUser $organizationUser): bool
    {
        if ($user->isSuperadmin()) {
            return $this->isManagerMembership($organizationUser);
        }

        return $this->canAccessManagerMembership($user, $organizationUser)
            && $organizationUser->user_id !== $user->id;
    }

    public function disableManager(User $user, OrganizationUser $organizationUser): bool
    {
        return $this->updateManagerPermissions($user, $organizationUser)
            && $organizationUser->status !== ManagerMembershipStatus::DISABLED;
    }

    public function reactivateManager(User $user, OrganizationUser $organizationUser): bool
    {
        return $this->updateManagerPermissions($user, $organizationUser)
            && $organizationUser->status === ManagerMembershipStatus::DISABLED;
    }

    public function revokeInvitation(User $user, OrganizationUser $organizationUser): bool
    {
        return $this->updateManagerPermissions($user, $organizationUser)
            && in_array($organizationUser->status, [
                ManagerMembershipStatus::INVITED,
                ManagerMembershipStatus::EXPIRED,
            ], true);
    }

    public function resendInvitation(User $user, OrganizationUser $organizationUser): bool
    {
        return $this->updateManagerPermissions($user, $organizationUser)
            && in_array($organizationUser->status, [
                ManagerMembershipStatus::INVITED,
                ManagerMembershipStatus::EXPIRED,
            ], true);
    }

    public function copyInvitationLink(User $user, OrganizationUser $organizationUser): bool
    {
        return $this->resendInvitation($user, $organizationUser);
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
            && $this->isManagerMembership($organizationUser);
    }

    private function isManagerMembership(OrganizationUser $organizationUser): bool
    {
        return $organizationUser->role === UserRole::MANAGER->value;
    }
}
