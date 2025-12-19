<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Security;

use App\Services\Security\CspHeaderBuilder;
use App\ValueObjects\SecurityNonce;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Services\Security\CspHeaderBuilder
 */
final class CspHeaderBuilderTest extends TestCase
{
    public function test_builds_basic_csp(): void
    {
        $csp = (new CspHeaderBuilder())
            ->defaultSrc("'self'")
            ->scriptSrc("'self'")
            ->build();

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src 'self'", $csp);
    }

    public function test_builds_with_multiple_sources(): void
    {
        $csp = (new CspHeaderBuilder())
            ->scriptSrc("'self'", 'cdn.example.com', 'https://trusted.com')
            ->build();

        $expected = "script-src 'self' cdn.example.com https://trusted.com";
        $this->assertEquals($expected, $csp);
    }

    public function test_adds_nonce_to_scripts(): void
    {
        $nonce = SecurityNonce::generate();
        
        $csp = (new CspHeaderBuilder())
            ->scriptSrc("'self'")
            ->withNonce($nonce)
            ->addNonceToScripts()
            ->build();

        $this->assertStringContainsString("'nonce-{$nonce->base64Encoded}'", $csp);
    }

    public function test_adds_nonce_to_styles(): void
    {
        $nonce = SecurityNonce::generate();
        
        $csp = (new CspHeaderBuilder())
            ->styleSrc("'self'")
            ->withNonce($nonce)
            ->addNonceToStyles()
            ->build();

        $this->assertStringContainsString("'nonce-{$nonce->base64Encoded}'", $csp);
    }

    public function test_prevents_duplicate_nonces(): void
    {
        $nonce = SecurityNonce::generate();
        
        $csp = (new CspHeaderBuilder())
            ->scriptSrc("'self'")
            ->withNonce($nonce)
            ->addNonceToScripts()
            ->addNonceToScripts() // Add twice
            ->build();

        $nonceCount = substr_count($csp, "'nonce-{$nonce->base64Encoded}'");
        $this->assertEquals(1, $nonceCount);
    }

    public function test_strict_preset(): void
    {
        $csp = CspHeaderBuilder::strict()->build();

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("frame-src 'none'", $csp);
    }

    public function test_development_preset(): void
    {
        $csp = CspHeaderBuilder::development()->build();

        $this->assertStringContainsString("script-src 'self' cdn.tailwindcss.com", $csp);
        $this->assertStringContainsString("style-src 'self' fonts.googleapis.com", $csp);
    }

    public function test_fluent_interface(): void
    {
        $builder = new CspHeaderBuilder();
        
        $result = $builder
            ->defaultSrc("'self'")
            ->scriptSrc("'self'")
            ->styleSrc("'self'");

        $this->assertSame($builder, $result);
    }

    public function test_rejects_empty_build(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one CSP directive must be set');

        (new CspHeaderBuilder())->build();
    }

    public function test_rejects_invalid_directive(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid CSP directive: invalid-directive');

        $builder = new CspHeaderBuilder();
        $reflection = new \ReflectionMethod($builder, 'setDirective');
        $reflection->setAccessible(true);
        $reflection->invoke($builder, 'invalid-directive', ["'self'"]);
    }

    public function test_rejects_empty_sources(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('CSP sources must be non-empty strings');

        (new CspHeaderBuilder())->scriptSrc('');
    }

    public function test_rejects_invalid_source_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid CSP source format: @invalid');

        (new CspHeaderBuilder())->scriptSrc('@invalid');
    }

    public function test_requires_nonce_for_script_addition(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nonce must be set before adding to scripts');

        (new CspHeaderBuilder())
            ->scriptSrc("'self'")
            ->addNonceToScripts();
    }

    public function test_requires_nonce_for_style_addition(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nonce must be set before adding to styles');

        (new CspHeaderBuilder())
            ->styleSrc("'self'")
            ->addNonceToStyles();
    }

    public function test_all_directive_methods(): void
    {
        $builder = (new CspHeaderBuilder())
            ->defaultSrc("'self'")
            ->scriptSrc("'self'")
            ->styleSrc("'self'")
            ->imgSrc("'self'")
            ->fontSrc("'self'")
            ->connectSrc("'self'")
            ->frameAncestors("'none'")
            ->frameSrc("'none'")
            ->objectSrc("'none'")
            ->baseUri("'self'")
            ->formAction("'self'");

        $csp = $builder->build();

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src 'self'", $csp);
        $this->assertStringContainsString("style-src 'self'", $csp);
        $this->assertStringContainsString("img-src 'self'", $csp);
        $this->assertStringContainsString("font-src 'self'", $csp);
        $this->assertStringContainsString("connect-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("frame-src 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
    }
}