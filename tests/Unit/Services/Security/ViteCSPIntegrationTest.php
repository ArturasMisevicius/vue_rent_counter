<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Security;

use App\Services\Security\NonceGeneratorService;
use App\Services\Security\ViteCSPIntegration;
use App\ValueObjects\SecurityNonce;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

/**
 * Integration tests for ViteCSPIntegration service
 * 
 * Uses real services instead of mocks to test actual integration behavior.
 */
final class ViteCSPIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private ViteCSPIntegration $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ViteCSPIntegration::class);
    }

    public function test_initializes_vite_csp_nonce(): void
    {
        $request = new Request();

        $result = $this->service->initialize($request);

        $this->assertInstanceOf(SecurityNonce::class, $result);
        $this->assertTrue($request->attributes->has('vite_csp_nonce'));
        $this->assertTrue($result->isValid());
    }

    public function test_returns_cached_nonce_on_subsequent_calls(): void
    {
        $request = new Request();

        // First call
        $result1 = $this->service->initialize($request);
        
        // Second call should return cached nonce
        $result2 = $this->service->initialize($request);

        $this->assertSame($result1, $result2);
        $this->assertEquals($result1->base64Encoded, $result2->base64Encoded);
    }

    public function test_gets_current_nonce(): void
    {
        $request = new Request();
        $nonce = SecurityNonce::generate();

        $request->attributes->set('vite_csp_nonce', $nonce);

        $result = $this->service->getCurrentNonce($request);

        $this->assertSame($nonce, $result);
    }

    public function test_returns_null_when_no_nonce_set(): void
    {
        $request = new Request();

        $result = $this->service->getCurrentNonce($request);

        $this->assertNull($result);
    }

    public function test_gets_vite_nonce_value(): void
    {
        $result = $this->service->getViteNonce();

        $this->assertIsString($result);
    }

    public function test_checks_if_configured(): void
    {
        $result = $this->service->isConfigured();

        $this->assertIsBool($result);
    }

    public function test_integration_with_nonce_generator(): void
    {
        $request = new Request();

        // Initialize should create a nonce and store it
        $nonce = $this->service->initialize($request);
        
        // The nonce should be valid and properly formatted
        $this->assertTrue($nonce->isValid());
        $this->assertGreaterThanOrEqual(16, strlen(base64_decode($nonce->base64Encoded)));
        
        // Should be accessible via getCurrentNonce
        $retrievedNonce = $this->service->getCurrentNonce($request);
        $this->assertSame($nonce, $retrievedNonce);
    }
}