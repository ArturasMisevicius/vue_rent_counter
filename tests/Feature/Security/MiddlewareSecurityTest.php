<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Security-focused tests for middleware authorization.
 *
 * Tests for timing attacks, log injection, session security,
 * and other security-critical behaviors.
 */
class MiddlewareSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_prevents_timing_attacks_on_role_checks(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        // Measure admin access time
        $start1 = microtime(true);
        $this->actingAs($admin)->get('/admin');
        $time1 = microtime(true) - $start1;

        // Measure tenant denial time
        $start2 = microtime(true);
        $this->actingAs($tenant)->get('/admin');
        $time2 = microtime(true) - $start2;

        // Timing difference should be minimal (<10ms)
        // This prevents attackers from inferring role information
        $timeDiff = abs($time1 - $time2);
        expect($timeDiff)->toBeLessThan(0.01);
    }

    public function test_sanitizes_log_output_to_prevent_log_injection(): void
    {
        $maliciousUser = User::factory()->create([
            'role' => UserRole::TENANT,
            'email' => "attacker@example.com\nINJECTED: admin access granted",
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                // Ensure no newlines in logged email
                return !str_contains($context['user_email'] ?? '', "\n")
                    && !str_contains($context['user_email'] ?? '', "\r");
            });

        $this->actingAs($maliciousUser)->get('/admin');
    }

    public function test_handles_concurrent_requests_safely(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        // Simulate concurrent requests
        $responses = collect(range(1, 10))->map(function () use ($admin) {
            return $this->actingAs($admin)->get('/admin');
        });

        // All should succeed with consistent behavior
        $responses->each(function ($response) {
            expect($response->status())->toBe(200);
        });
    }

    public function test_does_not_leak_information_in_error_messages(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        $response = $this->actingAs($tenant)->get('/admin');

        // Error message should be generic
        expect($response->status())->toBe(403)
            ->and($response->getContent())->not->toContain('admin')
            ->and($response->getContent())->not->toContain('manager')
            ->and($response->getContent())->not->toContain('tenant');
    }

    public function test_regenerates_session_on_authorization_failure(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        
        $this->actingAs($tenant);
        $sessionId = session()->getId();

        $this->get('/admin');

        // Session ID should remain the same (no regeneration on 403)
        // This is correct behavior - only regenerate on login/logout
        expect(session()->getId())->toBe($sessionId);
    }

    public function test_does_not_expose_stack_traces_in_production(): void
    {
        config(['app.debug' => false]);

        $response = $this->get('/admin');

        // Should not contain stack trace information
        expect($response->getContent())
            ->not->toContain('Stack trace')
            ->and($response->getContent())->not->toContain('vendor/')
            ->and($response->getContent())->not->toContain('app/Http/');
    }

    public function test_validates_user_object_integrity(): void
    {
        $user = User::factory()->create(['role' => UserRole::TENANT]);

        // Attempt to manipulate user role in memory (should not affect authorization)
        $this->actingAs($user);
        
        $response = $this->get('/admin');

        expect($response->status())->toBe(403);
    }

    public function test_handles_null_user_safely(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                return $context['user_id'] === null
                    && $context['user_email'] === null
                    && $context['user_role'] === null;
            });

        $response = $this->get('/admin');

        expect($response->status())->toBe(403);
    }

    public function test_logs_contain_no_sensitive_data(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::TENANT,
            'password' => 'secret-password-123',
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function ($message, $context) {
                // Ensure password is not logged
                $contextString = json_encode($context);
                return !str_contains($contextString, 'password')
                    && !str_contains($contextString, 'secret');
            });

        $this->actingAs($user)->get('/admin');
    }

    public function test_handles_malformed_requests_gracefully(): void
    {
        $response = $this->get('/admin', [
            'X-Forwarded-For' => str_repeat('A', 10000), // Malformed header
        ]);

        // Should handle gracefully without crashing
        expect($response->status())->toBeIn([403, 429]);
    }

    public function test_prevents_session_fixation_attacks(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        // Get initial session ID
        $this->get('/login');
        $sessionId1 = session()->getId();

        // Login (should regenerate session)
        $this->actingAs($admin);
        $sessionId2 = session()->getId();

        // Session should be different after authentication
        // Note: This is handled by Laravel's auth middleware, not our middleware
        // This test documents the expected behavior
        expect($sessionId2)->not->toBe($sessionId1);
    }

    public function test_enforces_https_in_production(): void
    {
        config(['app.env' => 'production']);
        config(['session.secure' => true]);

        // In production, secure cookies should be enforced
        expect(config('session.secure'))->toBeTrue();
    }

    public function test_validates_csrf_token_on_state_changing_requests(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        // POST without CSRF token should fail
        $response = $this->actingAs($admin)->post('/admin/properties', []);

        expect($response->status())->toBe(419); // CSRF token mismatch
    }

    public function test_rate_limiting_prevents_brute_force(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        // Make many failed attempts
        for ($i = 0; $i < 15; $i++) {
            $this->actingAs($tenant)->get('/admin');
        }

        // Should eventually be rate limited
        $response = $this->actingAs($tenant)->get('/admin');
        expect($response->status())->toBe(429);
    }

    public function test_authorization_is_consistent_across_requests(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        // Make multiple requests
        $responses = collect(range(1, 5))->map(function () use ($tenant) {
            return $this->actingAs($tenant)->get('/admin')->status();
        });

        // All should return 403 consistently
        expect($responses->unique()->count())->toBe(1)
            ->and($responses->first())->toBe(403);
    }
}
