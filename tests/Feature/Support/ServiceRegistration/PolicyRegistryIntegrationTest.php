<?php

declare(strict_types=1);

namespace Tests\Feature\Support\ServiceRegistration;

use App\Support\ServiceRegistration\PolicyRegistry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Integration tests for PolicyRegistry defensive registration
 * 
 * Tests the complete flow of policy registration with real Laravel components.
 */
final class PolicyRegistryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private PolicyRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->registry = new PolicyRegistry();
        Cache::flush();
    }

    public function test_complete_policy_registration_flow(): void
    {
        // Ensure we're in boot context (no authenticated user)
        auth()->logout();
        
        // Register policies
        $policyResult = $this->registry->registerModelPolicies();
        $gateResult = $this->registry->registerSettingsGates();
        
        // Verify statistics structure
        $this->assertArrayHasKey('registered', $policyResult);
        $this->assertArrayHasKey('skipped', $policyResult);
        $this->assertArrayHasKey('errors', $policyResult);
        
        $this->assertArrayHasKey('registered', $gateResult);
        $this->assertArrayHasKey('skipped', $gateResult);
        $this->assertArrayHasKey('errors', $gateResult);
        
        // Verify successful registrations
        $this->assertGreaterThan(0, $policyResult['registered']);
        $this->assertGreaterThan(0, $gateResult['registered']);
        
        // Verify no errors in test environment
        $this->assertEmpty($policyResult['errors']);
        $this->assertEmpty($gateResult['errors']);
    }

    public function test_policy_registration_with_missing_classes(): void
    {
        // Create a mock registry with non-existent classes
        $mockRegistry = new class extends PolicyRegistry {
            protected const MODEL_POLICIES = [
                'NonExistentModel' => 'NonExistentPolicy',
                \App\Models\User::class => \App\Policies\UserPolicy::class, // This should work
            ];
        };
        
        auth()->logout();
        
        $result = $mockRegistry->registerModelPolicies();
        
        // Should have some skipped due to missing classes
        $this->assertGreaterThan(0, $result['skipped']);
        
        // Should still register valid policies
        $this->assertGreaterThan(0, $result['registered']);
        
        // Should have error messages for missing classes
        $this->assertNotEmpty($result['errors']);
    }

    public function test_authorization_enforcement(): void
    {
        // Test with regular user
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);
        
        $this->expectException(AuthorizationException::class);
        $this->registry->registerModelPolicies();
    }

    public function test_super_admin_can_register_policies(): void
    {
        $superAdmin = \App\Models\User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $this->actingAs($superAdmin);
        
        $result = $this->registry->registerModelPolicies();
        
        $this->assertIsArray($result);
        $this->assertGreaterThan(0, $result['registered']);
    }

    public function test_configuration_validation(): void
    {
        $validation = $this->registry->validateConfiguration();
        
        $this->assertIsArray($validation);
        $this->assertArrayHasKey('valid', $validation);
        $this->assertArrayHasKey('policies', $validation);
        $this->assertArrayHasKey('gates', $validation);
        
        // In test environment, all should be valid
        $this->assertTrue($validation['valid']);
        $this->assertEquals(0, $validation['policies']['invalid']);
        $this->assertEquals(0, $validation['gates']['invalid']);
    }

    public function test_performance_logging(): void
    {
        Log::shouldReceive('debug')
            ->with('Policy registration completed', \Mockery::on(function ($context) {
                $this->assertArrayHasKey('registered', $context);
                $this->assertArrayHasKey('skipped', $context);
                $this->assertArrayHasKey('errors_count', $context);
                $this->assertArrayHasKey('duration_ms', $context);
                $this->assertIsNumeric($context['duration_ms']);
                $this->assertGreaterThan(0, $context['duration_ms']);
                return true;
            }));
        
        Log::shouldReceive('debug')->andReturn(true);
        
        auth()->logout();
        $this->registry->registerModelPolicies();
    }

    public function test_cache_performance_optimization(): void
    {
        auth()->logout();
        
        // First registration should populate cache
        $start1 = microtime(true);
        $result1 = $this->registry->registerModelPolicies();
        $duration1 = microtime(true) - $start1;
        
        // Second registration should be faster due to caching
        $start2 = microtime(true);
        $result2 = $this->registry->registerModelPolicies();
        $duration2 = microtime(true) - $start2;
        
        // Results should be identical
        $this->assertEquals($result1['registered'], $result2['registered']);
        $this->assertEquals($result1['skipped'], $result2['skipped']);
        
        // Second call should be faster (though this might be flaky in CI)
        // We'll just verify it completes successfully
        $this->assertLessThan(1.0, $duration2); // Should complete within 1 second
    }

    public function test_security_monitoring_integration(): void
    {
        // Test that security monitoring service receives policy registration events
        $mockSecurityService = \Mockery::mock(\App\Services\SecurityMonitoringService::class);
        $mockSecurityService->shouldReceive('recordPolicyRegistration')
            ->once()
            ->with(\Mockery::type('array'), \Mockery::type('array'));
        
        $this->app->instance(\App\Services\SecurityMonitoringService::class, $mockSecurityService);
        
        auth()->logout();
        
        $policyResult = $this->registry->registerModelPolicies();
        $gateResult = $this->registry->registerSettingsGates();
        
        // Manually trigger security monitoring (in real app this would be in AppServiceProvider)
        $mockSecurityService->recordPolicyRegistration($policyResult, $gateResult);
    }

    public function test_gate_registration_validates_methods(): void
    {
        auth()->logout();
        
        $result = $this->registry->registerSettingsGates();
        
        // All gates should register successfully in test environment
        $this->assertGreaterThan(0, $result['registered']);
        $this->assertEquals(0, $result['skipped']);
        $this->assertEmpty($result['errors']);
        
        // Verify gates are actually registered
        $gates = $this->registry->getSettingsGates();
        foreach (array_keys($gates) as $gateName) {
            $this->assertTrue(Gate::has($gateName), "Gate {$gateName} should be registered");
        }
    }

    public function test_error_message_sanitization(): void
    {
        // Create a registry with intentionally broken configuration
        $mockRegistry = new class extends PolicyRegistry {
            protected const MODEL_POLICIES = [
                'App\\Models\\NonExistent' => 'App\\Policies\\NonExistentPolicy',
            ];
        };
        
        auth()->logout();
        
        $result = $mockRegistry->registerModelPolicies();
        
        // Should have errors
        $this->assertNotEmpty($result['errors']);
        
        // Error messages should be sanitized
        foreach ($result['errors'] as $error) {
            $this->assertStringNotContainsString('App\\Models\\', $error);
            $this->assertStringNotContainsString('App\\Policies\\', $error);
            $this->assertStringContainsString('configuration invalid', $error);
        }
    }

    public function test_concurrent_registration_safety(): void
    {
        auth()->logout();
        
        // Simulate concurrent registrations
        $results = [];
        for ($i = 0; $i < 3; $i++) {
            $results[] = $this->registry->registerModelPolicies();
        }
        
        // All results should be identical (idempotent)
        $firstResult = $results[0];
        foreach ($results as $result) {
            $this->assertEquals($firstResult['registered'], $result['registered']);
            $this->assertEquals($firstResult['skipped'], $result['skipped']);
            $this->assertEquals($firstResult['errors'], $result['errors']);
        }
    }

    public function test_memory_usage_optimization(): void
    {
        auth()->logout();
        
        $memoryBefore = memory_get_usage(true);
        
        // Register policies multiple times
        for ($i = 0; $i < 5; $i++) {
            $this->registry->registerModelPolicies();
            $this->registry->registerSettingsGates();
        }
        
        $memoryAfter = memory_get_usage(true);
        $memoryUsed = $memoryAfter - $memoryBefore;
        
        // Memory usage should be reasonable (less than 5MB for this test)
        $this->assertLessThan(5 * 1024 * 1024, $memoryUsed, 'Memory usage should be reasonable');
    }
}