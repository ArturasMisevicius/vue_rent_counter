<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Property;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PlatformUsageWidget extends ChartWidget
{
    protected static ?int $sort = 7;

    protected int | string | array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return 'Platform Growth (Last 12 Months)';
    }

    public function getDescription(): ?string
    {
        return 'Properties, users, and invoices over time';
    }

    protected function getData(): array
    {
        // Cache for 5 minutes
        $data = Cache::remember('superadmin.platform_usage', 300, function () {
            $months = collect();
            $labels = [];
            
            for ($i = 11; $i >= 0; $i--) {
                $date = now()->subMonths($i);
                $labels[] = $date->format('M Y');
                
                $months->push([
                    'properties' => Property::where('created_at', '<=', $date->endOfMonth())->count(),
                    'users' => User::where('created_at', '<=', $date->endOfMonth())->count(),
                    'invoices' => Invoice::where('created_at', '<=', $date->endOfMonth())->count(),
                ]);
            }

            return [
                'labels' => $labels,
                'properties' => $months->pluck('properties')->toArray(),
                'users' => $months->pluck('users')->toArray(),
                'invoices' => $months->pluck('invoices')->toArray(),
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Properties',
                    'data' => $data['properties'],
                    'borderColor' => 'rgba(99, 102, 241, 1)',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Users',
                    'data' => $data['users'],
                    'borderColor' => 'rgba(56, 189, 248, 1)',
                    'backgroundColor' => 'rgba(56, 189, 248, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Invoices',
                    'data' => $data['invoices'],
                    'borderColor' => 'rgba(34, 197, 94, 1)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->isSuperadmin() ?? false;
    }
}
