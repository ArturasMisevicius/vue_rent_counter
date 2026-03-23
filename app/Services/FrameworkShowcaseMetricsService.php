<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FrameworkShowcase;
use App\Models\Organization;
use App\Models\User;

final class FrameworkShowcaseMetricsService
{
    /**
     * @return array{organizations: int, users: int, showcases: int}
     */
    public function counts(): array
    {
        return [
            'organizations' => Organization::query()->count(),
            'users' => User::query()->count(),
            'showcases' => FrameworkShowcase::query()->count(),
        ];
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function statCards(): array
    {
        $counts = $this->counts();

        return [
            [
                'label' => 'Organizations',
                'value' => (string) $counts['organizations'],
            ],
            [
                'label' => 'Users',
                'value' => (string) $counts['users'],
            ],
            [
                'label' => 'Showcases',
                'value' => (string) $counts['showcases'],
            ],
        ];
    }
}
