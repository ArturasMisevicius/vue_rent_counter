<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;
use App\ValueObjects\TenantId;

interface TenantAuditLoggerInterface
{
    public function logContextSet(TenantId $tenantId): void;
    
    public function logContextSwitch(User $user, TenantId $newTenantId, ?TenantId $previousTenantId, string $organizationName): void;
    
    public function logContextCleared(?TenantId $previousTenantId): void;
    
    public function logInvalidContextReset(User $user, TenantId $invalidTenantId, ?TenantId $newTenantId): void;
}