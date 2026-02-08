<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when shared service cost distribution fails.
 */
class DistributionException extends Exception
{
    /**
     * Create exception for invalid distribution method.
     */
    public static function invalidMethod(string $method): self
    {
        return new self("Invalid distribution method: {$method}");
    }

    /**
     * Create exception for missing required data.
     */
    public static function missingData(string $dataType, int $propertyId): self
    {
        return new self("Missing {$dataType} data for property {$propertyId}");
    }

    /**
     * Create exception for formula evaluation failure.
     */
    public static function formulaError(string $formula, string $error): self
    {
        return new self("Formula evaluation failed for '{$formula}': {$error}");
    }

    /**
     * Create exception for invalid property collection.
     */
    public static function invalidProperties(string $reason): self
    {
        return new self("Invalid properties for distribution: {$reason}");
    }
}