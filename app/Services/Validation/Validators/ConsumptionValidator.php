<?php

declare(strict_types=1);

namespace App\Services\Validation\Validators;

use App\Services\Validation\Contracts\ValidatorInterface;
use App\Services\Validation\ValidationContext;
use App\Services\Validation\ValidationResult;

/**
 * Validates meter reading consumption against limits and patterns.
 */
final class ConsumptionValidator implements ValidatorInterface
{
    public function getName(): string
    {
        return 'consumption';
    }

    public function appliesTo(ValidationContext $context): bool
    {
        // Apply to all readings that have consumption data
        return $context->getConsumption() !== null;
    }

    public function validate(ValidationContext $context): ValidationResult
    {
        $consumption = $context->getConsumption();
        $errors = [];
        $warnings = [];

        // Basic consumption validation
        if ($consumption !== null) {
            if ($consumption < 0) {
                $errors[] = 'Consumption cannot be negative';
            }

            if ($consumption > 10000) { // Basic upper limit
                $errors[] = 'Consumption exceeds maximum limit';
            }

            // Check for unusual consumption patterns
            $historicalAverage = $context->getHistoricalAverageConsumption();
            if ($historicalAverage && $consumption > $historicalAverage * 3) {
                $warnings[] = 'Consumption is significantly higher than historical average';
            }
        }

        return empty($errors) 
            ? ValidationResult::valid($warnings)
            : ValidationResult::invalid($errors, $warnings);
    }
}