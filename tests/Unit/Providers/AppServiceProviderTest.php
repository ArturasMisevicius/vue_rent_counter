<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use App\Contracts\ServiceRegistration\ErrorHandlingStrategyInterface;
use App\Contracts\ServiceRegistration\PolicyRegistryInterface;
use App\Providers\AppServiceProvider;
use App\Services\ServiceRegistration\ServiceRegistrationOrchestrator;
use App\Services\TenantBoundaryService;
use App\Services\TenantContext;
use App\Support\ServiceRegistration\PolicyRegistry;
use Illuminate\Translation\FileLoader;
use Mockery;
use Tests\TestCase;

/**
 * Test suite for refactored AppServiceProvider
 *
 * Tests the improved architecture with proper separation of concerns,
 * dependency injection, and error handling strategies.
 */
final class AppServiceProviderTest extends TestCase
{
    private AppServiceProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new AppServiceProvider($this->app);
    }

    public function test_provider_is_final(): void
    {
        $reflection = new \ReflectionClass(AppServiceProvider::class);

        $this->assertTrue(
            $reflection->isFinal(),
            'AppServiceProvider should be final'
        );
    }

    public function test_register_method_registers_core_services(): void
    {
        $this->provider->register();

        // Test that core services are registered as singletons
        $this->assertTrue(
            $this->app->bound(TenantContext::class),
            'TenantContext should be registered'
        );

        $this->assertTrue(
            $this->app->bound(TenantBoundaryService::class),
            'TenantBoundaryService should be registered'
        );

        $this->assertTrue(
            $this->app->bound(PolicyRegistryInterface::class),
            'PolicyRegistryInterface should be registered'
        );

        $this->assertTrue(
            $this->app->bound(ErrorHandlingStrategyInterface::class),
            'ErrorHandlingStrategyInterface should be registered'
        );

        $this->assertTrue(
            $this->app->bound(ServiceRegistrationOrchestrator::class),
            'ServiceRegistrationOrchestrator should be registered'
        );
    }

    public function test_register_method_configures_laravel12_compatibility(): void
    {
        $this->provider->register();

        // Test translation loader registration
        $this->assertTrue(
            $this->app->bound('translation.loader'),
            'Translation loader should be registered'
        );

        $loader = $this->app->make('translation.loader');
        $this->assertInstanceOf(
            FileLoader::class,
            $loader,
            'Translation loader should be FileLoader instance'
        );
    }

    public function test_services_are_registered_as_singletons(): void
    {
        $this->provider->register();

        // Test singleton behavior
        $tenantContext1 = $this->app->make(TenantContext::class);
        $tenantContext2 = $this->app->make(TenantContext::class);

        $this->assertSame(
            $tenantContext1,
            $tenantContext2,
            'TenantContext should be registered as singleton'
        );

        $errorHandler1 = $this->app->make(ErrorHandlingStrategyInterface::class);
        $errorHandler2 = $this->app->make(ErrorHandlingStrategyInterface::class);

        $this->assertSame(
            $errorHandler1,
            $errorHandler2,
            'ErrorHandlingStrategy should be registered as singleton'
        );
    }

    public function test_boot_method_delegates_to_orchestrator(): void
    {
        $mockErrorHandler = Mockery::mock(ErrorHandlingStrategyInterface::class);
        $mockOrchestrator = Mockery::mock(
            new ServiceRegistrationOrchestrator($this->app, $mockErrorHandler)
        )->makePartial();
        $mockOrchestrator->shouldReceive('registerPolicies')
            ->once();

        $this->app->instance(ServiceRegistrationOrchestrator::class, $mockOrchestrator);

        $this->provider->boot();
    }

    public function test_boot_method_handles_orchestrator_failure_in_development(): void
    {
        $mockApp = Mockery::mock($this->app)->makePartial();
        $mockApp->shouldReceive('environment')
            ->with('local', 'testing')
            ->andReturn(true);

        $mockErrorHandler = Mockery::mock(ErrorHandlingStrategyInterface::class);
        $mockOrchestrator = Mockery::mock(
            new ServiceRegistrationOrchestrator($mockApp, $mockErrorHandler)
        )->makePartial();
        $mockOrchestrator->shouldReceive('registerPolicies')
            ->once()
            ->andThrow(new \RuntimeException('Orchestrator failed'));

        $mockApp->instance(ServiceRegistrationOrchestrator::class, $mockOrchestrator);
        $provider = new AppServiceProvider($mockApp);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Orchestrator failed');

        $provider->boot();
    }

    public function test_boot_method_handles_orchestrator_failure_in_production(): void
    {
        $mockApp = Mockery::mock($this->app)->makePartial();
        $mockApp->shouldReceive('environment')
            ->with('local', 'testing')
            ->andReturn(false);

        $mockErrorHandler = Mockery::mock(ErrorHandlingStrategyInterface::class);
        $mockOrchestrator = Mockery::mock(
            new ServiceRegistrationOrchestrator($mockApp, $mockErrorHandler)
        )->makePartial();
        $mockOrchestrator->shouldReceive('registerPolicies')
            ->once()
            ->andThrow(new \RuntimeException('Orchestrator failed'));

        $mockApp->instance(ServiceRegistrationOrchestrator::class, $mockOrchestrator);
        $provider = new AppServiceProvider($mockApp);

        // Should not throw in production
        $provider->boot();

        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    public function test_should_not_boot_services_when_configuration_is_cached(): void
    {
        $mockApp = Mockery::mock($this->app)->makePartial();
        $mockApp->shouldReceive('configurationIsCached')
            ->once()
            ->andReturn(true);

        $this->assertFalse($this->shouldBootServices(new AppServiceProvider($mockApp)));
    }

    public function test_should_not_boot_services_when_in_maintenance_mode(): void
    {
        $mockApp = Mockery::mock($this->app)->makePartial();
        $mockApp->shouldReceive('configurationIsCached')
            ->once()
            ->andReturn(false);

        $mockApp->shouldReceive('isDownForMaintenance')
            ->once()
            ->andReturn(true);

        $this->assertFalse($this->shouldBootServices(new AppServiceProvider($mockApp)));
    }

    public function test_registers_services_based_on_configuration(): void
    {
        // Mock configuration
        config([
            'service-registration.core_services.singletons' => [
                TenantContext::class,
                TenantBoundaryService::class,
            ],
            'service-registration.core_services.bindings' => [
                PolicyRegistryInterface::class => PolicyRegistry::class,
            ],
        ]);

        $this->provider->register();

        $this->assertTrue($this->app->bound(TenantContext::class));
        $this->assertTrue($this->app->bound(TenantBoundaryService::class));
        $this->assertTrue($this->app->bound(PolicyRegistryInterface::class));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function shouldBootServices(AppServiceProvider $provider): bool
    {
        $reflection = new \ReflectionClass($provider);
        $method = $reflection->getMethod('shouldBootServices');
        $method->setAccessible(true);

        return $method->invoke($provider);
    }
}
