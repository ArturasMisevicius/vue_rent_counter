<?php

declare(strict_types=1);

use App\Contracts\ServiceRegistration\PolicyRegistryInterface;
use App\Services\PolicyRegistryMonitoringService;
use Illuminate\Support\Facades\Cache;
use Mockery;

beforeEach(function () {
    Cache::flush();
    $this->mockRegistry = Mockery::mock(PolicyRegistryInterface::class);
    $this->service = new PolicyRegistryMonitoringService($this->mockRegistry);
});

describe('PolicyRegistryMonitoringService', function () {
    describe('healthCheck', function () {
        it('returns healthy status when validation passes', function () {
            $this->mockRegistry->shouldReceive('validateConfiguration')
                ->once()
                ->andReturn([
                    'valid' => true,
                    'policies' => ['valid' => 10, 'invalid' => 0, 'errors' => []],
                    'gates' => ['valid' => 5, 'invalid' => 0, 'errors' => []],
                ]);
            
            $this->mockRegistry->shouldReceive('getModelPolicies')
                ->once()
                ->andReturn(['Model1' => 'Policy1']);
            
            $this->mockRegistry->shouldReceive('getSettingsGates')
                ->once()
                ->andReturn(['gate1' => ['Policy1', 'method1']]);
            
            $result = $this->service->healthCheck();
            
            expect($result['healthy'])->toBeTrue();
            expect($result['metrics'])->toHaveKey('total_policies');
            expect($result['metrics'])->toHaveKey('total_gates');
            expect($result['issues']['critical'])->toBeEmpty();
        });

        it('returns unhealthy status when validation fails', function () {
            $this->mockRegistry->shouldReceive('validateConfiguration')
                ->once()
                ->andReturn([
                    'valid' => false,
                    'policies' => ['valid' => 8, 'invalid' => 2, 'errors' => ['Model1' => 'error']],
                    'gates' => ['valid' => 5, 'invalid' => 0, 'errors' => []],
                ]);
            
            $this->mockRegistry->shouldReceive('getModelPolicies')
                ->once()
                ->andReturn(['Model1' => 'Policy1']);
            
            $this->mockRegistry->shouldReceive('getSettingsGates')
                ->once()
                ->andReturn(['gate1' => ['Policy1', 'method1']]);
            
            $result = $this->service->healthCheck();
            
            expect($result['healthy'])->toBeFalse();
            expect($result['issues']['critical'])->toContain('Policy configuration validation failed');
        });

        it('caches health check results', function () {
            $this->mockRegistry->shouldReceive('validateConfiguration')
                ->once()
                ->andReturn([
                    'valid' => true,
                    'policies' => ['valid' => 10, 'invalid' => 0, 'errors' => []],
                    'gates' => ['valid' => 5, 'invalid' => 0, 'errors' => []],
                ]);
            
            $this->mockRegistry->shouldReceive('getModelPolicies')
                ->once()
                ->andReturn([]);
            
            $this->mockRegistry->shouldReceive('getSettingsGates')
                ->once()
                ->andReturn([]);
            
            $this->service->healthCheck();
            
            $cached = $this->service->getLastHealthCheck();
            expect($cached)->not->toBeNull();
            expect($cached['healthy'])->toBeTrue();
        });
    });

    describe('collectMetrics', function () {
        it('collects comprehensive metrics', function () {
            $this->mockRegistry->shouldReceive('getModelPolicies')
                ->once()
                ->andReturn(['Model1' => 'Policy1', 'Model2' => 'Policy2']);
            
            $this->mockRegistry->shouldReceive('getSettingsGates')
                ->once()
                ->andReturn(['gate1' => ['Policy1', 'method1']]);
            
            $metrics = $this->service->collectMetrics();
            
            expect($metrics['total_policies'])->toBe(2);
            expect($metrics['total_gates'])->toBe(1);
            expect($metrics)->toHaveKey('cache_hit_rate');
            expect($metrics)->toHaveKey('average_registration_time');
            expect($metrics)->toHaveKey('error_rate_24h');
        });
    });

    describe('recordRegistrationMetrics', function () {
        it('records performance metrics', function () {
            $this->service->recordRegistrationMetrics(0.05, 0); // 50ms, no errors
            
            // Verify metrics are stored
            $times = Cache::get('policy_registry_monitoring.registration_times', []);
            expect($times)->toContain(50.0);
            
            $operations = Cache::get('policy_registry_monitoring.operations_24h', 0);
            expect($operations)->toBe(1);
        });

        it('records error metrics', function () {
            $this->service->recordRegistrationMetrics(0.1, 2); // 100ms, 2 errors
            
            $errors = Cache::get('policy_registry_monitoring.errors_24h', 0);
            expect($errors)->toBe(1);
            
            $operations = Cache::get('policy_registry_monitoring.operations_24h', 0);
            expect($operations)->toBe(1);
        });

        it('limits stored registration times to 100 entries', function () {
            // Record 150 measurements
            for ($i = 0; $i < 150; $i++) {
                $this->service->recordRegistrationMetrics(0.01, 0);
            }
            
            $times = Cache::get('policy_registry_monitoring.registration_times', []);
            expect(count($times))->toBe(100);
        });
    });

    describe('clearMetrics', function () {
        it('clears all monitoring metrics', function () {
            // Set some metrics
            Cache::put('policy_registry_monitoring.cache_hits', 10);
            Cache::put('policy_registry_monitoring.errors_24h', 5);
            
            $this->service->clearMetrics();
            
            expect(Cache::get('policy_registry_monitoring.cache_hits'))->toBeNull();
            expect(Cache::get('policy_registry_monitoring.errors_24h'))->toBeNull();
        });
    });
});

afterEach(function () {
    Mockery::close();
});