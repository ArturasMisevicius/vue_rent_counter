<?php

namespace App\Policies;

use App\Models\MeterReading;
use App\Models\User;

class MeterReadingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdminLike();
    }

    public function view(User $user, MeterReading $meterReading): bool
    {
        if ($user->isAdminLike()) {
            return $user->organization_id === $meterReading->organization_id;
        }

        if (! $user->isTenant()) {
            return false;
        }

        $assignedPropertyId = $user->relationLoaded('currentPropertyAssignment')
            ? $user->currentPropertyAssignment?->property_id
            : $user->currentPropertyAssignment()->value('property_id');

        return $assignedPropertyId === $meterReading->property_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function update(User $user, MeterReading $meterReading): bool
    {
        return ($user->isAdmin() || $user->isManager())
            && $user->organization_id === $meterReading->organization_id;
    }

    public function delete(User $user, MeterReading $meterReading): bool
    {
        return ($user->isAdmin() || $user->isManager())
            && $user->organization_id === $meterReading->organization_id;
    }
}
