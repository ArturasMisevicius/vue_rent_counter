<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BillingGenerationLog;
use App\Models\User;

class BillingGenerationLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdminLike();
    }

    public function view(User $user, BillingGenerationLog $billingGenerationLog): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return ($user->isAdmin() || $user->isManager())
            && $user->organization_id === $billingGenerationLog->organization_id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, BillingGenerationLog $billingGenerationLog): bool
    {
        return false;
    }

    public function delete(User $user, BillingGenerationLog $billingGenerationLog): bool
    {
        return false;
    }
}
