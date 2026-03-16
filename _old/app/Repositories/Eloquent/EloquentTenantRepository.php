<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Organization;
use App\Repositories\TenantRepositoryInterface;
use App\ValueObjects\TenantId;

final readonly class EloquentTenantRepository implements TenantRepositoryInterface
{
    public function exists(TenantId $tenantId): bool
    {
        return Organization::query()
            ->whereKey($tenantId->getValue())
            ->exists();
    }

    public function getName(TenantId $tenantId): string
    {
        $organization = Organization::query()->find($tenantId->getValue());

        return $organization?->name ?? 'Unknown Organization';
    }
}
