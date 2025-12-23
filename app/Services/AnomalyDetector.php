<?php

declare(strict_types=1);

namespace App\Services;

use App\ValueObjects\AnomalyDetectionResult;
use Illuminate\Support\Collection;

final readonly class AnomalyDetector
{
    /**
     * Detect anomalies in consumption data using statistical methods
     */
    public function detect(
        Collection $consumptionData,
        array $baseline,
        array $thresholds
    ): AnomalyDetectionResult {
        if ($consumptionData->isEmpty()) {
            return new AnomalyDetectionResult(
                anomalies: [],
                statistics: $baseline,
                detectionMethod: 'statistical_threshold',
                thresholds: $thresholds,
                metadata: ['note' => 'No data available for anomaly detection']
            );
        }

        $anomalies = [];
        $statistics = $this->calculateStatistics($consumptionData);

        // Statistical threshold detection
        $statisticalAnomalies = $this->detectStatisticalAnomalies(
            $consumptionData,
            $baseline,
            $thresholds
        );

        // Seasonal anomaly detection
        $seasonalAnomalies = $this->detectSeasonalAnomalies(
            $consumptionData,
            $thresholds
        );

        // Trend-based anomaly detection
        $trendAnomalies = $this->detectTrendAnomalies(
            $consumptionData,
            $thresholds
        );

        // Combine all anomalies
        $anomalies = array_merge($statisticalAnomalies, $seasonalAnomalies, $trendAnomalies);

        // Remove duplicates and sort by severity
        $anomalies = $this->deduplicateAndSort($anomalies);

        return new AnomalyDetectionResult(
            anomalies: $anomalies,
            statistics: $statistics,
            detectionMethod: 'multi_method',
            thresholds: $thresholds,
            metadata: [
                'detection_methods' => ['statistical', 'seasonal', 'trend'],
                'data_points' => $consumptionData->count(),
                'anomaly_rate' => count($anomalies) / $consumptionData->count(),
            ]
        );
    }

    private function detectStatisticalAnomalies(
        Collection $consumptionData,
        array $baseline,
        array $thresholds
    ): array {
        $anomalies = [];
        $mean = $baseline['mean'] ?? 0;
        $stdDev = $baseline['std_dev'] ?? 0;

        if ($stdDev === 0) {
            return $anomalies; // Cannot detect anomalies without variance
        }

        $mildThreshold = $thresholds['mild_threshold'] ?? 2.0;
        $severeThreshold = $thresholds['severe_threshold'] ?? 3.0;

        foreach ($consumptionData as $record) {
            $zScore = abs(($record->consumption - $mean) / $stdDev);
            
            if ($zScore >= $severeThreshold) {
                $anomalies[] = [
                    'id' => $record->id ?? uniqid(),
                    'date' => $record->reading_date ?? $record->created_at,
                    'consumption' => $record->consumption,
                    'expected_range' => [
                        'min' => $mean - $severeThreshold * $stdDev,
                        'max' => $mean + $severeThreshold * $stdDev,
                    ],
                    'severity' => 'severe',
                    'type' => 'statistical_outlier',
                    'z_score' => $zScore,
                    'description' => "Consumption significantly deviates from normal pattern (z-score: {$zScore})",
                ];
            } elseif ($zScore >= $mildThreshold) {
                $anomalies[] = [
                    'id' => $record->id ?? uniqid(),
                    'date' => $record->reading_date ?? $record->created_at,
                    'consumption' => $record->consumption,
                    'expected_range' => [
                        'min' => $mean - $mildThreshold * $stdDev,
                        'max' => $mean + $mildThreshold * $stdDev,
                    ],
                    'severity' => 'mild',
                    'type' => 'statistical_outlier',
                    'z_score' => $zScore,
                    'description' => "Consumption moderately deviates from normal pattern (z-score: {$zScore})",
                ];
            }
        }

        return $anomalies;
    }

    private function detectSeasonalAnomalies(
        Collection $consumptionData,
        array $thresholds
    ): array {
        $anomalies = [];
        
        // Group data by season
        $seasonalData = $consumptionData->groupBy(function ($record) {
            $date = $record->reading_date ?? $record->created_at;
            return $this->getSeason($date);
        });

        foreach ($seasonalData as $season => $records) {
            if ($records->count() < 3) continue; // Need minimum data for seasonal analysis

            $seasonalMean = $records->avg('consumption');
            $seasonalStdDev = $this->calculateStandardDeviation(
                $records->pluck('consumption')->toArray(),
                $seasonalMean
            );

            if ($seasonalStdDev === 0) continue;

            $mildThreshold = $thresholds['mild_threshold'] ?? 2.0;
            $severeThreshold = $thresholds['severe_threshold'] ?? 3.0;

            foreach ($records as $record) {
                $zScore = abs(($record->consumption - $seasonalMean) / $seasonalStdDev);
                
                if ($zScore >= $severeThreshold) {
                    $anomalies[] = [
                        'id' => $record->id ?? uniqid(),
                        'date' => $record->reading_date ?? $record->created_at,
                        'consumption' => $record->consumption,
                        'expected_range' => [
                            'min' => $seasonalMean - $severeThreshold * $seasonalStdDev,
                            'max' => $seasonalMean + $severeThreshold * $seasonalStdDev,
                        ],
                        'severity' => 'severe',
                        'type' => 'seasonal_anomaly',
                        'season' => $season,
                        'z_score' => $zScore,
                        'description' => "Consumption unusual for {$season} season (z-score: {$zScore})",
                    ];
                } elseif ($zScore >= $mildThreshold) {
                    $anomalies[] = [
                        'id' => $record->id ?? uniqid(),
                        'date' => $record->reading_date ?? $record->created_at,
                        'consumption' => $record->consumption,
                        'expected_range' => [
                            'min' => $seasonalMean - $mildThreshold * $seasonalStdDev,
                            'max' => $seasonalMean + $mildThreshold * $seasonalStdDev,
                        ],
                        'severity' => 'mild',
                        'type' => 'seasonal_anomaly',
                        'season' => $season,
                        'z_score' => $zScore,
                        'description' => "Consumption moderately unusual for {$season} season (z-score: {$zScore})",
                    ];
                }
            }
        }

        return $anomalies;
    }

    private function detectTrendAnomalies(
        Collection $consumptionData,
        array $thresholds
    ): array {
        $anomalies = [];
        
        if ($consumptionData->count() < 5) {
            return $anomalies; // Need minimum data for trend analysis
        }

        // Calculate moving averages and detect sudden changes
        $windowSize = min(5, intval($consumptionData->count() / 3));
        $movingAverages = [];
        
        $dataArray = $consumptionData->values()->toArray();
        
        for ($i = $windowSize - 1; $i < count($dataArray); $i++) {
            $window = array_slice($dataArray, $i - $windowSize + 1, $windowSize);
            $average = array_sum(array_column($window, 'consumption')) / $windowSize;
            $movingAverages[] = [
                'index' => $i,
                'average' => $average,
                'record' => $dataArray[$i],
            ];
        }

        // Detect sudden changes in trend
        for ($i = 1; $i < count($movingAverages); $i++) {
            $current = $movingAverages[$i];
            $previous = $movingAverages[$i - 1];
            
            if ($previous['average'] === 0) continue;
            
            $changePercent = abs(($current['average'] - $previous['average']) / $previous['average']);
            
            if ($changePercent >= 0.5) { // 50% change threshold
                $severity = $changePercent >= 1.0 ? 'severe' : 'mild';
                
                $anomalies[] = [
                    'id' => $current['record']->id ?? uniqid(),
                    'date' => $current['record']->reading_date ?? $current['record']->created_at,
                    'consumption' => $current['record']->consumption,
                    'expected_range' => [
                        'min' => $previous['average'] * 0.8,
                        'max' => $previous['average'] * 1.2,
                    ],
                    'severity' => $severity,
                    'type' => 'trend_anomaly',
                    'change_percent' => $changePercent * 100,
                    'description' => "Sudden change in consumption trend ({$changePercent * 100}% change)",
                ];
            }
        }

        return $anomalies;
    }

    private function calculateStatistics(Collection $consumptionData): array
    {
        if ($consumptionData->isEmpty()) {
            return [
                'count' => 0,
                'mean' => 0,
                'median' => 0,
                'std_dev' => 0,
                'min' => 0,
                'max' => 0,
                'percentiles' => [25 => 0, 75 => 0, 95 => 0],
            ];
        }

        $consumptions = $consumptionData->pluck('consumption')->sort()->values()->toArray();
        $count = count($consumptions);
        $mean = array_sum($consumptions) / $count;
        $median = $this->calculateMedian($consumptions);
        $stdDev = $this->calculateStandardDeviation($consumptions, $mean);

        return [
            'count' => $count,
            'mean' => $mean,
            'median' => $median,
            'std_dev' => $stdDev,
            'min' => min($consumptions),
            'max' => max($consumptions),
            'percentiles' => [
                25 => $this->calculatePercentile($consumptions, 25),
                75 => $this->calculatePercentile($consumptions, 75),
                95 => $this->calculatePercentile($consumptions, 95),
            ],
        ];
    }

    private function deduplicateAndSort(array $anomalies): array
    {
        // Remove duplicates based on ID and date
        $unique = [];
        $seen = [];
        
        foreach ($anomalies as $anomaly) {
            $key = $anomaly['id'] . '_' . $anomaly['date'];
            if (!isset($seen[$key])) {
                $unique[] = $anomaly;
                $seen[$key] = true;
            }
        }

        // Sort by severity (severe first) then by date
        usort($unique, function ($a, $b) {
            if ($a['severity'] !== $b['severity']) {
                return $a['severity'] === 'severe' ? -1 : 1;
            }
            return $a['date'] <=> $b['date'];
        });

        return $unique;
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

    private function calculateMedian(array $values): float
    {
        $count = count($values);
        if ($count === 0) return 0;

        $middle = intval($count / 2);
        
        if ($count % 2 === 0) {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        }
        
        return $values[$middle];
    }

    private function calculateStandardDeviation(array $values, float $mean): float
    {
        if (count($values) <= 1) return 0;

        $variance = array_sum(array_map(
            fn($value) => pow($value - $mean, 2),
            $values
        )) / (count($values) - 1);

        return sqrt($variance);
    }

    private function calculatePercentile(array $values, int $percentile): float
    {
        if (empty($values)) return 0;

        $index = ($percentile / 100) * (count($values) - 1);
        $lower = floor($index);
        $upper = ceil($index);

        if ($lower === $upper) {
            return $values[$lower];
        }

        $weight = $index - $lower;
        return $values[$lower] * (1 - $weight) + $values[$upper] * $weight;
    }
}