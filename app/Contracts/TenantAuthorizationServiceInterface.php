<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\User;
use App\ValueObjects\TenantId;

interface TenantAuthorizationServiceInterface
{
    public function canSwitchTo(TenantId $tenantId, User $user): bool;
    
    public function canAccessTenant(TenantId $tenantId, User $user): bool;
    
    public function getDefaultTenant(User $user): ?TenantId;
}