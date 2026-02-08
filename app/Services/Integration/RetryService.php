<?php

declare(strict_types=1);

namespace App\Services\Integration;

use Illuminate\Support\Facades\Log;
use Exception;

class RetryService
{
    private int $maxAttempts;
    private int $baseDelay;
    private float $multiplier;
    private int $maxDelay;
    
    public function __construct(
        int $maxAttempts = 3,
        int $baseDelay = 1000, // milliseconds
        float $multiplier = 2.0,
        int $maxDelay = 30000 // milliseconds
    ) {
        $this->maxAttempts = $maxAttempts;
        $this->baseDelay = $baseDelay;
        $this->multiplier = $multiplier;
        $this->maxDelay = $maxDelay;
    }
    
    /**
     * Execute operation with exponential backoff retry
     */
    public function execute(callable $operation): mixed
    {
        $attempt = 1;
        $lastException = null;
        
        while ($attempt <= $this->maxAttempts) {
            try {
                $result = $operation();
                
                if ($attempt > 1) {
                    Log::info('Operation succeeded after retry', [
                        'attempt' => $attempt,
                        'total_attempts' => $this->maxAttempts,
                    ]);
                }
                
                return $result;
                
            } catch (Exception $e) {
                $lastException = $e;
                
                Log::warning('Operation failed, will retry', [
                    'attempt' => $attempt,
                    'max_attempts' => $this->maxAttempts,
                    'error' => $e->getMessage(),
                ]);
                
                if ($attempt < $this->maxAttempts) {
                    $delay = $this->calculateDelay($attempt);
                    $this->sleep($delay);
                }
                
                $attempt++;
            }
        }
        
        Log::error('Operation failed after all retry attempts', [
            'total_attempts' => $this->maxAttempts,
            'final_error' => $lastException?->getMessage(),
        ]);
        
        throw $lastException;
    }
    
    /**
     * Execute operation with custom retry configuration
     */
    public function executeWithConfig(
        callable $operation,
        int $maxAttempts = null,
        int $baseDelay = null,
        float $multiplier = null
    ): mixed {
        $originalMaxAttempts = $this->maxAttempts;
        $originalBaseDelay = $this->baseDelay;
        $originalMultiplier = $this->multiplier;
        
        // Temporarily override configuration
        if ($maxAttempts !== null) {
            $this->maxAttempts = $maxAttempts;
        }
        if ($baseDelay !== null) {
            $this->baseDelay = $baseDelay;
        }
        if ($multiplier !== null) {
            $this->multiplier = $multiplier;
        }
        
        try {
            return $this->execute($operation);
        } finally {
            // Restore original configuration
            $this->maxAttempts = $originalMaxAttempts;
            $this->baseDelay = $originalBaseDelay;
            $this->multiplier = $originalMultiplier;
        }
    }
    
    /**
     * Calculate delay for exponential backoff
     */
    private function calculateDelay(int $attempt): int
    {
        $delay = $this->baseDelay * pow($this->multiplier, $attempt - 1);
        
        // Add jitter to prevent thundering herd
        $jitter = rand(0, (int)($delay * 0.1));
        $delay += $jitter;
        
        return min((int)$delay, $this->maxDelay);
    }
    
    /**
     * Sleep for specified milliseconds
     */
    private function sleep(int $milliseconds): void
    {
        usleep($milliseconds * 1000);
    }
    
    /**
     * Check if exception is retryable
     */
    public function isRetryable(Exception $exception): bool
    {
        // Define which exceptions should be retried
        $retryableExceptions = [
            \Illuminate\Http\Client\ConnectionException::class,
            \Illuminate\Http\Client\RequestException::class,
            \GuzzleHttp\Exception\ConnectException::class,
            \GuzzleHttp\Exception\RequestException::class,
        ];
        
        foreach ($retryableExceptions as $retryableException) {
            if ($exception instanceof $retryableException) {
                return true;
            }
        }
        
        // Check HTTP status codes for retryable errors
        if (method_exists($exception, 'getResponse')) {
            $response = $exception->getResponse();
            if ($response && method_exists($response, 'getStatusCode')) {
                $statusCode = $response->getStatusCode();
                
                // Retry on server errors and rate limiting
                return in_array($statusCode, [429, 500, 502, 503, 504]);
            }
        }
        
        return false;
    }
    
    /**
     * Execute with conditional retry based on exception type
     */
    public function executeWithConditionalRetry(callable $operation): mixed
    {
        $attempt = 1;
        $lastException = null;
        
        while ($attempt <= $this->maxAttempts) {
            try {
                return $operation();
                
            } catch (Exception $e) {
                $lastException = $e;
                
                // Only retry if exception is retryable
                if (!$this->isRetryable($e)) {
                    Log::info('Exception is not retryable, failing immediately', [
                        'exception' => get_class($e),
                        'message' => $e->getMessage(),
                    ]);
                    throw $e;
                }
                
                Log::warning('Retryable exception occurred', [
                    'attempt' => $attempt,
                    'max_attempts' => $this->maxAttempts,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]);
                
                if ($attempt < $this->maxAttempts) {
                    $delay = $this->calculateDelay($attempt);
                    $this->sleep($delay);
                }
                
                $attempt++;
            }
        }
        
        throw $lastException;
    }
}