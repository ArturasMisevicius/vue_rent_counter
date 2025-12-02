<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security Headers Middleware
 * 
 * Implements comprehensive security headers following OWASP recommendations:
 * - Content Security Policy (CSP)
 * - X-Frame-Options (Clickjacking protection)
 * - X-Content-Type-Options (MIME sniffing protection)
 * - Referrer-Policy (Privacy protection)
 * - Permissions-Policy (Feature policy)
 * - Strict-Transport-Security (HTTPS enforcement)
 * 
 * @see https://owasp.org/www-project-secure-headers/
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Content Security Policy
        $response->headers->set('Content-Security-Policy', $this->getCSP());

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Prevent MIME sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // XSS Protection (legacy browsers)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions policy
        $response->headers->set('Permissions-Policy', $this->getPermissionsPolicy());

        // HSTS (only in production with HTTPS)
        if (app()->environment('production') && $request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        return $response;
    }

    /**
     * Get Content Security Policy directives.
     */
    protected function getCSP(): string
    {
        $directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.jsdelivr.net unpkg.com", // Tailwind/Alpine CDN
            "style-src 'self' 'unsafe-inline' cdn.jsdelivr.net", // Tailwind CDN
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
     * Get Permissions Policy directives.
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
