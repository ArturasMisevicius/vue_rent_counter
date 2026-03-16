<?php

declare(strict_types=1);

namespace App\Services\ServiceRegistration;

use App\Contracts\ServiceRegistration\ErrorHandlingStrategyInterface;
use App\ValueObjects\ServiceRegistration\RegistrationResult;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;

/**
 * Handles service registration errors with environment-specific strategies
 */
final readonly class RegistrationErrorHandler implements ErrorHandlingStrategyInterface
{
    public function __construct(
        private Application $app,
    ) {}

    public function handleRegistration(callable $operation, string $context): RegistrationResult
    {
        $startTime = microtime(true);
        
        try {
            $result = $operation();
            $duration = (microtime(true) - $startTime) * 1000;
            
            return new RegistrationResult(
                registered: $result['registered'] ?? 0,
                skipped: $result['skipped'] ?? 0,
                errors: $result['errors'] ?? [],
                durationMs: $duration,
            );
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            
            Log::debug('Registration skipped due to authorization', [
                'context' => $context,
                'reason' => 'unauthorized_user',
                'duration_ms' => $duration,
            ]);
            
            return new RegistrationResult(0, 0, [], $duration);
        } catch (\Throwable $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            
            Log::error('Registration operation failed', [
                'context' => $context,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
            ]);
            
            return new RegistrationResult(0, 0, ['system' => 'registration_failed'], $duration);
        }
    }

    public function logResults(RegistrationResult $result, string $context): void
    {
        $logData = [
            ...$result->toArray(),
            'context' => $context,
        ];

        // Log detailed results in development and testing
        if ($this->app->environment('local', 'testing')) {
            Log::info('Registration completed', $logData);
        }

        // Alert on production issues without exposing sensitive details
        if ($this->app->environment('production') && $result->hasErrors()) {
            Log::warning('Registration issues detected', [
                'error_count' => $result->getErrorCount(),
                'registered_count' => $result->registered,
                'context' => $context,
            ]);
        }
    }

    public function handleCriticalFailure(\Throwable $exception, string $context): void
    {
        Log::critical('Critical failure in service registration', [
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'context' => $context,
            'trace' => $this->app->environment('local', 'testing') 
                ? $exception->getTraceAsString() 
                : 'trace_hidden_in_production',
        ]);

        // In development/testing, we want to fail fast for debugging
        if ($this->app->environment('local', 'testing')) {
            throw $exception;
        }

        // In production, log the error but continue booting
        // This prevents the entire application from failing due to policy registration issues
    }
}