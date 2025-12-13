<?php

declare(strict_types=1);

namespace App\Services\Validation\Validators;

use App\Services\Validation\Contracts\ValidatorInterface;
use App\Services\Validation\ValidationContext;
use App\Services\Validation\ValidationResult;

/**
 * Validates readings against seasonal patterns and adjustments.
 */
final class SeasonalValidator implements ValidatorInterface
{
    public function getName(): string
    {
        return 'seasonal';
    }

    public function appliesTo(ValidationContext $context): bool
    {
        return $context->hasSeasonalConfig() && $context->getConsumption() !== null;
    }

    public function validate(ValidationContext $context): ValidationResult
    {
        $warnings = [];
        
        // Basic seasonal validation
        if ($context->isHeatingSeason() && $context->getUtilityType() === 'heating') {
            $consumption = $context->getConsumption();
            if ($consumption && $consumption < 50) {
                $warnings[] = 'Low heating consumption during heating season';
            }
        }

        return ValidationResult::valid($warnings);
    }
}