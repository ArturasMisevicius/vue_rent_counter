<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exception thrown when service configuration validation fails.
 * 
 * This exception is thrown when:
 * - Service configurations conflict with existing meter assignments
 * - Configuration validation fails against utility service schema
 * - Pricing model requirements are not met
 * - Distribution method requirements are not satisfied
 * 
 * Requirements: 3.1, 3.2, 3.3, 11.1, 11.2, 11.3
 */
final class ServiceConfigurationException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $message  The exception message
     * @param  int  $code  The exception code (default: 422)
     * @param  \Throwable|null  $previous  Previous exception for chaining
     */
    public function __construct(
        string $message = 'Service configuration validation failed.',
        int $code = Response::HTTP_UNPROCESSABLE_ENTITY,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception for conflicting meter assignment.
     */
    public static function conflictingMeterAssignment(int $meterId, int $propertyId): self
    {
        return new self(
            "Meter #{$meterId} is already assigned to property #{$propertyId} with a different service configuration."
        );
    }

    /**
     * Create exception for missing required configuration.
     */
    public static function missingRequiredConfiguration(string $field): self
    {
        return new self(
            "Required configuration field '{$field}' is missing."
        );
    }

    /**
     * Create exception for invalid pricing model.
     */
    public static function invalidPricingModel(string $pricingModel, string $reason): self
    {
        return new self(
            "Pricing model '{$pricingModel}' is invalid: {$reason}"
        );
    }

    /**
     * Create exception for invalid distribution method.
     */
    public static function invalidDistributionMethod(string $distributionMethod, string $reason): self
    {
        return new self(
            "Distribution method '{$distributionMethod}' is invalid: {$reason}"
        );
    }

    /**
     * Create exception for overlapping configurations.
     */
    public static function overlappingConfiguration(int $propertyId, string $dateRange): self
    {
        return new self(
            "Property #{$propertyId} already has an active service configuration for the date range: {$dateRange}"
        );
    }

    /**
     * Create exception for missing area data.
     */
    public static function missingAreaData(int $propertyId): self
    {
        return new self(
            "Property #{$propertyId} requires area data for the selected distribution method."
        );
    }

    /**
     * Create exception for validation errors.
     */
    public static function validationErrors(array $errors): self
    {
        $message = "Service configuration validation failed:\n" . implode("\n", $errors);
        return new self($message);
    }

    /**
     * Render the exception as an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function render(Request $request): JsonResponse|Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'error' => 'service_configuration_error',
            ], $this->getCode());
        }

        return response()->view('errors.422', [
            'message' => $this->getMessage(),
            'exception' => $this,
        ], $this->getCode());
    }

    /**
     * Report the exception.
     *
     * @return bool
     */
    public function report(): bool
    {
        Log::warning('Service configuration error', [
            'message' => $this->getMessage(),
            'trace' => $this->getTraceAsString(),
        ]);

        return true;
    }
}
