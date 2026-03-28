<?php

namespace App\Policies;

use App\Models\Tariff;
use App\Models\User;
use App\Policies\Concerns\AuthorizesManagerPermissionWrites;

class TariffPolicy
{
    use AuthorizesManagerPermissionWrites;

    public function viewAny(User $user): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return ($user->isAdmin() || $user->isManager())
            && $user->organization_id !== null;
    }

    public function create(User $user): bool
    {
        return $this->canWriteManagedResource($user, 'tariffs', 'create');
    }

    public function view(User $user, Tariff $tariff): bool
    {
        return $this->viewAny($user)
            && $this->resolveTariffOrganizationId($tariff) === $user->organization_id;
    }

    public function update(User $user, Tariff $tariff): bool
    {
        return $this->canWriteManagedResource($user, 'tariffs', 'edit', $this->resolveTariffOrganizationId($tariff));
    }

    public function delete(User $user, Tariff $tariff): bool
    {
        return $this->canWriteManagedResource($user, 'tariffs', 'delete', $this->resolveTariffOrganizationId($tariff));
    }

    private function resolveTariffOrganizationId(Tariff $tariff): ?int
    {
        if ($tariff->relationLoaded('provider')) {
            return $tariff->provider?->organization_id;
        }

        $organizationId = $tariff->provider()->value('organization_id');

        return $organizationId;
    }
}
