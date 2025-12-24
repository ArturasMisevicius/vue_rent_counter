<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Widgets;

use App\Models\Property;
use App\Services\UniversalBillingCalculator;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

final class ConsumptionOverviewWidget extends ChartWidget
{
    protected static ?string $heading = 'Consumption Overview';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '300px';

    public ?string $filter = 'last_6_months';

    protected function getFilters(): ?array
    {
        return [
            'last_3_months' => __('dashboard.filters.last_3_months'),
            'last_6_months' => __('dashboard.filters.last_6_months'),
            'last_12_months' => __('dashboard.filters.last_12_months'),
            'current_year' => __('dashboard.filters.current_year'),
        ];
    }

    protected function getData(): array
    {
        $user = Auth::user();
        
        if (!$user || !$user->currentTeam) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $cacheKey = "consumption_overview_{$user->currentTeam->id}_{$this->filter}";
        
        return Cache::remember($cacheKey, 300, function () use ($user) {
            $period = $this->getPeriod();
            $properties = $user->currentTeam->properties()
                ->with([
                    'meters.serviceConfiguration.utilityService',
                    'meters.readings' => function ($query) use ($period) {
                        $query->whereBetween('created_at', $period)
                            ->orderBy('created_at');
                    }
                ])
                ->get();

            $utilityServices = $properties->flatMap(fn($p) => $p->meters)
                ->pluck('serviceConfiguration.utilityService')
                ->unique('id')
                ->keyBy('id');

            $months = $this->getMonthLabels($period);
            $datasets = [];

            foreach ($utilityServices as $service) {
                $monthlyData = [];
                
                foreach ($months as $month) {
                    $monthStart = Carbon::parse($month['start']);
                    $monthEnd = Carbon::parse($month['end']);
                    
                    $consumption = $properties->flatMap(fn($p) => $p->meters)
                        ->filter(fn($m) => $m->serviceConfiguration->utilityService->id === $service->id)
                        ->flatMap(fn($m) => $m->readings)
                        ->filter(fn($r) => $r->created_at->between($monthStart, $monthEnd))
                        ->sum('value');
                    
                    $monthlyData[] = round($consumption, 2);
                }

                $datasets[] = [
                    'label' => $service->name,
                    'data' => $monthlyData,
                    'backgroundColor' => $this->getServiceColor($service->name, 0.2),
                    'borderColor' => $this->getServiceColor($service->name, 1),
                    'borderWidth' => 2,
                    'fill' => false,
                ];
            }

            return [
                'datasets' => $datasets,
                'labels' => array_column($months, 'label'),
            ];
        });
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => __('dashboard.consumption_units'),
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => __('dashboard.months'),
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
        ];
    }

    private function getPeriod(): array
    {
        return match ($this->filter) {
            'last_3_months' => [
                Carbon::now()->subMonths(3)->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ],
            'last_6_months' => [
                Carbon::now()->subMonths(6)->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ],
            'last_12_months' => [
                Carbon::now()->subMonths(12)->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ],
            'current_year' => [
                Carbon::now()->startOfYear(),
                Carbon::now()->endOfYear(),
            ],
            default => [
                Carbon::now()->subMonths(6)->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ],
        };
    }

    private function getMonthLabels(array $period): array
    {
        $months = [];
        $current = Carbon::parse($period[0])->startOfMonth();
        $end = Carbon::parse($period[1])->endOfMonth();

        while ($current->lte($end)) {
            $months[] = [
                'label' => $current->format('M Y'),
                'start' => $current->copy()->startOfMonth(),
                'end' => $current->copy()->endOfMonth(),
            ];
            $current->addMonth();
        }

        return $months;
    }

    private function getServiceColor(string $serviceName, float $alpha): string
    {
        $colors = [
            'Electricity' => "rgba(59, 130, 246, {$alpha})", // Blue
            'Water' => "rgba(14, 165, 233, {$alpha})", // Sky blue
            'Heating' => "rgba(239, 68, 68, {$alpha})", // Red
            'Gas' => "rgba(245, 158, 11, {$alpha})", // Amber
            'Internet' => "rgba(139, 92, 246, {$alpha})", // Violet
            'Waste' => "rgba(34, 197, 94, {$alpha})", // Green
        ];

        return $colors[$serviceName] ?? "rgba(107, 114, 128, {$alpha})"; // Gray fallback
    }
}