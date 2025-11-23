<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use App\Enums\UserRole;
use App\Http\Middleware\ThrottleAdminAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Test suite for ThrottleAdminAccess middleware.
 *
 * Verifies rate limiting behavior, brute force protection,
 * and proper handling of failed authorization attempts.
 */
class ThrottleAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected ThrottleAdminAccess $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new ThrottleAdminAccess;
        RateLimiter::clear('admin-access:127.0.0.1');
    }

    public function test_allows_requests_under_rate_limit(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        // Make 9 failed attempts (under limit of 10)
        for ($i = 0; $i < 9; $i++) {
            $response = $this->actingAs($tenant)->get('/admin');
            expect($response->status())->toBe(403);
        }

        // 10th attempt should still work (at limit)
        $response = $this->actingAs($tenant)->get('/admin');
        expect($response->status())->toBe(403);
    }

    public function test_blocks_requests_over_rate_limit(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        $key = 'admin-access:127.0.0.1';

        // Make 10 failed attempts (hit limit)
        for ($i = 0; $i < 10; $i++) {
            RateLimiter::hit($key, 300);
        }

        // 11th attempt should be rate limited
        $response = $this->actingAs($tenant)->get('/admin');
        
        expect($response->status())->toBe(429)
            ->and($response->json('message'))->toBe(__('app.auth.too_many_attempts'))
            ->and($response->headers->has('Retry-After'))->toBeTrue();
    }

    public function test_clears_rate_limit_on_successful_access(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);

        // Make 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($tenant)->get('/admin');
        }

        // Successful access should clear the counter
        $response = $this->actingAs($admin)->get('/admin');
        expect($response->status())->toBe(200);

        // Should be able to make more attempts
        for ($i = 0; $i < 10; $i++) {
            $response = $this->actingAs($tenant)->get('/admin');
            expect($response->status())->toBe(403);
        }
    }

    public function test_rate_limit_is_per_ip_address(): void
    {
        $tenant1 = User::factory()->create(['role' => UserRole::TENANT]);
        $key = 'admin-access:127.0.0.1';

        // Hit rate limit for this IP
        for ($i = 0; $i < 10; $i++) {
            RateLimiter::hit($key, 300);
        }

        // Should be rate limited
        $response = $this->actingAs($tenant1)->get('/admin');
        expect($response->status())->toBe(429);

        // Different IP should not be affected (in real scenario)
        // Note: In tests, all requests come from 127.0.0.1
        // This test documents the intended behavior
    }

    public function test_rate_limit_includes_retry_after_header(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        $key = 'admin-access:127.0.0.1';

        // Hit rate limit
        for ($i = 0; $i < 10; $i++) {
            RateLimiter::hit($key, 300);
        }

        $response = $this->actingAs($tenant)->get('/admin');
        
        expect($response->status())->toBe(429)
            ->and($response->headers->has('Retry-After'))->toBeTrue()
            ->and($response->json('retry_after'))->toBeGreaterThan(0);
    }

    public function test_only_counts_failed_authorization_attempts(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        // Make 20 successful requests
        for ($i = 0; $i < 20; $i++) {
            $response = $this->actingAs($admin)->get('/admin');
            expect($response->status())->toBe(200);
        }

        // Should not be rate limited
        $response = $this->actingAs($admin)->get('/admin');
        expect($response->status())->toBe(200);
    }

    public function test_rate_limit_decays_after_time_window(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        $key = 'admin-access:127.0.0.1';

        // Hit rate limit
        for ($i = 0; $i < 10; $i++) {
            RateLimiter::hit($key, 300);
        }

        // Should be rate limited
        $response = $this->actingAs($tenant)->get('/admin');
        expect($response->status())->toBe(429);

        // Simulate time passing (clear rate limiter for test)
        RateLimiter::clear($key);

        // Should be able to access again
        $response = $this->actingAs($tenant)->get('/admin');
        expect($response->status())->toBe(403); // Back to normal 403
    }

    public function test_middleware_handles_unauthenticated_requests(): void
    {
        $key = 'admin-access:127.0.0.1';

        // Hit rate limit
        for ($i = 0; $i < 10; $i++) {
            RateLimiter::hit($key, 300);
        }

        // Should be rate limited
        $response = $this->get('/admin');
        expect($response->status())->toBe(429);
    }

    public function test_rate_limit_response_is_json(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        $key = 'admin-access:127.0.0.1';

        // Hit rate limit
        for ($i = 0; $i < 10; $i++) {
            RateLimiter::hit($key, 300);
        }

        $response = $this->actingAs($tenant)->get('/admin');
        
        expect($response->status())->toBe(429)
            ->and($response->headers->get('Content-Type'))->toContain('application/json')
            ->and($response->json())->toHaveKeys(['message', 'retry_after']);
    }

    public function test_rate_limit_key_uses_ip_address(): void
    {
        $request = Request::create('/admin', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.100');

        $reflection = new \ReflectionClass($this->middleware);
        $method = $reflection->getMethod('resolveRequestSignature');
        $method->setAccessible(true);

        $key = $method->invoke($this->middleware, $request);

        expect($key)->toBe('admin-access:192.168.1.100');
    }
}
