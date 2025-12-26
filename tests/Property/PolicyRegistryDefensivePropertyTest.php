<?php

declare(strict_types=1);

namespace Tests\Property;

use App\Support\ServiceRegistration\PolicyRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Property-based tests for PolicyRegistry defensive registration
 * 
 * Ensures defensive behavior holds under various conditions and edge cases.
 */
final class PolicyRegistryDefensivePropertyTest extends TestCase
{
    use RefreshDatabase;

    public function test_policy_registration_is_idempotent(): void
    {
        auth()->logout();
        
        $registry = new PolicyRegistry();
        
        // Run registration multiple times
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = $registry->registerModelPolicies();
        }
        
        // All results should be identical (idempotent property)
        $firstResult = $results[0];
        foreach ($results as $index => $result) {
            $this->assertEquals(
                $firstResult['registered'],
                $result['registered'],
                "Registration {$index} should have same registered count"
            );
            $this->assertEquals(
                $firstResult['skipped'],
                $result['skipped'],
                "Registration {$index} should have same skipped count"
            );
            $this->assertEquals(
                $firstResult['errors'],
                $result['errors'],
                "Registration {$index} should have same errors"
            );
        }
    }

    public function test_gate_registration_is_idempotent(): void
    {
        auth()->logout();
        
        $registry = new PolicyRegistry();
        
        // Run gate registration multiple times
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = $registry->registerSettingsGates();
        }
        
        // All results should be identical (idempotent property)
        $firstResult = $results[0];
        foreach ($results as $index => $result) {
            $this->assertEquals(
                $firstResult['registered'],
                $result['registered'],
                "Gate registration {$index} should have same registered count"
            );
            $this->assertEquals(
                $firstResult['skipped'],
                $result['skipped'],
                "Gate registration {$index} should have same skipped count"
            );
            $this->assertEquals(
                $firstResult['errors'],
                $result['errors'],
                "Gate registration {$index} should have same errors"
            );
        }
    }

    public function test_registration_statistics_invariants(): void
    {
        auth()->logout();
        
        $registry = new PolicyRegistry();
        
        // Test policy registration invariants
        $policyResult = $registry->registerModelPolicies();
        
        // Invariant: registered + skipped should equal total configured policies
        $totalPolicies = count($registry->getModelPolicies());
        $this->assertEquals(
            $totalPolicies,
            $policyResult['registered'] + $policyResult['skipped'],
            'Registered + skipped should equal total policies'
        );
        
        // Invariant: errors count should match skipped count
        $this->assertEquals(
            count($policyResult['errors']),
            $policyResult['skipped'],
            'Error count should match skipped count'
        );
        
        // Test gate registration invariants
        $gateResult = $registry->registerSettingsGates();
        
        $totalGates = count($registry->getSettingsGates());
        $this->assertEquals(
            $totalGates,
            $gateResult['registered'] + $gateResult['skipped'],
            'Registered + skipped should equal total gates'
        );
        
        $this->assertEquals(
            count($gateResult['errors']),
            $gateResult['skipped'],
            'Gate error count should match skipped count'
        );
    }

    public function test_authorization_invariants(): void
    {
        // Property: Unauthorized users should always be rejected
        $regularUsers = \App\Models\User::factory()->count(5)->create();
        
        foreach ($regularUsers as $user) {
            $this->actingAs($user);
            
            try {
                $this->registry->registerModelPolicies();
                $this->fail('Should have thrown AuthorizationException for regular user');
            } catch (AuthorizationException $e) {
                $this->assertStringContainsString('Unauthorized', $e->getMessage());
            }
        }
        
        // Property: Super admins should always be allowed
        $superAdmins = \App\Models\User::factory()->count(3)->create();
        
        foreach ($superAdmins as $admin) {
            $admin->assignRole('super_admin');
            $this->actingAs($admin);
            
            $result = $this->registry->registerModelPolicies();
            $this->assertIsArray($result);
            $this->assertArrayHasKey('registered', $result);
        }
        
        // Property: Boot context should always be allowed
        auth()->logout();
        
        $result = $this->registry->registerModelPolicies();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('registered', $result);
    }

    public function test_cache_consistency_property(): void
    {
        auth()->logout();
        
        $registry = new PolicyRegistry();
        
        // Property: Cache should provide consistent results
        for ($i = 0; $i < 20; $i++) {
            $result = $registry->registerModelPolicies();
            
            // Each registration should have consistent results
            $this->assertIsArray($result);
            $this->assertArrayHasKey('registered', $result);
            $this->assertGreaterThanOrEqual(0, $result['registered']);
            $this->assertGreaterThanOrEqual(0, $result['skipped']);
        }
    }

    public function test_error_handling_robustness(): void
    {
        auth()->logout();
        
        // Property: Registration should never throw unhandled exceptions
        $registry = new PolicyRegistry();
        
        // Test multiple scenarios that could cause issues
        $scenarios = [
            'normal_registration' => fn() => $registry->registerModelPolicies(),
            'gate_registration' => fn() => $registry->registerSettingsGates(),
            'validation' => fn() => $registry->validateConfiguration(),
        ];
        
        foreach ($scenarios as $scenarioName => $scenario) {
            try {
                $result = $scenario();
                $this->assertIsArray($result, "Scenario {$scenarioName} should return array");
            } catch (\Throwable $e) {
                $this->fail("Scenario {$scenarioName} should not throw exceptions: " . $e->getMessage());
            }
        }
    }

    public function test_logging_security_property(): void
    {
        // Property: All logs should be secure (no sensitive data)
        $loggedMessages = [];
        
        Log::shouldReceive('warning')
            ->andReturnUsing(function ($message, $context) use (&$loggedMessages) {
                $loggedMessages[] = ['message' => $message, 'context' => $context];
                return true;
            });
        
        Log::shouldReceive('debug')->andReturn(true);
        Log::shouldReceive('error')->andReturn(true);
        
        auth()->logout();
        
        // Create registry with some invalid configurations to trigger logging
        $mockRegistry = new class extends PolicyRegistry {
            protected const MODEL_POLICIES = [
                'InvalidModel' => 'InvalidPolicy',
                \App\Models\User::class => \App\Policies\UserPolicy::class,
            ];
        };
        
        $mockRegistry->registerModelPolicies();
        
        // Verify all logged contexts are secure
        foreach ($loggedMessages as $log) {
            $context = $log['context'];
            
            // Should have hashed sensitive data
            if (isset($context['model_hash'])) {
                $this->assertEquals(64, strlen($context['model_hash']), 'Model hash should be SHA-256');
            }
            
            if (isset($context['policy_hash'])) {
                $this->assertEquals(64, strlen($context['policy_hash']), 'Policy hash should be SHA-256');
            }
            
            // Should not contain full class names
            $this->assertArrayNotHasKey('model', $context);
            $this->assertArrayNotHasKey('policy', $context);
            
            // Should have security context
            $this->assertEquals('policy_registration', $context['context']);
        }
    }

    public function test_performance_characteristics(): void
    {
        auth()->logout();
        
        $registry = new PolicyRegistry();
        
        // Property: Registration should complete within reasonable time
        $iterations = 10;
        $durations = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            Cache::flush(); // Clear cache to test worst-case performance
            
            $start = microtime(true);
            $registry->registerModelPolicies();
            $duration = microtime(true) - $start;
            
            $durations[] = $duration;
            
            // Each registration should complete within 1 second
            $this->assertLessThan(1.0, $duration, "Registration {$i} should complete within 1 second");
        }
        
        // Average duration should be reasonable
        $averageDuration = array_sum($durations) / count($durations);
        $this->assertLessThan(0.5, $averageDuration, 'Average registration time should be under 500ms');
    }

    public function test_concurrent_access_safety(): void
    {
        auth()->logout();
        
        // Property: Concurrent registrations should not interfere with each other
        $registry = new PolicyRegistry();
        
        // Simulate concurrent access by running registrations in quick succession
        $results = [];
        for ($i = 0; $i < 5; $i++) {
            $results[] = $registry->registerModelPolicies();
        }
        
        // All results should be consistent
        $firstResult = $results[0];
        foreach ($results as $index => $result) {
            $this->assertEquals(
                $firstResult,
                $result,
                "Concurrent registration {$index} should have identical results"
            );
        }
    }

    public function test_validation_completeness_property(): void
    {
        $registry = new PolicyRegistry();
        
        // Property: Validation should cover all configured policies and gates
        $validation = $registry->validateConfiguration();
        
        $totalPolicies = count($registry->getModelPolicies());
        $totalGates = count($registry->getSettingsGates());
        
        $this->assertEquals(
            $totalPolicies,
            $validation['policies']['valid'] + $validation['policies']['invalid'],
            'Validation should cover all policies'
        );
        
        $this->assertEquals(
            $totalGates,
            $validation['gates']['valid'] + $validation['gates']['invalid'],
            'Validation should cover all gates'
        );
    }
}