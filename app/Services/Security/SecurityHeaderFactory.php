<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Services\Security\SecurityPerformanceMonitor;
use App\ValueObjects\SecurityHeaderSet;
use App\ValueObjects\SecurityNonce;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * Security Header Factory
 * 
 * Creates different sets of security headers based on context
 * with performance optimizations and caching.
 */
final class SecurityHeaderFactory
{
    private static ?SecurityHeaderSet $cachedBaseHeaders = null;
    private static array $cachedHeaderTemplates = [];
    private static array $cachedCspTemplates = [];
    
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly CspHeaderBuilder $cspBuilder,
        private readonly SecurityPerformanceMonitor $monitor
    ) {}

    /**
     * Create headers for production environment.
     */
    public function createProductionHeaders(SecurityNonce $nonce): SecurityHeaderSet
    {
        $headers = $this->getBaseHeaders();
        
        // Add production-specific headers
        $productionHeaders = [
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
            'Cross-Origin-Embedder-Policy' => 'require-corp',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Cross-Origin-Resource-Policy' => 'same-origin',
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=(), payment=(), usb=()',
            'X-Permitted-Cross-Domain-Policies' => 'none',
        ];

        // Build strict CSP for production
        $csp = $this->cspBuilder::strict()
            ->withNonce($nonce)
            ->addNonceToScripts()
            ->addNonceToStyles()
            ->build();

        return $headers
            ->merge(SecurityHeaderSet::create($productionHeaders))
            ->withHeader('Content-Security-Policy', $csp);
    }

    /**
     * Create headers for development environment.
     */
    public function createDevelopmentHeaders(SecurityNonce $nonce): SecurityHeaderSet
    {
        $headers = $this->getBaseHeaders();

        // Add development-specific headers (less strict)
        $developmentHeaders = [
            'X-Debug-Mode' => 'enabled',
        ];

        // Build development-friendly CSP
        $csp = $this->cspBuilder::development()
            ->withNonce($nonce)
            ->addNonceToScripts()
            ->addNonceToStyles()
            ->build();

        return $headers
            ->merge(SecurityHeaderSet::create($developmentHeaders))
            ->withHeader('Content-Security-Policy', $csp);
    }

    /**
     * Create headers for API endpoints.
     */
    public function createApiHeaders(): SecurityHeaderSet
    {
        return SecurityHeaderSet::create([
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Cross-Origin-Resource-Policy' => 'same-origin',
        ]);
    }

    /**
     * Create headers for admin panel.
     */
    public function createAdminHeaders(SecurityNonce $nonce): SecurityHeaderSet
    {
        $headers = $this->getBaseHeaders();

        // Enhanced CSP for admin panel
        $csp = $this->cspBuilder::strict()
            ->withNonce($nonce)
            ->addNonceToScripts()
            ->addNonceToStyles()
            ->scriptSrc("'self'", $nonce->forCsp()) // Allow admin scripts
            ->build();

        return $headers
            ->withHeader('Content-Security-Policy', $csp)
            ->withHeader('X-Frame-Options', 'DENY'); // Stricter for admin
    }

    /**
     * Create headers for tenant portal.
     */
    public function createTenantHeaders(SecurityNonce $nonce): SecurityHeaderSet
    {
        $headers = $this->getBaseHeaders();

        // Tenant-friendly CSP
        $csp = $this->cspBuilder::development()
            ->withNonce($nonce)
            ->addNonceToScripts()
            ->addNonceToStyles()
            ->build();

        return $headers->withHeader('Content-Security-Policy', $csp);
    }

    /**
     * Get base security headers from configuration with caching.
     */
    private function getBaseHeaders(): SecurityHeaderSet
    {
        if (self::$cachedBaseHeaders === null) {
            $configHeaders = $this->config->get('security.headers', []);
            
            // Filter out CSP as it's handled separately
            unset($configHeaders['Content-Security-Policy']);
            
            self::$cachedBaseHeaders = SecurityHeaderSet::create($configHeaders);
        }
        
        return self::$cachedBaseHeaders;
    }
    
    /**
     * Create API headers with caching (no CSP needed).
     */
    private function createApiHeadersCached(): SecurityHeaderSet
    {
        if (!isset(self::$cachedHeaderTemplates['api'])) {
            $this->monitor->recordCacheMiss();
            self::$cachedHeaderTemplates['api'] = SecurityHeaderSet::create([
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'DENY',
                'X-XSS-Protection' => '1; mode=block',
                'Referrer-Policy' => 'strict-origin-when-cross-origin',
                'Cross-Origin-Resource-Policy' => 'same-origin',
            ]);
        } else {
            $this->monitor->recordCacheHit();
        }
        
        return self::$cachedHeaderTemplates['api'];
    }
    
    /**
     * Create headers with CSP template and nonce injection.
     */
    private function createHeadersWithCspTemplate(string $context, SecurityNonce $nonce): SecurityHeaderSet
    {
        // Get cached CSP template for context
        $cspTemplate = $this->getCspTemplate($context);
        
        // Inject nonce into template
        $csp = str_replace('{{NONCE}}', $nonce->base64Encoded, $cspTemplate);
        
        // Get cached base headers for context
        $headers = $this->getHeadersTemplateForContext($context);
        
        return $headers->withHeader('Content-Security-Policy', $csp);
    }
    
    /**
     * Get cached CSP template for context.
     */
    private function getCspTemplate(string $context): string
    {
        if (!isset(self::$cachedCspTemplates[$context])) {
            // Build CSP template once with placeholder
            $builder = match ($context) {
                'production' => $this->cspBuilder::strict(),
                'admin' => $this->cspBuilder::strict(),
                'tenant', 'development' => $this->cspBuilder::development(),
                default => $this->cspBuilder::development(),
            };
            
            // Build CSP with placeholder nonce
            $tempNonce = SecurityNonce::generate();
            $csp = $builder
                ->withNonce($tempNonce)
                ->addNonceToScripts()
                ->addNonceToStyles()
                ->build();
            
            // Replace actual nonce with placeholder
            self::$cachedCspTemplates[$context] = str_replace(
                $tempNonce->base64Encoded,
                '{{NONCE}}',
                $csp
            );
        }
        
        return self::$cachedCspTemplates[$context];
    }
    
    /**
     * Get cached header template for context (without CSP).
     */
    private function getHeadersTemplateForContext(string $context): SecurityHeaderSet
    {
        if (!isset(self::$cachedHeaderTemplates[$context])) {
            $baseHeaders = $this->getBaseHeaders();
            
            self::$cachedHeaderTemplates[$context] = match ($context) {
                'production' => $baseHeaders->merge(SecurityHeaderSet::create([
                    'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
                    'Cross-Origin-Embedder-Policy' => 'require-corp',
                    'Cross-Origin-Opener-Policy' => 'same-origin',
                    'Cross-Origin-Resource-Policy' => 'same-origin',
                    'Permissions-Policy' => 'geolocation=(), microphone=(), camera=(), payment=(), usb=()',
                    'X-Permitted-Cross-Domain-Policies' => 'none',
                ])),
                'admin' => $baseHeaders->withHeader('X-Frame-Options', 'DENY'),
                'tenant', 'development' => $baseHeaders->withHeader('X-Debug-Mode', 'enabled'),
                default => $baseHeaders,
            };
        }
        
        return self::$cachedHeaderTemplates[$context];
    }

    /**
     * Determine context from request path.
     */
    public function determineContext(string $path): string
    {
        if (str_starts_with($path, '/api/')) {
            return 'api';
        }

        if (str_starts_with($path, '/admin/')) {
            return 'admin';
        }

        if (str_starts_with($path, '/tenant/') || str_starts_with($path, '/dashboard/')) {
            return 'tenant';
        }

        return 'web';
    }

    /**
     * Create headers based on context with performance optimization.
     */
    public function createForContext(string $context, ?SecurityNonce $nonce = null): SecurityHeaderSet
    {
        return match ($context) {
            'api' => $this->createApiHeaders(),
            'admin' => $this->createAdminHeaders($nonce ?? SecurityNonce::generate()),
            'tenant' => $this->createTenantHeaders($nonce ?? SecurityNonce::generate()),
            'production' => $this->createProductionHeaders($nonce ?? SecurityNonce::generate()),
            'development' => $this->createDevelopmentHeaders($nonce ?? SecurityNonce::generate()),
            default => $this->createDevelopmentHeaders($nonce ?? SecurityNonce::generate()),
        };
    }
    
    /**
     * Optimized version of createForContext with template caching.
     */
    public function createForContextOptimized(string $context, SecurityNonce $nonce): SecurityHeaderSet
    {
        // For API context, return cached headers (no CSP needed)
        if ($context === 'api') {
            return $this->createApiHeadersCached();
        }
        
        // For contexts requiring CSP, use template + nonce injection
        return $this->createHeadersWithCspTemplate($context, $nonce);
    }
}