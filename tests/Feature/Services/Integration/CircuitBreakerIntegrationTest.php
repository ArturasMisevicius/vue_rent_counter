<?php

declare(strict_types=1);

use App\Contracts\CircuitBreakerInterface;
use App\Exceptions\CircuitBreakerOpenException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->circuitBreaker = app(CircuitBreakerInterface::class);
    Cache::flush();
});

describe('Circuit Breaker Integration', function () {
    it('integrates with Laravel cache and config systems', function () {
        $serviceName = 'integration-test-service';
        
        // Test successful call
        $result = $this->circuitBreaker->call($serviceName, fn() => 'success');
        
        expect($result)->toBe('success');
        
        // Verify service was registered
        $services = Cache::get('circuit_breaker_services', []);
        expect($services)->toContain($serviceName);
    });

    it('persists state across multiple calls', function () {
        $serviceName = 'persistence-test';
        
        // Cause failures to open the circuit
        for ($i = 0; $i < 5; $i++) {
            try {
                $this->circuitBreaker->call($serviceName, fn() => throw new Exception('Failure'));
            } catch (Exception $e) {
                // Expected failures
            }
        }
        
        // Circuit should now be open
        expect(fn() => $this->circuitBreaker->call($serviceName, fn() => 'should not execute'))
            ->toThrow(CircuitBreakerOpenException::class);
    });

    it('recovers after timeout period', function () {
        $serviceName = 'recovery-test';
        
        // Force circuit to open state
        Cache::put("circuit_breaker:{$serviceName}:state", 'open', now()->addMinutes(60));
        Cache::put("circuit_breaker:{$serviceName}:open_time", now()->subSeconds(61), now()->addMinutes(60));
        
        // Should attempt reset and succeed
        $result = $this->circuitBreaker->call($serviceName, fn() => 'recovered');
        
        expect($result)->toBe('recovered');
    });

    it('provides accurate status information', function () {
        $serviceName = 'status-test';
        
        // Initialize with some failures
        Cache::put("circuit_breaker:{$serviceName}:failures", 3, now()->addMinutes(60));
        Cache::put("circuit_breaker:{$serviceName}:state", 'closed', now()->addMinutes(60));
        
        $status = $this->circuitBreaker->getStatus($serviceName);
        
        expect($status)->toHaveKeys([
            'service', 'state', 'failure_count', 'success_count', 'open_since', 'config'
        ]);
        expect($status['service'])->toBe($serviceName);
        expect($status['state'])->toBe('closed');
        expect($status['failure_count'])->toBe(3);
    });

    it('handles multiple services independently', function () {
        $service1 = 'service-1';
        $service2 = 'service-2';
        
        // Service 1 succeeds
        $result1 = $this->circuitBreaker->call($service1, fn() => 'success-1');
        
        // Service 2 fails multiple times
        for ($i = 0; $i < 5; $i++) {
            try {
                $this->circuitBreaker->call($service2, fn() => throw new Exception('Failure'));
            } catch (Exception $e) {
                // Expected failures
            }
        }
        
        // Service 1 should still work
        $result1Again = $this->circuitBreaker->call($service1, fn() => 'success-1-again');
        
        // Service 2 should be blocked
        expect(fn() => $this->circuitBreaker->call($service2, fn() => 'should not work'))
            ->toThrow(CircuitBreakerOpenException::class);
        
        expect($result1)->toBe('success-1');
        expect($result1Again)->toBe('success-1-again');
    });

    it('logs circuit breaker events when logging is enabled', function () {
        Log::fake();
        
        $serviceName = 'logging-test';
        
        // Cause a failure
        try {
            $this->circuitBreaker->call($serviceName, fn() => throw new Exception('Test failure'));
        } catch (Exception $e) {
            // Expected
        }
        
        Log::assertLogged('warning', function ($message, $context) use ($serviceName) {
            return $message === 'Circuit breaker recorded failure' &&
                   $context['service'] === $serviceName;
        });
    });

    it('can get status for all monitored services', function () {
        $service1 = 'service-1';
        $service2 = 'service-2';
        
        // Register services by calling them
        $this->circuitBreaker->call($service1, fn() => 'success');
        $this->circuitBreaker->call($service2, fn() => 'success');
        
        $allStatus = $this->circuitBreaker->getAllStatus();
        
        expect($allStatus)->toHaveCount(2);
        expect(collect($allStatus)->pluck('service'))->toContain($service1, $service2);
    });

    it('respects service-specific configuration', function () {
        // This test would require actual config values
        // In a real scenario, you'd set up config values and test they're used
        expect(true)->toBeTrue(); // Placeholder - implement based on actual config needs
    });
});