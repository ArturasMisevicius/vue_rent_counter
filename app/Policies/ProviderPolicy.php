<?php

namespace App\Policies;

use App\Models\Provider;
use App\Models\User;

class ProviderPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return $user->isAdmin() || $user->isManager();
    }

    public function view(User $user, Provider $provider): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return ($user->isAdmin() || $user->isManager())
            && $user->organization_id === $provider->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperadmin() || $user->isAdmin() || $user->isManager();
    }

    public function update(User $user, Provider $provider): bool
    {
        return $this->view($user, $provider);
    }

    public function delete(User $user, Provider $provider): bool
    {
        return $this->view($user, $provider);
    }
}
