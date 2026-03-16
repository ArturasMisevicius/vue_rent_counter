<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Interface for circuit breaker implementations.
 * 
 * A circuit breaker prevents cascading failures by monitoring
 * service calls and temporarily blocking requests when failures
 * exceed a threshold.
 */
interface CircuitBreakerInterface
{
    /**
     * Execute a callable with circuit breaker protection.
     * 
     * @param string $serviceName Unique identifier for the service
     * @param callable $callback The operation to execute
     * @param callable|null $fallback Optional fallback when circuit is open
     * @return mixed The result of the callback or fallback
     * 
     * @throws \App\Exceptions\CircuitBreakerOpenException When circuit is open and no fallback provided
     */
    public function call(string $serviceName, callable $callback, ?callable $fallback = null): mixed;
    
    /**
     * Get the current status of a circuit breaker.
     * 
     * @param string $serviceName The service to check
     * @return array{service: string, state: string, failure_count: int, success_count: int, open_since: ?\Carbon\Carbon}
     */
    public function getStatus(string $serviceName): array;
    
    /**
     * Get status for all monitored services.
     * 
     * @return array<array{service: string, state: string, failure_count: int, success_count: int, open_since: ?\Carbon\Carbon}>
     */
    public function getAllStatus(): array;
    
    /**
     * Register a service for monitoring.
     * 
     * @param string $serviceName The service to monitor
     */
    public function registerService(string $serviceName): void;
    
    /**
     * Manually reset a circuit breaker to closed state.
     * 
     * @param string $serviceName The service to reset
     */
    public function reset(string $serviceName): void;
}