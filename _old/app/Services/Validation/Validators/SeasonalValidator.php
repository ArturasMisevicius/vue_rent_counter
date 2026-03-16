<?php

declare(strict_types=1);

namespace App\Services\Validation\Validators;

use App\Services\Validation\Contracts\ValidatorInterface;
use App\Services\Validation\ValidationContext;
use App\Services\Validation\ValidationResult;

/**
 * Validates seasonal consumption patterns.
 * 
 * Validates consumption against expected seasonal patterns for different utility types.
 */
final class SeasonalValidator implements ValidatorInterface
{
    public function validate(ValidationContext $context): ValidationResult
    {
        if (!$this->isApplicable($context)) {
            return ValidationResult::valid(metadata: ['validator' => $this->getName(), 'skipped' => 'not_applicable']);
        }

        $consumption = $context->getConsumption();
        $serviceType = $context->getUtilityService()?->service_type_bridge?->value;
        
        $result = ValidationResult::valid();

        // Apply service-specific seasonal validation
        $result = match ($serviceType) {
            'heating' => $this->validateHeatingSeasonalPattern($context, $consumption, $result),
            'electricity' => $this->validateElectricitySeasonalPattern($context, $consumption, $result),
            'water' => $this->validateWaterSeasonalPattern($context, $consumption, $result),
            default => $this->validateDefaultSeasonalPattern($context, $consumption, $result),
        };

        return $result->addMetadata('validator', $this->getName())
                     ->addMetadata('season', $this->getCurrentSeason($context))
                     ->addMetadata('service_type', $serviceType);
    }

    public function getName(): string
    {
        return 'seasonal';
    }

    public function isApplicable(ValidationContext $context): bool
    {
        return $context->getConsumption() !== null 
            && $context->getUtilityService() !== null;
    }

    public function getPriority(): int
    {
        return 80; // Medium-high priority - seasonal patterns are important
    }

    /**
     * Validate heating consumption seasonal patterns.
     */
    private function validateHeatingSeasonalPattern(
        ValidationContext $context,
        float $consumption,
        ValidationResult $result
    ): ValidationResult {
        $seasonalConfig = $context->getSeasonalConfig();
        $heatingConfig = $seasonalConfig['heating'] ?? [];

        if ($context->isSummerPeriod()) {
            // Summer heating should be minimal
            $summerMaxThreshold = $heatingConfig['summer_max_threshold'] ?? 50;
            
            if ($consumption > $summerMaxThreshold) {
                $result = $result->addWarning(
                    __('validation.heating_consumption_high_summer', [
                        'consumption' => $consumption,
                        'threshold' => $summerMaxThreshold,
                        'unit' => $context->getUtilityService()?->unit_of_measurement ?? 'kWh'
                    ])
                );
                
                $result = $result->addRecommendation(__('validation.check_heating_system_summer'));
            }
        } elseif ($context->isWinterPeriod()) {
            // Winter heating should meet minimum threshold
            $winterMinThreshold = $heatingConfig['winter_min_threshold'] ?? 100;
            
            if ($consumption < $winterMinThreshold) {
                $result = $result->addWarning(
                    __('validation.heating_consumption_low_winter', [
                        'consumption' => $consumption,
                        'threshold' => $winterMinThreshold,
                        'unit' => $context->getUtilityService()?->unit_of_measurement ?? 'kWh'
                    ])
                );
                
                $result = $result->addRecommendation(__('validation.check_heating_efficiency_winter'));
            }

            // Check for peak winter consumption
            $peakMultiplier = $heatingConfig['peak_winter_multiplier'] ?? 1.5;
            $historicalAverage = $context->getHistoricalAverage();
            
            if ($historicalAverage && $consumption > ($historicalAverage * $peakMultiplier)) {
                $result = $result->addWarning(
                    __('validation.heating_consumption_peak_winter', [
                        'consumption' => $consumption,
                        'expected_max' => round($historicalAverage * $peakMultiplier, 2),
                        'unit' => $context->getUtilityService()?->unit_of_measurement ?? 'kWh'
                    ])
                );
            }
        } else {
            // Shoulder season (spring/fall)
            $shoulderMultiplier = $heatingConfig['shoulder_season_multiplier'] ?? 1.2;
            $historicalAverage = $context->getHistoricalAverage();
            
            if ($historicalAverage && $consumption > ($historicalAverage * $shoulderMultiplier)) {
                $result = $result->addWarning(
                    __('validation.heating_consumption_high_shoulder', [
                        'consumption' => $consumption,
                        'expected_max' => round($historicalAverage * $shoulderMultiplier, 2),
                        'unit' => $context->getUtilityService()?->unit_of_measurement ?? 'kWh'
                    ])
                );
            }
        }

        return $result;
    }

    /**
     * Validate electricity consumption seasonal patterns.
     */
    private function validateElectricitySeasonalPattern(
        ValidationContext $context,
        float $consumption,
        ValidationResult $result
    ): ValidationResult {
        $seasonalConfig = $context->getSeasonalConfig();
        $electricityConfig = $seasonalConfig['electricity'] ?? [];

        $season = $this->getCurrentSeason($context);
        $seasonRange = $electricityConfig["{$season}_range"] ?? null;

        if ($seasonRange) {
            $minExpected = $seasonRange['min'] ?? 0;
            $maxExpected = $seasonRange['max'] ?? PHP_FLOAT_MAX;

            if ($consumption < $minExpected) {
                $result = $result->addWarning(
                    __('validation.electricity_consumption_low_season', [
                        'consumption' => $consumption,
                        'season' => $season,
                        'minimum' => $minExpected,
                        'unit' => $context->getUtilityService()?->unit_of_measurement ?? 'kWh'
                    ])
                );
            } elseif ($consumption > $maxExpected) {
                $result = $result->addWarning(
                    __('validation.electricity_consumption_high_season', [
                        'consumption' => $consumption,
                        'season' => $season,
                        'maximum' => $maxExpected,
                        'unit' => $context->getUtilityService()?->unit_of_measurement ?? 'kWh'
                    ])
                );

                // Add recommendation for high electricity usage
                if ($context->isWinterPeriod()) {
                    $result = $result->addRecommendation(__('validation.check_heating_efficiency'));
                } elseif ($context->isSummerPeriod()) {
                    $result = $result->addRecommendation(__('validation.check_cooling_efficiency'));
                }
            }
        }

        // Check for heating season multiplier effect
        if ($context->isWinterPeriod()) {
            $heatingMultiplier = $electricityConfig['heating_season_multiplier'] ?? 1.3;
            $historicalAverage = $context->getHistoricalAverage();
            
            if ($historicalAverage && $consumption > ($historicalAverage * $heatingMultiplier)) {
                $result = $result->addWarning(
                    __('validation.electricity_heating_season_high', [
                        'consumption' => $consumption,
                        'expected_max' => round($historicalAverage * $heatingMultiplier, 2),
                        'unit' => $context->getUtilityService()?->unit_of_measurement ?? 'kWh'
                    ])
                );
            }
        }

        return $result;
    }

    /**
     * Validate water consumption seasonal patterns.
     */
    private function validateWaterSeasonalPattern(
        ValidationContext $context,
        float $consumption,
        ValidationResult $result
    ): ValidationResult {
        $seasonalConfig = $context->getSeasonalConfig();
        $waterConfig = $seasonalConfig['water'] ?? [];

        $season = $this->getCurrentSeason($context);
        $seasonRange = $waterConfig["{$season}_range"] ?? null;

        if ($seasonRange) {
            $minExpected = $seasonRange['min'] ?? 0;
            $maxExpected = $seasonRange['max'] ?? PHP_FLOAT_MAX;

            if ($consumption < $minExpected) {
                $result = $result->addWarning(
                    __('validation.water_consumption_low_season', [
                        'consumption' => $consumption,
                        'season' => $season,
                        'minimum' => $minExpected,
                        'unit' => $context->getUtilityService()?->unit_of_measurement ?? 'm³'
                    ])
                );
            } elseif ($consumption > $maxExpected) {
                $result = $result->addWarning(
                    __('validation.water_consumption_high_season', [
                        'consumption' => $consumption,
                        'season' => $season,
                        'maximum' => $maxExpected,
                        'unit' => $context->getUtilityService()?->unit_of_measurement ?? 'm³'
                    ])
                );

                $result = $result->addRecommendation(__('validation.check_for_water_leaks'));
            }
        }

        // Check seasonal variance threshold
        $varianceThreshold = $waterConfig['seasonal_variance_threshold'] ?? 0.3;
        $historicalAverage = $context->getHistoricalAverage();
        
        if ($historicalAverage) {
            $variance = abs($consumption - $historicalAverage) / $historicalAverage;
            
            if ($variance > $varianceThreshold) {
                $result = $result->addWarning(
                    __('validation.water_seasonal_variance_high', [
                        'consumption' => $consumption,
                        'average' => round($historicalAverage, 2),
                        'variance' => round($variance * 100, 1),
                        'unit' => $context->getUtilityService()?->unit_of_measurement ?? 'm³'
                    ])
                );
            }
        }

        return $result;
    }

    /**
     * Validate default seasonal patterns for unknown utility types.
     */
    private function validateDefaultSeasonalPattern(
        ValidationContext $context,
        float $consumption,
        ValidationResult $result
    ): ValidationResult {
        $seasonalConfig = $context->getSeasonalConfig();
        $defaultConfig = $seasonalConfig['default'] ?? [];

        $varianceThreshold = $defaultConfig['variance_threshold'] ?? 0.3;
        $adjustmentFactor = $defaultConfig['seasonal_adjustment_factor'] ?? 1.1;
        
        $historicalAverage = $context->getHistoricalAverage();
        
        if ($historicalAverage) {
            $expectedRange = $historicalAverage * $adjustmentFactor;
            
            if ($consumption > $expectedRange) {
                $result = $result->addWarning(
                    __('validation.consumption_above_seasonal_expectation', [
                        'consumption' => $consumption,
                        'expected_max' => round($expectedRange, 2),
                        'unit' => $context->getUtilityService()?->unit_of_measurement ?? 'units'
                    ])
                );
            }

            // Check general variance
            $variance = abs($consumption - $historicalAverage) / $historicalAverage;
            
            if ($variance > $varianceThreshold) {
                $result = $result->addWarning(
                    __('validation.seasonal_variance_detected', [
                        'consumption' => $consumption,
                        'average' => round($historicalAverage, 2),
                        'variance' => round($variance * 100, 1),
                        'unit' => $context->getUtilityService()?->unit_of_measurement ?? 'units'
                    ])
                );
            }
        }

        return $result;
    }

    /**
     * Get the current season based on reading date.
     */
    private function getCurrentSeason(ValidationContext $context): string
    {
        if ($context->isSummerPeriod()) {
            return 'summer';
        } elseif ($context->isWinterPeriod()) {
            return 'winter';
        } else {
            return 'shoulder'; // Spring/Fall
        }
    }
}
