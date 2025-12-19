<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\ValueObjects\SecurityNonce;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Psr\Log\LoggerInterface;

/**
 * Vite CSP Integration Service
 * 
 * Integrates Laravel's security nonce system with Vite's CSP nonce
 * requirements for seamless development and production builds.
 */
final class ViteCSPIntegration
{
    private const REQUEST_NONCE_KEY = 'vite_csp_nonce';

    public function __construct(
        private readonly NonceGeneratorService $nonceGenerator,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Initialize Vite CSP integration for the request.
     */
    public function initialize(Request $request): SecurityNonce
    {
        // Check if we already have a nonce for this request
        if ($request->attributes->has(self::REQUEST_NONCE_KEY)) {
            return $request->attributes->get(self::REQUEST_NONCE_KEY);
        }

        try {
            // Generate nonce using our security service
            $nonce = $this->nonceGenerator->getNonce($request);
            
            // Configure Vite to use our nonce
            Vite::useCspNonce($nonce->base64Encoded);
            
            // Store for request duration
            $request->attributes->set(self::REQUEST_NONCE_KEY, $nonce);
            
            // Debug logging removed for performance
            
            return $nonce;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to initialize Vite CSP nonce', [
                'error' => $e->getMessage(),
                'path' => $request->getPathInfo(),
            ]);
            
            // Fallback: let Vite generate its own nonce
            Vite::useCspNonce();
            
            // Create a fallback nonce for our system
            $fallbackNonce = SecurityNonce::generate();
            $request->attributes->set(self::REQUEST_NONCE_KEY, $fallbackNonce);
            
            return $fallbackNonce;
        }
    }

    /**
     * Get the current Vite CSP nonce.
     */
    public function getCurrentNonce(Request $request): ?SecurityNonce
    {
        return $request->attributes->get(self::REQUEST_NONCE_KEY);
    }

    /**
     * Get the Vite nonce value for template usage.
     */
    public function getViteNonce(): string
    {
        return Vite::cspNonce() ?? '';
    }

    /**
     * Check if Vite CSP is properly configured.
     */
    public function isConfigured(): bool
    {
        return !empty(Vite::cspNonce());
    }
}