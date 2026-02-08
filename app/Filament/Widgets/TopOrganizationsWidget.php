<?php

namespace App\Filament\Widgets;

use App\Models\Organization;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class TopOrganizationsWidget extends ChartWidget
{
    protected static ?int $sort = 6;

    protected ?string $heading = 'Top 10 Organizations by Property Count';

    protected ?string $description = 'Organizations with the most properties';

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        // Cache for 5 minutes
        $data = Cache::remember('superadmin.top_organizations', 300, function () {
            $topOrgs = Organization::query()
                ->withCount('properties')
                ->orderBy('properties_count', 'desc')
                ->limit(10)
                ->get();

            return [
                'labels' => $topOrgs->pluck('name')->toArray(),
                'data' => $topOrgs->pluck('properties_count')->toArray(),
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Properties',
                    'data' => $data['data'],
                    'backgroundColor' => [
                        'rgba(99, 102, 241, 0.8)',
                        'rgba(56, 189, 248, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(251, 146, 60, 0.8)',
                        'rgba(244, 63, 94, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                        'rgba(236, 72, 153, 0.8)',
                        'rgba(14, 165, 233, 0.8)',
                        'rgba(132, 204, 22, 0.8)',
                        'rgba(234, 179, 8, 0.8)',
                    ],
                    'borderColor' => [
                        'rgba(99, 102, 241, 1)',
                        'rgba(56, 189, 248, 1)',
                        'rgba(34, 197, 94, 1)',
                        'rgba(251, 146, 60, 1)',
                        'rgba(244, 63, 94, 1)',
                        'rgba(168, 85, 247, 1)',
                        'rgba(236, 72, 153, 1)',
                        'rgba(14, 165, 233, 1)',
                        'rgba(132, 204, 22, 1)',
                        'rgba(234, 179, 8, 1)',
                    ],
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }
}
