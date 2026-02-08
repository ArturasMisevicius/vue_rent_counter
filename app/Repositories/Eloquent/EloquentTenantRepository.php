<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Tenant;
use App\Repositories\TenantRepositoryInterface;
use App\ValueObjects\TenantId;

final readonly class EloquentTenantRepository implements TenantRepositoryInterface
{
    public function exists(TenantId $tenantId): bool
    {
        return Tenant::where('id', $tenantId->getValue())->exists();
    }

    public function getName(TenantId $tenantId): string
    {
        $tenant = Tenant::find($tenantId->getValue());
        return $tenant?->name ?? 'Unknown Organization';
    }
}