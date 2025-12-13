<?php

declare(strict_types=1);

namespace App\Data\System;

final readonly class ReportFilters
{
    public function __construct(
        public ?DateRange $dateRange = null,
        public ?array $tenantIds = null,
        public ?array $plans = null,
        public ?string $status = null,
        public ?string $groupBy = null,
        public ?array $metrics = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $dateRange = null;
        if (!empty($data['start_date']) && !empty($data['end_date'])) {
            $dateRange = DateRange::custom($data['start_date'], $data['end_date']);
        }

        return new self(
            dateRange: $dateRange,
            tenantIds: $data['tenant_ids'] ?? null,
            plans: $data['plans'] ?? null,
            status: $data['status'] ?? null,
            groupBy: $data['group_by'] ?? null,
            metrics: $data['metrics'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'date_range' => $this->dateRange ? [
                'start' => $this->dateRange->startDate->toISOString(),
                'end' => $this->dateRange->endDate->toISOString(),
            ] : null,
            'tenant_ids' => $this->tenantIds,
            'plans' => $this->plans,
            'status' => $this->status,
            'group_by' => $this->groupBy,
            'metrics' => $this->metrics,
        ];
    }
}