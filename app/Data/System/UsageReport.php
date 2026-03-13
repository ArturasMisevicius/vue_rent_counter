<?php

declare(strict_types=1);

namespace App\Data\System;

final readonly class UsageReport
{
    public function __construct(
        public array $data,
    ) {}

    public function getFilters(): array
    {
        return $this->data['filters'] ?? [];
    }

    public function getGeneratedAt(): \Carbon\Carbon
    {
        return $this->data['generated_at'];
    }

    public function getTenantCount(): int
    {
        return $this->data['tenant_count'] ?? 0;
    }

    public function getActiveTenantCount(): int
    {
        return $this->data['active_tenant_count'] ?? 0;
    }

    public function getTotalUsers(): int
    {
        return $this->data['total_users'] ?? 0;
    }

    public function getTotalStorageMB(): float
    {
        return $this->data['total_storage_mb'] ?? 0;
    }

    public function getTotalApiCallsToday(): int
    {
        return $this->data['total_api_calls_today'] ?? 0;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}