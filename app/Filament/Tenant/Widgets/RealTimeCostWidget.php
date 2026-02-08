<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Widgets;

use App\Services\UniversalBillingCalculator;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

final class RealTimeCostWidget extends Widget
{
    protected static string $view = 'filament.tenant.widgets.real-time-cost';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    public function getRealTimeCosts(): array
    {
        $user = Auth::user();
        
        if (!$user || !$user->currentTeam) {
            return [];
        }

        $cacheKey = "real_time_costs_{$user->currentTeam->id}";
        
        return Cache::remember($cacheKey, 60, function () use ($user) {
            $properties = $user->currentTeam->properties()
                ->with([
                    'meters.serviceConfiguration.utilityService',
                    'meters.readings' => function ($query) {
                        $query->latest()->limit(1);
                    }
                ])
                ->get();

            $billingCalculator = app(UniversalBillingCalculator::class);
            $costs = [];

            foreach ($properties as $property) {
                foreach ($property->meters as $meter) {
                    $serviceConfig = $meter->serviceConfiguration;
                    $latestReading = $meter->readings->first();

                    if (!$serviceConfig || !$latestReading) {
                        continue;
                    }

                    $service = $serviceConfig->utilityService;
                    
                    // Calculate daily cost based on latest reading
                    try {
                        $dailyCost = $billingCalculator->calculateCost(
                            $latestReading->value,
                            $serviceConfig,
                            Carbon::now()->startOfDay(),
                            Carbon::now()->endOfDay()
                        );

                        $monthlyCost = $billingCalculator->calculateCost(
                            $latestReading->value * 30, // Estimate monthly consumption
                            $serviceConfig,
                            Carbon::now()->startOfMonth(),
                            Carbon::now()->endOfMonth()
                        );

                        if (!isset($costs[$service->name])) {
                            $costs[$service->name] = [
                                'service' => $service->name,
                                'unit' => $service->unit_of_measurement,
                                'daily_cost' => 0,
                                'monthly_estimate' => 0,
                                'latest_reading' => 0,
                                'reading_date' => null,
                                'color' => $this->getServiceColor($service->name),
                            ];
                        }

                        $costs[$service->name]['daily_cost'] += $dailyCost;
                        $costs[$service->name]['monthly_estimate'] += $monthlyCost;
                        $costs[$service->name]['latest_reading'] += $latestReading->value;
                        
                        if (!$costs[$service->name]['reading_date'] || 
                            $latestReading->created_at->gt($costs[$service->name]['reading_date'])) {
                            $costs[$service->name]['reading_date'] = $latestReading->created_at;
                        }
                    } catch (\Exception $e) {
                        logger()->warning('Real-time cost calculation failed', [
                            'meter_id' => $meter->id,
                            'service' => $service->name,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            return array_values($costs);
        });
    }

    public function getDailyProjection(): array
    {
        $user = Auth::user();
        
        if (!$user || !$user->currentTeam) {
            return [
                'current' => 0,
                'projected' => 0,
                'percentage' => 0,
            ];
        }

        $cacheKey = "daily_projection_{$user->currentTeam->id}";
        
        return Cache::remember($cacheKey, 300, function () use ($user) {
            $todayStart = Carbon::now()->startOfDay();
            $todayEnd = Carbon::now()->endOfDay();
            $currentHour = Carbon::now()->hour;
            
            $properties = $user->currentTeam->properties()
                ->with([
                    'meters.serviceConfiguration.utilityService',
                    'meters.readings' => function ($query) use ($todayStart, $todayEnd) {
                        $query->whereBetween('created_at', [$todayStart, $todayEnd]);
                    }
                ])
                ->get();

            $billingCalculator = app(UniversalBillingCalculator::class);
            $currentCost = 0;

            foreach ($properties as $property) {
                foreach ($property->meters as $meter) {
                    $serviceConfig = $meter->serviceConfiguration;
                    $todayReadings = $meter->readings;

                    if (!$serviceConfig || $todayReadings->isEmpty()) {
                        continue;
                    }

                    $totalConsumption = $todayReadings->sum('value');

                    try {
                        $cost = $billingCalculator->calculateCost(
                            $totalConsumption,
                            $serviceConfig,
                            $todayStart,
                            Carbon::now()
                        );
                        
                        $currentCost += $cost;
                    } catch (\Exception $e) {
                        logger()->warning('Daily projection calculation failed', [
                            'meter_id' => $meter->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            // Project full day cost based on current progress
            $hoursElapsed = $currentHour + (Carbon::now()->minute / 60);
            $projectedCost = $hoursElapsed > 0 ? ($currentCost / $hoursElapsed) * 24 : 0;
            $percentage = $hoursElapsed > 0 ? ($hoursElapsed / 24) * 100 : 0;

            return [
                'current' => round($currentCost, 2),
                'projected' => round($projectedCost, 2),
                'percentage' => round($percentage, 1),
            ];
        });
    }

    private function getServiceColor(string $serviceName): string
    {
        $colors = [
            'Electricity' => 'blue',
            'Water' => 'sky',
            'Heating' => 'red',
            'Gas' => 'amber',
            'Internet' => 'violet',
            'Waste' => 'green',
            'Sewage' => 'cyan',
            'Maintenance' => 'slate',
        ];

        return $colors[$serviceName] ?? 'gray';
    }
}