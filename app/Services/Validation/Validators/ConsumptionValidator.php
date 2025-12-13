<?php

declare(strict_types=1);

namespace App\Services\Validation\Validators;

use App\Services\Validation\ValidationContext;
use App\Services\Validation\ValidationResult;

/**
 * Validates consumption limits and patterns.
 * 
 * Handles consumption validation including limits, variance, and anomaly detection.
 */
final class ConsumptionValidator extends AbstractValidator
{
    private const DEFAULT_MIN_CONSUMPTION = 0;
    private const DEFAULT_MAX_CONSUMPTION = 10000;
    private const DEFAULT_VARIANCE_THRESHOLD = 0.5;
    private const MAX_CONSUMPTION_MULTIPLIER = 5.0;
    private const MIN_CONSUMPTION_THRESHOLD = 0.01;

    public function getName(): string
    {
        return 'consumption';
    }

    public function validate(ValidationContext $context): ValidationResult
    {
        try {
            $consumption = $context->getConsumption();
            
            if ($consumption === null) {
                return ValidationResult::withWarning(
                    'No previous reading available for consumption calculation'
                );
            }

            $limits = $this->getConsumptionLimits($context);
            $errors = [];
            $warnings = [];
            $recommendations = [];

            // Validate absolute limits
            $limitValidation = $this->validateAbsoluteLimits($consumption, $limits, $context);
            $errors = array_merge($errors, $limitValidation['errors']);
            $warnings = array_merge($warnings, $limitValidation['warnings']);

            // Validate variance against historical data
            if ($context->hasHistoricalReadings()) {
                $varianceValidation = $this->validateConsumptionVariance($consumption, $limits, $context);
                $warnings = array_merge($warnings, $varianceValidation['warnings']);
            }

            // Detect anomalies
            if ($context->hasHistoricalReadings()) {
                $anomalyValidation = $this->detectConsumptionAnomalies($consumption, $context);
                $warnings = array_merge($warnings, $anomalyValidation['warnings']);
                $recommendations = array_merge($recommendations, $anomalyValidation['recommendations']);
            }

            $metadata = [
                'rules_applied' => ['consumption_limits', 'variance_check', 'anomaly_detection'],
                'consumption_value' => $consumption,
                'limits_applied' => $limits,
            ];

            if (empty($errors)) {
                return ValidationResult::valid($warnings, $recommendations, $metadata);
            }

            return ValidationResult::invalid($errors, $warnings, $recommendations, $metadata);

        } catch (\Exception $e) {
            return $this->handleException($e, $context);
        }
    }

    private function getConsumptionLimits(ValidationContext $context): array
    {
        $cacheKey = $this->buildCacheKey('limits', $context->serviceConfiguration?->id ?? 'default');
        
        return $this->cache->remember(
            $cacheKey,
            self::CACHE_TTL_SECONDS,
            function () use ($context) {
                $serviceRules = $context->serviceConfiguration?->utilityService?->validation_rules ?? [];
                $consumptionLimits = $serviceRules['consumption_limits'] ?? [];

                return [
                    'min_consumption' => $consumptionLimits['min'] ?? 
                        $this->getConfigValue('service_validation.default_min_consumption', self::DEFAULT_MIN_CONSUMPTION),
                    'max_consumption' => $consumptionLimits['max'] ?? 
                        $this->getConfigValue('service_validation.default_max_consumption', self::DEFAULT_MAX_CONSUMPTION),
                    'variance_threshold' => $consumptionLimits['variance_threshold'] ?? self::DEFAULT_VARIANCE_THRESHOLD,
                ];
            }
        );
    }

    private function validateAbsoluteLimits(float $consumption, array $limits, ValidationContext $context): array
    {
        $errors = [];
        $warnings = [];

        if ($consumption < $limits['min_consumption']) {
            if ($consumption < self::MIN_CONSUMPTION_THRESHOLD) {
                $warnings[] = "Very low consumption detected: {$consumption} {$context->getUnit()}";
            } else {
                $errors[] = "Consumption below minimum limit: {$consumption} < {$limits['min_consumption']} {$context->getUnit()}";
            }
        }

        if ($consumption > $limits['max_consumption']) {
            $errors[] = "Consumption exceeds maximum limit: {$consumption} > {$limits['max_consumption']} {$context->getUnit()}";
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    private function validateConsumptionVariance(float $consumption, array $limits, ValidationContext $context): array
    {
        $warnings = [];
        
        $historicalAverage = $context->historicalReadings
            ->map(fn($reading) => $reading->getConsumption())
            ->filter()
            ->average();

        if ($historicalAverage > 0) {
            $variance = abs($consumption - $historicalAverage) / $historicalAverage;
            
            if ($variance > $limits['variance_threshold']) {
                $percentageChange = round($variance * 100, 1);
                $warnings[] = "Consumption varies significantly from historical average: {$percentageChange}% change";
            }
        }

        return ['warnings' => $warnings];
    }

    private function detectConsumptionAnomalies(float $consumption, ValidationContext $context): array
    {
        $warnings = [];
        $recommendations = [];

        $consumptions = $context->historicalReadings
            ->map(fn($reading) => $reading->getConsumption())
            ->filter();

        if ($consumptions->count() >= 3) {
            $average = $consumptions->average();
            $stdDev = $this->calculateStandardDeviation($consumptions->toArray());

            // Check for outliers (more than 2 standard deviations from mean)
            if ($stdDev > 0 && abs($consumption - $average) > (2 * $stdDev)) {
                $warnings[] = "Consumption appears to be an outlier based on historical patterns";
                $recommendations[] = "Consider verifying this reading manually";
            }

            // Check for extreme values
            if ($consumption > ($average * self::MAX_CONSUMPTION_MULTIPLIER)) {
                $warnings[] = "Consumption is extremely high compared to historical average";
            }
        }

        return ['warnings' => $warnings, 'recommendations' => $recommendations];
    }

    private function calculateStandardDeviation(array $values): float
    {
        $count = count($values);
        if ($count < 2) {
            return 0;
        }
        
        $mean = array_sum($values) / $count;
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $values)) / ($count - 1);
        
        return sqrt($variance);
    }
}