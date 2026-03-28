<?php

namespace App\Policies;

use App\Models\Meter;
use App\Models\User;
use App\Policies\Concerns\AuthorizesManagerPermissionWrites;

class MeterPolicy
{
    use AuthorizesManagerPermissionWrites;

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

    public function create(User $user): bool
    {
        return $this->canWriteManagedResource($user, 'meters', 'create');
    }

    public function update(User $user, Meter $meter): bool
    {
        return $this->canWriteManagedResource($user, 'meters', 'edit', $meter->organization_id);
    }

    public function delete(User $user, Meter $meter): bool
    {
        return $this->canWriteManagedResource($user, 'meters', 'delete', $meter->organization_id);
    }
}
