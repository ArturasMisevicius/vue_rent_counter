<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Exceptions\CircuitBreakerOpenException;
use App\Services\Integration\CircuitBreakerService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CircuitBreakerServiceTest extends TestCase
{
    private CircuitBreakerService $circuitBreaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->circuitBreaker = new CircuitBreakerService(
            failureThreshold: 2,
            recoveryTimeout: 30,
            successThreshold: 1
        );
        
        Cache::flush();
    }

    public function test_successful_call_returns_result(): void
    {
        $result = $this->circuitBreaker->call('test-service', function () {
            return 'success';
        });
        
        $this->assertEquals('success', $result);
    }

    public function test_failed_call_throws_exception(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test failure');
        
        $this->circuitBreaker->call('test-service', function () {
            throw new \Exception('Test failure');
        });
    }

    public function test_circuit_opens_after_threshold_failures(): void
    {
        $serviceName = 'test-service';
        
        // First failure
        try {
            $this->circuitBreaker->call($serviceName, function () {
                throw new \Exception('Failure 1');
            });
        } catch (\Exception $e) {
            // Expected
        }
        
        $status = $this->circuitBreaker->getStatus($serviceName);
        $this->assertEquals('closed', $status['state']);
        $this->assertEquals(1, $status['failure_count']);
        
        // Second failure should open circuit
        try {
            $this->circuitBreaker->call($serviceName, function () {
                throw new \Exception('Failure 2');
            });
        } catch (\Exception $e) {
            // Expected
        }
        
        $status = $this->circuitBreaker->getStatus($serviceName);
        $this->assertEquals('open', $status['state']);
        $this->assertEquals(2, $status['failure_count']);
    }

    public function test_open_circuit_blocks_calls(): void
    {
        $serviceName = 'test-service';
        
        // Force circuit to open
        for ($i = 0; $i < 2; $i++) {
            try {
                $this->circuitBreaker->call($serviceName, function () {
                    throw new \Exception('Failure');
                });
            } catch (\Exception $e) {
                // Expected
            }
        }
        
        // Next call should be blocked
        $this->expectException(CircuitBreakerOpenException::class);
        
        $this->circuitBreaker->call($serviceName, function () {
            return 'should not execute';
        });
    }

    public function test_fallback_is_called_when_circuit_open(): void
    {
        $serviceName = 'test-service';
        
        // Force circuit to open
        for ($i = 0; $i < 2; $i++) {
            try {
                $this->circuitBreaker->call($serviceName, function () {
                    throw new \Exception('Failure');
                });
            } catch (\Exception $e) {
                // Expected
            }
        }
        
        // Call with fallback
        $result = $this->circuitBreaker->call(
            $serviceName,
            function () {
                return 'primary';
            },
            function ($exception) {
                $this->assertInstanceOf(CircuitBreakerOpenException::class, $exception);
                return 'fallback';
            }
        );
        
        $this->assertEquals('fallback', $result);
    }

    public function test_circuit_transitions_to_half_open_after_timeout(): void
    {
        $serviceName = 'test-service';
        
        // Force circuit to open
        for ($i = 0; $i < 2; $i++) {
            try {
                $this->circuitBreaker->call($serviceName, function () {
                    throw new \Exception('Failure');
                });
            } catch (\Exception $e) {
                // Expected
            }
        }
        
        // Simulate timeout by manipulating cache
        Cache::put("circuit_breaker:{$serviceName}:open_time", now()->subSeconds(31), 60);
        
        // Next call should transition to half-open and succeed
        $result = $this->circuitBreaker->call($serviceName, function () {
            return 'success';
        });
        
        $this->assertEquals('success', $result);
        
        // Circuit should be closed again
        $status = $this->circuitBreaker->getStatus($serviceName);
        $this->assertEquals('closed', $status['state']);
    }

    public function test_service_registration_and_status(): void
    {
        $serviceName = 'test-service';
        
        $this->circuitBreaker->registerService($serviceName);
        
        $allStatus = $this->circuitBreaker->getAllStatus();
        $this->assertCount(1, $allStatus);
        $this->assertEquals($serviceName, $allStatus[0]['service']);
    }

    public function test_success_resets_failure_count(): void
    {
        $serviceName = 'test-service';
        
        // One failure
        try {
            $this->circuitBreaker->call($serviceName, function () {
                throw new \Exception('Failure');
            });
        } catch (\Exception $e) {
            // Expected
        }
        
        $status = $this->circuitBreaker->getStatus($serviceName);
        $this->assertEquals(1, $status['failure_count']);
        
        // Success should reset failure count
        $this->circuitBreaker->call($serviceName, function () {
            return 'success';
        });
        
        $status = $this->circuitBreaker->getStatus($serviceName);
        $this->assertEquals(0, $status['failure_count']);
    }
}