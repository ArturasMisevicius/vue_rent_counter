<?php

declare(strict_types=1);

namespace App\Services\Validation\Validators;

use App\Services\Validation\Contracts\ValidatorInterface;
use App\Services\Validation\ValidationContext;
use App\Services\Validation\ValidationResult;

/**
 * Validates consumption limits and patterns.
 * 
 * Checks meter readings against configured consumption limits,
 * historical patterns, and reasonable usage thresholds.
 */
final class ConsumptionValidator implements ValidatorInterface
{
    public function validate(ValidationContext $context): ValidationResult
    {
        if (!$this->isApplicable($context)) {
            return ValidationResult::valid(metadata: ['validator' => $this->getName(), 'skipped' => 'not_applicable']);
        }

        $consumption = $context->getConsumption();
        $result = ValidationResult::valid();

        // Validate against configured limits
        $result = $this->validateConsumptionLimits($context, $consumption, $result);

        // Validate against historical patterns
        $result = $this->validateHistoricalPatterns($context, $consumption, $result);

        // Validate consumption reasonableness
        $result = $this->validateReasonableness($context, $consumption, $result);

        return $result->addMetadata('validator', $this->getName())
                     ->addMetadata('consumption_validated', $consumption);
    }

    public function getName(): string
    {
        return 'consumption';
    }

    public function isApplicable(ValidationContext $context): bool
    {
        return $context->getConsumption() !== null;
    }

    public function getPriority(): int
    {
        return 100; // High priority - basic consumption validation
    }

    /**
     * Validate consumption against configured limits.
     */
    private function validateConsumptionLimits(
        ValidationContext $context,
        float $consumption,
        ValidationResult $result
    ): ValidationResult {
        $config = $context->serviceConfiguration?->getMergedConfiguration() ?? [];
        $validationConfig = $context->validationConfig;

        // Get consumption limits from service configuration or defaults
        $minConsumption = $config['consumption_limits']['min'] 
            ?? $validationConfig['default_min_consumption'] 
            ?? 0;

        $maxConsumption = $config['consumption_limits']['max'] 
            ?? $validationConfig['default_max_consumption'] 
            ?? 10000;

        // Validate minimum consumption
        if ($consumption < $minConsumption) {
            $result = $result->addError(
                __('validation.consumption_below_minimum', [
                    'consumption' => $consumption,
                    'minimum' => $minConsumption,
                    'unit' => $context->getUtilityService()?->unit_of_measurement ?? 'units'
                ])
            );
        }

        // Validate maximum consumption
        if ($consumption > $maxConsumption) {
            $result = $result->addError(
                __('validation.consumption_exceeds_maximum', [
                    'consumption' => $consumption,
                    'maximum' => $maxConsumption,
                    'unit' => $context->getUtilityService()?->unit_of_measurement ?? 'units'
                ])
            );
        }

        return $result;
    }

    /**
     * Validate consumption against historical patterns.
     */
    private function validateHistoricalPatterns(
        ValidationContext $context,
        float $consumption,
        ValidationResult $result
    ): ValidationResult {
        if (!$context->hasHistoricalData()) {
            return $result->addWarning(__('validation.insufficient_historical_data'));
        }

        $historicalAverage = $context->getHistoricalAverage();
        if ($historicalAverage === null) {
            return $result;
        }

        $varianceThreshold = $context->getValidationConfig('data_quality.consumption_variance_threshold', 0.5);
        $variance = abs($consumption - $historicalAverage) / $historicalAverage;

        if ($variance > $varianceThreshold) {
            $percentageChange = round($variance * 100, 1);
            
            if ($variance > 1.0) { // 100% variance is an error
                $result = $result->addError(
                    __('validation.consumption_extreme_variance', [
                        'consumption' => $consumption,
                        'average' => round($historicalAverage, 2),
                        'variance' => $percentageChange,
                        'unit' => $context->getUtilityService()?->unit_of_measurement ?? 'units'
                    ])
                );
            } else {
                $result = $result->addWarning(
                    __('validation.consumption_high_variance', [
                        'consumption' => $consumption,
                        'average' => round($historicalAverage, 2),
                        'variance' => $percentageChange,
                        'unit' => $context->getUtilityService()?->unit_of_measurement ?? 'units'
                    ])
                );

                // Add recommendations for high variance
                if ($consumption > $historicalAverage * 1.5) {
                    $result = $result->addRecommendation(__('validation.check_for_leaks_or_issues'));
                } elseif ($consumption < $historicalAverage * 0.5) {
                    $result = $result->addRecommendation(__('validation.verify_meter_functionality'));
                }
            }
        }

        return $result->addMetadata('historical_average', $historicalAverage)
                     ->addMetadata('variance_percentage', round($variance * 100, 2));
    }

    /**
     * Validate consumption reasonableness based on property and service type.
     */
    private function validateReasonableness(
        ValidationContext $context,
        float $consumption,
        ValidationResult $result
    ): ValidationResult {
        // Zero or negative consumption check
        if ($consumption <= 0) {
            $result = $result->addWarning(__('validation.zero_or_negative_consumption'));
        }

        // Extremely high consumption check (5x historical average or absolute threshold)
        $maxMultiplier = $context->getValidationConfig('data_quality.max_consumption_multiplier', 5.0);
        $historicalAverage = $context->getHistoricalAverage();
        
        if ($historicalAverage && $consumption > ($historicalAverage * $maxMultiplier)) {
            $result = $result->addError(
                __('validation.consumption_unreasonably_high', [
                    'consumption' => $consumption,
                    'threshold' => round($historicalAverage * $maxMultiplier, 2),
                    'unit' => $context->getUtilityService()?->unit_of_measurement ?? 'units'
                ])
            );
        }

        // Service-specific reasonableness checks
        $serviceType = $context->getUtilityService()?->service_type_bridge?->value;
        $result = $this->validateServiceSpecificLimits($context, $consumption, $result, $serviceType);

        return $result;
    }

    /**
     * Validate service-specific consumption limits.
     */
    private function validateServiceSpecificLimits(
        ValidationContext $context,
        float $consumption,
        ValidationResult $result,
        ?string $serviceType
    ): ValidationResult {
        return match ($serviceType) {
            'electricity' => $this->validateElectricityConsumption($context, $consumption, $result),
            'water' => $this->validateWaterConsumption($context, $consumption, $result),
            'heating' => $this->validateHeatingConsumption($context, $consumption, $result),
            default => $result,
        };
    }

    /**
     * Validate electricity consumption patterns.
     */
    private function validateElectricityConsumption(
        ValidationContext $context,
        float $consumption,
        ValidationResult $result
    ): ValidationResult {
        // Typical residential electricity consumption: 200-800 kWh/month
        if ($consumption > 1000) {
            $result = $result->addWarning(__('validation.electricity_consumption_very_high'));
        } elseif ($consumption < 50) {
            $result = $result->addWarning(__('validation.electricity_consumption_very_low'));
        }

        return $result;
    }

    /**
     * Validate water consumption patterns.
     */
    private function validateWaterConsumption(
        ValidationContext $context,
        float $consumption,
        ValidationResult $result
    ): ValidationResult {
        // Typical residential water consumption: 10-30 m³/month
        if ($consumption > 50) {
            $result = $result->addWarning(__('validation.water_consumption_very_high'));
        } elseif ($consumption < 2) {
            $result = $result->addWarning(__('validation.water_consumption_very_low'));
        }

        return $result;
    }

    /**
     * Validate heating consumption patterns (seasonal thresholds).
     */
    private function validateHeatingConsumption(
        ValidationContext $context,
        float $consumption,
        ValidationResult $result
    ): ValidationResult {
        // Use seasonal context for heating validation
        if ($context->isSummerPeriod() && $consumption > 100) {
            $result = $result->addWarning(__('validation.heating_consumption_high_in_summer'));
        } elseif ($context->isWinterPeriod() && $consumption < 50) {
            $result = $result->addWarning(__('validation.heating_consumption_low_in_winter'));
        }

        return $result;
    }
}
