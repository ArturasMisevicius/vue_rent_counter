<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Throwable;

/**
 * Exception thrown when external service integration operations fail.
 * 
 * Provides specific error types for different integration failure scenarios
 * with appropriate error codes and messages for proper error handling.
 * 
 * @package App\Exceptions
 * @author Laravel Development Team
 * @since 1.0.0
 */
final class IntegrationException extends Exception
{
    public const CODE_OPERATION_FAILED = 1001;
    public const CODE_SERVICE_UNAVAILABLE = 1002;
    public const CODE_CIRCUIT_BREAKER_OPEN = 1003;
    public const CODE_NO_FALLBACK_AVAILABLE = 1004;
    public const CODE_SYNC_FAILED = 1005;
    public const CODE_INVALID_CONFIGURATION = 1006;
    public const CODE_AUTHENTICATION_FAILED = 1007;
    public const CODE_RATE_LIMIT_EXCEEDED = 1008;
    public const CODE_DATA_VALIDATION_FAILED = 1009;
    public const CODE_TIMEOUT = 1010;

    /**
     * Create exception for failed operation.
     */
    public static function operationFailed(string $serviceName, ?Throwable $previous = null): self
    {
        return new self(
            message: "External service operation failed: {$serviceName}",
            code: self::CODE_OPERATION_FAILED,
            previous: $previous
        );
    }

    /**
     * Create exception for unavailable service.
     */
    public static function serviceUnavailable(string $serviceName, ?Throwable $previous = null): self
    {
        return new self(
            message: "External service is unavailable: {$serviceName}",
            code: self::CODE_SERVICE_UNAVAILABLE,
            previous: $previous
        );
    }

    /**
     * Create exception for circuit breaker open state.
     */
    public static function circuitBreakerOpen(string $serviceName): self
    {
        return new self(
            message: "Circuit breaker is open for service: {$serviceName}",
            code: self::CODE_CIRCUIT_BREAKER_OPEN
        );
    }

    /**
     * Create exception when no fallback is available.
     */
    public static function noFallbackAvailable(string $serviceName, ?Throwable $previous = null): self
    {
        return new self(
            message: "No fallback available for failed service: {$serviceName}",
            code: self::CODE_NO_FALLBACK_AVAILABLE,
            previous: $previous
        );
    }

    /**
     * Create exception for synchronization failure.
     */
    public static function syncFailed(string $serviceName, string $reason, ?Throwable $previous = null): self
    {
        return new self(
            message: "Data synchronization failed for service {$serviceName}: {$reason}",
            code: self::CODE_SYNC_FAILED,
            previous: $previous
        );
    }

    /**
     * Create exception for invalid configuration.
     */
    public static function invalidConfiguration(string $serviceName, string $configKey): self
    {
        return new self(
            message: "Invalid configuration for service {$serviceName}: {$configKey}",
            code: self::CODE_INVALID_CONFIGURATION
        );
    }

    /**
     * Create exception for authentication failure.
     */
    public static function authenticationFailed(string $serviceName, ?Throwable $previous = null): self
    {
        return new self(
            message: "Authentication failed for service: {$serviceName}",
            code: self::CODE_AUTHENTICATION_FAILED,
            previous: $previous
        );
    }

    /**
     * Create exception for rate limit exceeded.
     */
    public static function rateLimitExceeded(string $serviceName, int $retryAfterSeconds = 0): self
    {
        $message = "Rate limit exceeded for service: {$serviceName}";
        if ($retryAfterSeconds > 0) {
            $message .= " (retry after {$retryAfterSeconds} seconds)";
        }

        return new self(
            message: $message,
            code: self::CODE_RATE_LIMIT_EXCEEDED
        );
    }

    /**
     * Create exception for data validation failure.
     */
    public static function dataValidationFailed(string $serviceName, array $errors): self
    {
        $errorMessages = implode(', ', $errors);
        
        return new self(
            message: "Data validation failed for service {$serviceName}: {$errorMessages}",
            code: self::CODE_DATA_VALIDATION_FAILED
        );
    }

    /**
     * Create exception for timeout.
     */
    public static function timeout(string $serviceName, int $timeoutSeconds): self
    {
        return new self(
            message: "Timeout occurred for service {$serviceName} after {$timeoutSeconds} seconds",
            code: self::CODE_TIMEOUT
        );
    }

    /**
     * Get user-friendly error message.
     */
    public function getUserMessage(): string
    {
        return match ($this->code) {
            self::CODE_OPERATION_FAILED => __('integration.errors.operation_failed'),
            self::CODE_SERVICE_UNAVAILABLE => __('integration.errors.service_unavailable'),
            self::CODE_CIRCUIT_BREAKER_OPEN => __('integration.errors.service_temporarily_unavailable'),
            self::CODE_NO_FALLBACK_AVAILABLE => __('integration.errors.service_required'),
            self::CODE_SYNC_FAILED => __('integration.errors.sync_failed'),
            self::CODE_INVALID_CONFIGURATION => __('integration.errors.configuration_error'),
            self::CODE_AUTHENTICATION_FAILED => __('integration.errors.authentication_failed'),
            self::CODE_RATE_LIMIT_EXCEEDED => __('integration.errors.rate_limit_exceeded'),
            self::CODE_DATA_VALIDATION_FAILED => __('integration.errors.data_validation_failed'),
            self::CODE_TIMEOUT => __('integration.errors.timeout'),
            default => __('integration.errors.unknown_error'),
        };
    }

    /**
     * Check if the error is retryable.
     */
    public function isRetryable(): bool
    {
        return match ($this->code) {
            self::CODE_SERVICE_UNAVAILABLE,
            self::CODE_TIMEOUT,
            self::CODE_RATE_LIMIT_EXCEEDED => true,
            self::CODE_CIRCUIT_BREAKER_OPEN,
            self::CODE_AUTHENTICATION_FAILED,
            self::CODE_INVALID_CONFIGURATION,
            self::CODE_DATA_VALIDATION_FAILED => false,
            default => false,
        };
    }

    /**
     * Get suggested retry delay in seconds.
     */
    public function getRetryDelay(): int
    {
        return match ($this->code) {
            self::CODE_SERVICE_UNAVAILABLE => 30,
            self::CODE_TIMEOUT => 60,
            self::CODE_RATE_LIMIT_EXCEEDED => 300, // 5 minutes
            default => 0,
        };
    }

    /**
     * Convert to array for logging or API responses.
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'error_code' => $this->code,
            'message' => $this->message,
            'user_message' => $this->getUserMessage(),
            'is_retryable' => $this->isRetryable(),
            'retry_delay' => $this->getRetryDelay(),
            'timestamp' => now()->toISOString(),
            'previous_error' => $this->getPrevious()?->getMessage(),
        ];
    }
}