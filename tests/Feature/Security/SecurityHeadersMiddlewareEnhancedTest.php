<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Http\Middleware\SecurityHeaders;
use App\Services\Security\SecurityAnalyticsMcpService;
use App\Services\Security\SecurityHeaderService;
use App\Services\Security\ViteCSPIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use Tests\TestCase;

/**
 * Enhanced SecurityHeaders Middleware Tests
 * 
 * Tests the enhanced middleware with BaseResponse type safety,
 * MCP integration, and comprehensive security analytics.
 */
final class SecurityHeadersMiddlewareEnhancedTest extends TestCase
{
    use RefreshDatabase;

    public function test_middleware_works_with_base_response_type(): void
    {
        // Test that the new BaseResponse import works correctly
        $request = Request::create('/test');
        $middleware = app(SecurityHeaders::class);

        $response = $middleware->handle($request, function ($req) {
            // Return Symfony BaseResponse instead of Illuminate Response
            return new BaseResponse('OK', 200, [
                'Content-Type' => 'text/plain',
            ]);
        });

        $this->assertInstanceOf(BaseResponse::class, $response);
        $this->assertEquals('OK', $response->getContent());
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function test_fallback_headers_applied_with_base_response(): void
    {
        // Mock services to throw exceptions
        $this->mock(SecurityHeaderService::class, function ($mock) {
            $mock->shouldReceive('applyHeaders')
                ->andThrow(new \Exception('Service error'));
        });

        $this->mock(ViteCSPIntegration::class, function ($mock) {
            $mock->shouldReceive('initialize')
                ->andThrow(new \Exception('Vite error'));
        });

        Log::shouldReceive('error')->once();

        $request = Request::create('/test');
        $middleware = app(SecurityHeaders::class);

        $response = $middleware->handle($request, function ($req) {
            return new BaseResponse('OK');
        });

        // Should have fallback headers even with BaseResponse
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertEquals('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        $this->assertEquals('1; mode=block', $response->headers->get('X-XSS-Protection'));
    }

    public function test_mcp_security_analytics_integration(): void
    {
        $user = \App\Models\User::factory()->create([
            'role' => \App\Enums\UserRole::ADMIN,
            'is_active' => true,
        ]);

        // Mock MCP service
        $this->mock(SecurityAnalyticsMcpService::class, function ($mock) {
            $mock->shouldReceive('analyzeSecurityMetrics')
                ->once()
                ->with(\Mockery::type('array'))
                ->andReturn([
                    'violations_count' => 5,
                    'threat_level' => 'medium',
                    'anomalies_detected' => 2,
                ]);
        });

        $response = $this->actingAs($user)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertHeader('Content-Security-Policy');
    }

    public function test_csp_violation_processing_with_mcp(): void
    {
        $violationData = [
            'csp-report' => [
                'violated-directive' => 'script-src',
                'blocked-uri' => 'https://malicious.example.com/script.js',
                'document-uri' => 'https://app.test/admin',
                'referrer' => 'https://app.test/',
                'source-file' => 'https://app.test/admin',
                'line-number' => 42,
                'column-number' => 15,
            ],
        ];

        // Mock MCP service
        $this->mock(SecurityAnalyticsMcpService::class, function ($mock) {
            $mock->shouldReceive('trackCspViolation')
                ->once()
                ->andReturn(true);
            
            $mock->shouldReceive('processCspViolationFromRequest')
                ->once()
                ->andReturn(\App\Models\SecurityViolation::factory()->make());
        });

        $response = $this->postJson('/api/csp-report', $violationData);

        $response->assertStatus(201);
        
        // Verify violation was stored
        $this->assertDatabaseHas('security_violations', [
            'violation_type' => 'csp',
            'policy_directive' => 'script-src',
        ]);
    }

    public function test_mcp_rate_limiting_enforcement(): void
    {
        $user = \App\Models\User::factory()->create([
            'role' => \App\Enums\UserRole::ADMIN,
        ]);

        // Make many requests to trigger rate limiting
        for ($i = 0; $i < 105; $i++) {
            $response = $this->actingAs($user)->get('/admin/security/metrics');
            
            if ($response->status() === 429) {
                break;
            }
        }

        $this->assertEquals(429, $response->status());
    }

    public function test_tenant_isolation_in_mcp_analytics(): void
    {
        $tenant1 = \App\Models\Tenant::factory()->create();
        $tenant2 = \App\Models\Tenant::factory()->create();
        
        $user1 = \App\Models\User::factory()->create([
            'tenant_id' => $tenant1->id,
            'role' => \App\Enums\UserRole::ADMIN,
        ]);
        
        $user2 = \App\Models\User::factory()->create([
            'tenant_id' => $tenant2->id,
            'role' => \App\Enums\UserRole::ADMIN,
        ]);

        // Mock MCP service to verify tenant isolation
        $this->mock(SecurityAnalyticsMcpService::class, function ($mock) use ($tenant1) {
            $mock->shouldReceive('analyzeSecurityMetrics')
                ->once()
                ->with(\Mockery::on(function ($args) use ($tenant1) {
                    return isset($args['tenant_id']) && $args['tenant_id'] === $tenant1->id;
                }))
                ->andReturn(['tenant_specific_data' => true]);
        });

        // User 1 should only see their tenant's data
        $response = $this->actingAs($user1)->getJson('/api/security/analytics');
        $response->assertStatus(200);

        // User 2 should not access user 1's tenant data
        $response = $this->actingAs($user2)->getJson('/api/security/analytics');
        $response->assertStatus(403); // Should be forbidden due to tenant isolation
    }

    public function test_mcp_service_fallback_on_failure(): void
    {
        // Mock MCP service to fail
        $this->mock(SecurityAnalyticsMcpService::class, function ($mock) {
            $mock->shouldReceive('analyzeSecurityMetrics')
                ->andThrow(new \Exception('MCP server unavailable'));
        });

        Log::shouldReceive('error')
            ->once()
            ->with('MCP security analytics failed', \Mockery::type('array'));

        $user = \App\Models\User::factory()->create([
            'role' => \App\Enums\UserRole::ADMIN,
        ]);

        // Should still work with fallback behavior
        $response = $this->actingAs($user)->get('/admin/dashboard');
        $response->assertStatus(200);
        $response->assertHeader('Content-Security-Policy');
    }

    public function test_performance_monitoring_with_mcp(): void
    {
        $startTime = microtime(true);

        $response = $this->get('/');

        $processingTime = (microtime(true) - $startTime) * 1000;

        $response->assertStatus(200);
        $response->assertHeader('Content-Security-Policy');
        
        // Should complete within performance threshold
        $this->assertLessThan(50, $processingTime);
    }

    public function test_security_headers_with_different_response_types(): void
    {
        $responseTypes = [
            'json' => fn() => response()->json(['status' => 'ok']),
            'view' => fn() => response()->view('welcome'),
            'redirect' => fn() => redirect('/'),
            'download' => fn() => response()->download(__FILE__),
        ];

        foreach ($responseTypes as $type => $responseFactory) {
            $request = Request::create('/test-' . $type);
            $middleware = app(SecurityHeaders::class);

            $response = $middleware->handle($request, function ($req) use ($responseFactory) {
                return $responseFactory();
            });

            $this->assertTrue($response->headers->has('X-Content-Type-Options'),
                "Missing security headers for {$type} response");
        }
    }

    public function test_csp_nonce_consistency_across_services(): void
    {
        $request = Request::create('/test');
        $middleware = app(SecurityHeaders::class);

        $response = $middleware->handle($request, function ($req) {
            // Verify nonce is available in request attributes
            $this->assertNotNull($req->attributes->get('csp_nonce'));
            $this->assertNotNull($req->attributes->get('vite_csp_nonce'));
            
            return new Response('OK');
        });

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("'nonce-", $csp);
    }

    public function test_malicious_csp_violation_detection(): void
    {
        $maliciousViolations = [
            [
                'csp-report' => [
                    'violated-directive' => 'script-src',
                    'blocked-uri' => 'javascript:alert("xss")',
                    'document-uri' => 'https://app.test/',
                ],
            ],
            [
                'csp-report' => [
                    'violated-directive' => 'script-src',
                    'blocked-uri' => 'data:text/html,<script>alert("xss")</script>',
                    'document-uri' => 'https://app.test/',
                ],
            ],
        ];

        foreach ($maliciousViolations as $violationData) {
            Log::shouldReceive('alert')
                ->once()
                ->with('Potential CSP attack detected', \Mockery::type('array'));

            $response = $this->postJson('/api/csp-report', $violationData);
            
            // Should still process but log as malicious
            $response->assertStatus(201);
        }
    }
}