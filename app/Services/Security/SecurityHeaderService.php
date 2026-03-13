<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Services\Security\SecurityPerformanceMonitor;
use App\ValueObjects\SecurityHeaderSet;
use App\ValueObjects\SecurityNonce;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

/**
 * Security Header Service
 * 
 * Main orchestrator for applying security headers to HTTP responses.
 * Optimized for performance with caching and reduced overhead.
 */
final class SecurityHeaderService
{
    private static array $performanceCache = [];
    private static ?float $lastSlowLogTime = null;
    
    public function __construct(
        private readonly NonceGeneratorService $nonceGenerator,
        private readonly SecurityHeaderFactory $headerFactory,
        private readonly LoggerInterface $logger,
        private readonly SecurityPerformanceMonitor $monitor
    ) {}

    /**
     * Apply security headers to a response with performance optimization.
     * 
     * Performance optimizations:
     * - Cached context determination
     * - Reduced logging (only on slow requests)
     * - Streamlined error handling
     * - Optimized header application
     */
    public function applyHeaders(Request $request, BaseResponse $response): BaseResponse
    {
        $startTime = microtime(true);
        
        try {
            // Get nonce from request attributes (set by ViteCSPIntegration)
            $nonce = $this->getNonceFromRequest($request);
            
            // Store nonce in request attributes for template access
            $request->attributes->set('csp_nonce', $nonce->base64Encoded);

            // Determine context and create appropriate headers (with caching)
            $context = $this->determineContextCached($request);
            $headers = $this->headerFactory->createForContextOptimized($context, $nonce);

            // Apply headers to response efficiently
            $this->applyHeadersToResponseOptimized($response, $headers);

            // Record performance metrics
            $processingTime = (microtime(true) - $startTime) * 1000;
            $this->monitor->recordMetric('apply_headers', $processingTime, [
                'context' => $context,
                'path' => $request->getPathInfo(),
            ]);
            
            // Log slow performance with throttling
            if ($processingTime > 15) {
                $this->logSlowPerformance($request, $processingTime, $context);
            }

            return $response;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to apply security headers', [
                'error' => $e->getMessage(),
                'path' => $request->getPathInfo(),
            ]);

            // Apply minimal fallback headers
            $this->applyFallbackHeaders($response);
            
            return $response;
        }
    }

    /**
     * Get nonce from request or generate new one.
     */
    private function getNonceFromRequest(Request $request): SecurityNonce
    {
        // Try to get existing nonce from ViteCSPIntegration
        $existingNonce = $request->attributes->get('vite_csp_nonce');
        
        if ($existingNonce instanceof SecurityNonce) {
            return $existingNonce;
        }
        
        // Fallback: generate new nonce
        return $this->nonceGenerator->getNonce($request);
    }

    /**
     * Determine the security context from the request with caching.
     */
    private function determineContextCached(Request $request): string
    {
        $path = $request->getPathInfo();
        
        // Cache context determination for identical paths
        $cacheKey = $path . '_' . app()->environment();
        
        if (!isset(self::$performanceCache[$cacheKey])) {
            $baseContext = $this->headerFactory->determineContext($path);
            
            // Override with environment-specific context if needed
            if (app()->environment('production')) {
                self::$performanceCache[$cacheKey] = $baseContext === 'api' ? 'api' : 'production';
            } else {
                self::$performanceCache[$cacheKey] = $baseContext === 'api' ? 'api' : 'development';
            }
        }
        
        return self::$performanceCache[$cacheKey];
    }
    
    /**
     * Log slow performance with throttling to prevent log spam.
     */
    private function logSlowPerformance(Request $request, float $processingTime, string $context): void
    {
        $now = microtime(true);
        
        // Throttle logging to once per 30 seconds for performance
        if (self::$lastSlowLogTime === null || ($now - self::$lastSlowLogTime) > 30) {
            $this->logger->warning('Slow security header processing', [
                'duration_ms' => round($processingTime, 2),
                'context' => $context,
                'path' => $request->getPathInfo(),
                'method' => $request->getMethod(),
            ]);
            
            self::$lastSlowLogTime = $now;
        }
    }
    
    /**
     * Apply headers to response with optimized iteration.
     */
    private function applyHeadersToResponseOptimized(BaseResponse $response, SecurityHeaderSet $headers): void
    {
        $responseHeaders = $response->headers;
        foreach ($headers->toArray() as $name => $value) {
            $responseHeaders->set($name, $value);
        }
    }





    /**
     * Apply minimal fallback headers when main process fails.
     */
    private function applyFallbackHeaders(BaseResponse $response): void
    {
        $fallbackHeaders = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-XSS-Protection' => '1; mode=block',
        ];

        foreach ($fallbackHeaders as $name => $value) {
            if (!$response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }
    }

    /**
     * Get the current nonce for template usage.
     */
    public function getCurrentNonce(Request $request): ?SecurityNonce
    {
        return $request->attributes->get('current_nonce');
    }

    /**
     * Validate that required security headers are present.
     */
    public function validateHeaders(BaseResponse $response): array
    {
        $requiredHeaders = [
            'X-Content-Type-Options',
            'X-Frame-Options',
            'Content-Security-Policy',
        ];

        $missing = [];
        foreach ($requiredHeaders as $header) {
            if (!$response->headers->has($header)) {
                $missing[] = $header;
            }
        }

        if (!empty($missing)) {
            $this->logger->warning('Missing required security headers', [
                'missing_headers' => $missing,
            ]);
        }

        return $missing;
    }
}