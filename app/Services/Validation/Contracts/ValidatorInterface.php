<?php

declare(strict_types=1);

namespace App\Services\Validation\Contracts;

use App\Services\Validation\ValidationContext;
use App\Services\Validation\ValidationResult;

/**
 * Interface for validation rule implementations.
 */
interface ValidatorInterface
{
    /**
     * Get the validator name.
     */
    public function getName(): string;

    /**
     * Check if this validator applies to the given context.
     */
    public function appliesTo(ValidationContext $context): bool;

    /**
     * Validate the context and return results.
     */
    public function validate(ValidationContext $context): ValidationResult;
}