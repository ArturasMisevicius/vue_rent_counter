<?php

declare(strict_types=1);

namespace App\Contracts\ServiceRegistration;

use App\ValueObjects\ServiceRegistration\RegistrationResult;

/**
 * Interface for handling service registration errors
 */
interface ErrorHandlingStrategyInterface
{
    /**
     * Handle a registration operation with error handling
     */
    public function handleRegistration(callable $operation, string $context): RegistrationResult;

    /**
     * Log registration results
     */
    public function logResults(RegistrationResult $result, string $context): void;

    /**
     * Handle critical failures
     */
    public function handleCriticalFailure(\Throwable $exception, string $context): void;
}