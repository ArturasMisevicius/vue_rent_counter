<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Widgets;

use App\Services\UniversalBillingCalculator;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

final class CostTrackingWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $user = Auth::user();
        
        if (!$user || !$user->currentTeam) {
            return [];
        }

        $cacheKey = "cost_tracking_{$user->currentTeam->id}";
        
        return Cache::remember($cacheKey, 300, function () use ($user) {
            $properties = $user->currentTeam->properties()
                ->with([
                    'meters.serviceConfiguration.utilityService',
                    'meters.readings' => function ($query) {
                        $query->whereMonth('created_at', now()->month)
                            ->whereYear('created_at', now()->year);
                    }
                ])
                ->get();

            $currentMonthCost = $this->calculateTotalCost($properties);
            $lastMonthCost = $this->calculateLastMonthCost($user->currentTeam->id);
            $yearToDateCost = $this->calculateYearToDateCost($user->currentTeam->id);
            $averageMonthlyCost = $this->calculateAverageMonthlyCost($user->currentTeam->id);

            $monthlyChange = $lastMonthCost > 0 
                ? (($currentMonthCost - $lastMonthCost) / $lastMonthCost) * 100 
                : 0;

            return [
                Stat::make(__('dashboard.current_month_cost'), '€' . number_format($currentMonthCost, 2))
                    ->description($monthlyChange >= 0 
                        ? '+' . number_format($monthlyChange, 1) . '% ' . __('dashboard.from_last_month')
                        : number_format($monthlyChange, 1) . '% ' . __('dashboard.from_last_month')
                    )
                    ->descriptionIcon($monthlyChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->color($monthlyChange >= 0 ? 'danger' : 'success'),

                Stat::make(__('dashboard.year_to_date_cost'), '€' . number_format($yearToDateCost, 2))
                    ->description(__('dashboard.total_this_year'))
                    ->descriptionIcon('heroicon-m-calendar-days')
                    ->color('primary'),

                Stat::make(__('dashboard.average_monthly_cost'), '€' . number_format($averageMonthlyCost, 2))
                    ->description(__('dashboard.last_6_months_average'))
                    ->descriptionIcon('heroicon-m-chart-bar')
                    ->color('gray'),
            ];
        });
    }

    private function calculateTotalCost($properties): float
    {
        $billingCalculator = app(UniversalBillingCalculator::class);
        $totalCost = 0;

        foreach ($properties as $property) {
            foreach ($property->meters as $meter) {
                if ($meter->readings->isEmpty()) {
                    continue;
                }

                $reading = $meter->readings->first();
                $serviceConfig = $meter->serviceConfiguration;
                
                if (!$serviceConfig) {
                    continue;
                }

                try {
                    $cost = $billingCalculator->calculateCost(
                        $reading->value,
                        $serviceConfig,
                        Carbon::now()->startOfMonth(),
                        Carbon::now()->endOfMonth()
                    );
                    
                    $totalCost += $cost;
                } catch (\Exception $e) {
                    // Log error but continue with other calculations
                    logger()->warning('Cost calculation failed', [
                        'meter_id' => $meter->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $totalCost;
    }

    private function calculateLastMonthCost(int $teamId): float
    {
        $cacheKey = "last_month_cost_{$teamId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($teamId) {
            $lastMonth = Carbon::now()->subMonth();
            
            $properties = \App\Models\Team::find($teamId)
                ->properties()
                ->with([
                    'meters.serviceConfiguration.utilityService',
                    'meters.readings' => function ($query) use ($lastMonth) {
                        $query->whereMonth('created_at', $lastMonth->month)
                            ->whereYear('created_at', $lastMonth->year);
                    }
                ])
                ->get();

            return $this->calculateTotalCost($properties);
        });
    }

    private function calculateYearToDateCost(int $teamId): float
    {
        $cacheKey = "ytd_cost_{$teamId}_" . now()->year;
        
        return Cache::remember($cacheKey, 1800, function () use ($teamId) {
            $properties = \App\Models\Team::find($teamId)
                ->properties()
                ->with([
                    'meters.serviceConfiguration.utilityService',
                    'meters.readings' => function ($query) {
                        $query->whereYear('created_at', now()->year);
                    }
                ])
                ->get();

            $billingCalculator = app(UniversalBillingCalculator::class);
            $totalCost = 0;

            foreach ($properties as $property) {
                foreach ($property->meters as $meter) {
                    $serviceConfig = $meter->serviceConfiguration;
                    
                    if (!$serviceConfig) {
                        continue;
                    }

                    $monthlyReadings = $meter->readings->groupBy(function ($reading) {
                        return $reading->created_at->format('Y-m');
                    });

                    foreach ($monthlyReadings as $month => $readings) {
                        $totalConsumption = $readings->sum('value');
                        $monthStart = Carbon::parse($month . '-01')->startOfMonth();
                        $monthEnd = $monthStart->copy()->endOfMonth();

                        try {
                            $cost = $billingCalculator->calculateCost(
                                $totalConsumption,
                                $serviceConfig,
                                $monthStart,
                                $monthEnd
                            );
                            
                            $totalCost += $cost;
                        } catch (\Exception $e) {
                            logger()->warning('YTD cost calculation failed', [
                                'meter_id' => $meter->id,
                                'month' => $month,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }

            return $totalCost;
        });
    }

    private function calculateAverageMonthlyCost(int $teamId): float
    {
        $cacheKey = "avg_monthly_cost_{$teamId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($teamId) {
            $sixMonthsAgo = Carbon::now()->subMonths(6);
            
            $properties = \App\Models\Team::find($teamId)
                ->properties()
                ->with([
                    'meters.serviceConfiguration.utilityService',
                    'meters.readings' => function ($query) use ($sixMonthsAgo) {
                        $query->where('created_at', '>=', $sixMonthsAgo);
                    }
                ])
                ->get();

            $billingCalculator = app(UniversalBillingCalculator::class);
            $monthlyCosts = [];

            for ($i = 5; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i);
                $monthStart = $month->copy()->startOfMonth();
                $monthEnd = $month->copy()->endOfMonth();
                $monthKey = $month->format('Y-m');
                $monthlyCosts[$monthKey] = 0;

                foreach ($properties as $property) {
                    foreach ($property->meters as $meter) {
                        $serviceConfig = $meter->serviceConfiguration;
                        
                        if (!$serviceConfig) {
                            continue;
                        }

                        $monthlyReadings = $meter->readings->filter(function ($reading) use ($monthStart, $monthEnd) {
                            return $reading->created_at->between($monthStart, $monthEnd);
                        });

                        if ($monthlyReadings->isEmpty()) {
                            continue;
                        }

                        $totalConsumption = $monthlyReadings->sum('value');

                        try {
                            $cost = $billingCalculator->calculateCost(
                                $totalConsumption,
                                $serviceConfig,
                                $monthStart,
                                $monthEnd
                            );
                            
                            $monthlyCosts[$monthKey] += $cost;
                        } catch (\Exception $e) {
                            logger()->warning('Average monthly cost calculation failed', [
                                'meter_id' => $meter->id,
                                'month' => $monthKey,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            }

            $validCosts = array_filter($monthlyCosts, fn($cost) => $cost > 0);
            
            return count($validCosts) > 0 ? array_sum($validCosts) / count($validCosts) : 0;
        });
    }
}