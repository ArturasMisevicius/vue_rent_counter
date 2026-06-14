<?php

namespace App\Policies;

use App\Models\RentalContract;
use App\Models\User;
use App\Policies\Concerns\AuthorizesManagerPermissionWrites;

class RentalContractPolicy
{
    use AuthorizesManagerPermissionWrites;

    public function viewAny(User $user): bool
    {
        return $user->isAdminLike() || $user->isTenant();
    }

    public function view(User $user, RentalContract $rentalContract): bool
    {
        if ($user->isAdminLike()) {
            return $user->organization_id === $rentalContract->organization_id;
        }

        return $user->isTenant()
            && $rentalContract->tenant_visible
            && $rentalContract->tenant_id === $user->id
            && $rentalContract->organization_id === $user->organization_id;
    }

    public function download(User $user, RentalContract $rentalContract): bool
    {
        return $this->view($user, $rentalContract);
    }

    public function create(User $user): bool
    {
        return $this->canWriteManagedResource($user, 'rental_contracts', 'create');
    }

    public function update(User $user, RentalContract $rentalContract): bool
    {
        return $this->canWriteManagedResource($user, 'rental_contracts', 'edit', $rentalContract->organization_id);
    }

    public function upload(User $user, RentalContract $rentalContract): bool
    {
        return $this->update($user, $rentalContract);
    }

    public function terminate(User $user, RentalContract $rentalContract): bool
    {
        return $this->update($user, $rentalContract);
    }

    public function renew(User $user, RentalContract $rentalContract): bool
    {
        return $this->canWriteManagedResource($user, 'rental_contracts', 'create', $rentalContract->organization_id)
            && $this->update($user, $rentalContract);
    }

    public function delete(User $user, RentalContract $rentalContract): bool
    {
        return $this->canWriteManagedResource($user, 'rental_contracts', 'delete', $rentalContract->organization_id);
    }
}
