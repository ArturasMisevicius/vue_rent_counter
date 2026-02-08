<?php

declare(strict_types=1);

namespace App\Services\Integration;

use App\Contracts\CircuitBreakerInterface;
use App\Exceptions\CircuitBreakerOpenException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Exception;

/**
 * Circuit Breaker Service Implementation
 * 
 * Prevents cascading failures by monitoring service calls and temporarily
 * blocking requests when failures exceed configured thresholds.
 * 
 * @package App\Services\Integration
 */
final readonly class CircuitBreakerService implements CircuitBreakerInterface
{
    private const STATE_CLOSED = 'closed';
    private const STATE_OPEN = 'open';
    private const STATE_HALF_OPEN = 'half_open';
    
    public function __construct(
        private CacheRepository $cache,
        private ConfigRepository $config,
        private LoggerInterface $logger,
    ) {}
    
    /**
     * Execute a callable with circuit breaker protection
     */
    public function call(string $serviceName, callable $callback, ?callable $fallback = null): mixed
    {
        $this->registerService($serviceName);
        $state = $this->getState($serviceName);
        
        if ($state === self::STATE_OPEN) {
            if ($this->shouldAttemptReset($serviceName)) {
                $this->setState($serviceName, self::STATE_HALF_OPEN);
            } else {
                return $this->handleOpenCircuit($serviceName, $fallback);
            }
        }
        
        try {
            $result = $callback();
            $this->onSuccess($serviceName);
            return $result;
        } catch (Exception $e) {
            $this->onFailure($serviceName, $e);
            
            if ($fallback) {
                return $fallback($e);
            }
            
            throw $e;
        }
    }
    
    /**
     * Get configuration for a specific service or use defaults
     */
    private function getServiceConfig(string $serviceName): array
    {
        $serviceConfig = $this->config->get("circuit-breaker.services.{$serviceName}", []);
        $defaultConfig = $this->config->get('circuit-breaker.default', []);
        
        return array_merge($defaultConfig, $serviceConfig);
    }
    
    /**
     * Get the current state of the circuit breaker
     */
    private function getState(string $serviceName): string
    {
        return $this->cache->get($this->getStateKey($serviceName), self::STATE_CLOSED);
    }
    
    /**
     * Set the state of the circuit breaker
     */
    private function setState(string $serviceName, string $state): void
    {
        $cacheTtl = $this->getServiceConfig($serviceName)['cache_ttl'] ?? 60;
        
        $this->cache->put($this->getStateKey($serviceName), $state, now()->addMinutes($cacheTtl));
        
        if ($state === self::STATE_OPEN) {
            $this->cache->put($this->getOpenTimeKey($serviceName), now(), now()->addMinutes($cacheTtl));
        }
        
        if ($this->isLoggingEnabled()) {
            $this->logger->info('Circuit breaker state changed', [
                'service' => $serviceName,
                'state' => $state,
                'timestamp' => now()->toISOString(),
            ]);
        }
    }
    
    /**
     * Handle success response
     */
    private function onSuccess(string $serviceName): void
    {
        $state = $this->getState($serviceName);
        
        if ($state === self::STATE_HALF_OPEN) {
            $successCount = $this->incrementSuccessCount($serviceName);
            $successThreshold = $this->getServiceConfig($serviceName)['success_threshold'] ?? 3;
            
            if ($successCount >= $successThreshold) {
                $this->reset($serviceName);
            }
        } elseif ($state === self::STATE_CLOSED) {
            $this->resetFailureCount($serviceName);
        }
    }
    
    /**
     * Handle failure response
     */
    private function onFailure(string $serviceName, Exception $e): void
    {
        $failureCount = $this->incrementFailureCount($serviceName);
        $failureThreshold = $this->getServiceConfig($serviceName)['failure_threshold'] ?? 5;
        
        if ($this->isLoggingEnabled()) {
            $this->logger->warning('Circuit breaker recorded failure', [
                'service' => $serviceName,
                'failure_count' => $failureCount,
                'failure_threshold' => $failureThreshold,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'timestamp' => now()->toISOString(),
            ]);
        }
        
        if ($failureCount >= $failureThreshold) {
            $this->setState($serviceName, self::STATE_OPEN);
        }
    }
    
    /**
     * Check if we should attempt to reset the circuit breaker
     */
    private function shouldAttemptReset(string $serviceName): bool
    {
        $openTime = $this->cache->get($this->getOpenTimeKey($serviceName));
        
        if (!$openTime) {
            return true;
        }
        
        $recoveryTimeout = $this->getServiceConfig($serviceName)['recovery_timeout'] ?? 60;
        
        return now()->diffInSeconds($openTime) >= $recoveryTimeout;
    }
    
    /**
     * Handle open circuit scenario
     */
    private function handleOpenCircuit(string $serviceName, ?callable $fallback): mixed
    {
        if ($this->isLoggingEnabled()) {
            $this->logger->warning('Circuit breaker is open, request blocked', [
                'service' => $serviceName,
                'timestamp' => now()->toISOString(),
            ]);
        }
        
        if ($fallback) {
            return $fallback(new CircuitBreakerOpenException($serviceName));
        }
        
        throw new CircuitBreakerOpenException($serviceName);
    }
    
    /**
     * Reset the circuit breaker to closed state
     */
    public function reset(string $serviceName): void
    {
        $keys = [
            $this->getStateKey($serviceName),
            $this->getFailureCountKey($serviceName),
            $this->getSuccessCountKey($serviceName),
            $this->getOpenTimeKey($serviceName),
        ];
        
        // Batch delete for better performance
        foreach ($keys as $key) {
            $this->cache->forget($key);
        }
        
        if ($this->isLoggingEnabled()) {
            $this->logger->info('Circuit breaker reset to closed state', [
                'service' => $serviceName,
                'timestamp' => now()->toISOString(),
            ]);
        }
    }
    
    /**
     * Increment failure count
     */
    private function incrementFailureCount(string $serviceName): int
    {
        $key = $this->getFailureCountKey($serviceName);
        $cacheTtl = $this->getServiceConfig($serviceName)['cache_ttl'] ?? 60;
        $count = $this->cache->get($key, 0) + 1;
        
        $this->cache->put($key, $count, now()->addMinutes($cacheTtl));
        
        return $count;
    }
    
    /**
     * Reset failure count
     */
    private function resetFailureCount(string $serviceName): void
    {
        $this->cache->forget($this->getFailureCountKey($serviceName));
    }
    
    /**
     * Increment success count
     */
    private function incrementSuccessCount(string $serviceName): int
    {
        $key = $this->getSuccessCountKey($serviceName);
        $cacheTtl = $this->getServiceConfig($serviceName)['cache_ttl'] ?? 60;
        $count = $this->cache->get($key, 0) + 1;
        
        $this->cache->put($key, $count, now()->addMinutes($cacheTtl));
        
        return $count;
    }
    
    /**
     * Get circuit breaker status for monitoring
     */
    public function getStatus(string $serviceName): array
    {
        return [
            'service' => $serviceName,
            'state' => $this->getState($serviceName),
            'failure_count' => $this->cache->get($this->getFailureCountKey($serviceName), 0),
            'success_count' => $this->cache->get($this->getSuccessCountKey($serviceName), 0),
            'open_since' => $this->cache->get($this->getOpenTimeKey($serviceName)),
            'config' => $this->getServiceConfig($serviceName),
        ];
    }
    
    /**
     * Get all monitored services status
     */
    public function getAllStatus(): array
    {
        $services = $this->cache->get('circuit_breaker_services', []);
        
        return collect($services)->map(fn($service) => $this->getStatus($service))->toArray();
    }
    
    /**
     * Register a service for monitoring
     */
    public function registerService(string $serviceName): void
    {
        $services = $this->cache->get('circuit_breaker_services', []);
        
        if (!in_array($serviceName, $services, true)) {
            $services[] = $serviceName;
            $registryTtl = $this->config->get('circuit-breaker.default.registry_ttl', 30);
            
            $this->cache->put('circuit_breaker_services', $services, now()->addDays($registryTtl));
        }
    }
    
    /**
     * Check if logging is enabled
     */
    private function isLoggingEnabled(): bool
    {
        return $this->config->get('circuit-breaker.logging.enabled', true);
    }
    
    /**
     * Generate cache keys for circuit breaker data
     */
    private function getStateKey(string $serviceName): string
    {
        return "circuit_breaker:{$serviceName}:state";
    }
    
    private function getFailureCountKey(string $serviceName): string
    {
        return "circuit_breaker:{$serviceName}:failures";
    }
    
    private function getSuccessCountKey(string $serviceName): string
    {
        return "circuit_breaker:{$serviceName}:successes";
    }
    
    private function getOpenTimeKey(string $serviceName): string
    {
        return "circuit_breaker:{$serviceName}:open_time";
    }
}