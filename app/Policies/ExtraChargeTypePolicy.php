<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ExtraChargeType;
use App\Models\User;
use App\Policies\Concerns\AuthorizesManagerPermissionWrites;

class ExtraChargeTypePolicy
{
    use AuthorizesManagerPermissionWrites;

    public function viewAny(User $user): bool
    {
        return $user->isAdminLike();
    }

    public function view(User $user, ExtraChargeType $chargeType): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return $user->isAdminLike()
            && $user->organization_id === $chargeType->organization_id;
    }

    public function create(User $user): bool
    {
        return $this->canWriteManagedResource($user, 'extra_charges', 'create');
    }

    public function update(User $user, ExtraChargeType $chargeType): bool
    {
        return $this->canWriteManagedResource($user, 'extra_charges', 'edit', $chargeType->organization_id);
    }

    public function delete(User $user, ExtraChargeType $chargeType): bool
    {
        return $this->canWriteManagedResource($user, 'extra_charges', 'delete', $chargeType->organization_id)
            && ! $chargeType->extraCharges()->exists();
    }

    public function restore(User $user, ExtraChargeType $chargeType): bool
    {
        return false;
    }

    public function forceDelete(User $user, ExtraChargeType $chargeType): bool
    {
        return false;
    }
}
