<?php

declare(strict_types=1);

namespace App\Services\Enhanced;

use App\Services\ServiceResponse;
use App\Services\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Enhanced Base Service Class
 * 
 * Provides comprehensive foundation for all service classes with:
 * - Advanced transaction management with savepoints
 * - Standardized error handling with context preservation
 * - Consistent response formatting with metadata
 * - Structured logging with tenant and user context
 * - Authorization helpers with audit trails
 * - Performance monitoring and metrics collection
 * 
 * @package App\Services\Enhanced
 */
abstract class BaseService
{
    protected LoggerInterface $logger;
    protected array $performanceMetrics = [];

    public function __construct()
    {
        $this->logger = Log::channel('services');
    }

    /**
     * Execute a callback within a database transaction with savepoints.
     * 
     * Provides nested transaction support and automatic rollback on exceptions.
     * Includes performance monitoring and comprehensive error logging.
     *
     * @param callable $callback The operation to execute
     * @param string|null $savepointName Optional savepoint name for nested transactions
     * @return mixed The result of the callback
     * @throws Throwable Re-throws the exception after rollback and logging
     */
    protected function executeInTransaction(callable $callback, ?string $savepointName = null): mixed
    {
        $startTime = microtime(true);
        $transactionId = uniqid('txn_');
        
        $this->logTransactionStart($transactionId, $savepointName);

        try {
            if ($savepointName) {
                return DB::transaction($callback, 5, $savepointName);
            }
            
            return DB::transaction($callback);
            
        } catch (Throwable $e) {
            $this->handleTransactionException($e, $transactionId, $savepointName);
            throw $e;
        } finally {
            $this->recordTransactionMetrics($transactionId, microtime(true) - $startTime);
        }
    }

    /**
     * Handle an exception with comprehensive logging and context preservation.
     *
     * @param Throwable $e The exception to handle
     * @param array $context Additional context for logging
     * @param bool $notify Whether to trigger notification systems
     * @return void
     */
    protected function handleException(Throwable $e, array $context = [], bool $notify = false): void
    {
        $errorContext = array_merge([
            'exception_class' => get_class($e),
            'exception_code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'service_class' => static::class,
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ], $context);

        // Add stack trace for non-production environments
        if (!app()->isProduction()) {
            $errorContext['stack_trace'] = $e->getTraceAsString();
        }

        $this->log('error', $e->getMessage(), $errorContext);

        // Trigger notification systems for critical errors
        if ($notify && $this->isCriticalException($e)) {
            $this->notifyCriticalError($e, $errorContext);
        }
    }

    /**
     * Create a success response with optional metadata.
     *
     * @param mixed $data The data to return
     * @param string $message Optional success message
     * @param array $metadata Optional metadata
     * @return ServiceResponse
     */
    protected function success(mixed $data = null, string $message = '', array $metadata = []): ServiceResponse
    {
        // Add performance metrics if available
        if (!empty($this->performanceMetrics)) {
            $metadata['performance'] = $this->performanceMetrics;
        }

        // Add tenant context
        if ($tenantId = TenantContext::id()) {
            $metadata['tenant_id'] = $tenantId;
        }

        return new ServiceResponse(
            success: true,
            data: $data,
            message: $message,
            metadata: $metadata
        );
    }

    /**
     * Create an error response with detailed context.
     *
     * @param string $message The error message
     * @param mixed $data Optional error data
     * @param int $code Optional error code
     * @param array $metadata Optional metadata
     * @return ServiceResponse
     */
    protected function error(
        string $message, 
        mixed $data = null, 
        int $code = 0, 
        array $metadata = []
    ): ServiceResponse {
        // Add error context
        $metadata['error_context'] = [
            'service' => static::class,
            'timestamp' => now()->toISOString(),
            'request_id' => request()->header('X-Request-ID', uniqid('req_')),
        ];

        return new ServiceResponse(
            success: false,
            data: $data,
            message: $message,
            code: $code,
            metadata: $metadata
        );
    }

    /**
     * Log a message with comprehensive context.
     *
     * @param string $level Log level (debug, info, warning, error, critical)
     * @param string $message The message to log
     * @param array $context Additional context
     * @return void
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        // Build comprehensive context
        $logContext = array_merge([
            'service' => static::class,
            'timestamp' => now()->toISOString(),
            'request_id' => request()->header('X-Request-ID', uniqid('req_')),
        ], $context);

        // Add tenant context if available
        if ($tenantId = TenantContext::id()) {
            $logContext['tenant_id'] = $tenantId;
        }

        // Add authenticated user context if available
        if (auth()->check()) {
            $logContext['user_id'] = auth()->id();
            $logContext['user_role'] = auth()->user()->role->value ?? null;
            $logContext['user_email'] = auth()->user()->email;
        }

        // Add request context for web requests
        if (request()->hasHeader('User-Agent')) {
            $logContext['user_agent'] = request()->header('User-Agent');
            $logContext['ip_address'] = request()->ip();
            $logContext['request_method'] = request()->method();
            $logContext['request_url'] = request()->fullUrl();
        }

        $this->logger->log($level, $message, $logContext);
    }

    /**
     * Validate that a model belongs to the current tenant with audit logging.
     *
     * @param object $model The model to validate
     * @param bool $throwOnFailure Whether to throw exception on failure
     * @return bool True if valid, false otherwise
     * @throws AuthorizationException If validation fails and throwOnFailure is true
     */
    protected function validateTenantOwnership(object $model, bool $throwOnFailure = true): bool
    {
        if (!property_exists($model, 'tenant_id')) {
            return true; // Model doesn't have tenant_id
        }

        $currentTenantId = TenantContext::id();
        
        if (!$currentTenantId) {
            return true; // No tenant context (e.g., superadmin)
        }

        $isValid = $model->tenant_id === $currentTenantId;

        if (!$isValid) {
            $this->log('warning', 'Tenant ownership validation failed', [
                'model_class' => get_class($model),
                'model_id' => $model->id ?? 'unknown',
                'model_tenant_id' => $model->tenant_id,
                'current_tenant_id' => $currentTenantId,
                'user_id' => auth()->id(),
            ]);

            if ($throwOnFailure) {
                throw new AuthorizationException('Access denied: Resource belongs to different tenant');
            }
        }

        return $isValid;
    }

    /**
     * Authorize an action on a model with comprehensive logging.
     *
     * @param string $ability The ability to check
     * @param mixed $model The model to authorize against
     * @param bool $throwOnFailure Whether to throw exception on failure
     * @return bool True if authorized, false otherwise
     * @throws AuthorizationException If authorization fails and throwOnFailure is true
     */
    protected function authorize(string $ability, mixed $model = null, bool $throwOnFailure = true): bool
    {
        if (!auth()->check()) {
            if ($throwOnFailure) {
                throw new AuthorizationException('Authentication required');
            }
            return false;
        }

        $user = auth()->user();
        $isAuthorized = $user->can($ability, $model);

        if (!$isAuthorized) {
            $this->log('warning', 'Authorization failed', [
                'ability' => $ability,
                'model_class' => $model ? get_class($model) : null,
                'model_id' => $model && isset($model->id) ? $model->id : null,
                'user_id' => $user->id,
                'user_role' => $user->role->value ?? null,
            ]);

            if ($throwOnFailure) {
                throw new AuthorizationException("Access denied: Missing '{$ability}' permission");
            }
        }

        return $isAuthorized;
    }

    /**
     * Record performance metrics for monitoring.
     *
     * @param string $operation Operation name
     * @param float $duration Duration in seconds
     * @param array $metadata Additional metadata
     * @return void
     */
    protected function recordMetric(string $operation, float $duration, array $metadata = []): void
    {
        $this->performanceMetrics[] = [
            'operation' => $operation,
            'duration_ms' => round($duration * 1000, 2),
            'memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'timestamp' => microtime(true),
            'metadata' => $metadata,
        ];

        // Log slow operations
        if ($duration > 1.0) { // 1 second threshold
            $this->log('warning', 'Slow operation detected', [
                'operation' => $operation,
                'duration_seconds' => $duration,
                'metadata' => $metadata,
            ]);
        }
    }

    /**
     * Execute an operation with performance monitoring.
     *
     * @param string $operationName Name of the operation for metrics
     * @param callable $callback The operation to execute
     * @return mixed The result of the callback
     */
    protected function withMetrics(string $operationName, callable $callback): mixed
    {
        $startTime = microtime(true);
        
        try {
            $result = $callback();
            $this->recordMetric($operationName, microtime(true) - $startTime, ['success' => true]);
            return $result;
        } catch (Throwable $e) {
            $this->recordMetric($operationName, microtime(true) - $startTime, [
                'success' => false,
                'exception' => get_class($e),
            ]);
            throw $e;
        }
    }

    /**
     * Log transaction start for audit trail.
     */
    private function logTransactionStart(string $transactionId, ?string $savepointName): void
    {
        $this->log('debug', 'Transaction started', [
            'transaction_id' => $transactionId,
            'savepoint_name' => $savepointName,
            'connection' => DB::getDefaultConnection(),
        ]);
    }

    /**
     * Handle transaction exceptions with detailed logging.
     */
    private function handleTransactionException(Throwable $e, string $transactionId, ?string $savepointName): void
    {
        $this->log('error', 'Transaction failed and rolled back', [
            'transaction_id' => $transactionId,
            'savepoint_name' => $savepointName,
            'exception' => get_class($e),
            'exception_message' => $e->getMessage(),
        ]);
    }

    /**
     * Record transaction performance metrics.
     */
    private function recordTransactionMetrics(string $transactionId, float $duration): void
    {
        $this->recordMetric('database_transaction', $duration, [
            'transaction_id' => $transactionId,
        ]);
    }

    /**
     * Determine if an exception is critical and requires immediate attention.
     */
    private function isCriticalException(Throwable $e): bool
    {
        return $e instanceof \Error ||
               $e instanceof \PDOException ||
               $e instanceof \Illuminate\Database\QueryException ||
               str_contains($e->getMessage(), 'memory') ||
               str_contains($e->getMessage(), 'timeout');
    }

    /**
     * Notify critical error monitoring systems.
     */
    private function notifyCriticalError(Throwable $e, array $context): void
    {
        // This would integrate with your monitoring system (Sentry, Bugsnag, etc.)
        // For now, just log at critical level
        $this->log('critical', 'Critical system error detected', array_merge($context, [
            'requires_immediate_attention' => true,
            'error_classification' => 'critical',
        ]));
    }
}
