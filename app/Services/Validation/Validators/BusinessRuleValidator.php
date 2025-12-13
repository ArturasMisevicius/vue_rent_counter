<?php

declare(strict_types=1);

namespace App\Services\Validation\Validators;

use App\Services\Validation\Contracts\ValidatorInterface;
use App\Services\Validation\ValidationContext;
use App\Services\Validation\ValidationResult;

/**
 * Validates business rules and constraints.
 */
final class BusinessRuleValidator implements ValidatorInterface
{
    public function getName(): string
    {
        return 'business_rules';
    }

    public function appliesTo(ValidationContext $context): bool
    {
        return $context->hasValidationConfig();
    }

    public function validate(ValidationContext $context): ValidationResult
    {
        $warnings = [];
        
        // Basic business rule validation
        $consumption = $context->getConsumption();
        if ($consumption && $consumption > 1000) {
            $warnings[] = 'High consumption may require review';
        }

        return ValidationResult::valid($warnings);
    }
}