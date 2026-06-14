<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ListingLead;
use App\Models\User;
use App\Policies\Concerns\AuthorizesManagerPermissionWrites;

class ListingLeadPolicy
{
    use AuthorizesManagerPermissionWrites;

    public function viewAny(User $user): bool
    {
        return $user->isAdminLike();
    }

    public function view(User $user, ListingLead $lead): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        if ($user->isAdmin()) {
            return $user->organization_id === $lead->organization_id;
        }

        return $user->isManager()
            && $user->organization_id === $lead->organization_id
            && $lead->assigned_to_user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->canWriteManagedResource($user, 'leads', 'create');
    }

    public function update(User $user, ListingLead $lead): bool
    {
        return $this->canWriteManagedResource($user, 'leads', 'edit', $lead->organization_id)
            && (! $user->isManager() || $lead->assigned_to_user_id === $user->id);
    }

    public function assign(User $user, ListingLead $lead): bool
    {
        return $this->canWriteManagedResource($user, 'leads', 'edit', $lead->organization_id);
    }

    public function recordOutreach(User $user, ListingLead $lead): bool
    {
        return $this->update($user, $lead);
    }

    public function convert(User $user, ListingLead $lead): bool
    {
        return $lead->canConvert() && $this->update($user, $lead);
    }

    public function export(User $user): bool
    {
        return $this->canWriteManagedResource($user, 'leads', 'edit');
    }

    public function delete(User $user, ListingLead $lead): bool
    {
        return $this->canWriteManagedResource($user, 'leads', 'delete', $lead->organization_id);
    }
}
