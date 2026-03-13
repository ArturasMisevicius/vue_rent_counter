<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Base Service Class
 * 
 * Provides common functionality for all service classes including:
 * - Transaction management
 * - Standardized error handling
 * - Consistent response formatting
 * - Structured logging with context
 * - Input validation
 * - Service availability checks
 * 
 * @package App\Services
 */
abstract class BaseService implements \App\Contracts\ServiceInterface
{
    /**
     * Execute a callback within a database transaction.
     * 
     * Automatically rolls back on any exception and logs the error.
     *
     * @param callable $callback The operation to execute
     * @return mixed The result of the callback
     * @throws Throwable Re-throws the exception after rollback
     */
    protected function executeInTransaction(callable $callback): mixed
    {
        try {
            return DB::transaction($callback);
        } catch (Throwable $e) {
            $this->handleException($e);
            throw $e;
        }
    }

    /**
     * Handle an exception with logging and context.
     *
     * @param Throwable $e The exception to handle
     * @param array $context Additional context for logging
     * @return void
     */
    protected function handleException(Throwable $e, array $context = []): void
    {
        $this->log('error', $e->getMessage(), array_merge([
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ], $context));
    }

    /**
     * Create a success response.
     *
     * @param mixed $data The data to return
     * @param string $message Optional success message
     * @return ServiceResponse
     */
    protected function success(mixed $data = null, string $message = ''): ServiceResponse
    {
        return new ServiceResponse(
            success: true,
            data: $data,
            message: $message
        );
    }

    /**
     * Create an error response.
     *
     * @param string $message The error message
     * @param mixed $data Optional error data
     * @param int $code Optional error code
     * @return ServiceResponse
     */
    protected function error(string $message, mixed $data = null, int $code = 0): ServiceResponse
    {
        return new ServiceResponse(
            success: false,
            data: $data,
            message: $message,
            code: $code
        );
    }

    /**
     * Log a message with context.
     *
     * @param string $level Log level (debug, info, warning, error)
     * @param string $message The message to log
     * @param array $context Additional context
     * @return void
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        // Add service class to context
        $context['service'] = static::class;

        // Add tenant context if available
        if (class_exists(\App\Services\TenantContext::class)) {
            $context['tenant_id'] = app(\App\Services\TenantContext::class)->get();
        }

        // Add authenticated user if available
        if (auth()->check()) {
            $context['user_id'] = auth()->id();
            $context['user_role'] = auth()->user()->role->value ?? null;
        }

        Log::log($level, $message, $context);
    }

    /**
     * Validate that a model belongs to the current tenant.
     *
     * @param object $model The model to validate
     * @return bool True if valid, false otherwise
     */
    protected function validateTenantOwnership(object $model): bool
    {
        if (!property_exists($model, 'tenant_id')) {
            return true; // Model doesn't have tenant_id
        }

        $currentTenantId = app(\App\Services\TenantContext::class)->get();
        
        if (!$currentTenantId) {
            return true; // No tenant context (e.g., superadmin)
        }

        return $model->tenant_id === $currentTenantId;
    }

    /**
     * Get the service name for logging and identification.
     * 
     * @return string
     */
    public function getServiceName(): string
    {
        return class_basename(static::class);
    }

    /**
     * Validate input data before processing.
     * Default implementation - override in child classes for specific validation.
     * 
     * @param array $data
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function validateInput(array $data): bool
    {
        // Default validation - can be overridden
        return !empty($data);
    }

    /**
     * Check if the service is available/enabled.
     * Default implementation - override in child classes for specific checks.
     * 
     * @return bool
     */
    public function isAvailable(): bool
    {
        // Default availability - can be overridden
        return true;
    }
}
