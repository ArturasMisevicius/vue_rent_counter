<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdminLike();
    }

    public function view(User $user, Property $property): bool
    {
        if ($user->isAdminLike()) {
            return $user->organization_id === $property->organization_id;
        }

        if (! $user->isTenant()) {
            return false;
        }

        return $property->currentAssignment?->tenant_user_id === $user->id;
    }
}
