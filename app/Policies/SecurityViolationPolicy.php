<?php

namespace App\Policies;

use App\Models\SecurityViolation;
use App\Models\User;

class SecurityViolationPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isSuperadmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, SecurityViolation $securityViolation): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, SecurityViolation $securityViolation): bool
    {
        return false;
    }
}
