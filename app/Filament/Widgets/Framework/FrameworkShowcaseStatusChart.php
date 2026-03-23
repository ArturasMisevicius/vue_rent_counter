<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Framework;

use App\Models\FrameworkShowcase;
use Filament\Widgets\ChartWidget;

final class FrameworkShowcaseStatusChart extends ChartWidget
{
    protected ?string $heading = 'Framework showcases by status';

    protected ?string $pollingInterval = '30s';

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $statuses = [
            'draft' => 'Draft',
            'review' => 'In Review',
            'published' => 'Published',
        ];

        return [
            'datasets' => [[
                'label' => 'Showcases',
                'data' => collect(array_keys($statuses))
                    ->map(fn (string $status): int => FrameworkShowcase::query()
                        ->where('status', $status)
                        ->count())
                    ->all(),
                'backgroundColor' => [
                    '#94a3b8',
                    '#f59e0b',
                    '#10b981',
                ],
            ]],
            'labels' => array_values($statuses),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
