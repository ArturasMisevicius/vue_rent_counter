<?php

declare(strict_types=1);

namespace App\Services\Validation\Validators;

use App\Services\Validation\Contracts\ValidatorInterface;
use App\Services\Validation\ValidationContext;
use App\Services\Validation\ValidationResult;
use App\Models\MeterReading;

/**
 * Validates data quality and integrity.
 * 
 * Performs comprehensive data quality checks including anomaly detection,
 * duplicate detection, and reading sequence validation.
 */
final class DataQualityValidator implements ValidatorInterface
{
    public function validate(ValidationContext $context): ValidationResult
    {
        $result = ValidationResult::valid();

        // Validate reading sequence
        $result = $this->validateReadingSequence($context, $result);

        // Detect anomalies using statistical methods
        $result = $this->detectAnomalies($context, $result);

        // Check for duplicate readings
        $result = $this->checkDuplicateReadings($context, $result);

        // Validate reading structure
        $result = $this->validateReadingStructure($context, $result);

        // Validate audit trail consistency
        $result = $this->validateAuditTrail($context, $result);

        return $result->addMetadata('validator', $this->getName());
    }

    public function getName(): string
    {
        return 'data_quality';
    }

    public function isApplicable(ValidationContext $context): bool
    {
        return true; // Always applicable for data quality checks
    }

    public function getPriority(): int
    {
        return 90; // High priority - data quality is fundamental
    }

    /**
     * Validate reading sequence and progression.
     */
    private function validateReadingSequence(ValidationContext $context, ValidationResult $result): ValidationResult
    {
        if (!$context->getValidationConfig('data_quality.reading_sequence_validation', true)) {
            return $result;
        }

        $currentReading = $context->reading;
        $previousReading = $context->previousReading;

        if (!$previousReading) {
            return $result->addWarning(__('validation.no_previous_reading_for_sequence'));
        }

        // Check if current reading is less than previous (meter rollover or error)
        if ($currentReading->getEffectiveValue() < $previousReading->getEffectiveValue()) {
            // Check if this could be a legitimate meter rollover
            $maxMeterValue = $this->getMaxMeterValue($context);
            $rolloverThreshold = $maxMeterValue * 0.9; // 90% of max value

            if ($previousReading->getEffectiveValue() > $rolloverThreshold) {
                $result = $result->addWarning(
                    __('validation.possible_meter_rollover', [
                        'current' => $currentReading->getEffectiveValue(),
                        'previous' => $previousReading->getEffectiveValue(),
                        'unit' => $context->getUtilityService()?->unit_of_measurement ?? 'units'
                    ])
                );
                $result = $result->addRecommendation(__('validation.verify_meter_rollover'));
            } else {
                $result = $result->addError(
                    __('validation.reading_sequence_invalid', [
                        'current' => $currentReading->getEffectiveValue(),
                        'previous' => $previousReading->getEffectiveValue(),
                        'unit' => $context->getUtilityService()?->unit_of_measurement ?? 'units'
                    ])
                );
            }
        }

        // Check reading date sequence
        if ($currentReading->reading_date <= $previousReading->reading_date) {
            $result = $result->addError(
                __('validation.reading_date_sequence_invalid', [
                    'current_date' => $currentReading->reading_date->format('Y-m-d'),
                    'previous_date' => $previousReading->reading_date->format('Y-m-d')
                ])
            );
        }

        return $result;
    }

    /**
     * Detect statistical anomalies in consumption patterns.
     */
    private function detectAnomalies(ValidationContext $context, ValidationResult $result): ValidationResult
    {
        if (!$context->hasHistoricalData()) {
            return $result;
        }

        $consumption = $context->getConsumption();
        if ($consumption === null) {
            return $result;
        }

        $historicalReadings = $context->historicalReadings;
        $consumptions = $historicalReadings
            ->map(fn($reading) => $reading->getConsumption())
            ->filter(fn($consumption) => $consumption !== null && $consumption > 0)
            ->values();

        if ($consumptions->count() < 3) {
            return $result->addWarning(__('validation.insufficient_data_for_anomaly_detection'));
        }

        // Calculate statistical measures
        $mean = $consumptions->avg();
        $stdDev = $this->calculateStandardDeviation($consumptions->toArray(), $mean);
        
        if ($stdDev == 0) {
            return $result; // No variance in historical data
        }

        // Z-score anomaly detection
        $zScore = abs(($consumption - $mean) / $stdDev);
        $anomalyThreshold = $context->getValidationConfig('data_quality.anomaly_detection_threshold', 2.0);

        if ($zScore > $anomalyThreshold) {
            $result = $result->addWarning(
                __('validation.statistical_anomaly_detected', [
                    'consumption' => $consumption,
                    'z_score' => round($zScore, 2),
                    'threshold' => $anomalyThreshold,
                    'mean' => round($mean, 2),
                    'unit' => $context->getUtilityService()?->unit_of_measurement ?? 'units'
                ])
            );

            $result = $result->addRecommendation(__('validation.investigate_consumption_anomaly'));
        }

        return $result->addMetadata('z_score', round($zScore, 2))
                     ->addMetadata('statistical_mean', round($mean, 2))
                     ->addMetadata('standard_deviation', round($stdDev, 2));
    }

    /**
     * Check for duplicate readings within the detection window.
     */
    private function checkDuplicateReadings(ValidationContext $context, ValidationResult $result): ValidationResult
    {
        $currentReading = $context->reading;
        $detectionWindow = $context->getValidationConfig('data_quality.duplicate_detection_window_hours', 24);

        // Query for potential duplicates
        $duplicates = MeterReading::query()
            ->where('meter_id', $currentReading->meter_id)
            ->where('zone', $currentReading->zone)
            ->where('id', '!=', $currentReading->id)
            ->where('reading_date', '>=', $currentReading->reading_date->subHours($detectionWindow))
            ->where('reading_date', '<=', $currentReading->reading_date->addHours($detectionWindow))
            ->where('value', $currentReading->getEffectiveValue())
            ->exists();

        if ($duplicates) {
            $result = $result->addWarning(
                __('validation.duplicate_reading_detected', [
                    'value' => $currentReading->getEffectiveValue(),
                    'date' => $currentReading->reading_date->format('Y-m-d H:i'),
                    'window_hours' => $detectionWindow,
                    'unit' => $context->getUtilityService()?->unit_of_measurement ?? 'units'
                ])
            );

            $result = $result->addRecommendation(__('validation.verify_reading_uniqueness'));
        }

        return $result;
    }

    /**
     * Validate reading structure for multi-value readings.
     */
    private function validateReadingStructure(ValidationContext $context, ValidationResult $result): ValidationResult
    {
        $reading = $context->reading;

        if ($reading->isMultiValue()) {
            $structureErrors = $reading->validateReadingValues();
            
            foreach ($structureErrors as $error) {
                $result = $result->addError($error);
            }

            // Validate that reading_values is consistent with meter structure
            $meter = $context->getMeter();
            if (method_exists($meter, 'getReadingStructure')) {
                $expectedStructure = $meter->getReadingStructure();
                $actualValues = $reading->reading_values ?? [];

                foreach ($expectedStructure['required_fields'] ?? [] as $field) {
                    if (!isset($actualValues[$field])) {
                        $result = $result->addError(
                            __('validation.missing_required_reading_field', ['field' => $field])
                        );
                    }
                }
            }
        }

        // Validate backward compatibility
        if ($reading->value !== null && $reading->isMultiValue()) {
            $calculatedValue = $reading->getEffectiveValue();
            $tolerance = 0.01; // Allow small floating-point differences

            if (abs($reading->value - $calculatedValue) > $tolerance) {
                $result = $result->addWarning(
                    __('validation.reading_value_mismatch', [
                        'stored_value' => $reading->value,
                        'calculated_value' => $calculatedValue,
                        'unit' => $context->getUtilityService()?->unit_of_measurement ?? 'units'
                    ])
                );
            }
        }

        return $result;
    }

    /**
     * Validate audit trail consistency.
     */
    private function validateAuditTrail(ValidationContext $context, ValidationResult $result): ValidationResult
    {
        if (!$context->getValidationConfig('data_quality.audit_trail_validation', true)) {
            return $result;
        }

        $reading = $context->reading;

        // Check if reading has proper audit information
        if (!$reading->entered_by) {
            $result = $result->addWarning(__('validation.missing_audit_entered_by'));
        }

        // Check validation status consistency
        if ($reading->validation_status->isApproved() && !$reading->validated_by) {
            $result = $result->addError(__('validation.validated_reading_missing_validator'));
        }

        // Check input method consistency
        if ($reading->input_method->requiresPhoto() && !$reading->hasPhoto()) {
            $result = $result->addError(
                __('validation.photo_required_for_input_method', [
                    'input_method' => $reading->input_method->getLabel()
                ])
            );
        }

        return $result;
    }

    /**
     * Calculate standard deviation for anomaly detection.
     */
    private function calculateStandardDeviation(array $values, float $mean): float
    {
        $count = count($values);
        if ($count <= 1) {
            return 0;
        }

        $sumSquaredDifferences = array_sum(
            array_map(fn($value) => pow($value - $mean, 2), $values)
        );

        return sqrt($sumSquaredDifferences / ($count - 1));
    }

    /**
     * Get maximum meter value for rollover detection.
     */
    private function getMaxMeterValue(ValidationContext $context): float
    {
        // Default maximum values by utility type
        $serviceType = $context->getUtilityService()?->service_type_bridge?->value;
        
        return match ($serviceType) {
            'electricity' => 999999.9, // 6 digits + 1 decimal
            'water' => 99999.99,       // 5 digits + 2 decimals
            'heating' => 999999.9,     // 6 digits + 1 decimal
            default => 999999.99,      // Default 6 digits + 2 decimals
        };
    }
}