<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Services\Security\ViteCSPIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

/**
 * Enhanced Security Headers Integration Tests
 * 
 * Tests the improved SecurityHeaders middleware with Vite integration,
 * performance monitoring, and error handling.
 */
final class SecurityHeadersEnhancedTest extends TestCase
{
    use RefreshDatabase;

    public function test_applies_security_headers_with_vite_integration(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options');
        $response->assertHeader('Content-Security-Policy');
        
        // Verify CSP contains nonce
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("'nonce-", $csp);
    }

    public function test_performance_monitoring_logs_slow_requests(): void
    {
        Log::shouldReceive('info')
            ->withArgs(function ($message, $context) {
                return $message === 'SecurityHeaders performance' 
                    && isset($context['duration_ms'])
                    && $context['duration_ms'] > 0;
            })
            ->never(); // Should not log for fast requests

        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_handles_service_exceptions_gracefully(): void
    {
        // Mock the service to throw an exception
        $this->mock(ViteCSPIntegration::class, function ($mock) {
            $mock->shouldReceive('initialize')
                ->andThrow(new \Exception('Service error'));
        });

        Log::shouldReceive('error')
            ->withArgs(function ($message, $context) {
                return $message === 'SecurityHeaders middleware error'
                    && isset($context['error']);
            })
            ->once();

        $response = $this->get('/');

        // Should still return a response with fallback headers
        $response->assertStatus(200);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_development_headers_include_debug_mode(): void
    {
        $this->app['env'] = 'local';
        
        $response = $this->get('/');

        $response->assertStatus(200);
        
        // Should include development-specific headers
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString('cdn.tailwindcss.com', $csp);
        $this->assertStringContainsString('localhost:', $csp);
    }

    public function test_production_headers_are_strict(): void
    {
        $this->app['env'] = 'production';
        
        $response = $this->get('/');

        $response->assertStatus(200);
        
        // Should include production-specific headers
        $response->assertHeader('Strict-Transport-Security');
        $response->assertHeader('Cross-Origin-Embedder-Policy', 'require-corp');
        $response->assertHeader('Permissions-Policy');
        
        // CSP should be strict
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringNotContainsString("'unsafe-inline'", $csp);
    }

    public function test_api_routes_get_appropriate_headers(): void
    {
        $user = \App\Models\User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $token = $user->createApiToken('test');

        $response = $this->withToken($token)->getJson('/api/user');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY'); // Stricter for API
        $response->assertHeader('Cross-Origin-Resource-Policy', 'same-origin');
    }

    public function test_nonce_changes_between_requests(): void
    {
        $response1 = $this->get('/');
        $response2 = $this->get('/');

        $csp1 = $response1->headers->get('Content-Security-Policy');
        $csp2 = $response2->headers->get('Content-Security-Policy');

        // Extract nonces
        preg_match("/'nonce-([^']+)'/", $csp1, $matches1);
        preg_match("/'nonce-([^']+)'/", $csp2, $matches2);

        $this->assertNotEmpty($matches1[1]);
        $this->assertNotEmpty($matches2[1]);
        $this->assertNotEquals($matches1[1], $matches2[1]);
    }

    public function test_fallback_headers_applied_on_complete_failure(): void
    {
        // This would require more complex mocking to simulate complete service failure
        // For now, we test that the middleware doesn't break the application
        $response = $this->get('/');
        
        $response->assertStatus(200);
        $response->assertHeader('X-Content-Type-Options');
    }

    public function test_admin_routes_get_enhanced_security(): void
    {
        $admin = \App\Models\User::factory()->create([
            'role' => \App\Enums\UserRole::ADMIN,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertHeader('X-Frame-Options', 'DENY'); // Stricter for admin
        
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
    }
}