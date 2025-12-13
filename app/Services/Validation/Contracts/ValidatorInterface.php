<?php

declare(strict_types=1);

namespace App\Services\Validation\Contracts;

use App\Services\Validation\ValidationContext;
use App\Services\Validation\ValidationResult;

/**
 * Interface for all validation strategies.
 * 
 * Implements the Strategy pattern for different validation types.
 */
interface ValidatorInterface
{
    /**
     * Validate the given context and return a validation result.
     */
    public function validate(ValidationContext $context): ValidationResult;

    /**
     * Get the validator name for logging and debugging.
     */
    public function getName(): string;

    /**
     * Check if this validator applies to the given context.
     */
    public function appliesTo(ValidationContext $context): bool;
}