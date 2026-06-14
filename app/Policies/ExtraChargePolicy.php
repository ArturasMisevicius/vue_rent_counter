<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ExtraCharge;
use App\Models\User;
use App\Policies\Concerns\AuthorizesManagerPermissionWrites;

class ExtraChargePolicy
{
    use AuthorizesManagerPermissionWrites;

    public function viewAny(User $user): bool
    {
        return $user->isAdminLike() || $user->isTenant();
    }

    public function view(User $user, ExtraCharge $charge): bool
    {
        if ($user->isAdminLike()) {
            return $user->organization_id === $charge->organization_id;
        }

        return $user->isTenant()
            && $charge->tenant_id === $user->id
            && $charge->organization_id === $user->organization_id;
    }

    public function create(User $user): bool
    {
        return $this->canWriteManagedResource($user, 'extra_charges', 'create');
    }

    public function update(User $user, ExtraCharge $charge): bool
    {
        return $this->canWriteManagedResource($user, 'extra_charges', 'edit', $charge->organization_id);
    }

    public function approve(User $user, ExtraCharge $charge): bool
    {
        return $this->canWriteManagedResource($user, 'extra_charges', 'edit', $charge->organization_id);
    }

    public function reject(User $user, ExtraCharge $charge): bool
    {
        return $this->canWriteManagedResource($user, 'extra_charges', 'edit', $charge->organization_id);
    }

    public function delete(User $user, ExtraCharge $charge): bool
    {
        return $this->canWriteManagedResource($user, 'extra_charges', 'delete', $charge->organization_id);
    }

    public function restore(User $user, ExtraCharge $charge): bool
    {
        return false;
    }

    public function forceDelete(User $user, ExtraCharge $charge): bool
    {
        return false;
    }
}
