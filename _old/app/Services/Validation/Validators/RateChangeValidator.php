<?php

declare(strict_types=1);

namespace App\Services\Validation\Validators;

use App\Enums\PricingModel;
use App\Models\ServiceConfiguration;
use App\Services\TimeRangeValidator;
use App\Services\Validation\ValidationContext;
use App\Services\Validation\ValidationResult;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository;
use Psr\Log\LoggerInterface;

/**
 * Validates rate change restrictions using existing tariff active date functionality.
 */
final class RateChangeValidator extends AbstractValidator
{
    private const DEFAULT_RATE_CHANGE_FREQUENCY_DAYS = 30;

    public function __construct(
        Repository $cache,
        \Illuminate\Contracts\Config\Repository $config,
        LoggerInterface $logger,
        private readonly TimeRangeValidator $timeRangeValidator,
    ) {
        parent::__construct($cache, $config, $logger);
    }

    public function getName(): string
    {
        return 'rate_change';
    }

    public function isApplicable(ValidationContext $context): bool
    {
        // This validator is used separately for rate change validation
        return false;
    }

    public function getPriority(): int
    {
        return 10; // Low priority as it's used separately
    }

    public function validate(ValidationContext $context): ValidationResult
    {
        // This method is not used as this validator is called separately
        return ValidationResult::valid();
    }

    /**
     * Validate rate change restrictions for a service configuration.
     */
    public function validateRateChangeRestrictions(ServiceConfiguration $serviceConfig, array $newRateSchedule): ValidationResult
    {
        try {
            $errors = [];
            $warnings = [];
            $recommendations = [];

            // 1. Check rate change frequency limits
            $frequencyValidation = $this->validateRateChangeFrequency($serviceConfig);
            $warnings = array_merge($warnings, $frequencyValidation['warnings']);

            // 2. Validate effective dates
            $dateValidation = $this->validateEffectiveDates($serviceConfig, $newRateSchedule);
            $errors = array_merge($errors, $dateValidation['errors']);
            $warnings = array_merge($warnings, $dateValidation['warnings']);

            // 3. Validate rate schedule structure
            $structureValidation = $this->validateRateScheduleStructure($newRateSchedule, $serviceConfig);
            $errors = array_merge($errors, $structureValidation['errors']);

            // 4. Check for overlapping configurations
            $overlapValidation = $this->validateConfigurationOverlaps($serviceConfig, $newRateSchedule);
            $warnings = array_merge($warnings, $overlapValidation['warnings']);

            $metadata = [
                'rules_applied' => ['frequency_limits', 'effective_dates', 'structure_validation', 'overlap_check'],
                'service_config_id' => $serviceConfig->id,
            ];

            if (empty($errors)) {
                return ValidationResult::valid($warnings, $recommendations, $metadata);
            }

            return ValidationResult::invalid($errors, $warnings, $recommendations, $metadata);

        } catch (\Exception $e) {
            $this->logger->error('Rate change validation failed', [
                'service_config_id' => $serviceConfig->id,
                'error' => $e->getMessage(),
            ]);

            return ValidationResult::withError('Rate change validation system error: '.$e->getMessage());
        }
    }

    private function validateRateChangeFrequency(ServiceConfiguration $serviceConfig): array
    {
        $warnings = [];

        $minDaysBetweenChanges = $this->getConfigValue(
            'service_validation.rate_change_frequency_days',
            self::DEFAULT_RATE_CHANGE_FREQUENCY_DAYS
        );

        $lastUpdate = $serviceConfig->updated_at;
        $daysSinceUpdate = now()->diffInDays($lastUpdate);

        if ($daysSinceUpdate < $minDaysBetweenChanges) {
            $warnings[] = "Rate change requested within {$minDaysBetweenChanges} day minimum period";
        }

        return ['warnings' => $warnings];
    }

    private function validateEffectiveDates(ServiceConfiguration $serviceConfig, array $newRateSchedule): array
    {
        $errors = [];
        $warnings = [];

        $effectiveFrom = $newRateSchedule['effective_from'] ?? null;

        if ($effectiveFrom) {
            $effectiveDate = Carbon::parse($effectiveFrom);

            // Effective date should not be in the past (with some tolerance)
            if ($effectiveDate->isPast() && $effectiveDate->diffInDays(now()) > 1) {
                $warnings[] = "Effective date is in the past: {$effectiveDate->toDateString()}";
            }

            // Effective date should not be too far in the future
            if ($effectiveDate->isFuture() && $effectiveDate->diffInDays(now()) > 365) {
                $warnings[] = 'Effective date is more than one year in the future';
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    private function validateRateScheduleStructure(array $rateSchedule, ServiceConfiguration $serviceConfig): array
    {
        $errors = [];

        $pricingModel = $serviceConfig->pricing_model;

        // Validate structure based on pricing model
        switch ($pricingModel) {
            case PricingModel::TIERED_RATES:
                if (empty($rateSchedule['tiers'])) {
                    $errors[] = 'Tiered pricing model requires tier definitions';
                }
                break;

            case PricingModel::TIME_OF_USE:
                $hasZoneRates = ! empty($rateSchedule['zone_rates']) && is_array($rateSchedule['zone_rates']);
                $hasTimeWindows = ! empty($rateSchedule['time_windows']) && is_array($rateSchedule['time_windows']);
                $hasTimeSlots = ! empty($rateSchedule['time_slots']) && is_array($rateSchedule['time_slots']);

                if (! $hasZoneRates && ! $hasTimeWindows && ! $hasTimeSlots) {
                    $errors[] = 'Time-of-use pricing model requires zone_rates, time_windows, or time_slots';
                    break;
                }

                if ($hasTimeSlots) {
                    $normalizedSlots = array_map(function (mixed $slot): array {
                        if (! is_array($slot)) {
                            return [];
                        }

                        if (isset($slot['start'], $slot['end'])) {
                            return $slot;
                        }

                        $startHour = $slot['start_hour'] ?? null;
                        $endHour = $slot['end_hour'] ?? null;

                        if (! is_numeric($startHour) || ! is_numeric($endHour)) {
                            return [];
                        }

                        return [
                            'start' => sprintf('%02d:00', (int) $startHour),
                            'end' => sprintf('%02d:00', (int) $endHour),
                        ];
                    }, $rateSchedule['time_slots']);

                    $normalizedSlots = array_values(array_filter($normalizedSlots, static fn (array $slot): bool => $slot !== []));

                    $timeValidationErrors = $this->timeRangeValidator->validate($normalizedSlots);
                    $errors = array_merge($errors, $timeValidationErrors);
                }
                break;
        }

        return ['errors' => $errors];
    }

    private function validateConfigurationOverlaps(ServiceConfiguration $serviceConfig, array $newRateSchedule): array
    {
        $warnings = [];

        // Check for overlapping service configurations on the same property
        $overlappingConfigs = ServiceConfiguration::where('property_id', $serviceConfig->property_id)
            ->where('utility_service_id', $serviceConfig->utility_service_id)
            ->where('id', '!=', $serviceConfig->id)
            ->effectiveOn()
            ->get();

        if ($overlappingConfigs->isNotEmpty()) {
            $warnings[] = 'Multiple active configurations found for the same utility service on this property';
        }

        return ['warnings' => $warnings];
    }
}
