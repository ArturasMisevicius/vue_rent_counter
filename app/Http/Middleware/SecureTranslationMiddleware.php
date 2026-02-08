<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Secure Translation Middleware
 * 
 * Ensures translation security and prevents injection attacks
 */
final class SecureTranslationMiddleware
{
    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Rate limiting for translation requests
        $this->applyRateLimit($request);
        
        // Validate locale parameter
        if ($request->has('locale')) {
            $locale = $request->get('locale');
            
            if (!$this->isValidLocale($locale)) {
                Log::warning('Invalid locale attempted', [
                    'locale_hash' => hash('sha256', $locale),
                    'ip_hash' => hash('sha256', $request->ip()),
                    'context' => 'security_violation'
                ]);
                
                abort(400, 'Invalid locale');
            }
        }
        
        // Validate translation keys in request
        if ($request->has('translation_key')) {
            $key = $request->get('translation_key');
            
            if (!$this->isValidTranslationKey($key)) {
                Log::warning('Invalid translation key attempted', [
                    'key_hash' => hash('sha256', $key),
                    'ip_hash' => hash('sha256', $request->ip()),
                    'context' => 'security_violation'
                ]);
                
                abort(400, 'Invalid translation key');
            }
        }
        
        // Validate all translation-related parameters
        $this->validateTranslationParameters($request);
        
        return $next($request);
    }

    /**
     * Apply rate limiting to translation requests
     */
    private function applyRateLimit(Request $request): void
    {
        $key = 'translation-requests:' . hash('sha256', $request->ip());
        
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 100)) {
            Log::warning('Translation request rate limit exceeded', [
                'ip_hash' => hash('sha256', $request->ip()),
                'context' => 'security_violation'
            ]);
            
            abort(429, 'Too many translation requests');
        }
        
        \Illuminate\Support\Facades\RateLimiter::hit($key, 60);
    }

    /**
     * Validate all translation-related parameters
     */
    private function validateTranslationParameters(Request $request): void
    {
        $translationParams = [
            'locale', 'translation_key', 'lang', 'language', 
            'translate', 'i18n', 'l10n'
        ];
        
        foreach ($translationParams as $param) {
            if ($request->has($param)) {
                $value = $request->get($param);
                
                // Check for potential injection attempts
                if ($this->containsSuspiciousContent($value)) {
                    Log::warning('Suspicious translation parameter detected', [
                        'param' => $param,
                        'value_hash' => hash('sha256', $value),
                        'ip_hash' => hash('sha256', $request->ip()),
                        'context' => 'security_violation'
                    ]);
                    
                    abort(400, 'Invalid parameter');
                }
            }
        }
    }

    /**
     * Check for suspicious content in parameters
     */
    private function containsSuspiciousContent(mixed $value): bool
    {
        if (!is_string($value)) {
            return true; // Only strings allowed
        }
        
        $suspiciousPatterns = [
            '/\.\.\//i',           // Path traversal
            '/<script/i',          // XSS
            '/javascript:/i',      // XSS
            '/on\w+\s*=/i',       // Event handlers
            '/union\s+select/i',   // SQL injection
            '/drop\s+table/i',     // SQL injection
            '/exec\s*\(/i',       // Code execution
            '/eval\s*\(/i',       // Code execution
            '/system\s*\(/i',     // System calls
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Validate locale against allowed locales
     */
    private function isValidLocale(string $locale): bool
    {
        $allowedLocales = array_keys(config('locales.available', []));
        
        return in_array($locale, $allowedLocales, true) && 
               preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $locale);
    }

    /**
     * Validate translation key format
     */
    private function isValidTranslationKey(string $key): bool
    {
        // Allow only alphanumeric, dots, underscores, and hyphens
        return preg_match('/^[a-zA-Z0-9._-]+$/', $key) && 
               strlen($key) <= 255 &&
               !str_contains($key, '..'); // Prevent path traversal
    }
}