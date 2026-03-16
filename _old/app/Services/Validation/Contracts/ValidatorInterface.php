<?php

declare(strict_types=1);

namespace App\Services\Validation\Contracts;

use App\Services\Validation\ValidationContext;
use App\Services\Validation\ValidationResult;

/**
 * Interface for all validation strategies.
 * 
 * Defines the contract for validation implementations using the Strategy pattern.
 * Each validator focuses on a single validation concern for maintainability.
 */
interface ValidatorInterface
{
    /**
     * Validate the given context and return a result.
     */
    public function validate(ValidationContext $context): ValidationResult;

    /**
     * Get the name of this validator for logging and debugging.
     */
    public function getName(): string;

    /**
     * Check if this validator is applicable to the given context.
     */
    public function isApplicable(ValidationContext $context): bool;

    /**
     * Get the priority of this validator (higher numbers run first).
     */
    public function getPriority(): int;
}