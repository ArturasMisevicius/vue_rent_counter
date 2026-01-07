<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\TenantAuthorizationServiceInterface;
use App\Models\User;
use App\ValueObjects\TenantId;

final readonly class TenantAuthorizationService implements TenantAuthorizationServiceInterface
{
    public function canSwitchTo(TenantId $tenantId, User $user): bool
    {
        // Superadmins can switch to any tenant
        if ($user->isSuperadmin()) {
            return true;
        }

        // Regular users can only access their own tenant
        return $user->tenant_id === $tenantId->getValue();
    }

    public function canAccessTenant(TenantId $tenantId, User $user): bool
    {
        return $this->canSwitchTo($tenantId, $user);
    }

    public function getDefaultTenant(User $user): ?TenantId
    {
        if ($user->tenant_id) {
            return TenantId::from($user->tenant_id);
        }

        return null;
    }
}