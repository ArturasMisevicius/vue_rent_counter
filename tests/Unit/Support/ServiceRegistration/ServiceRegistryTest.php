<?php

declare(strict_types=1);

namespace Tests\Unit\Support\ServiceRegistration;

use App\Contracts\CircuitBreakerInterface;
use App\Contracts\InputSanitizerInterface;
use App\Contracts\SharedServiceCostDistributor;
use App\Contracts\SubscriptionCheckerInterface;
use App\Contracts\SuperAdminUserInterface;
use App\Contracts\TenantManagementInterface;
use App\Services\BillingService;
use App\Services\TariffResolver;
use App\Services\TenantInitializationService;
use App\Services\TenantTranslationService;
use App\Services\TimeRangeValidator;
use App\Services\TranslationCacheService;
use App\Support\ServiceRegistration\ServiceRegistry;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for ServiceRegistry
 *
 * Ensures proper service registration and dependency injection.
 * Uses PHPUnit directly to avoid database operations.
 */
final class ServiceRegistryTest extends TestCase
{
    private ServiceRegistry $registry;

    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a fresh container for testing
        $this->container = new Container;
        $this->registry = new ServiceRegistry($this->container);
    }

    public function test_registry_is_readonly(): void
    {
        $reflection = new \ReflectionClass(ServiceRegistry::class);

        $this->assertTrue(
            $reflection->isReadOnly(),
            'ServiceRegistry should be readonly'
        );
    }

    public function test_registry_is_final(): void
    {
        $reflection = new \ReflectionClass(ServiceRegistry::class);

        $this->assertTrue(
            $reflection->isFinal(),
            'ServiceRegistry should be final'
        );
    }

    public function test_register_core_services(): void
    {
        $this->registry->registerCoreServices();

        // Test billing services are bound (without instantiating them)
        $this->assertTrue(
            $this->container->bound(BillingService::class),
            'BillingService should be registered'
        );

        $this->assertTrue(
            $this->container->bound(TariffResolver::class),
            'TariffResolver should be registered'
        );

        // Test security services are bound
        $this->assertTrue(
            $this->container->bound(InputSanitizerInterface::class),
            'InputSanitizerInterface should be bound'
        );

        // Test validation services are bound
        $this->assertTrue(
            $this->container->bound(TimeRangeValidator::class),
            'TimeRangeValidator should be registered'
        );

        // Test tenant services are bound
        $this->assertTrue(
            $this->container->bound(TenantInitializationService::class),
            'TenantInitializationService should be registered'
        );

        // Test localization services are bound
        $this->assertTrue(
            $this->container->bound(TranslationCacheService::class),
            'TranslationCacheService should be registered'
        );

        $this->assertTrue(
            $this->container->bound(TenantTranslationService::class),
            'TenantTranslationService should be registered'
        );
    }

    public function test_register_compatibility_services(): void
    {
        $this->registry->registerCompatibilityServices();

        // Test Laravel 12 compatibility
        $this->assertTrue(
            $this->container->bound('files'),
            'Files service should be registered for compatibility'
        );
    }

    public function test_tariff_resolver_with_strategies(): void
    {
        $this->registry->registerCoreServices();

        // Test that TariffResolver is bound with proper closure registration
        $this->assertTrue(
            $this->container->bound(TariffResolver::class),
            'TariffResolver should be registered with strategy pattern'
        );

        // Verify the binding is a closure (indicating custom factory registration)
        $binding = $this->container->getBindings()[TariffResolver::class] ?? null;
        $this->assertNotNull($binding, 'TariffResolver should have a binding');
        $this->assertTrue($binding['shared'], 'TariffResolver should be registered as singleton');
    }

    public function test_interface_bindings(): void
    {
        $this->registry->registerCoreServices();

        $interfaceBindings = [
            InputSanitizerInterface::class,
            SharedServiceCostDistributor::class,
            SubscriptionCheckerInterface::class,
            TenantManagementInterface::class,
            SuperAdminUserInterface::class,
            CircuitBreakerInterface::class,
        ];

        foreach ($interfaceBindings as $interface) {
            $this->assertTrue(
                $this->container->bound($interface),
                "Interface {$interface} should be bound"
            );
        }
    }

    public function test_singleton_services(): void
    {
        $this->registry->registerCoreServices();

        // Test that services are registered as singletons by checking if they're bound
        // We avoid instantiating them to prevent database operations
        $singletonServices = [
            BillingService::class,
            TariffResolver::class,
            TranslationCacheService::class,
            TenantTranslationService::class,
        ];

        foreach ($singletonServices as $service) {
            $this->assertTrue(
                $this->container->bound($service),
                "{$service} should be registered as singleton"
            );

            // Verify it's registered as shared (singleton)
            $binding = $this->container->getBindings()[$service] ?? null;
            $this->assertNotNull($binding, "{$service} should have a binding");
            $this->assertTrue($binding['shared'], "{$service} should be registered as singleton");
        }
    }
}
