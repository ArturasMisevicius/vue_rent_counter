<?php

namespace App\Filament\Support\Admin\Dashboard;

use App\Models\User;

class UpcomingReadingDeadlineData
{
    public function __construct(
        protected AdminDashboardStats $adminDashboardStats,
    ) {}

    /**
     * @return array<int, array{
     *     meter_name: string,
     *     property_name: string,
     *     due_label: string
     * }>
     */
    public function for(User $user, int $limit = 5): array
    {
        return $this->adminDashboardStats->upcomingReadingDeadlinesFor($user, $limit);
    }
}
