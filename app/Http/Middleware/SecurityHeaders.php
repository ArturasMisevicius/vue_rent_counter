<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security Headers Middleware
 *
 * Applies security headers to all responses to protect against common web vulnerabilities.
 *
 * Headers applied:
 * - X-Frame-Options: Prevents clickjacking
 * - X-Content-Type-Options: Prevents MIME sniffing
 * - X-XSS-Protection: Legacy XSS protection
 * - Referrer-Policy: Controls referrer information
 * - Permissions-Policy: Controls browser features
 * - Strict-Transport-Security: Forces HTTPS (production only)
 * - Content-Security-Policy: Prevents XSS and code injection
 *
 * @see config/security.php For configuration
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

        // Apply security headers from config
        $headers = config('security.headers', []);

        foreach ($headers as $key => $value) {
            if ($value !== null) {
                $headerName = $this->formatHeaderName($key);
                $response->headers->set($headerName, $value);
            }
        }

        // Apply Content Security Policy
        if (config('security.csp.enabled', true)) {
            $cspHeader = $this->buildCspHeader();
            $headerName = config('security.csp.report_only', false)
                ? 'Content-Security-Policy-Report-Only'
                : 'Content-Security-Policy';

            $response->headers->set($headerName, $cspHeader);
        }

        return $response;
    }

    /**
     * Format header name from config key.
     *
     * Converts 'x-frame-options' to 'X-Frame-Options'
     */
    private function formatHeaderName(string $key): string
    {
        return implode('-', array_map('ucfirst', explode('-', $key)));
    }

    /**
     * Build Content Security Policy header value.
     */
    private function buildCspHeader(): string
    {
        $directives = config('security.csp.directives', []);
        $cspParts = [];

        foreach ($directives as $directive => $sources) {
            if (is_array($sources) && ! empty($sources)) {
                $cspParts[] = $directive.' '.implode(' ', $sources);
            }
        }

        // Add report-uri if configured
        if ($reportUri = config('security.csp.report_uri')) {
            $cspParts[] = 'report-uri '.$reportUri;
        }

        return implode('; ', $cspParts);
    }
}
