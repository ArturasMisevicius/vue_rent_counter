<?php

declare(strict_types=1);

namespace Tests\Unit\Services\ServiceRegistration;

use App\Contracts\ServiceRegistration\ErrorHandlingStrategyInterface;
use App\Contracts\ServiceRegistration\PolicyRegistryInterface;
use App\Services\PolicyRegistryMonitoringService;
use App\Services\ServiceRegistration\ServiceRegistrationOrchestrator;
use App\ValueObjects\ServiceRegistration\RegistrationResult;
use Illuminate\Contracts\Foundation\Application;
use Mockery;
use Tests\TestCase;

final class ServiceRegistrationOrchestratorTest extends TestCase
{
    private ServiceRegistrationOrchestrator $orchestrator;
    private Application $app;
    private ErrorHandlingStrategyInterface $errorHandler;
    private PolicyRegistryMonitoringService $monitoringService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->app = Mockery::mock(Application::class);
        $this->errorHandler = Mockery::mock(ErrorHandlingStrategyInterface::class);
        $this->monitoringService = Mockery::mock(PolicyRegistryMonitoringService::class);
        
        $this->orchestrator = new ServiceRegistrationOrchestrator(
            $this->app,
            $this->errorHandler,
            $this->monitoringService
        );
    }

    public function test_registers_policies_successfully(): void
    {
        $policyRegistry = Mockery::mock(PolicyRegistryInterface::class);
        
        $this->app->shouldReceive('make')
            ->with(PolicyRegistryInterface::class)
            ->andReturn($policyRegistry);

        $policyResult = new RegistrationResult(5, 0, [], 50.0);
        $gateResult = new RegistrationResult(3, 0, [], 30.0);

        $this->errorHandler->shouldReceive('handleRegistration')
            ->twice()
            ->andReturn($policyResult, $gateResult);

        $this->errorHandler->shouldReceive('logResults')
            ->once()
            ->with(Mockery::type(RegistrationResult::class), 'combined_registration');

        $this->monitoringService->shouldReceive('recordRegistrationMetrics')
            ->once()
            ->with(0.08, 0); // 80ms total, 0 errors

        $this->orchestrator->registerPolicies();
    }

    public function test_handles_registry_creation_failure(): void
    {
        $exception = new \RuntimeException('Registry creation failed');
        
        $this->app->shouldReceive('make')
            ->with(PolicyRegistryInterface::class)
            ->andThrow($exception);

        $this->errorHandler->shouldReceive('handleCriticalFailure')
            ->once()
            ->with($exception, 'policy_orchestration');

        $this->orchestrator->registerPolicies();
    }

    public function test_validates_configuration(): void
    {
        $policyRegistry = Mockery::mock(PolicyRegistryInterface::class);
        
        $this->app->shouldReceive('make')
            ->with(PolicyRegistryInterface::class)
            ->andReturn($policyRegistry);

        $expectedValidation = [
            'valid' => true,
            'policies' => ['valid' => 5, 'invalid' => 0, 'errors' => []],
            'gates' => ['valid' => 3, 'invalid' => 0, 'errors' => []],
        ];

        $policyRegistry->shouldReceive('validateConfiguration')
            ->once()
            ->andReturn($expectedValidation);

        $result = $this->orchestrator->validateConfiguration();
        
        $this->assertEquals($expectedValidation, $result);
    }

    public function test_handles_validation_failure(): void
    {
        $exception = new \RuntimeException('Validation failed');
        
        $this->app->shouldReceive('make')
            ->with(PolicyRegistryInterface::class)
            ->andThrow($exception);

        $result = $this->orchestrator->validateConfiguration();
        
        $this->assertFalse($result['valid']);
        $this->assertEquals('Validation failed', $result['error']);
    }

    public function test_works_without_monitoring_service(): void
    {
        $orchestrator = new ServiceRegistrationOrchestrator(
            $this->app,
            $this->errorHandler,
            null // No monitoring service
        );

        $policyRegistry = Mockery::mock(PolicyRegistryInterface::class);
        
        $this->app->shouldReceive('make')
            ->with(PolicyRegistryInterface::class)
            ->andReturn($policyRegistry);

        $result = new RegistrationResult(5, 0, [], 50.0);

        $this->errorHandler->shouldReceive('handleRegistration')
            ->twice()
            ->andReturn($result);

        $this->errorHandler->shouldReceive('logResults')
            ->once();

        // Should not call monitoring service
        $orchestrator->registerPolicies();
        
        $this->assertTrue(true); // Test passes if no exceptions
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}