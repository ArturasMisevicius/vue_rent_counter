<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Widgets;

use App\Services\UniversalBillingCalculator;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

final class MultiUtilityComparisonWidget extends ChartWidget
{
    protected static ?string $heading = 'Utility Cost Comparison';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 1,
    ];

    protected static ?string $maxHeight = '300px';

    public ?string $filter = 'current_month';

    protected function getFilters(): ?array
    {
        return [
            'current_month' => __('dashboard.filters.current_month'),
            'last_month' => __('dashboard.filters.last_month'),
            'last_3_months' => __('dashboard.filters.last_3_months'),
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

        $cacheKey = "utility_comparison_{$user->currentTeam->id}_{$this->filter}";
        
        return Cache::remember($cacheKey, 300, function () use ($user) {
            $period = $this->getPeriod();
            $properties = $user->currentTeam->properties()
                ->with([
                    'meters.serviceConfiguration.utilityService',
                    'meters.readings' => function ($query) use ($period) {
                        $query->whereBetween('created_at', $period);
                    }
                ])
                ->get();

            $billingCalculator = app(UniversalBillingCalculator::class);
            $serviceCosts = [];
            $serviceColors = [];

            $utilityServices = $properties->flatMap(fn($p) => $p->meters)
                ->pluck('serviceConfiguration.utilityService')
                ->unique('id')
                ->keyBy('id');

            foreach ($utilityServices as $service) {
                $totalCost = 0;

                $serviceMeters = $properties->flatMap(fn($p) => $p->meters)
                    ->filter(fn($m) => $m->serviceConfiguration->utilityService->id === $service->id);

                foreach ($serviceMeters as $meter) {
                    $serviceConfig = $meter->serviceConfiguration;
                    $totalConsumption = $meter->readings->sum('value');

                    if ($totalConsumption > 0 && $serviceConfig) {
                        try {
                            $cost = $billingCalculator->calculateCost(
                                $totalConsumption,
                                $serviceConfig,
                                Carbon::parse($period[0]),
                                Carbon::parse($period[1])
                            );
                            
                            $totalCost += $cost;
                        } catch (\Exception $e) {
                            logger()->warning('Utility comparison cost calculation failed', [
                                'meter_id' => $meter->id,
                                'service' => $service->name,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }

                if ($totalCost > 0) {
                    $serviceCosts[$service->name] = round($totalCost, 2);
                    $serviceColors[] = $this->getServiceColor($service->name);
                }
            }

            return [
                'datasets' => [
                    [
                        'data' => array_values($serviceCosts),
                        'backgroundColor' => $serviceColors,
                        'borderWidth' => 2,
                        'borderColor' => '#ffffff',
                    ],
                ],
                'labels' => array_keys($serviceCosts),
            ];
        });
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            return context.label + ": €" + context.parsed.toFixed(2);
                        }',
                    ],
                ],
            ],
            'cutout' => '60%',
        ];
    }

    private function getPeriod(): array
    {
        return match ($this->filter) {
            'current_month' => [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ],
            'last_month' => [
                Carbon::now()->subMonth()->startOfMonth(),
                Carbon::now()->subMonth()->endOfMonth(),
            ],
            'last_3_months' => [
                Carbon::now()->subMonths(3)->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ],
            'current_year' => [
                Carbon::now()->startOfYear(),
                Carbon::now()->endOfYear(),
            ],
            default => [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ],
        };
    }

    private function getServiceColor(string $serviceName): string
    {
        $colors = [
            'Electricity' => '#3b82f6', // Blue
            'Water' => '#0ea5e9', // Sky blue
            'Heating' => '#ef4444', // Red
            'Gas' => '#f59e0b', // Amber
            'Internet' => '#8b5cf6', // Violet
            'Waste' => '#22c55e', // Green
            'Sewage' => '#06b6d4', // Cyan
            'Maintenance' => '#64748b', // Slate
        ];

        return $colors[$serviceName] ?? '#6b7280'; // Gray fallback
    }
}