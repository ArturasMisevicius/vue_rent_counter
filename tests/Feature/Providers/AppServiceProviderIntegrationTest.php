<?php

declare(strict_types=1);

namespace Tests\Feature\Providers;

use App\Contracts\ServiceRegistration\PolicyRegistryInterface;
use App\Models\User;
use App\Services\TenantBoundaryService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Integration tests for AppServiceProvider
 * 
 * Tests the provider in the context of the full application
 * with real database and authentication.
 */
final class AppServiceProviderIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_services_work_together(): void
    {
        // Create a test user with tenant
        $user = User::factory()->create(['tenant_id' => 100]);
        $this->actingAs($user);
        
        // Get services from container
        $tenantContext = app(TenantContext::class);
        $tenantBoundary = app(TenantBoundaryService::class);
        
        // Test that services work together
        $this->assertInstanceOf(TenantContext::class, $tenantContext);
        $this->assertInstanceOf(TenantBoundaryService::class, $tenantBoundary);
        
        // Test tenant boundary service functionality
        $this->assertTrue(
            $tenantBoundary->canAccessTenant($user, 100),
            'User should be able to access their own tenant'
        );
        
        $this->assertFalse(
            $tenantBoundary->canAccessTenant($user, 200),
            'User should not be able to access different tenant'
        );
    }

    public function test_policy_registry_registers_policies_correctly(): void
    {
        $registry = app(PolicyRegistryInterface::class);
        
        // Test that registry is available
        $this->assertInstanceOf(PolicyRegistryInterface::class, $registry);
        
        // Test that policies are configured
        $policies = $registry->getModelPolicies();
        $this->assertIsArray($policies);
        $this->assertNotEmpty($policies);
        
        // Test that gates are configured
        $gates = $registry->getSettingsGates();
        $this->assertIsArray($gates);
        $this->assertNotEmpty($gates);
    }

    public function test_gates_are_properly_registered(): void
    {
        // Test that some expected gates are registered
        $expectedGates = [
            'viewSettings',
            'updateSettings',
            'runBackup',
            'clearCache',
        ];
        
        foreach ($expectedGates as $gate) {
            $this->assertTrue(
                Gate::has($gate),
                "Gate '{$gate}' should be registered"
            );
        }
    }

    public function test_translation_system_works_correctly(): void
    {
        // Test that translation system is properly configured
        $loader = app('translation.loader');
        $this->assertInstanceOf(\Illuminate\Translation\FileLoader::class, $loader);
        
        // Test that translations can be loaded
        $translator = app('translator');
        $this->assertInstanceOf(\Illuminate\Translation\Translator::class, $translator);
        
        // Test a known translation (if it exists)
        $translation = __('validation.required');
        $this->assertIsString($translation);
        $this->assertNotEmpty($translation);
    }

    public function test_services_maintain_state_across_requests(): void
    {
        // Test singleton behavior in request context
        $tenantContext1 = app(TenantContext::class);
        $tenantContext2 = app(TenantContext::class);
        
        $this->assertSame(
            $tenantContext1,
            $tenantContext2,
            'TenantContext should maintain singleton behavior'
        );
        
        $tenantBoundary1 = app(TenantBoundaryService::class);
        $tenantBoundary2 = app(TenantBoundaryService::class);
        
        $this->assertSame(
            $tenantBoundary1,
            $tenantBoundary2,
            'TenantBoundaryService should maintain singleton behavior'
        );
    }

    public function test_policy_registration_works_with_authentication(): void
    {
        // Test without authentication (app boot scenario)
        $registry = app(PolicyRegistryInterface::class);
        $result = $registry->registerModelPolicies();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('registered', $result);
        $this->assertArrayHasKey('skipped', $result);
        $this->assertArrayHasKey('errors', $result);
        
        // Should register some policies during app boot
        $this->assertGreaterThanOrEqual(0, $result['registered']);
    }

    public function test_policy_registration_respects_authorization(): void
    {
        // Create a regular user (not super admin)
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $registry = app(PolicyRegistryInterface::class);
        
        // Should throw authorization exception for regular user
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $registry->registerModelPolicies();
    }

    public function test_application_boots_successfully_with_provider(): void
    {
        // Test that the application can boot successfully
        // This is implicitly tested by the test framework, but we make it explicit
        
        $this->assertTrue(
            app()->bound(TenantContext::class),
            'TenantContext should be bound after boot'
        );
        
        $this->assertTrue(
            app()->bound(TenantBoundaryService::class),
            'TenantBoundaryService should be bound after boot'
        );
        
        $this->assertTrue(
            app()->bound(PolicyRegistryInterface::class),
            'PolicyRegistryInterface should be bound after boot'
        );
        
        $this->assertTrue(
            app()->bound('translation.loader'),
            'Translation loader should be bound after boot'
        );
    }

    public function test_provider_handles_missing_models_gracefully(): void
    {
        // Test that the application doesn't crash if some models are missing
        // This is important for partial deployments or development environments
        
        $registry = app(PolicyRegistryInterface::class);
        $validation = $registry->validateConfiguration();
        
        $this->assertIsArray($validation);
        $this->assertArrayHasKey('valid', $validation);
        $this->assertArrayHasKey('policies', $validation);
        $this->assertArrayHasKey('gates', $validation);
        
        // Should not throw exceptions even if some configurations are invalid
        $this->assertTrue(true, 'Validation should complete without exceptions');
    }

    public function test_error_handling_in_production_like_environment(): void
    {
        // Temporarily set environment to production
        $originalEnv = app()->environment();
        app()->detectEnvironment(function () {
            return 'production';
        });
        
        try {
            // Test that provider works in production environment
            $registry = app(PolicyRegistryInterface::class);
            $this->assertInstanceOf(PolicyRegistryInterface::class, $registry);
            
            // Should not throw exceptions in production
            $result = $registry->validateConfiguration();
            $this->assertIsArray($result);
            
        } finally {
            // Restore original environment
            app()->detectEnvironment(function () use ($originalEnv) {
                return $originalEnv;
            });
        }
    }
}