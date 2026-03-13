<?php

declare(strict_types=1);

namespace App\Data\System;

final readonly class PerformanceReport
{
    public function __construct(
        public array $data,
    ) {}

    public function getPeriod(): array
    {
        return $this->data['period'] ?? [];
    }

    public function getResponseTimes(): array
    {
        return $this->data['response_times'] ?? [];
    }

    public function getApiCalls(): array
    {
        return $this->data['api_calls'] ?? [];
    }

    public function getErrorRates(): array
    {
        return $this->data['error_rates'] ?? [];
    }

    public function getTenantActivity(): array
    {
        return $this->data['tenant_activity'] ?? [];
    }

    public function toArray(): array
    {
        return $this->data;
    }
}