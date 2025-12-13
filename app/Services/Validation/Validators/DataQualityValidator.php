<?php

declare(strict_types=1);

namespace App\Services\Validation\Validators;

use App\Services\Validation\Contracts\ValidatorInterface;
use App\Services\Validation\ValidationContext;
use App\Services\Validation\ValidationResult;

/**
 * Validates data quality and consistency.
 */
final class DataQualityValidator implements ValidatorInterface
{
    public function getName(): string
    {
        return 'data_quality';
    }

    public function appliesTo(ValidationContext $context): bool
    {
        return true; // Apply to all readings
    }

    public function validate(ValidationContext $context): ValidationResult
    {
        $errors = [];
        $warnings = [];

        // Basic data quality checks
        $reading = $context->reading;
        
        if ($reading->reading_date->isFuture()) {
            $errors[] = 'Reading date cannot be in the future';
        }

        if ($reading->getEffectiveValue() <= 0) {
            $warnings[] = 'Zero or negative reading value detected';
        }

        return empty($errors) 
            ? ValidationResult::valid($warnings)
            : ValidationResult::invalid($errors, $warnings);
    }
}