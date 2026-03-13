<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Http\Middleware\SecurityHeaders;
use App\Services\Security\SecurityHeaderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

/**
 * @covers \App\Http\Middleware\SecurityHeaders
 */
final class SecurityHeadersMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_applies_security_headers_to_web_routes(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options');
        $response->assertHeader('Content-Security-Policy');
    }

    public function test_applies_security_headers_to_admin_routes(): void
    {
        $admin = \App\Models\User::factory()->create([
            'role' => \App\Enums\UserRole::ADMIN,
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Content-Security-Policy');
    }

    public function test_applies_api_headers_to_api_routes(): void
    {
        $user = \App\Models\User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $token = $user->createApiToken('test');

        $response = $this->withToken($token)->getJson('/api/user');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options');
    }

    public function test_includes_nonce_in_csp_header(): void
    {
        $response = $this->get('/');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("'nonce-", $csp);
    }

    public function test_nonce_is_available_in_request_attributes(): void
    {
        $request = Request::create('/test');
        $middleware = app(SecurityHeaders::class);

        $response = $middleware->handle($request, function ($req) {
            $nonce = $req->attributes->get('csp_nonce');
            $this->assertNotEmpty($nonce);
            
            return new Response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_production_headers_in_production_environment(): void
    {
        $this->app['env'] = 'production';
        
        $response = $this->get('/');

        // Should include production-specific headers
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
    }

    public function test_development_headers_in_development_environment(): void
    {
        $this->app['env'] = 'local';
        
        $response = $this->get('/');

        // Should include development-friendly CSP
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString('cdn.tailwindcss.com', $csp);
    }

    public function test_middleware_handles_service_exceptions_gracefully(): void
    {
        // Mock the service to throw an exception
        $this->mock(SecurityHeaderService::class, function ($mock) {
            $mock->shouldReceive('applyHeaders')
                ->andThrow(new \Exception('Service error'));
        });

        Log::shouldReceive('error')->once();

        $response = $this->get('/');

        // Should still return a response with fallback headers
        $response->assertStatus(200);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_middleware_works_with_symfony_base_response(): void
    {
        // Test BaseResponse type compatibility
        $request = Request::create('/test-base-response');
        $middleware = app(SecurityHeaders::class);

        $response = $middleware->handle($request, function ($req) {
            return new \Symfony\Component\HttpFoundation\Response('Base Response Content');
        });

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\Response::class, $response);
        $this->assertEquals('Base Response Content', $response->getContent());
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function test_mcp_analytics_integration_in_middleware(): void
    {
        $user = \App\Models\User::factory()->create([
            'role' => \App\Enums\UserRole::ADMIN,
        ]);

        // Mock MCP service to verify it's called
        $this->mock(\App\Services\Security\SecurityAnalyticsMcpService::class, function ($mock) {
            $mock->shouldReceive('analyzeSecurityMetrics')
                ->once()
                ->andReturn(['status' => 'ok']);
        });

        $response = $this->actingAs($user)->get('/admin/dashboard');
        $response->assertStatus(200);
    }

    public function test_csp_prevents_unsafe_inline(): void
    {
        $response = $this->get('/');

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringNotContainsString("'unsafe-inline'", $csp);
        $this->assertStringNotContainsString("'unsafe-eval'", $csp);
    }

    public function test_headers_are_consistent_across_requests(): void
    {
        $response1 = $this->get('/');
        $response2 = $this->get('/');

        $headers1 = $response1->headers->all();
        $headers2 = $response2->headers->all();

        // Security headers should be present in both
        $securityHeaders = [
            'x-content-type-options',
            'x-frame-options',
            'content-security-policy',
        ];

        foreach ($securityHeaders as $header) {
            $this->assertArrayHasKey($header, $headers1);
            $this->assertArrayHasKey($header, $headers2);
        }
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
}