<?php

declare(strict_types=1);

namespace App\ValueObjects;

use InvalidArgumentException;

/**
 * Security Nonce Value Object
 * 
 * Immutable value object representing a cryptographically secure nonce
 * for Content Security Policy and other security headers.
 */
final readonly class SecurityNonce
{
    private function __construct(
        public string $value,
        public string $base64Encoded,
        public int $generatedAt
    ) {
        if (empty($value)) {
            throw new InvalidArgumentException('Nonce value cannot be empty');
        }
        
        if (strlen($value) < 16) {
            throw new InvalidArgumentException('Nonce must be at least 16 bytes');
        }
    }

    /**
     * Generate a new cryptographically secure nonce.
     */
    public static function generate(int $bytes = 16): self
    {
        if ($bytes < 16) {
            throw new InvalidArgumentException('Nonce must be at least 16 bytes');
        }

        try {
            $randomBytes = random_bytes($bytes);
            $base64 = base64_encode($randomBytes);
            
            return new self(
                value: bin2hex($randomBytes),
                base64Encoded: $base64,
                generatedAt: time()
            );
        } catch (\Exception $e) {
            throw new InvalidArgumentException('Failed to generate secure nonce: ' . $e->getMessage());
        }
    }

    /**
     * Create from existing base64 encoded value.
     */
    public static function fromBase64(string $base64): self
    {
        $decoded = base64_decode($base64, true);
        
        if ($decoded === false) {
            throw new InvalidArgumentException('Invalid base64 nonce value');
        }

        return new self(
            value: bin2hex($decoded),
            base64Encoded: $base64,
            generatedAt: time()
        );
    }

    /**
     * Get the nonce formatted for CSP header.
     */
    public function forCsp(): string
    {
        return "'nonce-{$this->base64Encoded}'";
    }

    /**
     * Check if nonce is still valid (not expired).
     */
    public function isValid(int $maxAge = 3600): bool
    {
        return (time() - $this->generatedAt) <= $maxAge;
    }

    /**
     * Get nonce as string (returns base64 encoded value).
     */
    public function __toString(): string
    {
        return $this->base64Encoded;
    }
}