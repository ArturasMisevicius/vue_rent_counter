<?php

declare(strict_types=1);

namespace App\Repositories;

use App\ValueObjects\TenantId;

interface TenantRepositoryInterface
{
    public function exists(TenantId $tenantId): bool;
    
    public function getName(TenantId $tenantId): string;
}