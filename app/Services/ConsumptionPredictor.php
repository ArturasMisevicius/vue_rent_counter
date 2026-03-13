<?php

declare(strict_types=1);

namespace App\Services;

use App\ValueObjects\ConsumptionPrediction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final readonly class ConsumptionPredictor
{
    /**
     * Predict consumption using multiple algorithms and ensemble methods
     */
    public function predict(
        Collection $historicalData,
        Carbon $startDate,
        Carbon $endDate,
        array $seasonalFactors,
        array $propertyFactors
    ): ConsumptionPrediction {
        if ($historicalData->isEmpty()) {
            return $this->createDefaultPrediction($seasonalFactors, $propertyFactors);
        }

        // Use ensemble of multiple prediction methods
        $predictions = [
            'linear_trend' => $this->linearTrendPrediction($historicalData, $startDate, $endDate),
            'seasonal_decomposition' => $this->seasonalDecompositionPrediction(
                $historicalData,
                $startDate,
                $endDate,
                $seasonalFactors
            ),
            'moving_average' => $this->movingAveragePrediction($historicalData, $seasonalFactors),
            'exponential_smoothing' => $this->exponentialSmoothingPrediction($historicalData),
        ];

        // Weight predictions based on data quality and method reliability
        $weights = $this->calculatePredictionWeights($historicalData, $predictions);
        $ensemblePrediction = $this->combinePredictions($predictions, $weights);

        // Apply property-specific adjustments
        $adjustedPrediction = $this->applyPropertyAdjustments(
            $ensemblePrediction,
            $propertyFactors
        );

        // Calculate confidence interval
        $confidenceInterval = $this->calculateConfidenceInterval(
            $historicalData,
            $adjustedPrediction,
            $predictions
        );

        return new ConsumptionPrediction(
            estimatedConsumption: $adjustedPrediction,
            confidenceInterval: $confidenceInterval,
            confidenceLevel: $this->calculateConfidenceLevel($historicalData, $predictions),
            influencingFactors: $this->identifyInfluencingFactors(
                $seasonalFactors,
                $propertyFactors,
                $weights
            ),
            predictionMethod: 'ensemble',
            metadata: [
                'data_points' => $historicalData->count(),
                'prediction_weights' => $weights,
                'individual_predictions' => $predictions,
                'period_days' => $startDate->diffInDays($endDate),
            ]
        );
    }

    private function linearTrendPrediction(
        Collection $historicalData,
        Carbon $startDate,
        Carbon $endDate
    ): float {
        if ($historicalData->count() < 2) {
            return $historicalData->avg('consumption') ?? 0;
        }

        // Simple linear regression
        $n = $historicalData->count();
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        $historicalData->each(function ($record, $index) use (&$sumX, &$sumY, &$sumXY, &$sumX2) {
            $x = $index + 1;
            $y = $record->consumption;
            
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        });

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;

        // Project to future period
        $futurePeriod = $n + 1;
        return max(0, $slope * $futurePeriod + $intercept);
    }

    private function seasonalDecompositionPrediction(
        Collection $historicalData,
        Carbon $startDate,
        Carbon $endDate,
        array $seasonalFactors
    ): float {
        $baseConsumption = $historicalData->avg('consumption') ?? 0;
        $currentSeason = $this->getSeason($startDate);
        $seasonalMultiplier = $seasonalFactors[$currentSeason] ?? 1.0;

        return $baseConsumption * $seasonalMultiplier;
    }

    private function movingAveragePrediction(
        Collection $historicalData,
        array $seasonalFactors
    ): float {
        $windowSize = min(3, $historicalData->count());
        if ($windowSize === 0) return 0;

        $recentData = $historicalData->take(-$windowSize);
        $average = $recentData->avg('consumption') ?? 0;

        // Apply seasonal adjustment
        $currentSeason = $this->getSeason(now());
        $seasonalMultiplier = $seasonalFactors[$currentSeason] ?? 1.0;

        return $average * $seasonalMultiplier;
    }

    private function exponentialSmoothingPrediction(Collection $historicalData): float
    {
        if ($historicalData->isEmpty()) return 0;

        $alpha = 0.3; // Smoothing parameter
        $forecast = $historicalData->first()->consumption;

        $historicalData->skip(1)->each(function ($record) use (&$forecast, $alpha) {
            $forecast = $alpha * $record->consumption + (1 - $alpha) * $forecast;
        });

        return $forecast;
    }

    private function calculatePredictionWeights(
        Collection $historicalData,
        array $predictions
    ): array {
        $dataPoints = $historicalData->count();
        
        // Base weights - adjust based on data availability and method suitability
        $baseWeights = [
            'linear_trend' => $dataPoints >= 6 ? 0.3 : 0.1,
            'seasonal_decomposition' => $dataPoints >= 12 ? 0.4 : 0.2,
            'moving_average' => 0.2,
            'exponential_smoothing' => 0.1,
        ];

        // Normalize weights to sum to 1
        $totalWeight = array_sum($baseWeights);
        return array_map(fn($weight) => $weight / $totalWeight, $baseWeights);
    }

    private function combinePredictions(array $predictions, array $weights): float
    {
        $weightedSum = 0;
        foreach ($predictions as $method => $prediction) {
            $weightedSum += $prediction * ($weights[$method] ?? 0);
        }
        return $weightedSum;
    }

    private function applyPropertyAdjustments(float $prediction, array $propertyFactors): float
    {
        $adjustment = 1.0;
        
        // Apply area factor
        $adjustment *= $propertyFactors['area_factor'] ?? 1.0;
        
        // Apply occupancy factor
        $adjustment *= $propertyFactors['occupancy_factor'] ?? 1.0;
        
        // Apply floor factor
        $adjustment *= $propertyFactors['floor_factor'] ?? 1.0;
        
        // Apply room factor
        $adjustment *= $propertyFactors['room_factor'] ?? 1.0;

        return $prediction * $adjustment;
    }

    private function calculateConfidenceInterval(
        Collection $historicalData,
        float $prediction,
        array $predictions
    ): array {
        if ($historicalData->count() < 3) {
            // Wide interval for limited data
            return [
                'lower' => $prediction * 0.5,
                'upper' => $prediction * 1.5,
            ];
        }

        // Calculate prediction variance
        $predictionVariance = $this->calculatePredictionVariance($predictions);
        $historicalVariance = $this->calculateHistoricalVariance($historicalData);
        
        $combinedStdDev = sqrt($predictionVariance + $historicalVariance);
        
        // 95% confidence interval (approximately 2 standard deviations)
        return [
            'lower' => max(0, $prediction - 2 * $combinedStdDev),
            'upper' => $prediction + 2 * $combinedStdDev,
        ];
    }

    private function calculateConfidenceLevel(
        Collection $historicalData,
        array $predictions
    ): float {
        $dataPoints = $historicalData->count();
        
        // Base confidence on data availability
        $baseConfidence = match (true) {
            $dataPoints >= 24 => 0.9,
            $dataPoints >= 12 => 0.8,
            $dataPoints >= 6 => 0.7,
            $dataPoints >= 3 => 0.6,
            default => 0.4,
        };

        // Adjust for prediction consistency
        $predictionVariance = $this->calculatePredictionVariance($predictions);
        $consistencyFactor = 1 / (1 + $predictionVariance / 100);

        return min(0.95, $baseConfidence * $consistencyFactor);
    }

    private function identifyInfluencingFactors(
        array $seasonalFactors,
        array $propertyFactors,
        array $weights
    ): array {
        $factors = [];

        // Seasonal influence
        $currentSeason = $this->getSeason(now());
        $seasonalImpact = $seasonalFactors[$currentSeason] ?? 1.0;
        if (abs($seasonalImpact - 1.0) > 0.1) {
            $factors['seasonal'] = [
                'season' => $currentSeason,
                'impact' => $seasonalImpact,
                'description' => "Seasonal adjustment for {$currentSeason}",
            ];
        }

        // Property characteristics
        foreach ($propertyFactors as $factor => $value) {
            if (abs($value - 1.0) > 0.1) {
                $factors[$factor] = [
                    'value' => $value,
                    'impact' => $value > 1.0 ? 'increase' : 'decrease',
                ];
            }
        }

        // Prediction method weights
        $dominantMethod = array_keys($weights, max($weights))[0];
        $factors['prediction_method'] = [
            'primary_method' => $dominantMethod,
            'weight' => $weights[$dominantMethod],
        ];

        return $factors;
    }

    private function createDefaultPrediction(
        array $seasonalFactors,
        array $propertyFactors
    ): ConsumptionPrediction {
        // Default consumption based on property characteristics
        $baseConsumption = 100; // Default base consumption
        
        // Apply property adjustments
        $areaFactor = $propertyFactors['area_factor'] ?? 1.0;
        $occupancyFactor = $propertyFactors['occupancy_factor'] ?? 1.0;
        
        $estimatedConsumption = $baseConsumption * $areaFactor * $occupancyFactor;

        return new ConsumptionPrediction(
            estimatedConsumption: $estimatedConsumption,
            confidenceInterval: [
                'lower' => $estimatedConsumption * 0.3,
                'upper' => $estimatedConsumption * 2.0,
            ],
            confidenceLevel: 0.3,
            influencingFactors: [
                'data_availability' => 'No historical data available',
                'property_factors' => $propertyFactors,
            ],
            predictionMethod: 'default_estimation',
            metadata: ['note' => 'Prediction based on property characteristics only']
        );
    }

    private function getSeason(Carbon $date): string
    {
        $month = $date->month;
        return match (true) {
            in_array($month, [12, 1, 2]) => 'winter',
            in_array($month, [3, 4, 5]) => 'spring',
            in_array($month, [6, 7, 8]) => 'summer',
            in_array($month, [9, 10, 11]) => 'autumn',
        };
    }

    private function calculatePredictionVariance(array $predictions): float
    {
        if (count($predictions) < 2) return 0;

        $mean = array_sum($predictions) / count($predictions);
        $variance = array_sum(array_map(
            fn($prediction) => pow($prediction - $mean, 2),
            $predictions
        )) / count($predictions);

        return $variance;
    }

    private function calculateHistoricalVariance(Collection $historicalData): float
    {
        if ($historicalData->count() < 2) return 0;

        $consumptions = $historicalData->pluck('consumption')->toArray();
        $mean = array_sum($consumptions) / count($consumptions);
        
        $variance = array_sum(array_map(
            fn($consumption) => pow($consumption - $mean, 2),
            $consumptions
        )) / (count($consumptions) - 1);

        return $variance;
    }
}