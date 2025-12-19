<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Security\SecurityHeaderService;
use App\Services\Security\ViteCSPIntegration;
use App\Services\Security\SecurityAnalyticsMcpService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

/**
 * Security Headers Middleware
 * 
 * Applies comprehensive security headers to all HTTP responses to prevent common
 * web vulnerabilities including XSS, clickjacking, MIME sniffing, and content injection.
 * 
 * This middleware integrates with Laravel's Vite system for CSP nonce generation,
 * provides performance monitoring, and implements graceful error handling with
 * fallback security measures for multi-tenant utility billing applications.
 * 
 * Features:
 * - CSP nonce generation with Vite integration for secure inline scripts/styles
 * - Environment-aware security policies (development vs production)
 * - Performance monitoring with configurable thresholds (10ms warning)
 * - Graceful error handling with minimal fallback headers
 * - Request-level nonce caching for optimal performance
 * - Multi-tenant context awareness for appropriate security levels
 * 
 * Security Headers Applied:
 * - Content-Security-Policy: Prevents XSS and code injection
 * - X-Content-Type-Options: Prevents MIME sniffing attacks
 * - X-Frame-Options: Prevents clickjacking attacks
 * - X-XSS-Protection: Legacy XSS protection (fallback)
 * - Strict-Transport-Security: Enforces HTTPS (production)
 * - Cross-Origin-* policies: Controls cross-origin resource sharing
 * 
 * @see \App\Services\Security\SecurityHeaderService Main header orchestration
 * @see \App\Services\Security\ViteCSPIntegration Vite CSP nonce integration
 * @see \App\Services\Security\SecurityHeaderFactory Context-aware header creation
 * @see \App\ValueObjects\SecurityNonce Cryptographically secure nonce generation
 * 
 * @package App\Http\Middleware
 * @author Laravel Development Team
 * @since Laravel 12.x
 */
final class SecurityHeaders
{
    public function __construct(
        private readonly SecurityHeaderService $securityHeaderService,
        private readonly ViteCSPIntegration $viteIntegration,
        private readonly SecurityAnalyticsMcpService $analyticsService
    ) {}

    /**
     * Handle an incoming request and apply security headers.
     *
     * Optimized for performance:
     * - Removed duplicate performance tracking (handled by service)
     * - Streamlined error handling
     * - Reduced logging overhead
     *
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware in the pipeline
     *
     * @return BaseResponse The response with applied security headers
     */
    public function handle(Request $request, Closure $next): BaseResponse
    {
        try {
            // Initialize Vite CSP integration early
            $this->viteIntegration->initialize($request);
            
            $response = $next($request);
            
            // Apply security headers (performance tracking handled by service)
            $enhancedResponse = $this->securityHeaderService->applyHeaders($request, $response);
            
            // Track security metrics via MCP if enabled
            $this->trackSecurityMetrics($request, $enhancedResponse);
            
            return $enhancedResponse;
            
        } catch (\Throwable $e) {
            // Log only critical errors, not full stack traces
            Log::error('SecurityHeaders middleware error', [
                'error' => $e->getMessage(),
                'path' => $request->getPathInfo(),
                'method' => $request->getMethod(),
            ]);
            
            // Continue with response and apply minimal fallback headers
            $response = $next($request);
            $this->applyFallbackHeaders($response);
            
            return $response;
        }
    }

    /**
     * Apply minimal fallback security headers when main process fails.
     * 
     * Ensures basic security protection even when the primary security
     * header service encounters errors. These headers provide essential
     * protection against common vulnerabilities.
     * 
     * @param BaseResponse $response The HTTP response to modify
     * 
     * @return void
     * 
     * @internal This method is called only during error conditions
     */
    /**
     * Track security metrics via MCP service with enhanced security.
     */
    private function trackSecurityMetrics(Request $request, BaseResponse $response): void
    {
        try {
            // Only track if MCP analytics is available and user is authenticated
            if (!config('security.mcp.analytics_enabled', true) || !auth()->check()) {
                return;
            }

            // Sanitize and validate metrics data
            $metrics = [
                'request_path' => $this->sanitizePath($request->getPathInfo()),
                'request_method' => $request->getMethod(),
                'response_status' => $response->getStatusCode(),
                'headers_applied' => count($response->headers->all()),
                'csp_nonce_used' => $request->attributes->has('csp_nonce'),
                'tenant_id' => tenant()?->id,
                'user_id' => auth()->id(),
                'timestamp' => now()->toISOString(),
                'ip_hash' => hash('sha256', $request->ip() . config('app.key')), // Hash IP for privacy
                'user_agent_hash' => hash('sha256', $request->userAgent() . config('app.key')),
            ];

            // Validate tenant access
            if (!$this->validateTenantAccess($request)) {
                Log::warning('Unauthorized tenant access attempt', [
                    'tenant_id' => tenant()?->id,
                    'user_id' => auth()->id(),
                    'ip_hash' => $metrics['ip_hash'],
                ]);
                return;
            }

            // Track asynchronously with rate limiting
            if ($this->shouldTrackMetrics()) {
                dispatch(function () use ($metrics) {
                    $this->analyticsService->analyzeSecurityMetrics($metrics);
                })->afterResponse();
            }

        } catch (\Exception $e) {
            // Log security-related errors but don't expose details
            Log::error('Security metrics tracking failed', [
                'error_type' => get_class($e),
                'tenant_id' => tenant()?->id,
                'timestamp' => now()->toISOString(),
            ]);
        }
    }

    /**
     * Sanitize request path to prevent information disclosure.
     */
    private function sanitizePath(string $path): string
    {
        // Remove sensitive parameters and normalize path
        $path = preg_replace('/\/\d+/', '/{id}', $path); // Replace IDs with placeholder
        $path = preg_replace('/[?&]token=[^&]*/', '', $path); // Remove tokens
        $path = preg_replace('/[?&]key=[^&]*/', '', $path); // Remove keys
        
        return substr($path, 0, 255); // Limit length
    }

    /**
     * Validate tenant access for security metrics.
     */
    private function validateTenantAccess(Request $request): bool
    {
        $user = auth()->user();
        $tenant = tenant();

        // Superadmin can access all tenants
        if ($user?->isSuperAdmin()) {
            return true;
        }

        // User must belong to the current tenant
        if ($tenant && $user?->tenant_id !== $tenant->id) {
            return false;
        }

        return true;
    }

    /**
     * Rate limit metrics tracking to prevent abuse.
     */
    private function shouldTrackMetrics(): bool
    {
        $key = 'security_metrics_' . (auth()->id() ?? 'anonymous');
        $attempts = cache()->get($key, 0);
        
        if ($attempts >= 100) { // Max 100 metrics per minute per user
            return false;
        }
        
        cache()->put($key, $attempts + 1, 60);
        return true;
    }

    /**
     * Apply minimal fallback security headers when main process fails.
     * 
     * Ensures basic security protection even when the primary security
     * header service encounters errors. These headers provide essential
     * protection against common vulnerabilities.
     * 
     * @param BaseResponse $response The HTTP response to modify
     * 
     * @return void
     * 
     * @internal This method is called only during error conditions
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


}