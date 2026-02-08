<?php

declare(strict_types=1);

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\SecurityNonce;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\ValueObjects\SecurityNonce
 */
final class SecurityNonceTest extends TestCase
{
    public function test_generates_valid_nonce(): void
    {
        $nonce = SecurityNonce::generate();

        $this->assertNotEmpty($nonce->value);
        $this->assertNotEmpty($nonce->base64Encoded);
        $this->assertGreaterThan(0, $nonce->generatedAt);
        $this->assertTrue($nonce->isValid());
    }

    public function test_generates_nonce_with_custom_bytes(): void
    {
        $nonce = SecurityNonce::generate(32);

        // 32 bytes = 64 hex characters
        $this->assertEquals(64, strlen($nonce->value));
        $this->assertTrue($nonce->isValid());
    }

    public function test_rejects_insufficient_bytes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nonce must be at least 16 bytes');

        SecurityNonce::generate(8);
    }

    public function test_creates_from_base64(): void
    {
        $originalNonce = SecurityNonce::generate();
        $recreatedNonce = SecurityNonce::fromBase64($originalNonce->base64Encoded);

        $this->assertEquals($originalNonce->base64Encoded, $recreatedNonce->base64Encoded);
    }

    public function test_rejects_invalid_base64(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid base64 nonce value');

        SecurityNonce::fromBase64('invalid-base64!');
    }

    public function test_formats_for_csp(): void
    {
        $nonce = SecurityNonce::generate();
        $cspFormat = $nonce->forCsp();

        $this->assertStringStartsWith("'nonce-", $cspFormat);
        $this->assertStringEndsWith("'", $cspFormat);
        $this->assertStringContainsString($nonce->base64Encoded, $cspFormat);
    }

    public function test_validates_expiry(): void
    {
        $nonce = SecurityNonce::generate();

        // Should be valid immediately
        $this->assertTrue($nonce->isValid(3600));

        // Should be invalid with very short max age (need to wait a moment)
        sleep(1);
        $this->assertFalse($nonce->isValid(0));
    }

    public function test_string_conversion(): void
    {
        $nonce = SecurityNonce::generate();

        $this->assertEquals($nonce->base64Encoded, (string) $nonce);
    }

    public function test_rejects_empty_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nonce value cannot be empty');

        $reflection = new \ReflectionClass(SecurityNonce::class);
        $constructor = $reflection->getConstructor();
        $constructor->setAccessible(true);
        $constructor->invoke(
            $reflection->newInstanceWithoutConstructor(),
            '',
            'dGVzdA==',
            time()
        );
    }

    public function test_rejects_short_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nonce must be at least 16 bytes');

        $reflection = new \ReflectionClass(SecurityNonce::class);
        $constructor = $reflection->getConstructor();
        $constructor->setAccessible(true);
        $constructor->invoke(
            $reflection->newInstanceWithoutConstructor(),
            'short',
            'c2hvcnQ=',
            time()
        );
    }
}