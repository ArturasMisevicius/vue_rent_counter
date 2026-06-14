<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LeadContact;
use App\Models\User;
use App\Policies\Concerns\AuthorizesManagerPermissionWrites;

class LeadContactPolicy
{
    use AuthorizesManagerPermissionWrites;

    public function viewAny(User $user): bool
    {
        return $user->isAdminLike();
    }

    public function view(User $user, LeadContact $contact): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        if ($user->isAdmin()) {
            return $user->organization_id === $contact->organization_id;
        }

        return $user->isManager()
            && $user->organization_id === $contact->organization_id
            && $contact->listingLeads()
                ->select(['id', 'lead_contact_id', 'assigned_to_user_id'])
                ->assignedTo((int) $user->id)
                ->exists();
    }

    public function update(User $user, LeadContact $contact): bool
    {
        return $this->canWriteManagedResource($user, 'leads', 'edit', $contact->organization_id);
    }
}
