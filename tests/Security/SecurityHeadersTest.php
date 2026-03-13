<?php

declare(strict_types=1);

namespace Tests\Security;

use Tests\TestCase;

/**
 * Security Headers Tests
 * 
 * Validates that proper security headers are set to prevent
 * common web vulnerabilities like XSS, clickjacking, etc.
 */
class SecurityHeadersTest extends TestCase
{
    /** @test */
    public function it_includes_required_security_headers(): void
    {
        $response = $this->get('/');
        
        // Basic security headers
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // HSTS header
        $response->assertHeader('Strict-Transport-Security');
        $hsts = $response->headers->get('Strict-Transport-Security');
        $this->assertStringContainsString('max-age=31536000', $hsts);
        $this->assertStringContainsString('includeSubDomains', $hsts);
        
        // CSP header
        $response->assertHeader('Content-Security-Policy');
        
        // Permissions policy
        $response->assertHeader('Permissions-Policy');
    }

    /** @test */
    public function csp_header_prevents_unsafe_inline_scripts(): void
    {
        $response = $this->get('/');
        
        $csp = $response->headers->get('Content-Security-Policy');
        
        // Should not allow unsafe-inline or unsafe-eval
        $this->assertStringNotContainsString("'unsafe-inline'", $csp);
        $this->assertStringNotContainsString("'unsafe-eval'", $csp);
        
        // Should use nonce-based approach
        $this->assertStringContainsString("'nonce-", $csp);
        
        // Should have strict frame policies
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("frame-src 'none'", $csp);
    }

    /** @test */
    public function it_sets_secure_cookie_attributes(): void
    {
        // Make a request that would set a session cookie
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        
        $cookies = $response->headers->getCookies();
        
        foreach ($cookies as $cookie) {
            if (str_contains($cookie->getName(), 'session')) {
                $this->assertTrue($cookie->isHttpOnly(), 'Session cookie should be HttpOnly');
                
                if (config('session.secure')) {
                    $this->assertTrue($cookie->isSecure(), 'Session cookie should be Secure in production');
                }
                
                $this->assertEquals('strict', $cookie->getSameSite(), 'Session cookie should use SameSite=strict');
            }
        }
    }

    /** @test */
    public function it_prevents_clickjacking_attacks(): void
    {
        $response = $this->get('/admin');
        
        $frameOptions = $response->headers->get('X-Frame-Options');
        $csp = $response->headers->get('Content-Security-Policy');
        
        // Should prevent framing
        $this->assertEquals('SAMEORIGIN', $frameOptions);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
    }

    /** @test */
    public function it_prevents_mime_type_sniffing(): void
    {
        $response = $this->get('/');
        
        $contentTypeOptions = $response->headers->get('X-Content-Type-Options');
        $this->assertEquals('nosniff', $contentTypeOptions);
    }

    /** @test */
    public function it_controls_referrer_information(): void
    {
        $response = $this->get('/');
        
        $referrerPolicy = $response->headers->get('Referrer-Policy');
        $this->assertEquals('strict-origin-when-cross-origin', $referrerPolicy);
    }

    /** @test */
    public function it_restricts_dangerous_permissions(): void
    {
        $response = $this->get('/');
        
        $permissionsPolicy = $response->headers->get('Permissions-Policy');
        
        // Should deny dangerous permissions
        $this->assertStringContainsString('geolocation=()', $permissionsPolicy);
        $this->assertStringContainsString('microphone=()', $permissionsPolicy);
        $this->assertStringContainsString('camera=()', $permissionsPolicy);
    }

    /** @test */
    public function api_endpoints_have_security_headers(): void
    {
        $user = \App\Models\User::factory()->create([
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        
        $token = $user->createApiToken('test');
        
        $response = $this->withToken($token)->getJson('/api/user');
        
        // API endpoints should also have security headers
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Content-Security-Policy');
    }

    /** @test */
    public function it_enforces_https_in_production(): void
    {
        if (app()->environment('production')) {
            $response = $this->get('http://example.com/');
            
            // Should redirect to HTTPS or return appropriate headers
            $this->assertTrue(
                $response->isRedirect() || 
                $response->headers->has('Strict-Transport-Security')
            );
        } else {
            $this->markTestSkipped('HTTPS enforcement only tested in production');
        }
    }
}