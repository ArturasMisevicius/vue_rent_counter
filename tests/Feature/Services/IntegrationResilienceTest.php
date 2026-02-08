<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Exceptions\IntegrationException;
use App\Services\Integration\IntegrationResilienceHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class IntegrationResilienceTest extends TestCase
{
    use RefreshDatabase;

    private IntegrationResilienceHandler $resilienceHandler;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->resilienceHandler = new IntegrationResilienceHandler();
        
        Cache::flush();
    }

    public function test_execute_with_resilience_success(): void
    {
        $serviceName = 'test-service';
        $expectedResult = ['success' => true, 'data' => 'test'];
        
        $result = $this->resilienceHandler->executeWithResilience(
            $serviceName,
            function () use ($expectedResult) {
                return $expectedResult;
            }
        );
        
        $this->assertEquals($expectedResult, $result);
    }

    public function test_execute_with_resilience_with_fallback(): void
    {
        $serviceName = 'test-service';
        $fallbackData = ['fallback' => true];
        
        $result = $this->resilienceHandler->executeWithResilience(
            $serviceName,
            function () {
                throw new \Exception('Service failure');
            },
            $fallbackData
        );
        
        $this->assertEquals($fallbackData, $result);
    }

    public function test_execute_with_resilience_offline_mode(): void
    {
        $serviceName = 'test-service';
        
        $result = $this->resilienceHandler->executeWithResilience(
            $serviceName,
            function () {
                throw new \Exception('Service failure');
            },
            [],
            true // allow offline
        );
        
        $this->assertEquals(['offline' => true, 'service' => $serviceName], $result);
    }

    public function test_queue_for_later_execution(): void
    {
        $serviceName = 'test-service';
        $operationData = ['operation' => 'test'];
        
        $jobId = $this->resilienceHandler->queueForLaterExecution(
            $serviceName,
            $operationData
        );
        
        $this->assertIsString($jobId);
        $this->assertStringStartsWith('integration_', $jobId);
    }

    public function test_synchronize_offline_data(): void
    {
        $serviceName = 'test-service';
        
        $result = $this->resilienceHandler->synchronizeOfflineData($serviceName);
        
        $this->assertEquals(['synchronized' => 0, 'errors' => 0], $result);
    }

    public function test_get_services_health_status(): void
    {
        $healthStatus = $this->resilienceHandler->getServicesHealthStatus();
        
        $this->assertIsArray($healthStatus);
        $this->assertArrayHasKey('services', $healthStatus);
        $this->assertIsArray($healthStatus['services']);
    }

    public function test_perform_health_check(): void
    {
        $serviceName = 'test-service';
        
        $result = $this->resilienceHandler->performHealthCheck($serviceName);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('service', $result);
        $this->assertEquals($serviceName, $result['service']);
    }

    public function test_enable_maintenance_mode(): void
    {
        $serviceName = 'test-service';
        
        $this->resilienceHandler->enableMaintenanceMode($serviceName, 30);
        
        $this->assertTrue($this->resilienceHandler->isInMaintenanceMode($serviceName));
    }

    public function test_cache_operation_data(): void
    {
        $serviceName = 'test-service';
        $data = ['cached' => 'data'];
        
        $this->resilienceHandler->cacheOperationData($serviceName, $data);
        
        // Verify data is cached
        $cached = Cache::get("service_cache:{$serviceName}:data");
        $this->assertEquals($data, $cached);
    }
}