<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Organization;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Trait for logging tenant operations with consistent formatting.
 * 
 * Provides standardized logging methods for tenant-related operations
 * including start, success, error, warning, and info messages with
 * proper context and structured data.
 * 
 * @package App\Traits
 * @author Laravel Development Team
 * @since 1.0.0
 */
trait LogsTenantOperations
{
    /**
     * Log the start of a tenant operation.
     * 
     * @param array<string, mixed> $context Additional context data
     */
    protected function logTenantOperationStart(
        Organization $tenant,
        string $operation,
        array $context = []
    ): void {
        Log::info("Starting tenant operation: {$operation}", [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'operation' => $operation,
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString(),
            ...$context,
        ]);
    }

    /**
     * Log the successful completion of a tenant operation.
     * 
     * @param array<string, mixed> $context Additional context data
     */
    protected function logTenantOperationSuccess(
        Organization $tenant,
        string $operation,
        array $context = []
    ): void {
        Log::info("Tenant operation completed successfully: {$operation}", [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'operation' => $operation,
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString(),
            'status' => 'success',
            ...$context,
        ]);
    }

    /**
     * Log an error during a tenant operation.
     * 
     * @param array<string, mixed> $context Additional context data
     */
    protected function logTenantOperationError(
        Organization $tenant,
        string $operation,
        Throwable $exception,
        array $context = []
    ): void {
        Log::error("Tenant operation failed: {$operation}", [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'operation' => $operation,
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString(),
            'status' => 'error',
            'error_message' => $exception->getMessage(),
            'error_code' => $exception->getCode(),
            'error_file' => $exception->getFile(),
            'error_line' => $exception->getLine(),
            'stack_trace' => $exception->getTraceAsString(),
            ...$context,
        ]);
    }

    /**
     * Log a warning during a tenant operation.
     * 
     * @param array<string, mixed> $context Additional context data
     */
    protected function logTenantOperationWarning(
        Organization $tenant,
        string $operation,
        string $message,
        array $context = []
    ): void {
        Log::warning("Tenant operation warning: {$operation} - {$message}", [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'operation' => $operation,
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString(),
            'status' => 'warning',
            'warning_message' => $message,
            ...$context,
        ]);
    }

    /**
     * Log informational message during a tenant operation.
     * 
     * @param array<string, mixed> $context Additional context data
     */
    protected function logTenantOperationInfo(
        Organization $tenant,
        string $operation,
        string $message,
        array $context = []
    ): void {
        Log::info("Tenant operation info: {$operation} - {$message}", [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'operation' => $operation,
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString(),
            'status' => 'info',
            'info_message' => $message,
            ...$context,
        ]);
    }

    /**
     * Log a debug message during a tenant operation.
     * 
     * @param array<string, mixed> $context Additional context data
     */
    protected function logTenantOperationDebug(
        Organization $tenant,
        string $operation,
        string $message,
        array $context = []
    ): void {
        Log::debug("Tenant operation debug: {$operation} - {$message}", [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'operation' => $operation,
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString(),
            'status' => 'debug',
            'debug_message' => $message,
            ...$context,
        ]);
    }

    /**
     * Log performance metrics for a tenant operation.
     * 
     * @param array<string, mixed> $metrics Performance metrics data
     */
    protected function logTenantOperationPerformance(
        Organization $tenant,
        string $operation,
        array $metrics
    ): void {
        Log::info("Tenant operation performance: {$operation}", [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'operation' => $operation,
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString(),
            'type' => 'performance',
            'metrics' => $metrics,
        ]);
    }
}