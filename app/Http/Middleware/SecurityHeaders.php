<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security Headers Middleware
 * 
 * Applies OWASP-recommended security headers to all responses.
 * 
 * Headers Applied:
 * - X-Frame-Options: Prevents clickjacking
 * - X-Content-Type-Options: Prevents MIME sniffing
 * - X-XSS-Protection: Legacy XSS protection
 * - Referrer-Policy: Controls referrer information
 * - Content-Security-Policy: Prevents XSS and injection attacks
 * - Permissions-Policy: Controls browser features
 * - Strict-Transport-Security: Enforces HTTPS
 * 
 * @package App\Http\Middleware
 */
final class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        
        // Prevent MIME sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        
        // Legacy XSS protection (still useful for older browsers)
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        // Control referrer information
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Content Security Policy
        $csp = $this->getContentSecurityPolicy();
        $response->headers->set('Content-Security-Policy', $csp);
        
        // Permissions Policy (formerly Feature-Policy)
        $response->headers->set('Permissions-Policy', $this->getPermissionsPolicy());
        
        // HSTS (only in production with HTTPS)
        if (config('app.env') === 'production' && $request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }
        
        return $response;
    }
    
    /**
     * Get Content Security Policy header value.
     */
    protected function getContentSecurityPolicy(): string
    {
        $directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.jsdelivr.net", // Alpine.js from CDN
            "style-src 'self' 'unsafe-inline' cdn.jsdelivr.net", // Tailwind from CDN
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ];
        
        return implode('; ', $directives);
    }
    
    /**
     * Get Permissions Policy header value.
     */
    protected function getPermissionsPolicy(): string
    {
        return implode(', ', [
            'geolocation=()',
            'microphone=()',
            'camera=()',
            'payment=()',
            'usb=()',
            'magnetometer=()',
            'gyroscope=()',
            'accelerometer=()',
        ]);
    }
}
