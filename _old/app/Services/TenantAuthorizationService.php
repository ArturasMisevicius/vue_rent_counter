<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\TenantAuthorizationServiceInterface;
use App\Enums\UserRole;
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

        if ($user->role === UserRole::TENANT) {
            return false;
        }

        // Admins and managers can only switch to their own organization.
        return $user->tenant_id === $tenantId->getValue();
    }

    public function canAccessTenant(TenantId $tenantId, User $user): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return $user->tenant_id === $tenantId->getValue();
    }

    public function getDefaultTenant(User $user): ?TenantId
    {
        if ($user->tenant_id) {
            return TenantId::from($user->tenant_id);
        }

        return null;
    }
}
