<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Collection;

final readonly class TrendAnalyzer
{
    /**
     * Analyze consumption trends and patterns
     */
    public function analyze(Collection $consumptionData, array $options = []): array
    {
        if ($consumptionData->isEmpty()) {
            return [
                'trend_direction' => 'unknown',
                'trend_strength' => 0,
                'seasonal_patterns' => [],
                'growth_rate' => 0,
                'volatility' => 0,
                'efficiency_trends' => [],
                'metadata' => ['note' => 'No data available for trend analysis'],
            ];
        }

        $analysis = [];

        // Basic trend analysis
        if ($options['growth_rate'] ?? true) {
            $analysis['growth_rate'] = $this->calculateGrowthRate($consumptionData);
            $analysis['trend_direction'] = $this->determineTrendDirection($analysis['growth_rate']);
            $analysis['trend_strength'] = $this->calculateTrendStrength($consumptionData);
        }

        // Seasonal pattern analysis
        if ($options['seasonal_patterns'] ?? true) {
            $analysis['seasonal_patterns'] = $this->analyzeSeasonalPatterns($consumptionData);
        }

        // Volatility analysis
        if ($options['volatility'] ?? true) {
            $analysis['volatility'] = $this->calculateVolatility($consumptionData);
        }

        // Efficiency trends
        if ($options['efficiency_trends'] ?? true) {
            $analysis['efficiency_trends'] = $this->analyzeEfficiencyTrends($consumptionData);
        }

        // Additional insights
        $analysis['insights'] = $this->generateInsights($analysis, $consumptionData);
        $analysis['metadata'] = [
            'data_points' => $consumptionData->count(),
            'analysis_period' => [
                'start' => $consumptionData->first()->reading_date ?? $consumptionData->first()->created_at,
                'end' => $consumptionData->last()->reading_date ?? $consumptionData->last()->created_at,
            ],
            'analysis_options' => $options,
        ];

        return $analysis;
    }

    private function calculateGrowthRate(Collection $consumptionData): float
    {
        if ($consumptionData->count() < 2) {
            return 0;
        }

        $first = $consumptionData->first()->consumption;
        $last = $consumptionData->last()->consumption;

        if ($first === 0) {
            return $last > 0 ? 100 : 0; // 100% growth from zero
        }

        return (($last - $first) / $first) * 100;
    }

    private function determineTrendDirection(float $growthRate): string
    {
        return match (true) {
            $growthRate > 5 => 'increasing',
            $growthRate < -5 => 'decreasing',
            default => 'stable',
        };
    }

    private function calculateTrendStrength(Collection $consumptionData): float
    {
        if ($consumptionData->count() < 3) {
            return 0;
        }

        // Calculate correlation coefficient with time
        $n = $consumptionData->count();
        $timeValues = range(1, $n);
        $consumptionValues = $consumptionData->pluck('consumption')->toArray();

        $correlation = $this->calculateCorrelation($timeValues, $consumptionValues);
        
        return abs($correlation); // Strength is absolute value of correlation
    }

    private function analyzeSeasonalPatterns(Collection $consumptionData): array
    {
        $seasonalData = $consumptionData->groupBy(function ($record) {
            $date = $record->reading_date ?? $record->created_at;
            return $this->getSeason($date);
        });

        $patterns = [];
        $overallMean = $consumptionData->avg('consumption');

        foreach (['winter', 'spring', 'summer', 'autumn'] as $season) {
            $seasonData = $seasonalData->get($season, collect());
            
            if ($seasonData->isEmpty()) {
                $patterns[$season] = [
                    'average_consumption' => 0,
                    'relative_to_annual' => 0,
                    'data_points' => 0,
                    'pattern' => 'no_data',
                ];
                continue;
            }

            $seasonalMean = $seasonData->avg('consumption');
            $relativeConsumption = $overallMean > 0 ? ($seasonalMean / $overallMean) : 1;

            $patterns[$season] = [
                'average_consumption' => $seasonalMean,
                'relative_to_annual' => $relativeConsumption,
                'data_points' => $seasonData->count(),
                'pattern' => $this->classifySeasonalPattern($relativeConsumption),
                'volatility' => $this->calculateSeasonalVolatility($seasonData),
            ];
        }

        return [
            'seasonal_breakdown' => $patterns,
            'most_intensive_season' => $this->findMostIntensiveSeason($patterns),
            'seasonal_variation' => $this->calculateSeasonalVariation($patterns),
        ];
    }

    private function calculateVolatility(Collection $consumptionData): float
    {
        if ($consumptionData->count() < 2) {
            return 0;
        }

        $consumptions = $consumptionData->pluck('consumption')->toArray();
        $mean = array_sum($consumptions) / count($consumptions);
        
        $variance = array_sum(array_map(
            fn($consumption) => pow($consumption - $mean, 2),
            $consumptions
        )) / (count($consumptions) - 1);

        $stdDev = sqrt($variance);
        
        // Return coefficient of variation (volatility as percentage)
        return $mean > 0 ? ($stdDev / $mean) * 100 : 0;
    }

    private function analyzeEfficiencyTrends(Collection $consumptionData): array
    {
        if ($consumptionData->count() < 6) {
            return [
                'efficiency_score' => 0,
                'trend' => 'insufficient_data',
                'recommendations' => ['Collect more data for efficiency analysis'],
            ];
        }

        // Calculate rolling efficiency (lower consumption = higher efficiency)
        $windowSize = min(6, intval($consumptionData->count() / 2));
        $rollingAverages = [];
        
        $dataArray = $consumptionData->values()->toArray();
        
        for ($i = $windowSize - 1; $i < count($dataArray); $i++) {
            $window = array_slice($dataArray, $i - $windowSize + 1, $windowSize);
            $average = array_sum(array_column($window, 'consumption')) / $windowSize;
            $rollingAverages[] = $average;
        }

        // Calculate efficiency trend (negative slope = improving efficiency)
        $efficiencyTrend = $this->calculateSlope($rollingAverages);
        
        $efficiencyScore = $this->calculateEfficiencyScore($consumptionData);
        
        return [
            'efficiency_score' => $efficiencyScore,
            'trend' => $this->classifyEfficiencyTrend($efficiencyTrend),
            'improvement_rate' => -$efficiencyTrend, // Negative trend = improvement
            'recommendations' => $this->generateEfficiencyRecommendations($efficiencyScore, $efficiencyTrend),
        ];
    }

    private function generateInsights(array $analysis, Collection $consumptionData): array
    {
        $insights = [];

        // Growth insights
        if (isset($analysis['growth_rate'])) {
            $growthRate = $analysis['growth_rate'];
            if (abs($growthRate) > 20) {
                $insights[] = [
                    'type' => 'growth_alert',
                    'severity' => abs($growthRate) > 50 ? 'high' : 'medium',
                    'message' => "Consumption has " . ($growthRate > 0 ? 'increased' : 'decreased') . 
                                " by " . abs($growthRate) . "% over the analysis period",
                ];
            }
        }

        // Volatility insights
        if (isset($analysis['volatility'])) {
            $volatility = $analysis['volatility'];
            if ($volatility > 30) {
                $insights[] = [
                    'type' => 'volatility_alert',
                    'severity' => $volatility > 50 ? 'high' : 'medium',
                    'message' => "High consumption volatility detected ({$volatility}% coefficient of variation)",
                ];
            }
        }

        // Seasonal insights
        if (isset($analysis['seasonal_patterns']['seasonal_variation'])) {
            $variation = $analysis['seasonal_patterns']['seasonal_variation'];
            if ($variation > 0.5) {
                $insights[] = [
                    'type' => 'seasonal_pattern',
                    'severity' => 'low',
                    'message' => "Strong seasonal consumption patterns detected (variation: {$variation})",
                ];
            }
        }

        return $insights;
    }

    private function calculateCorrelation(array $x, array $y): float
    {
        $n = count($x);
        if ($n !== count($y) || $n < 2) {
            return 0;
        }

        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumX2 = 0;
        $sumY2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumX2 += $x[$i] * $x[$i];
            $sumY2 += $y[$i] * $y[$i];
        }

        $numerator = $n * $sumXY - $sumX * $sumY;
        $denominator = sqrt(($n * $sumX2 - $sumX * $sumX) * ($n * $sumY2 - $sumY * $sumY));

        return $denominator !== 0 ? $numerator / $denominator : 0;
    }

    private function getSeason($date): string
    {
        $month = $date->month ?? date('n', strtotime($date));
        return match (true) {
            in_array($month, [12, 1, 2]) => 'winter',
            in_array($month, [3, 4, 5]) => 'spring',
            in_array($month, [6, 7, 8]) => 'summer',
            in_array($month, [9, 10, 11]) => 'autumn',
        };
    }

    private function classifySeasonalPattern(float $relativeConsumption): string
    {
        return match (true) {
            $relativeConsumption > 1.3 => 'high_consumption',
            $relativeConsumption > 1.1 => 'above_average',
            $relativeConsumption > 0.9 => 'average',
            $relativeConsumption > 0.7 => 'below_average',
            default => 'low_consumption',
        };
    }

    private function calculateSeasonalVolatility(Collection $seasonData): float
    {
        if ($seasonData->count() < 2) {
            return 0;
        }

        $consumptions = $seasonData->pluck('consumption')->toArray();
        $mean = array_sum($consumptions) / count($consumptions);
        
        $variance = array_sum(array_map(
            fn($consumption) => pow($consumption - $mean, 2),
            $consumptions
        )) / (count($consumptions) - 1);

        return sqrt($variance);
    }

    private function findMostIntensiveSeason(array $patterns): string
    {
        $maxConsumption = 0;
        $mostIntensive = 'unknown';

        foreach ($patterns as $season => $data) {
            if ($data['average_consumption'] > $maxConsumption) {
                $maxConsumption = $data['average_consumption'];
                $mostIntensive = $season;
            }
        }

        return $mostIntensive;
    }

    private function calculateSeasonalVariation(array $patterns): float
    {
        $consumptions = array_column($patterns, 'average_consumption');
        $consumptions = array_filter($consumptions, fn($c) => $c > 0);
        
        if (count($consumptions) < 2) {
            return 0;
        }

        $max = max($consumptions);
        $min = min($consumptions);
        
        return $max > 0 ? ($max - $min) / $max : 0;
    }

    private function calculateSlope(array $values): float
    {
        $n = count($values);
        if ($n < 2) return 0;

        $x = range(1, $n);
        $sumX = array_sum($x);
        $sumY = array_sum($values);
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $values[$i];
            $sumX2 += $x[$i] * $x[$i];
        }

        return ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
    }

    private function calculateEfficiencyScore(Collection $consumptionData): float
    {
        // Simple efficiency score based on consumption stability and trend
        $volatility = $this->calculateVolatility($consumptionData);
        $growthRate = $this->calculateGrowthRate($consumptionData);
        
        // Lower volatility and negative growth rate = higher efficiency
        $stabilityScore = max(0, 100 - $volatility);
        $trendScore = $growthRate <= 0 ? 100 : max(0, 100 - abs($growthRate));
        
        return ($stabilityScore + $trendScore) / 2;
    }

    private function classifyEfficiencyTrend(float $trend): string
    {
        return match (true) {
            $trend < -5 => 'improving',
            $trend > 5 => 'declining',
            default => 'stable',
        };
    }

    private function generateEfficiencyRecommendations(float $score, float $trend): array
    {
        $recommendations = [];

        if ($score < 50) {
            $recommendations[] = 'Consider energy efficiency improvements';
        }

        if ($trend > 5) {
            $recommendations[] = 'Consumption is increasing - investigate potential causes';
        }

        if ($score > 80) {
            $recommendations[] = 'Excellent efficiency - maintain current practices';
        }

        return $recommendations ?: ['Continue monitoring consumption patterns'];
    }
}