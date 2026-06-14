<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LeadOutreachActivity;
use App\Models\ListingLead;
use App\Models\User;
use App\Policies\Concerns\AuthorizesManagerPermissionWrites;

class LeadOutreachActivityPolicy
{
    use AuthorizesManagerPermissionWrites;

    public function viewAny(User $user): bool
    {
        return $user->isAdminLike();
    }

    public function view(User $user, LeadOutreachActivity $activity): bool
    {
        $lead = $activity->relationLoaded('lead')
            ? $activity->lead
            : ListingLead::query()
                ->select(['id', 'organization_id', 'assigned_to_user_id', 'status', 'converted_property_id'])
                ->find($activity->listing_lead_id);

        return $lead instanceof ListingLead
            && app(ListingLeadPolicy::class)->view($user, $lead);
    }

    public function create(User $user): bool
    {
        return $this->canWriteManagedResource($user, 'leads', 'edit');
    }

    public function update(User $user, LeadOutreachActivity $activity): bool
    {
        return $this->canWriteManagedResource($user, 'leads', 'edit', $activity->organization_id);
    }
}
