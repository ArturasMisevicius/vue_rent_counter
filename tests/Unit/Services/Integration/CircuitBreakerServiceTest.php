<?php

declare(strict_types=1);

use App\Contracts\CircuitBreakerInterface;
use App\Exceptions\CircuitBreakerOpenException;
use App\Services\Integration\CircuitBreakerService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Psr\Log\LoggerInterface;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->cache = Mockery::mock(CacheRepository::class);
    $this->config = Mockery::mock(ConfigRepository::class);
    $this->logger = Mockery::mock(LoggerInterface::class);
    
    $this->circuitBreaker = new CircuitBreakerService(
        $this->cache,
        $this->config,
        $this->logger
    );
    
    // Default config
    $this->config->shouldReceive('get')
        ->with('circuit-breaker.default', [])
        ->andReturn([
            'failure_threshold' => 5,
            'recovery_timeout' => 60,
            'success_threshold' => 3,
            'cache_ttl' => 60,
            'registry_ttl' => 30,
        ])
        ->byDefault();
        
    $this->config->shouldReceive('get')
        ->with('circuit-breaker.logging.enabled', true)
        ->andReturn(true)
        ->byDefault();
});

afterEach(function () {
    Mockery::close();
});

describe('CircuitBreakerService', function () {
    it('implements CircuitBreakerInterface', function () {
        expect($this->circuitBreaker)->toBeInstanceOf(CircuitBreakerInterface::class);
    });

    it('executes callback successfully when circuit is closed', function () {
        $serviceName = 'test-service';
        $expectedResult = 'success';
        
        // Mock cache calls for closed state
        $this->cache->shouldReceive('get')
            ->with("circuit_breaker:{$serviceName}:state", 'closed')
            ->andReturn('closed');
            
        $this->cache->shouldReceive('get')
            ->with('circuit_breaker_services', [])
            ->andReturn([]);
            
        $this->cache->shouldReceive('put')
            ->with('circuit_breaker_services', [$serviceName], Mockery::any());
            
        $this->cache->shouldReceive('forget')
            ->with("circuit_breaker:{$serviceName}:failures");
        
        $callback = fn() => $expectedResult;
        
        $result = $this->circuitBreaker->call($serviceName, $callback);
        
        expect($result)->toBe($expectedResult);
    });

    it('throws CircuitBreakerOpenException when circuit is open and no fallback provided', function () {
        $serviceName = 'test-service';
        
        // Mock cache calls for open state
        $this->cache->shouldReceive('get')
            ->with("circuit_breaker:{$serviceName}:state", 'closed')
            ->andReturn('open');
            
        $this->cache->shouldReceive('get')
            ->with("circuit_breaker:{$serviceName}:open_time")
            ->andReturn(now());
            
        $this->cache->shouldReceive('get')
            ->with('circuit_breaker_services', [])
            ->andReturn([]);
            
        $this->cache->shouldReceive('put')
            ->with('circuit_breaker_services', [$serviceName], Mockery::any());
        
        $this->logger->shouldReceive('warning')
            ->once()
            ->with('Circuit breaker is open, request blocked', Mockery::any());
        
        $callback = fn() => 'should not execute';
        
        expect(fn() => $this->circuitBreaker->call($serviceName, $callback))
            ->toThrow(CircuitBreakerOpenException::class);
    });

    it('uses fallback when circuit is open', function () {
        $serviceName = 'test-service';
        $fallbackResult = 'fallback executed';
        
        // Mock cache calls for open state
        $this->cache->shouldReceive('get')
            ->with("circuit_breaker:{$serviceName}:state", 'closed')
            ->andReturn('open');
            
        $this->cache->shouldReceive('get')
            ->with("circuit_breaker:{$serviceName}:open_time")
            ->andReturn(now());
            
        $this->cache->shouldReceive('get')
            ->with('circuit_breaker_services', [])
            ->andReturn([]);
            
        $this->cache->shouldReceive('put')
            ->with('circuit_breaker_services', [$serviceName], Mockery::any());
        
        $this->logger->shouldReceive('warning')
            ->once()
            ->with('Circuit breaker is open, request blocked', Mockery::any());
        
        $callback = fn() => 'should not execute';
        $fallback = fn($e) => $fallbackResult;
        
        $result = $this->circuitBreaker->call($serviceName, $callback, $fallback);
        
        expect($result)->toBe($fallbackResult);
    });

    it('opens circuit when failure threshold is reached', function () {
        $serviceName = 'test-service';
        
        // Mock cache calls for closed state and failure tracking
        $this->cache->shouldReceive('get')
            ->with("circuit_breaker:{$serviceName}:state", 'closed')
            ->andReturn('closed');
            
        $this->cache->shouldReceive('get')
            ->with("circuit_breaker:{$serviceName}:failures", 0)
            ->andReturn(4); // One less than threshold
            
        $this->cache->shouldReceive('put')
            ->with("circuit_breaker:{$serviceName}:failures", 5, Mockery::any());
            
        $this->cache->shouldReceive('put')
            ->with("circuit_breaker:{$serviceName}:state", 'open', Mockery::any());
            
        $this->cache->shouldReceive('put')
            ->with("circuit_breaker:{$serviceName}:open_time", Mockery::any(), Mockery::any());
            
        $this->cache->shouldReceive('get')
            ->with('circuit_breaker_services', [])
            ->andReturn([]);
            
        $this->cache->shouldReceive('put')
            ->with('circuit_breaker_services', [$serviceName], Mockery::any());
        
        $this->logger->shouldReceive('warning')
            ->once()
            ->with('Circuit breaker recorded failure', Mockery::any());
            
        $this->logger->shouldReceive('info')
            ->once()
            ->with('Circuit breaker state changed', Mockery::any());
        
        $callback = fn() => throw new Exception('Service failure');
        
        expect(fn() => $this->circuitBreaker->call($serviceName, $callback))
            ->toThrow(Exception::class, 'Service failure');
    });

    it('resets circuit after successful calls in half-open state', function () {
        $serviceName = 'test-service';
        
        // Mock cache calls for half-open state
        $this->cache->shouldReceive('get')
            ->with("circuit_breaker:{$serviceName}:state", 'closed')
            ->andReturn('half_open');
            
        $this->cache->shouldReceive('get')
            ->with("circuit_breaker:{$serviceName}:successes", 0)
            ->andReturn(2); // One less than success threshold
            
        $this->cache->shouldReceive('put')
            ->with("circuit_breaker:{$serviceName}:successes", 3, Mockery::any());
            
        // Mock reset calls
        $this->cache->shouldReceive('forget')
            ->with("circuit_breaker:{$serviceName}:state");
        $this->cache->shouldReceive('forget')
            ->with("circuit_breaker:{$serviceName}:failures");
        $this->cache->shouldReceive('forget')
            ->with("circuit_breaker:{$serviceName}:successes");
        $this->cache->shouldReceive('forget')
            ->with("circuit_breaker:{$serviceName}:open_time");
            
        $this->cache->shouldReceive('get')
            ->with('circuit_breaker_services', [])
            ->andReturn([]);
            
        $this->cache->shouldReceive('put')
            ->with('circuit_breaker_services', [$serviceName], Mockery::any());
        
        $this->logger->shouldReceive('info')
            ->once()
            ->with('Circuit breaker reset to closed state', Mockery::any());
        
        $callback = fn() => 'success';
        
        $result = $this->circuitBreaker->call($serviceName, $callback);
        
        expect($result)->toBe('success');
    });

    it('returns correct status information', function () {
        $serviceName = 'test-service';
        
        $this->cache->shouldReceive('get')
            ->with("circuit_breaker:{$serviceName}:state", 'closed')
            ->andReturn('open');
            
        $this->cache->shouldReceive('get')
            ->with("circuit_breaker:{$serviceName}:failures", 0)
            ->andReturn(5);
            
        $this->cache->shouldReceive('get')
            ->with("circuit_breaker:{$serviceName}:successes", 0)
            ->andReturn(0);
            
        $this->cache->shouldReceive('get')
            ->with("circuit_breaker:{$serviceName}:open_time")
            ->andReturn($openTime = now());
        
        $status = $this->circuitBreaker->getStatus($serviceName);
        
        expect($status)->toHaveKeys([
            'service', 'state', 'failure_count', 'success_count', 'open_since', 'config'
        ]);
        expect($status['service'])->toBe($serviceName);
        expect($status['state'])->toBe('open');
        expect($status['failure_count'])->toBe(5);
        expect($status['success_count'])->toBe(0);
        expect($status['open_since'])->toBe($openTime);
    });

    it('uses service-specific configuration when available', function () {
        $serviceName = 'special-service';
        
        $this->config->shouldReceive('get')
            ->with("circuit-breaker.services.{$serviceName}", [])
            ->andReturn([
                'failure_threshold' => 2,
                'recovery_timeout' => 30,
            ]);
        
        // Mock cache calls for closed state and failure tracking
        $this->cache->shouldReceive('get')
            ->with("circuit_breaker:{$serviceName}:state", 'closed')
            ->andReturn('closed');
            
        $this->cache->shouldReceive('get')
            ->with("circuit_breaker:{$serviceName}:failures", 0)
            ->andReturn(1); // One less than custom threshold
            
        $this->cache->shouldReceive('put')
            ->with("circuit_breaker:{$serviceName}:failures", 2, Mockery::any());
            
        $this->cache->shouldReceive('put')
            ->with("circuit_breaker:{$serviceName}:state", 'open', Mockery::any());
            
        $this->cache->shouldReceive('put')
            ->with("circuit_breaker:{$serviceName}:open_time", Mockery::any(), Mockery::any());
            
        $this->cache->shouldReceive('get')
            ->with('circuit_breaker_services', [])
            ->andReturn([]);
            
        $this->cache->shouldReceive('put')
            ->with('circuit_breaker_services', [$serviceName], Mockery::any());
        
        $this->logger->shouldReceive('warning')
            ->once()
            ->with('Circuit breaker recorded failure', Mockery::any());
            
        $this->logger->shouldReceive('info')
            ->once()
            ->with('Circuit breaker state changed', Mockery::any());
        
        $callback = fn() => throw new Exception('Service failure');
        
        expect(fn() => $this->circuitBreaker->call($serviceName, $callback))
            ->toThrow(Exception::class, 'Service failure');
    });

    it('can manually reset a circuit breaker', function () {
        $serviceName = 'test-service';
        
        // Mock reset calls
        $this->cache->shouldReceive('forget')
            ->with("circuit_breaker:{$serviceName}:state");
        $this->cache->shouldReceive('forget')
            ->with("circuit_breaker:{$serviceName}:failures");
        $this->cache->shouldReceive('forget')
            ->with("circuit_breaker:{$serviceName}:successes");
        $this->cache->shouldReceive('forget')
            ->with("circuit_breaker:{$serviceName}:open_time");
        
        $this->logger->shouldReceive('info')
            ->once()
            ->with('Circuit breaker reset to closed state', Mockery::any());
        
        $this->circuitBreaker->reset($serviceName);
        
        // Verify the reset was called (mocks verify this)
        expect(true)->toBeTrue();
    });
});