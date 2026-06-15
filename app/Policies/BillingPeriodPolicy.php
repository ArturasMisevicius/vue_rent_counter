<?php

namespace App\Policies;

use App\Models\BillingPeriod;
use App\Models\User;
use App\Policies\Concerns\AuthorizesManagerPermissionWrites;

class BillingPeriodPolicy
{
    use AuthorizesManagerPermissionWrites;

    public function viewAny(User $user): bool
    {
        return $user->isAdminLike();
    }

    public function view(User $user, BillingPeriod $billingPeriod): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return ($user->isAdmin() || $user->isManager())
            && $user->organization_id === $billingPeriod->organization_id;
    }

    public function create(User $user): bool
    {
        return $this->canWriteManagedResource($user, 'billing', 'create');
    }

    public function update(User $user, BillingPeriod $billingPeriod): bool
    {
        return $this->canWriteManagedResource($user, 'billing', 'edit', $billingPeriod->organization_id);
    }

    public function delete(User $user, BillingPeriod $billingPeriod): bool
    {
        return $this->canWriteManagedResource($user, 'billing', 'delete', $billingPeriod->organization_id)
            && ! $billingPeriod->invoices()->exists()
            && ! $billingPeriod->extraCharges()->exists();
    }
}
