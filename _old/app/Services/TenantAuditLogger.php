<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\TenantAuditLoggerInterface;
use App\Models\User;
use App\ValueObjects\TenantId;
use Illuminate\Support\Facades\Log;

final readonly class TenantAuditLogger implements TenantAuditLoggerInterface
{
    public function logContextSet(TenantId $tenantId): void
    {
        Log::info('Tenant context set', [
            'tenant_id' => $tenantId->getValue(),
            'user_id' => auth()->id(),
        ]);
    }

    public function logContextSwitch(User $user, TenantId $newTenantId, ?TenantId $previousTenantId, string $organizationName): void
    {
        Log::info('Tenant context switched', [
            'user_id' => $user->id,
            'new_tenant_id' => $newTenantId->getValue(),
            'previous_tenant_id' => $previousTenantId?->getValue(),
            'organization_name' => $organizationName,
        ]);
    }

    public function logContextCleared(?TenantId $previousTenantId): void
    {
        Log::info('Tenant context cleared', [
            'previous_tenant_id' => $previousTenantId?->getValue(),
            'user_id' => auth()->id(),
        ]);
    }

    public function logInvalidContextReset(User $user, TenantId $invalidTenantId, ?TenantId $newTenantId): void
    {
        Log::warning('Invalid tenant context reset', [
            'user_id' => $user->id,
            'invalid_tenant_id' => $invalidTenantId->getValue(),
            'new_tenant_id' => $newTenantId?->getValue(),
        ]);
    }
}