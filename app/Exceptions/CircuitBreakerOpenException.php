<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when a circuit breaker is in the OPEN state
 * and requests are being blocked to prevent cascading failures.
 */
final class CircuitBreakerOpenException extends Exception
{
    public function __construct(
        private readonly string $serviceName,
        string $message = null,
        int $code = 0,
        ?Exception $previous = null,
    ) {
        $message = $message ?? "Circuit breaker is OPEN for service '{$this->serviceName}'. Requests are being blocked.";
        
        parent::__construct($message, $code, $previous);
    }
    
    public function getServiceName(): string
    {
        return $this->serviceName;
    }
    
    /**
     * Get the exception context for logging
     */
    public function context(): array
    {
        return [
            'service_name' => $this->serviceName,
            'circuit_breaker_state' => 'OPEN',
            'exception_type' => 'CircuitBreakerOpen',
        ];
    }
}