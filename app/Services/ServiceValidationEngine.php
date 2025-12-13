<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InvalidMeterReadingException;
use App\Exceptions\ServiceConfigurationException;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\ServiceConfiguration;
use App\Models\Tariff;
use Carbon\Carbon;

/**
 * Service Validation Engine
 * 
 * Defines consumption limits and validation rules extending existing meter reading validation.
 * Supports rate change restrictions using existing tariff active date functionality.
 * Includes seasonal adjustments building on gyvatukas summer/winter logic.
 * Implements data quality checks leveraging existing meter reading audit trail.
 * 
 * Requirements: 11.1, 11.2, 11.3, 11.4, 11.5
 * 
 * @package App\Services
 */
final class ServiceValidationEngine
{
    public function __construct(
        private readonly MeterReadingService $meterReadingService,
    ) {}

    /**
     * Validate service configuration against business rules.
     *
     * @param ServiceConfiguration $configuration
     * @return array Validation errors (empty if valid)
     */
    public function validateServiceConfiguration(ServiceConfiguration $configuration): array
    {
        $errors = [];

        // Validate consumption limits
        $limitErrors = $this->validateConsumptionLimits($configuration);
        $errors = array_merge($errors, $limitErrors);

        // Validate rate change restrictions
        $rateErrors = $this->validateRateChangeRestrictions($configuration);
        $errors = array_merge($errors, $rateErrors);

        // Validate seasonal adjustments
        $seasonalErrors = $this->validateSeasonalAdjustments($configuration);
        $errors = array_merge($errors, $seasonalErrors);

        // Validate business logic configuration
        $businessLogicErrors = $this->validateBusinessLogic($configuration);
        $errors = array_merge($errors, $businessLogicErrors);

        return $errors;
    }

    /**
     * Validate meter reading against service configuration rules.
     *
     * @param MeterReading $reading
     * @param ServiceConfiguration $configuration
     * @throws InvalidMeterReadingException
     */
    public function validateMeterReading(
        MeterReading $reading,
        ServiceConfiguration $configuration
    ): void {
        // Get validation rules from configuration
        $validationRules = $configuration->utilityService->validation_rules ?? [];

        // Validate consumption limits
        $this->validateReadingConsumptionLimits($reading, $validationRules);

        // Validate reading frequency
        $this->validateReadingFrequency($reading, $validationRules);

        // Validate data quality
        $this->validateDataQuality($reading, $validationRules);

        // Validate seasonal expectations
        $this->validateSeasonalExpectations($reading, $configuration);
    }

    /**
     * Validate consumption limits for configuration.
     *
     * @param ServiceConfiguration $configuration
     * @return array Validation errors
     */
    private function validateConsumptionLimits(ServiceConfiguration $configuration): array
    {
        $errors = [];
        $validationRules = $configuration->utilityService->validation_rules ?? [];

        // Check if consumption limits are defined
        if (isset($validationRules['consumption_limits'])) {
            $limits = $validationRules['consumption_limits'];

            // Validate minimum consumption threshold
            if (isset($limits['min_monthly']) && $limits['min_monthly'] < 0) {
                $errors[] = 'Minimum monthly consumption cannot be negative';
            }

            // Validate maximum consumption threshold
            if (isset($limits['max_monthly']) && isset($limits['min_monthly'])) {
                if ($limits['max_monthly'] < $limits['min_monthly']) {
                    $errors[] = 'Maximum monthly consumption must be greater than minimum';
                }
            }

            // Validate daily limits
            if (isset($limits['max_daily']) && $limits['max_daily'] < 0) {
                $errors[] = 'Maximum daily consumption cannot be negative';
            }
        }

        return $errors;
    }

    /**
     * Validate rate change restrictions.
     *
     * @param ServiceConfiguration $configuration
     * @return array Validation errors
     */
    private function validateRateChangeRestrictions(ServiceConfiguration $configuration): array
    {
        $errors = [];

        // If tariff is linked, validate rate change restrictions
        if ($configuration->tariff_id) {
            $tariff = Tariff::find($configuration->tariff_id);
            
            if ($tariff) {
                // Check if tariff is active for the configuration period
                if ($configuration->effective_from < $tariff->active_from) {
                    $errors[] = 'Configuration effective date is before tariff active date';
                }

                if ($tariff->active_until && $configuration->effective_from > $tariff->active_until) {
                    $errors[] = 'Configuration effective date is after tariff expiration';
                }

                // Validate rate schedule matches tariff type
                $rateSchedule = $configuration->rate_schedule ?? [];
                if (empty($rateSchedule)) {
                    $errors[] = 'Rate schedule is required when tariff is linked';
                }
            }
        }

        // Validate rate change frequency restrictions
        $validationRules = $configuration->utilityService->validation_rules ?? [];
        if (isset($validationRules['rate_change_restrictions'])) {
            $restrictions = $validationRules['rate_change_restrictions'];
            
            // Check minimum days between rate changes
            if (isset($restrictions['min_days_between_changes'])) {
                $minDays = $restrictions['min_days_between_changes'];
                
                // Find previous configuration for same property and service
                $previousConfig = ServiceConfiguration::where('property_id', $configuration->property_id)
                    ->where('utility_service_id', $configuration->utility_service_id)
                    ->where('id', '!=', $configuration->id)
                    ->orderBy('effective_from', 'desc')
                    ->first();

                if ($previousConfig) {
                    $daysDiff = $configuration->effective_from->diffInDays($previousConfig->effective_from);
                    if ($daysDiff < $minDays) {
                        $errors[] = "Rate changes must be at least {$minDays} days apart";
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Validate seasonal adjustments.
     *
     * @param ServiceConfiguration $configuration
     * @return array Validation errors
     */
    private function validateSeasonalAdjustments(ServiceConfiguration $configuration): array
    {
        $errors = [];
        $businessLogic = $configuration->utilityService->business_logic_config ?? [];

        // Check if seasonal adjustments are configured
        if (isset($businessLogic['seasonal_adjustments'])) {
            $adjustments = $businessLogic['seasonal_adjustments'];

            // Validate summer period
            if (isset($adjustments['summer_period'])) {
                $summer = $adjustments['summer_period'];
                
                if (!isset($summer['start_month']) || !isset($summer['end_month'])) {
                    $errors[] = 'Summer period must define start_month and end_month';
                }

                if (isset($summer['start_month']) && ($summer['start_month'] < 1 || $summer['start_month'] > 12)) {
                    $errors[] = 'Summer start_month must be between 1 and 12';
                }

                if (isset($summer['end_month']) && ($summer['end_month'] < 1 || $summer['end_month'] > 12)) {
                    $errors[] = 'Summer end_month must be between 1 and 12';
                }
            }

            // Validate winter period
            if (isset($adjustments['winter_period'])) {
                $winter = $adjustments['winter_period'];
                
                if (!isset($winter['start_month']) || !isset($winter['end_month'])) {
                    $errors[] = 'Winter period must define start_month and end_month';
                }
            }

            // Validate adjustment factors
            if (isset($adjustments['factors'])) {
                foreach ($adjustments['factors'] as $season => $factor) {
                    if (!is_numeric($factor) || $factor < 0) {
                        $errors[] = "Seasonal adjustment factor for {$season} must be a positive number";
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Validate business logic configuration.
     *
     * @param ServiceConfiguration $configuration
     * @return array Validation errors
     */
    private function validateBusinessLogic(ServiceConfiguration $configuration): array
    {
        $errors = [];
        $businessLogic = $configuration->utilityService->business_logic_config ?? [];

        // Validate conditional pricing rules
        if (isset($businessLogic['conditional_pricing'])) {
            $rules = $businessLogic['conditional_pricing'];
            
            foreach ($rules as $rule) {
                if (!isset($rule['condition']) || !isset($rule['action'])) {
                    $errors[] = 'Conditional pricing rules must define condition and action';
                }
            }
        }

        // Validate automatic adjustments
        if (isset($businessLogic['automatic_adjustments'])) {
            $adjustments = $businessLogic['automatic_adjustments'];
            
            foreach ($adjustments as $adjustment) {
                if (!isset($adjustment['trigger']) || !isset($adjustment['adjustment_type'])) {
                    $errors[] = 'Automatic adjustments must define trigger and adjustment_type';
                }
            }
        }

        return $errors;
    }

    /**
     * Validate reading consumption limits.
     *
     * @param MeterReading $reading
     * @param array $validationRules
     * @throws InvalidMeterReadingException
     */
    private function validateReadingConsumptionLimits(
        MeterReading $reading,
        array $validationRules
    ): void {
        if (!isset($validationRules['consumption_limits'])) {
            return;
        }

        $limits = $validationRules['consumption_limits'];
        
        // Calculate consumption
        $previousReading = $this->meterReadingService->getPreviousReading(
            $reading->meter,
            null,
            $reading->reading_date->toDateString()
        );

        if (!$previousReading) {
            return; // Can't validate without previous reading
        }

        $consumption = $reading->value - $previousReading->value;
        $daysDiff = $reading->reading_date->diffInDays($previousReading->reading_date);

        // Validate daily consumption
        if (isset($limits['max_daily']) && $daysDiff > 0) {
            $dailyConsumption = $consumption / $daysDiff;
            
            if ($dailyConsumption > $limits['max_daily']) {
                throw new InvalidMeterReadingException(
                    "Daily consumption ({$dailyConsumption}) exceeds maximum allowed ({$limits['max_daily']})"
                );
            }
        }

        // Validate minimum consumption (detect potential meter issues)
        if (isset($limits['min_daily']) && $daysDiff > 0) {
            $dailyConsumption = $consumption / $daysDiff;
            
            if ($dailyConsumption < $limits['min_daily'] && $consumption > 0) {
                // Log warning but don't reject
                activity()
                    ->performedOn($reading)
                    ->withProperties([
                        'daily_consumption' => $dailyConsumption,
                        'min_expected' => $limits['min_daily'],
                    ])
                    ->log('low_consumption_detected');
            }
        }
    }

    /**
     * Validate reading frequency.
     *
     * @param MeterReading $reading
     * @param array $validationRules
     * @throws InvalidMeterReadingException
     */
    private function validateReadingFrequency(
        MeterReading $reading,
        array $validationRules
    ): void {
        if (!isset($validationRules['reading_frequency'])) {
            return;
        }

        $frequency = $validationRules['reading_frequency'];
        
        // Get last reading
        $lastReading = $this->meterReadingService->getPreviousReading(
            $reading->meter,
            null,
            $reading->reading_date->toDateString()
        );

        if (!$lastReading) {
            return; // First reading, no frequency to validate
        }

        $daysSinceLastReading = $reading->reading_date->diffInDays($lastReading->reading_date);

        // Validate minimum frequency
        if (isset($frequency['min_days'])) {
            if ($daysSinceLastReading < $frequency['min_days']) {
                throw new InvalidMeterReadingException(
                    "Readings must be at least {$frequency['min_days']} days apart"
                );
            }
        }

        // Validate maximum frequency (warn if too long)
        if (isset($frequency['max_days'])) {
            if ($daysSinceLastReading > $frequency['max_days']) {
                // Log warning but don't reject
                activity()
                    ->performedOn($reading)
                    ->withProperties([
                        'days_since_last_reading' => $daysSinceLastReading,
                        'max_expected' => $frequency['max_days'],
                    ])
                    ->log('reading_frequency_exceeded');
            }
        }
    }

    /**
     * Validate data quality.
     *
     * @param MeterReading $reading
     * @param array $validationRules
     * @throws InvalidMeterReadingException
     */
    private function validateDataQuality(
        MeterReading $reading,
        array $validationRules
    ): void {
        if (!isset($validationRules['data_quality'])) {
            return;
        }

        $qualityRules = $validationRules['data_quality'];

        // Validate reading value precision
        if (isset($qualityRules['max_decimal_places'])) {
            $decimalPlaces = strlen(substr(strrchr((string) $reading->value, '.'), 1));
            
            if ($decimalPlaces > $qualityRules['max_decimal_places']) {
                throw new InvalidMeterReadingException(
                    "Reading value has too many decimal places (max: {$qualityRules['max_decimal_places']})"
                );
            }
        }

        // Validate reading is not estimated when actual is required
        if (isset($qualityRules['require_actual']) && $qualityRules['require_actual']) {
            if ($reading->is_estimated) {
                throw new InvalidMeterReadingException(
                    'Estimated readings are not allowed for this service'
                );
            }
        }

        // Validate photo evidence if required
        if (isset($qualityRules['require_photo']) && $qualityRules['require_photo']) {
            if (empty($reading->photo_path)) {
                throw new InvalidMeterReadingException(
                    'Photo evidence is required for this service'
                );
            }
        }
    }

    /**
     * Validate seasonal expectations.
     *
     * @param MeterReading $reading
     * @param ServiceConfiguration $configuration
     */
    private function validateSeasonalExpectations(
        MeterReading $reading,
        ServiceConfiguration $configuration
    ): void {
        $businessLogic = $configuration->utilityService->business_logic_config ?? [];

        if (!isset($businessLogic['seasonal_adjustments'])) {
            return;
        }

        $adjustments = $businessLogic['seasonal_adjustments'];
        $currentMonth = $reading->reading_date->month;

        // Determine current season
        $season = $this->determineSeason($currentMonth, $adjustments);

        // Get expected consumption range for season
        if (isset($adjustments['expected_ranges'][$season])) {
            $expectedRange = $adjustments['expected_ranges'][$season];
            
            // Calculate consumption
            $previousReading = $this->meterReadingService->getPreviousReading(
                $reading->meter,
                null,
                $reading->reading_date->toDateString()
            );

            if ($previousReading) {
                $consumption = $reading->value - $previousReading->value;
                $daysDiff = $reading->reading_date->diffInDays($previousReading->reading_date);
                
                if ($daysDiff > 0) {
                    $dailyConsumption = $consumption / $daysDiff;
                    
                    // Check if consumption is outside expected range
                    if (isset($expectedRange['min']) && $dailyConsumption < $expectedRange['min']) {
                        activity()
                            ->performedOn($reading)
                            ->withProperties([
                                'season' => $season,
                                'daily_consumption' => $dailyConsumption,
                                'expected_min' => $expectedRange['min'],
                            ])
                            ->log('consumption_below_seasonal_expectation');
                    }

                    if (isset($expectedRange['max']) && $dailyConsumption > $expectedRange['max']) {
                        activity()
                            ->performedOn($reading)
                            ->withProperties([
                                'season' => $season,
                                'daily_consumption' => $dailyConsumption,
                                'expected_max' => $expectedRange['max'],
                            ])
                            ->log('consumption_above_seasonal_expectation');
                    }
                }
            }
        }
    }

    /**
     * Determine season based on month and configuration.
     *
     * @param int $month
     * @param array $adjustments
     * @return string
     */
    private function determineSeason(int $month, array $adjustments): string
    {
        // Check summer period
        if (isset($adjustments['summer_period'])) {
            $summer = $adjustments['summer_period'];
            $startMonth = $summer['start_month'] ?? 5;
            $endMonth = $summer['end_month'] ?? 9;
            
            if ($month >= $startMonth && $month <= $endMonth) {
                return 'summer';
            }
        }

        // Check winter period
        if (isset($adjustments['winter_period'])) {
            $winter = $adjustments['winter_period'];
            $startMonth = $winter['start_month'] ?? 10;
            $endMonth = $winter['end_month'] ?? 4;
            
            // Handle winter spanning year boundary
            if ($startMonth > $endMonth) {
                if ($month >= $startMonth || $month <= $endMonth) {
                    return 'winter';
                }
            } else {
                if ($month >= $startMonth && $month <= $endMonth) {
                    return 'winter';
                }
            }
        }

        return 'standard';
    }
}
