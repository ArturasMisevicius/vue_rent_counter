<?php

declare(strict_types=1);

namespace Tests\Property;

use App\Services\Security\NonceGeneratorService;
use App\Services\Security\SecurityHeaderService;
use App\ValueObjects\SecurityNonce;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

/**
 * Property-based tests for SecurityHeaders system
 * 
 * Tests security properties that must hold across all scenarios:
 * - Nonce uniqueness
 * - Header consistency
 * - Performance bounds
 * - Error resilience
 */
final class SecurityHeadersPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Property: All generated nonces must be unique across requests
     * 
     * @test
     */
    public function nonce_uniqueness_property(): void
    {
        $service = app(NonceGeneratorService::class);
        $nonces = [];
        
        // Generate 100 nonces and verify uniqueness
        for ($i = 0; $i < 100; $i++) {
            $request = Request::create('/test-' . $i);
            $nonce = $service->getNonce($request);
            
            $this->assertNotContains($nonce->base64Encoded, $nonces, 
                'Nonce collision detected: ' . $nonce->base64Encoded);
            
            $nonces[] = $nonce->base64Encoded;
            
            // Verify nonce properties
            $this->assertTrue($nonce->isValid(), 'Generated nonce must be valid');
            $this->assertGreaterThanOrEqual(16, strlen(base64_decode($nonce->base64Encoded)), 
                'Nonce must be at least 16 bytes');
        }
    }

    /**
     * Property: Security headers must be consistent across identical requests
     * 
     * @test
     */
    public function header_consistency_property(): void
    {
        $paths = ['/', '/admin', '/api/user'];
        
        foreach ($paths as $path) {
            $responses = [];
            
            // Make multiple requests to the same path
            for ($i = 0; $i < 10; $i++) {
                $response = $this->get($path);
                $responses[] = $response;
            }
            
            // Verify consistent headers (except nonce which should change)
            $firstResponse = $responses[0];
            $expectedHeaders = [
                'X-Content-Type-Options',
                'X-Frame-Options',
                'Content-Security-Policy',
            ];
            
            foreach ($responses as $response) {
                foreach ($expectedHeaders as $header) {
                    $this->assertTrue($response->headers->has($header), 
                        "Header {$header} missing from response to {$path}");
                    
                    if ($header !== 'Content-Security-Policy') {
                        // Non-CSP headers should be identical
                        $this->assertEquals(
                            $firstResponse->headers->get($header),
                            $response->headers->get($header),
                            "Header {$header} inconsistent for {$path}"
                        );
                    }
                }
            }
        }
    }

    /**
     * Property: Security header processing must complete within performance bounds
     * 
     * @test
     */
    public function performance_bounds_property(): void
    {
        $maxProcessingTime = 50; // milliseconds
        $iterations = 50;
        
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            
            $response = $this->get('/test-performance-' . $i);
            
            $processingTime = (microtime(true) - $startTime) * 1000;
            
            $this->assertLessThan($maxProcessingTime, $processingTime,
                "Security header processing took {$processingTime}ms, exceeds {$maxProcessingTime}ms limit");
            
            $response->assertStatus(200);
            $response->assertHeader('X-Content-Type-Options');
        }
    }

    /**
     * Property: System must be resilient to various error conditions
     * 
     * @test
     */
    public function error_resilience_property(): void
    {
        // Test various error scenarios
        $errorScenarios = [
            'invalid-path' => '/invalid/path/that/does/not/exist',
            'malformed-request' => '//malformed//path',
            'long-path' => '/' . str_repeat('a', 1000),
        ];
        
        foreach ($errorScenarios as $scenario => $path) {
            $response = $this->get($path);
            
            // Even on errors, security headers should be present
            $this->assertTrue($response->headers->has('X-Content-Type-Options'),
                "Security headers missing in scenario: {$scenario}");
            
            // Should have fallback headers at minimum
            $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'),
                "Fallback headers not applied in scenario: {$scenario}");
        }
    }

    /**
     * Property: CSP nonces must be properly formatted and valid
     * 
     * @test
     */
    public function csp_nonce_format_property(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $response = $this->get('/test-nonce-' . $i);
            
            $csp = $response->headers->get('Content-Security-Policy');
            $this->assertNotEmpty($csp, 'CSP header must be present');
            
            // Extract nonce from CSP
            preg_match("/'nonce-([^']+)'/", $csp, $matches);
            $this->assertNotEmpty($matches[1], 'CSP must contain valid nonce');
            
            $nonceValue = $matches[1];
            
            // Verify nonce format
            $this->assertTrue(base64_decode($nonceValue, true) !== false,
                'Nonce must be valid base64: ' . $nonceValue);
            
            $decoded = base64_decode($nonceValue);
            $this->assertGreaterThanOrEqual(16, strlen($decoded),
                'Decoded nonce must be at least 16 bytes');
        }
    }

    /**
     * Property: Different user roles must receive appropriate security headers
     * 
     * @test
     */
    public function role_based_security_property(): void
    {
        $roles = [
            \App\Enums\UserRole::SUPERADMIN,
            \App\Enums\UserRole::ADMIN,
            \App\Enums\UserRole::MANAGER,
            \App\Enums\UserRole::TENANT,
        ];
        
        foreach ($roles as $role) {
            $user = \App\Models\User::factory()->create([
                'role' => $role,
                'is_active' => true,
            ]);
            
            $response = $this->actingAs($user)->get('/admin');
            
            // All roles should get basic security headers
            $response->assertHeader('X-Content-Type-Options', 'nosniff');
            $response->assertHeader('Content-Security-Policy');
            
            // Admin and superadmin should get stricter headers
            if (in_array($role, [\App\Enums\UserRole::ADMIN, \App\Enums\UserRole::SUPERADMIN])) {
                $frameOptions = $response->headers->get('X-Frame-Options');
                $this->assertContains($frameOptions, ['DENY', 'SAMEORIGIN'],
                    "Admin roles should have strict frame options, got: {$frameOptions}");
            }
        }
    }

    /**
     * Property: Security violation data must be properly encrypted and sanitized
     * 
     * @test
     */
    public function security_violation_data_protection_property(): void
    {
        $service = app(\App\Services\Security\SecurityAnalyticsMcpService::class);
        
        for ($i = 0; $i < 50; $i++) {
            $request = \Illuminate\Http\Request::create('/api/csp-report', 'POST', [], [], [], [], json_encode([
                'csp-report' => [
                    'violated-directive' => 'script-src',
                    'blocked-uri' => 'https://example.com/sensitive-' . $i,
                    'document-uri' => 'https://app.test/',
                    'original-policy' => "default-src 'self'; script-src 'self'",
                ],
            ]));

            $request->headers->set('Content-Type', 'application/json');

            $violation = $service->processCspViolationFromRequest($request);
            
            $this->assertInstanceOf(\App\Models\SecurityViolation::class, $violation);

            // Verify sensitive metadata is encrypted
            $metadata = $violation->metadata;
            
            if (isset($metadata['original_policy'])) {
                // Should be encrypted (not the original value)
                $this->assertNotEquals("default-src 'self'; script-src 'self'", $metadata['original_policy']);
                
                // Should be decryptable
                $decrypted = decrypt($metadata['original_policy']);
                $this->assertEquals("default-src 'self'; script-src 'self'", $decrypted);
            }
        }
    }

    /**
     * Property: Rate limiting must be consistently enforced across all security endpoints
     * 
     * @test
     */
    public function security_rate_limiting_property(): void
    {
        $endpoints = [
            '/api/security/violations',
            '/api/security/metrics',
            '/api/security/dashboard',
        ];

        $user = \App\Models\User::factory()->create([
            'role' => \App\Enums\UserRole::ADMIN,
        ]);

        foreach ($endpoints as $endpoint) {
            $rateLimitHit = false;
            
            for ($i = 0; $i < 70; $i++) {
                $response = $this->actingAs($user)->getJson($endpoint);
                
                if ($response->status() === 429) {
                    $rateLimitHit = true;
                    break;
                }
            }
            
            $this->assertTrue($rateLimitHit, "Rate limiting not enforced for {$endpoint}");
        }
    }

    /**
     * Property: CSP violation reports must be validated and sanitized consistently
     * 
     * @test
     */
    public function csp_violation_validation_property(): void
    {
        $maliciousPatterns = [
            'javascript:alert("xss")',
            'data:text/html,<script>alert("xss")</script>',
            '<script>alert("xss")</script>',
            'eval(atob("YWxlcnQoJ1hTUycpOw=="))',
            'vbscript:msgbox("xss")',
        ];

        foreach ($maliciousPatterns as $pattern) {
            $payload = [
                'csp-report' => [
                    'violated-directive' => 'script-src',
                    'document-uri' => 'https://example.com/test',
                    'blocked-uri' => $pattern,
                ],
            ];

            $response = $this->postJson('/api/csp-report', $payload);
            
            // Should either reject (422) or sanitize and accept (201)
            $this->assertContains($response->status(), [201, 422]);
            
            // If accepted, verify the malicious content was sanitized
            if ($response->status() === 201) {
                $violation = \App\Models\SecurityViolation::latest()->first();
                $this->assertNotEquals($pattern, decrypt($violation->blocked_uri));
            }
        }
    }
}