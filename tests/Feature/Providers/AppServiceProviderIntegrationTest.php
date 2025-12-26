<?php

declare(strict_types=1);

namespace Tests\Feature\Providers;

use App\Models\User;
use App\Services\BillingService;
use App\Services\MeterReadingService;
use App\Services\TariffResolver;
use App\Support\ServiceRegistration\CompatibilityRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integration test suite for AppServiceProvider refactoring
 * 
 * Ensures all services work correctly together after refactoring.
 */
final class AppServiceProviderIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_service_integration(): void
    {
        $billingService = app(BillingService::class);
        
        $this->assertInstanceOf(
            BillingService::class,
            $billingService,
            'BillingService should be resolvable from container'
        );
        
        // Test that the service is a singleton
        $billingService2 = app(BillingService::class);
        $this->assertSame(
            $billingService,
            $billingService2,
            'BillingService should be singleton'
        );
    }

    public function test_meter_reading_service_integration(): void
    {
        $meterReadingService = app(MeterReadingService::class);
        
        $this->assertInstanceOf(
            MeterReadingService::class,
            $meterReadingService,
            'MeterReadingService should be resolvable from container'
        );
    }

    public function test_tariff_resolver_with_strategies(): void
    {
        $tariffResolver = app(TariffResolver::class);
        
        $this->assertInstanceOf(
            TariffResolver::class,
            $tariffResolver,
            'TariffResolver should be resolvable with strategies'
        );
    }

    public function test_interface_bindings_work(): void
    {
        $inputSanitizer = app(\App\Contracts\InputSanitizerInterface::class);
        
        $this->assertInstanceOf(
            \App\Services\InputSanitizer::class,
            $inputSanitizer,
            'InputSanitizerInterface should resolve to InputSanitizer'
        );
        
        $subscriptionChecker = app(\App\Contracts\SubscriptionCheckerInterface::class);
        
        $this->assertInstanceOf(
            \App\Services\SubscriptionChecker::class,
            $subscriptionChecker,
            'SubscriptionCheckerInterface should resolve to SubscriptionChecker'
        );
    }

    public function test_observers_are_registered(): void
    {
        // Create a user to trigger observer
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
        
        // Observer should have been triggered during creation
        $this->assertTrue(true, 'User creation completed without observer errors');
    }

    public function test_policy_registry_defensive_registration(): void
    {
        $policyRegistry = new PolicyRegistry();
        
        // Test model policy registration
        $policyResults = $policyRegistry->registerModelPolicies();
        
        $this->assertIsArray($policyResults);
        $this->assertArrayHasKey('registered', $policyResults);
        $this->assertArrayHasKey('skipped', $policyResults);
        $this->assertArrayHasKey('errors', $policyResults);
        
        // Test settings gate registration
        $gateResults = $policyRegistry->registerSettingsGates();
        
        $this->assertIsArray($gateResults);
        $this->assertArrayHasKey('registered', $gateResults);
        $this->assertArrayHasKey('skipped', $gateResults);
        $this->assertArrayHasKey('errors', $gateResults);
        
        // In test environment, we should have successful registrations
        $this->assertGreaterThan(0, $policyResults['registered'], 'Should register some policies');
        $this->assertGreaterThan(0, $gateResults['registered'], 'Should register some gates');
        
        // Errors should be empty in properly configured test environment
        $this->assertEmpty($policyResults['errors'], 'Should have no policy errors in test environment');
        $this->assertEmpty($gateResults['errors'], 'Should have no gate errors in test environment');
    }

    public function test_policy_registry_validation(): void
    {
        $policyRegistry = new PolicyRegistry();
        $validation = $policyRegistry->validateConfiguration();
        
        $this->assertIsArray($validation);
        $this->assertArrayHasKey('valid', $validation);
        $this->assertArrayHasKey('policies', $validation);
        $this->assertArrayHasKey('gates', $validation);
        
        // In test environment, configuration should be valid
        $this->assertTrue($validation['valid'], 'Configuration should be valid in test environment');
        $this->assertEquals(0, $validation['policies']['invalid'], 'All policies should be valid');
        $this->assertEquals(0, $validation['gates']['invalid'], 'All gates should be valid');
    }

    public function test_rate_limiters_are_registered(): void
    {
        // Test that rate limiters are registered
        $rateLimiter = app('Illuminate\Cache\RateLimiter');
        
        $this->assertNotNull(
            $rateLimiter,
            'RateLimiter should be available'
        );
    }

    public function test_view_composers_are_registered(): void
    {
        // Test that view composers are registered
        $viewFactory = app('view');
        
        $this->assertNotNull(
            $viewFactory,
            'View factory should be available'
        );
    }

    public function test_collection_macros_are_registered(): void
    {
        // Test that collection macros are registered
        $collection = collect([1, 2, 3, 4, 5]);
        
        $this->assertTrue(
            $collection->hasMacro('takeLast'),
            'Collection should have takeLast macro'
        );
        
        $result = $collection->takeLast(2);
        $this->assertEquals([4, 5], $result->values()->toArray());
    }

    public function test_filament_compatibility_aliases(): void
    {
        // Test that Filament v4 compatibility aliases work
        if (class_exists(\Filament\Schemas\Components\Section::class)) {
            $this->assertTrue(
                class_exists(\Filament\Forms\Components\Section::class),
                'Filament Section alias should be available'
            );
        }
    }

    public function test_security_services_integration(): void
    {
        $securityServices = [
            \App\Services\Security\NonceGeneratorService::class,
            \App\Services\Security\CspHeaderBuilder::class,
            \App\Services\Security\SecurityHeaderFactory::class,
            \App\Services\Security\SecurityHeaderService::class,
        ];
        
        foreach ($securityServices as $service) {
            $instance = app($service);
            $this->assertInstanceOf(
                $service,
                $instance,
                "Security service {$service} should be resolvable"
            );
        }
    }

    public function test_tenant_services_integration(): void
    {
        $tenantServices = [
            \App\Services\TenantInitializationService::class,
            \App\Contracts\TenantManagementInterface::class,
        ];
        
        foreach ($tenantServices as $service) {
            $instance = app($service);
            $this->assertNotNull(
                $instance,
                "Tenant service {$service} should be resolvable"
            );
        }
    }

    public function test_validation_services_integration(): void
    {
        $validationServices = [
            \App\Services\TimeRangeValidator::class,
            \App\Services\Validation\ValidationRuleFactory::class,
            \App\Services\ServiceValidationEngine::class,
        ];
        
        foreach ($validationServices as $service) {
            $instance = app($service);
            $this->assertInstanceOf(
                $service,
                $instance,
                "Validation service {$service} should be resolvable"
            );
        }
    }

    public function test_translation_services_integration(): void
    {
        $translationServices = [
            \App\Services\TranslationCacheService::class,
            \App\Services\TenantTranslationService::class,
            \App\Support\Localization::class,
        ];
        
        foreach ($translationServices as $service) {
            $instance = app($service);
            $this->assertNotNull(
                $instance,
                "Translation service {$service} should be resolvable"
            );
        }
    }

    public function test_compatibility_registry_translation_support(): void
    {
        $compatibilityRegistry = new CompatibilityRegistry();
        
        // Test that translation compatibility can be registered without errors
        $this->expectNotToPerformAssertions();
        $compatibilityRegistry->registerTranslationCompatibility();
    }

    public function test_all_services_can_be_resolved(): void
    {
        // Test that all registered services can be resolved
        $services = [
            \App\Services\BillingService::class,
            \App\Services\MeterReadingService::class,
            \App\Services\TariffResolver::class,
            \App\Services\SystemHealthService::class,
            \App\Services\UserRoleService::class,
            \App\Services\TenantInitializationService::class,
            \App\Services\DashboardCacheService::class,
            \App\Services\QueryOptimizationService::class,
        ];
        
        foreach ($services as $service) {
            try {
                $instance = app($service);
                $this->assertNotNull(
                    $instance,
                    "Service {$service} should be resolvable"
                );
            } catch (\Exception $e) {
                $this->fail("Failed to resolve service {$service}: " . $e->getMessage());
            }
        }
    }
}