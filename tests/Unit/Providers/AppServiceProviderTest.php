<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use App\Contracts\ServiceRegistration\ErrorHandlingStrategyInterface;
use App\Contracts\ServiceRegistration\PolicyRegistryInterface;
use App\Providers\AppServiceProvider;
use App\Services\PolicyRegistryMonitoringService;
use App\Services\ServiceRegistration\RegistrationErrorHandler;
use App\Services\ServiceRegistration\ServiceRegistrationOrchestrator;
use App\Services\TenantBoundaryService;
use App\Services\TenantContext;
use App\Support\ServiceRegistration\PolicyRegistry;
use Illuminate\Foundation\Application;
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
            \Illuminate\Translation\FileLoader::class,
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
        // Mock the orchestrator
        $mockOrchestrator = Mockery::mock(ServiceRegistrationOrchestrator::class);
        $mockOrchestrator->shouldReceive('registerPolicies')
            ->once();
        
        $this->app->instance(ServiceRegistrationOrchestrator::class, $mockOrchestrator);
        
        $this->provider->boot();
    }

    public function test_boot_method_handles_orchestrator_failure_in_development(): void
    {
        $mockOrchestrator = Mockery::mock(ServiceRegistrationOrchestrator::class);
        $mockOrchestrator->shouldReceive('registerPolicies')
            ->once()
            ->andThrow(new \RuntimeException('Orchestrator failed'));
        
        $this->app->instance(ServiceRegistrationOrchestrator::class, $mockOrchestrator);
        $this->app['env'] = 'local';
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Orchestrator failed');
        
        $this->provider->boot();
    }

    public function test_boot_method_handles_orchestrator_failure_in_production(): void
    {
        $mockOrchestrator = Mockery::mock(ServiceRegistrationOrchestrator::class);
        $mockOrchestrator->shouldReceive('registerPolicies')
            ->once()
            ->andThrow(new \RuntimeException('Orchestrator failed'));
        
        $this->app->instance(ServiceRegistrationOrchestrator::class, $mockOrchestrator);
        $this->app['env'] = 'production';
        
        // Should not throw in production
        $this->provider->boot();
        
        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    public function test_skips_boot_when_configuration_is_cached(): void
    {
        $this->app->shouldReceive('configurationIsCached')
            ->once()
            ->andReturn(true);
        
        $this->app->shouldReceive('make')
            ->never();
        
        $this->provider->boot();
    }

    public function test_skips_boot_when_in_maintenance_mode(): void
    {
        $this->app->shouldReceive('configurationIsCached')
            ->once()
            ->andReturn(false);
        
        $this->app->shouldReceive('isDownForMaintenance')
            ->once()
            ->andReturn(true);
        
        $this->app->shouldReceive('make')
            ->never();
        
        $this->provider->boot();
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
}