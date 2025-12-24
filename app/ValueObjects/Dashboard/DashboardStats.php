<?php

declare(strict_types=1);

namespace App\ValueObjects\Dashboard;

/**
 * Value object for dashboard quick statistics.
 */
final readonly class DashboardStats
{
    public function __construct(
        public int $totalProperties,
        public int $activeServices,
        public int $currentMonthReadings,
        public int $pendingReadings,
    ) {}

    public function toArray(): array
    {
        return [
            'total_properties' => $this->totalProperties,
            'active_services' => $this->activeServices,
            'current_month_readings' => $this->currentMonthReadings,
            'pending_readings' => $this->pendingReadings,
        ];
    }
}