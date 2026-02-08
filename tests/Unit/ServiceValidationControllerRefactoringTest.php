<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\Api\ServiceValidationController;
use App\Repositories\MeterReadingRepository;
use App\Services\ServiceValidationEngine;
use App\Services\SystemHealthService;
use Tests\TestCase;

/**
 * Test class to verify the ServiceValidationController refactoring.
 * 
 * This test ensures that the refactored controller maintains
 * compatibility and follows SOLID principles.
 */
class ServiceValidationControllerRefactoringTest extends TestCase
{
    /**
     * Test that the controller can be instantiated with its dependencies.
     */
    public function test_controller_can_be_instantiated_with_dependencies(): void
    {
        // Since ServiceValidationEngine is final, we'll use the real instance
        $validationEngine = $this->app->make(ServiceValidationEngine::class);
        $healthService = $this->createMock(SystemHealthService::class);
        $repository = $this->createMock(MeterReadingRepository::class);

        $controller = new ServiceValidationController(
            $validationEngine,
            $healthService,
            $repository
        );

        $this->assertInstanceOf(ServiceValidationController::class, $controller);
    }

    /**
     * Test that all required services are registered in the container.
     */
    public function test_required_services_are_registered(): void
    {
        $this->assertTrue($this->app->bound(ServiceValidationEngine::class));
        $this->assertTrue($this->app->bound(SystemHealthService::class));
        $this->assertTrue($this->app->bound(MeterReadingRepository::class));
    }

    /**
     * Test that the controller can be resolved from the container.
     */
    public function test_controller_can_be_resolved_from_container(): void
    {
        $controller = $this->app->make(ServiceValidationController::class);
        
        $this->assertInstanceOf(ServiceValidationController::class, $controller);
    }

    /**
     * Test that the SystemHealthService has the required methods.
     */
    public function test_system_health_service_has_required_methods(): void
    {
        $service = $this->app->make(SystemHealthService::class);
        
        $this->assertTrue(method_exists($service, 'performHealthCheck'));
    }

    /**
     * Test that the MeterReadingRepository has the required methods.
     */
    public function test_meter_reading_repository_has_required_methods(): void
    {
        $repository = $this->app->make(MeterReadingRepository::class);
        
        $this->assertTrue(method_exists($repository, 'findManyWithRelations'));
        $this->assertTrue(method_exists($repository, 'getByStatusPaginated'));
        $this->assertTrue(method_exists($repository, 'getTodayValidationCount'));
        $this->assertTrue(method_exists($repository, 'bulkUpdateValidationStatus'));
    }
}