<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\ValueObjects\SecurityNonce;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;

/**
 * Nonce Generator Service
 * 
 * Handles secure nonce generation with request-level caching
 * and performance optimization.
 */
final class NonceGeneratorService
{
    private const CACHE_KEY_PREFIX = 'security_nonce:';
    private const DEFAULT_NONCE_BYTES = 16;
    private const REQUEST_CACHE_KEY = 'current_nonce';

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Get or generate a nonce for the current request.
     * Optimized: Removed unnecessary cache operations.
     */
    public function getNonce(Request $request): SecurityNonce
    {
        // Check if we already have a nonce for this request
        if ($request->attributes->has(self::REQUEST_CACHE_KEY)) {
            return $request->attributes->get(self::REQUEST_CACHE_KEY);
        }

        // Generate new nonce
        $nonce = $this->generateNonce();
        
        // Cache it for the request duration only
        $request->attributes->set(self::REQUEST_CACHE_KEY, $nonce);

        return $nonce;
    }

    /**
     * Generate a new cryptographically secure nonce.
     * Optimized: Removed debug logging for performance.
     */
    public function generateNonce(int $bytes = self::DEFAULT_NONCE_BYTES): SecurityNonce
    {
        try {
            return SecurityNonce::generate($bytes);
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate security nonce', [
                'error' => $e->getMessage(),
                'bytes' => $bytes,
            ]);

            throw $e;
        }
    }

    /**
     * Validate a nonce value.
     */
    public function validateNonce(string $nonceValue, int $maxAge = 3600): bool
    {
        try {
            $nonce = SecurityNonce::fromBase64($nonceValue);
            return $nonce->isValid($maxAge);
        } catch (\Exception $e) {
            $this->logger->warning('Invalid nonce validation attempt', [
                'error' => $e->getMessage(),
                'nonce_length' => strlen($nonceValue),
            ]);

            return false;
        }
    }



    /**
     * Clear expired nonces from cache.
     */
    public function clearExpiredNonces(): int
    {
        // This would typically be called by a scheduled job
        // For now, we rely on cache TTL for cleanup
        return 0;
    }
}