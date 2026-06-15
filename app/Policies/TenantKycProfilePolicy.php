<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TenantKycProfile;
use App\Models\User;
use App\Policies\Concerns\AuthorizesManagerPermissionWrites;

class TenantKycProfilePolicy
{
    use AuthorizesManagerPermissionWrites;

    public function viewAny(User $user): bool
    {
        return $user->isAdminLike() || $user->isTenant();
    }

    public function view(User $user, TenantKycProfile $tenantKycProfile): bool
    {
        if ($user->isTenant()) {
            return $tenantKycProfile->organization_id === $user->organization_id
                && $tenantKycProfile->tenant_id === $user->id;
        }

        return $this->canWriteManagedResource($user, 'tenant_documents', 'edit', $tenantKycProfile->organization_id);
    }

    public function approve(User $user, TenantKycProfile $tenantKycProfile): bool
    {
        return $this->canWriteManagedResource($user, 'tenant_documents', 'edit', $tenantKycProfile->organization_id);
    }

    public function reject(User $user, TenantKycProfile $tenantKycProfile): bool
    {
        return $this->approve($user, $tenantKycProfile);
    }
}
