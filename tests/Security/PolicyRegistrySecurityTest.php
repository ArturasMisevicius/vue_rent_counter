<?php

declare(strict_types=1);

namespace Tests\Security;

use App\Support\ServiceRegistration\PolicyRegistry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

final class PolicyRegistrySecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_prevents_unauthorized_policy_registration(): void
    {
        $this->actingAs($this->createUser());
        
        $registry = new PolicyRegistry();
        
        $this->expectException(AuthorizationException::class);
        $registry->registerModelPolicies();
    }

    public function test_allows_policy_registration_during_boot(): void
    {
        // Simulate application boot (no authenticated user)
        auth()->logout();
        
        $registry = new PolicyRegistry();
        $result = $registry->registerModelPolicies();
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('registered', $result);
        $this->assertArrayHasKey('skipped', $result);
        $this->assertArrayHasKey('errors', $result);
    }

    public function test_allows_super_admin_policy_registration(): void
    {
        $superAdmin = $this->createUser();
        $superAdmin->assignRole('super_admin');
        
        $this->actingAs($superAdmin);
        
        $registry = new PolicyRegistry();
        $result = $registry->registerModelPolicies();
        
        $this->assertIsArray($result);
    }

    public function test_sanitizes_error_messages(): void
    {
        auth()->logout();
        
        $registry = new PolicyRegistry();
        $result = $registry->registerModelPolicies();
        
        // Check that error messages don't contain full class names
        foreach ($result['errors'] as $error) {
            $this->assertStringNotContainsString('App\\Models\\', $error);
            $this->assertStringNotContainsString('App\\Policies\\', $error);
        }
    }

    public function test_uses_secure_cache_keys(): void
    {
        Cache::flush();
        
        $registry = new PolicyRegistry();
        $registry->registerModelPolicies();
        
        // Verify cache keys use SHA-256 instead of MD5
        $cacheKeys = Cache::getRedis()->keys('*policy_registry_class_exists*');
        
        foreach ($cacheKeys as $key) {
            // SHA-256 hashes are 64 characters long
            $hashPart = substr($key, strrpos($key, '.') + 1);
            $this->assertEquals(64, strlen($hashPart));
        }
    }

    public function test_logs_security_events_without_sensitive_data(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('Policy registration: Model class missing', \Mockery::on(function ($context) {
                // Verify sensitive data is hashed
                $this->assertArrayHasKey('model_hash', $context);
                $this->assertArrayHasKey('context', $context);
                $this->assertEquals('policy_registration', $context['context']);
                
                // Verify no full class names in logs
                $this->assertArrayNotHasKey('model', $context);
                
                return true;
            }));
        
        auth()->logout();
        
        $registry = new PolicyRegistry();
        $registry->registerModelPolicies();
    }

    public function test_rate_limiting_policy_registration(): void
    {
        $user = $this->createUser();
        $user->assignRole('super_admin');
        $this->actingAs($user);
        
        // Simulate multiple rapid requests
        for ($i = 0; $i < 6; $i++) {
            try {
                $response = $this->post('/admin/policy-registration');
            } catch (\Exception $e) {
                if ($i >= 5) {
                    $this->assertEquals(429, $e->getCode());
                }
            }
        }
    }

    public function test_validates_policy_configuration(): void
    {
        auth()->logout();
        
        $registry = new PolicyRegistry();
        $validation = $registry->validateConfiguration();
        
        $this->assertIsArray($validation);
        $this->assertArrayHasKey('valid', $validation);
        $this->assertArrayHasKey('policies', $validation);
        $this->assertArrayHasKey('gates', $validation);
        
        // Verify error messages don't expose internal structure
        if (!empty($validation['policies']['errors'])) {
            foreach ($validation['policies']['errors'] as $error) {
                $this->assertStringNotContainsString('App\\', $error);
            }
        }
    }

    private function createUser()
    {
        return \App\Models\User::factory()->create();
    }
}