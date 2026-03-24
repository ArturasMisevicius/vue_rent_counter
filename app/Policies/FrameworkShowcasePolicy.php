<?php

namespace App\Policies;

use App\Models\FrameworkShowcase;
use App\Models\User;

class FrameworkShowcasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdminLike();
    }

    public function view(User $user, FrameworkShowcase $frameworkShowcase): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return ($user->isAdmin() || $user->isManager())
            && $frameworkShowcase->organization_id === $user->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->isAdminLike();
    }

    public function update(User $user, FrameworkShowcase $frameworkShowcase): bool
    {
        return $this->view($user, $frameworkShowcase);
    }

    public function delete(User $user, FrameworkShowcase $frameworkShowcase): bool
    {
        return $this->view($user, $frameworkShowcase);
    }
}
