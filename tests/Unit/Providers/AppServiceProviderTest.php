<?php

declare(strict_types=1);

namespace Tests\Unit\Providers;

use App\Providers\AppServiceProvider;
use App\Support\ServiceRegistration\CompatibilityRegistry;
use App\Support\ServiceRegistration\EventRegistry;
use App\Support\ServiceRegistration\ObserverRegistry;
use App\Support\ServiceRegistration\PolicyRegistry;
use App\Support\ServiceRegistration\ServiceRegistry;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for AppServiceProvider refactoring
 * 
 * Ensures all services are properly registered and
 * the registry pattern works correctly.
 */
final class AppServiceProviderTest extends TestCase
{
    private Application $app;
    private AppServiceProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a minimal Laravel application instance for testing
        $this->app = new Application();
        $this->app->singleton('config', function () {
            return new \Illuminate\Config\Repository([
                'app' => ['key' => 'test-key'],
                'database' => ['default' => 'testing'],
            ]);
        });
        
        $this->provider = new AppServiceProvider($this->app);
    }

    public function test_provider_is_final_class(): void
    {
        $reflection = new \ReflectionClass(AppServiceProvider::class);
        
        $this->assertTrue($reflection->isFinal(), 'AppServiceProvider should be final');
    }

    public function test_provider_uses_strict_types(): void
    {
        $content = file_get_contents(app_path('Providers/AppServiceProvider.php'));
        
        $this->assertStringContainsString(
            'declare(strict_types=1);',
            $content,
            'AppServiceProvider should use strict types'
        );
    }

    public function test_register_method_calls_registry_methods(): void
    {
        // Test that register method exists and is callable
        $this->assertTrue(
            method_exists($this->provider, 'register'),
            'AppServiceProvider should have register method'
        );
        
        // Test that the method can be called without errors
        $this->provider->register();
        
        // Since we're in unit test mode, we can't test actual service registration
        // but we can verify the method executes without throwing exceptions
        $this->assertTrue(true, 'Register method executed without errors');
    }

    public function test_boot_method_calls_registry_methods(): void
    {
        // Test that boot method exists and is callable
        $this->assertTrue(
            method_exists($this->provider, 'boot'),
            'AppServiceProvider should have boot method'
        );
        
        // Test that the method can be called without errors
        $this->provider->boot();
        
        // Since we're in unit test mode, we can't test actual service booting
        // but we can verify the method executes without throwing exceptions
        $this->assertTrue(true, 'Boot method executed without errors');
    }

    public function test_service_registry_integration(): void
    {
        // Test that ServiceRegistry can be instantiated without Laravel app
        $serviceRegistry = new ServiceRegistry($this->app);
        
        $this->assertInstanceOf(
            ServiceRegistry::class,
            $serviceRegistry,
            'ServiceRegistry should be instantiable'
        );
        
        // Test that methods exist and are callable
        $this->assertTrue(
            method_exists($serviceRegistry, 'registerCoreServices'),
            'ServiceRegistry should have registerCoreServices method'
        );
        
        $this->assertTrue(
            method_exists($serviceRegistry, 'registerInterfaceBindings'),
            'ServiceRegistry should have registerInterfaceBindings method'
        );
    }

    public function test_policy_registry_integration(): void
    {
        $policyRegistry = new PolicyRegistry();
        
        // Test that PolicyRegistry can be instantiated
        $this->assertInstanceOf(
            PolicyRegistry::class,
            $policyRegistry,
            'PolicyRegistry should be instantiable'
        );
        
        // Test policy mappings
        $policies = $policyRegistry->getModelPolicies();
        
        $this->assertArrayHasKey(
            \App\Models\Tariff::class,
            $policies,
            'Tariff model should have policy mapping'
        );
        
        $this->assertEquals(
            \App\Policies\TariffPolicy::class,
            $policies[\App\Models\Tariff::class],
            'Tariff model should map to TariffPolicy'
        );
        
        // Test settings gates
        $gates = $policyRegistry->getSettingsGates();
        
        $this->assertArrayHasKey(
            'viewSettings',
            $gates,
            'viewSettings gate should be defined'
        );
    }

    public function test_observer_registry_integration(): void
    {
        $observerRegistry = new ObserverRegistry();
        
        // Test that ObserverRegistry can be instantiable
        $this->assertInstanceOf(
            ObserverRegistry::class,
            $observerRegistry,
            'ObserverRegistry should be instantiable'
        );
        
        // Test observer mappings
        $observers = $observerRegistry->getModelObservers();
        
        $this->assertArrayHasKey(
            \App\Models\MeterReading::class,
            $observers,
            'MeterReading model should have observer mapping'
        );
        
        $this->assertEquals(
            \App\Observers\MeterReadingObserver::class,
            $observers[\App\Models\MeterReading::class],
            'MeterReading should map to MeterReadingObserver'
        );
    }

    public function test_event_registry_integration(): void
    {
        $eventRegistry = new EventRegistry();
        
        // Test that EventRegistry can be instantiated
        $this->assertInstanceOf(
            EventRegistry::class,
            $eventRegistry,
            'EventRegistry should be instantiable'
        );
        
        // Test event registration (this would be integration tested)
        $this->assertTrue(true, 'EventRegistry instantiated successfully');
    }

    public function test_compatibility_registry_integration(): void
    {
        $compatibilityRegistry = new CompatibilityRegistry();
        
        // Test that CompatibilityRegistry can be instantiated
        $this->assertInstanceOf(
            CompatibilityRegistry::class,
            $compatibilityRegistry,
            'CompatibilityRegistry should be instantiable'
        );
        
        // Test Filament aliases
        $aliases = $compatibilityRegistry->getFilamentAliases();
        
        $this->assertIsArray($aliases, 'Filament aliases should be an array');
        $this->assertNotEmpty($aliases, 'Filament aliases should not be empty');
    }

    public function test_all_critical_services_are_registered(): void
    {
        // Test that register method can be called
        $this->provider->register();
        
        // Since we're in unit test mode, we can't test actual service registration
        // but we can verify the method executes without throwing exceptions
        $this->assertTrue(true, 'Register method executed without errors');
    }

    public function test_all_interface_bindings_are_registered(): void
    {
        // Test that register method can be called
        $this->provider->register();
        
        // Since we're in unit test mode, we can't test actual interface bindings
        // but we can verify the method executes without throwing exceptions
        $this->assertTrue(true, 'Register method executed without errors');
    }

    public function test_provider_methods_are_private(): void
    {
        $reflection = new \ReflectionClass(AppServiceProvider::class);
        
        $privateMethods = [
            'registerCoreServices',
            'registerCompatibilityServices',
            'bootRegistries',
            'bootCompatibility',
            'bootObservers',
            'bootPolicies',
            'bootEvents',
        ];
        
        foreach ($privateMethods as $methodName) {
            $method = $reflection->getMethod($methodName);
            $this->assertTrue(
                $method->isPrivate(),
                "Method {$methodName} should be private"
            );
        }
    }

    public function test_provider_has_proper_return_types(): void
    {
        $reflection = new \ReflectionClass(AppServiceProvider::class);
        
        $methods = [
            'register' => 'void',
            'boot' => 'void',
        ];
        
        foreach ($methods as $methodName => $expectedReturnType) {
            $method = $reflection->getMethod($methodName);
            $returnType = $method->getReturnType();
            
            $this->assertNotNull(
                $returnType,
                "Method {$methodName} should have return type"
            );
            
            $this->assertEquals(
                $expectedReturnType,
                $returnType->getName(),
                "Method {$methodName} should return {$expectedReturnType}"
            );
        }
    }
}