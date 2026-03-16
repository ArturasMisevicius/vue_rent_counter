<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Widgets;

use App\Services\UniversalBillingCalculator;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

final class UtilityAnalyticsWidget extends Widget
{
    protected static string $view = 'filament.tenant.widgets.utility-analytics';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    public function getAnalyticsData(): array
    {
        $user = Auth::user();
        
        if (!$user || !$user->currentTeam) {
            return [];
        }

        $cacheKey = "utility_analytics_{$user->currentTeam->id}";
        
        return Cache::remember($cacheKey, 600, function () use ($user) {
            $properties = $user->currentTeam->properties()
                ->with([
                    'meters.serviceConfiguration.utilityService',
                    'meters.readings' => function ($query) {
                        $query->where('created_at', '>=', now()->subMonths(12));
                    }
                ])
                ->get();

            return [
                'efficiency_trends' => $this->calculateEfficiencyTrends($properties),
                'cost_predictions' => $this->calculateCostPredictions($properties),
                'usage_patterns' => $this->analyzeUsagePatterns($properties),
                'recommendations' => $this->generateRecommendations($properties),
            ];
        });
    }

    private function calculateEfficiencyTrends($properties): array
    {
        $trends = [];
        $billingCalculator = app(UniversalBillingCalculator::class);

        foreach ($properties as $property) {
            foreach ($property->meters as $meter) {
                $serviceConfig = $meter->serviceConfiguration;
                
                if (!$serviceConfig) {
                    continue;
                }

                $service = $serviceConfig->utilityService;
                $monthlyData = [];

                // Get last 6 months of data
                for ($i = 5; $i >= 0; $i--) {
                    $month = Carbon::now()->subMonths($i);
                    $monthStart = $month->copy()->startOfMonth();
                    $monthEnd = $month->copy()->endOfMonth();

                    $monthlyReadings = $meter->readings->filter(function ($reading) use ($monthStart, $monthEnd) {
                        return $reading->created_at->between($monthStart, $monthEnd);
                    });

                    $consumption = $monthlyReadings->sum('value');
                    $cost = 0;

                    if ($consumption > 0) {
                        try {
                            $cost = $billingCalculator->calculateCost(
                                $consumption,
                                $serviceConfig,
                                $monthStart,
                                $monthEnd
                            );
                        } catch (\Exception $e) {
                            logger()->warning('Efficiency trend calculation failed', [
                                'meter_id' => $meter->id,
                                'month' => $month->format('Y-m'),
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    $efficiency = $consumption > 0 ? $cost / $consumption : 0;

                    $monthlyData[] = [
                        'month' => $month->format('M Y'),
                        'consumption' => $consumption,
                        'cost' => $cost,
                        'efficiency' => $efficiency,
                    ];
                }

                if (!isset($trends[$service->name])) {
                    $trends[$service->name] = [
                        'service' => $service->name,
                        'unit' => $service->unit_of_measurement,
                        'data' => [],
                        'trend' => 'stable',
                        'change_percentage' => 0,
                    ];
                }

                // Merge data for same service type
                foreach ($monthlyData as $index => $data) {
                    if (!isset($trends[$service->name]['data'][$index])) {
                        $trends[$service->name]['data'][$index] = $data;
                    } else {
                        $trends[$service->name]['data'][$index]['consumption'] += $data['consumption'];
                        $trends[$service->name]['data'][$index]['cost'] += $data['cost'];
                    }
                }
            }
        }

        // Calculate trends
        foreach ($trends as $serviceName => &$trend) {
            $data = $trend['data'];
            if (count($data) >= 2) {
                $firstMonth = $data[0];
                $lastMonth = end($data);
                
                if ($firstMonth['cost'] > 0) {
                    $changePercentage = (($lastMonth['cost'] - $firstMonth['cost']) / $firstMonth['cost']) * 100;
                    $trend['change_percentage'] = round($changePercentage, 1);
                    
                    $trend['trend'] = match (true) {
                        $changePercentage > 10 => 'increasing',
                        $changePercentage < -10 => 'decreasing',
                        default => 'stable',
                    };
                }
            }
        }

        return array_values($trends);
    }

    private function calculateCostPredictions($properties): array
    {
        $predictions = [];
        $billingCalculator = app(UniversalBillingCalculator::class);

        foreach ($properties as $property) {
            foreach ($property->meters as $meter) {
                $serviceConfig = $meter->serviceConfiguration;
                
                if (!$serviceConfig) {
                    continue;
                }

                $service = $serviceConfig->utilityService;

                // Get last 3 months average
                $recentReadings = $meter->readings->filter(function ($reading) {
                    return $reading->created_at->gte(now()->subMonths(3));
                });

                if ($recentReadings->isEmpty()) {
                    continue;
                }

                $averageMonthlyConsumption = $recentReadings->sum('value') / 3;
                
                try {
                    $predictedMonthlyCost = $billingCalculator->calculateCost(
                        $averageMonthlyConsumption,
                        $serviceConfig,
                        now()->startOfMonth(),
                        now()->endOfMonth()
                    );

                    if (!isset($predictions[$service->name])) {
                        $predictions[$service->name] = [
                            'service' => $service->name,
                            'predicted_monthly_cost' => 0,
                            'predicted_yearly_cost' => 0,
                            'confidence' => 'medium',
                        ];
                    }

                    $predictions[$service->name]['predicted_monthly_cost'] += $predictedMonthlyCost;
                    $predictions[$service->name]['predicted_yearly_cost'] += $predictedMonthlyCost * 12;

                    // Determine confidence based on data availability
                    $dataPoints = $recentReadings->count();
                    $predictions[$service->name]['confidence'] = match (true) {
                        $dataPoints >= 10 => 'high',
                        $dataPoints >= 5 => 'medium',
                        default => 'low',
                    };
                } catch (\Exception $e) {
                    logger()->warning('Cost prediction calculation failed', [
                        'meter_id' => $meter->id,
                        'service' => $service->name,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return array_values($predictions);
    }

    private function analyzeUsagePatterns($properties): array
    {
        $patterns = [];

        foreach ($properties as $property) {
            foreach ($property->meters as $meter) {
                $serviceConfig = $meter->serviceConfiguration;
                
                if (!$serviceConfig) {
                    continue;
                }

                $service = $serviceConfig->utilityService;
                $readings = $meter->readings->where('created_at', '>=', now()->subMonths(6));

                if ($readings->isEmpty()) {
                    continue;
                }

                // Analyze by day of week
                $dayOfWeekUsage = [];
                for ($i = 0; $i < 7; $i++) {
                    $dayReadings = $readings->filter(function ($reading) use ($i) {
                        return $reading->created_at->dayOfWeek === $i;
                    });
                    
                    $dayOfWeekUsage[] = [
                        'day' => Carbon::now()->startOfWeek()->addDays($i)->format('l'),
                        'average_usage' => $dayReadings->avg('value') ?? 0,
                    ];
                }

                // Analyze by month
                $monthlyUsage = [];
                for ($i = 5; $i >= 0; $i--) {
                    $month = Carbon::now()->subMonths($i);
                    $monthReadings = $readings->filter(function ($reading) use ($month) {
                        return $reading->created_at->month === $month->month &&
                               $reading->created_at->year === $month->year;
                    });
                    
                    $monthlyUsage[] = [
                        'month' => $month->format('M Y'),
                        'total_usage' => $monthReadings->sum('value'),
                        'average_usage' => $monthReadings->avg('value') ?? 0,
                    ];
                }

                if (!isset($patterns[$service->name])) {
                    $patterns[$service->name] = [
                        'service' => $service->name,
                        'unit' => $service->unit_of_measurement,
                        'day_of_week_usage' => $dayOfWeekUsage,
                        'monthly_usage' => $monthlyUsage,
                        'peak_day' => '',
                        'peak_month' => '',
                    ];
                } else {
                    // Merge data for same service type
                    foreach ($dayOfWeekUsage as $index => $dayData) {
                        $patterns[$service->name]['day_of_week_usage'][$index]['average_usage'] += $dayData['average_usage'];
                    }
                    
                    foreach ($monthlyUsage as $index => $monthData) {
                        $patterns[$service->name]['monthly_usage'][$index]['total_usage'] += $monthData['total_usage'];
                        $patterns[$service->name]['monthly_usage'][$index]['average_usage'] += $monthData['average_usage'];
                    }
                }

                // Find peak usage periods
                $peakDay = collect($patterns[$service->name]['day_of_week_usage'])
                    ->sortByDesc('average_usage')
                    ->first();
                $patterns[$service->name]['peak_day'] = $peakDay['day'] ?? '';

                $peakMonth = collect($patterns[$service->name]['monthly_usage'])
                    ->sortByDesc('total_usage')
                    ->first();
                $patterns[$service->name]['peak_month'] = $peakMonth['month'] ?? '';
            }
        }

        return array_values($patterns);
    }

    private function generateRecommendations($properties): array
    {
        $recommendations = [];

        foreach ($properties as $property) {
            foreach ($property->meters as $meter) {
                $serviceConfig = $meter->serviceConfiguration;
                
                if (!$serviceConfig) {
                    continue;
                }

                $service = $serviceConfig->utilityService;
                $recentReadings = $meter->readings->where('created_at', '>=', now()->subMonth());

                if ($recentReadings->isEmpty()) {
                    $recommendations[] = [
                        'type' => 'missing_data',
                        'priority' => 'high',
                        'title' => __('dashboard.missing_readings_title', ['service' => $service->name]),
                        'description' => __('dashboard.missing_readings_desc', ['property' => $property->name]),
                        'action' => __('dashboard.add_reading'),
                        'service' => $service->name,
                    ];
                    continue;
                }

                // Check for unusual consumption
                $currentMonthUsage = $recentReadings->sum('value');
                $previousMonthUsage = $meter->readings
                    ->whereBetween('created_at', [
                        now()->subMonths(2)->startOfMonth(),
                        now()->subMonths(2)->endOfMonth(),
                    ])
                    ->sum('value');

                if ($previousMonthUsage > 0) {
                    $changePercentage = (($currentMonthUsage - $previousMonthUsage) / $previousMonthUsage) * 100;
                    
                    if ($changePercentage > 50) {
                        $recommendations[] = [
                            'type' => 'high_usage',
                            'priority' => 'medium',
                            'title' => __('dashboard.high_usage_title', ['service' => $service->name]),
                            'description' => __('dashboard.high_usage_desc', [
                                'percentage' => round($changePercentage, 1),
                                'property' => $property->name,
                            ]),
                            'action' => __('dashboard.investigate_usage'),
                            'service' => $service->name,
                        ];
                    } elseif ($changePercentage < -30) {
                        $recommendations[] = [
                            'type' => 'low_usage',
                            'priority' => 'low',
                            'title' => __('dashboard.low_usage_title', ['service' => $service->name]),
                            'description' => __('dashboard.low_usage_desc', [
                                'percentage' => abs(round($changePercentage, 1)),
                                'property' => $property->name,
                            ]),
                            'action' => __('dashboard.verify_readings'),
                            'service' => $service->name,
                        ];
                    }
                }

                // Check for efficiency opportunities
                if ($service->name === 'Electricity' && $currentMonthUsage > 500) {
                    $recommendations[] = [
                        'type' => 'efficiency',
                        'priority' => 'low',
                        'title' => __('dashboard.efficiency_title'),
                        'description' => __('dashboard.efficiency_desc', ['property' => $property->name]),
                        'action' => __('dashboard.consider_efficiency'),
                        'service' => $service->name,
                    ];
                }
            }
        }

        // Sort by priority
        usort($recommendations, function ($a, $b) {
            $priorities = ['high' => 3, 'medium' => 2, 'low' => 1];
            return ($priorities[$b['priority']] ?? 0) <=> ($priorities[$a['priority']] ?? 0);
        });

        return array_slice($recommendations, 0, 5); // Limit to top 5 recommendations
    }
}