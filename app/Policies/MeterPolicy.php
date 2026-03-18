<?php

namespace App\Policies;

use App\Models\Meter;
use App\Models\User;

class MeterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdminLike();
    }

    public function view(User $user, Meter $meter): bool
    {
        if ($user->isAdminLike()) {
            return $user->organization_id === $meter->organization_id;
        }

        if (! $user->isTenant()) {
            return false;
        }

        $assignedPropertyId = $user->relationLoaded('currentPropertyAssignment')
            ? $user->currentPropertyAssignment?->property_id
            : $user->currentPropertyAssignment()->value('property_id');

        return $assignedPropertyId === $meter->property_id;
    }
}
