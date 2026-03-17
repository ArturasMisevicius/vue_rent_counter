<?php

namespace App\Policies;

use App\Models\IntegrationHealthCheck;
use App\Models\User;

class IntegrationHealthCheckPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperadmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, IntegrationHealthCheck $integrationHealthCheck): bool
    {
        return false;
    }

    public function update(User $user, IntegrationHealthCheck $integrationHealthCheck): bool
    {
        return false;
    }
}
