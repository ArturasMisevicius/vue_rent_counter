# Security Headers API Documentation

## Overview

The Security Headers system provides a comprehensive API for managing HTTP security headers in Laravel applications. This documentation covers the middleware behavior, configuration options, and integration patterns.

## Middleware API

### SecurityHeaders Middleware

**Class**: `App\Http\Middleware\SecurityHeaders`

**Purpose**: Automatically applies security headers to all HTTP responses

**Registration**: Global middleware in `bootstrap/app.php`

#### Method: `handle(Request $request, Closure $next): Response`

Processes incoming requests and applies appropriate security headers.

**Parameters:**
- `$request` (Request): The incoming HTTP request
- `$next` (Closure): Next middleware in the pipeline

**Returns:** Response with applied security headers

**Throws:** Catches all exceptions for graceful error handling

**Behavior:**
1. Initializes Vite CSP integration
2. Processes request through application
3. Applies context-appropriate security headers
4. Monitors performance (logs if > 10ms)
5. Handles errors with fallback headers

## Route Context Detection

The middleware automatically detects route context and applies appropriate headers:

### API Routes (`/api/*`)

```http
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Cross-Origin-Resource-Policy: same-origin
Referrer-Policy: strict-origin-when-cross-origin
```

**Authentication**: Requires valid API token
**Rate Limiting**: Applied via separate middleware
**CORS**: Handled by Laravel CORS middleware

### Admin Routes (`/admin/*`)

```http
Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{nonce}'; frame-ancestors 'none'
X-Frame-Options: DENY
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
```

**Authentication**: Requires admin role
**Enhanced Security**: Stricter CSP and frame protection
**Audit Logging**: All admin actions logged

### Tenant Routes (`/tenant/*`, `/dashboard/*`)

```http
Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{nonce}' cdn.tailwindcss.com
X-Frame-Options: SAMEORIGIN
```

**Authentication**: Requires tenant user
**Balanced Security**: User-friendly CSP policies
**Multi-Tenant**: Scoped to tenant context

## Configuration API

### Security Configuration

**File**: `config/security.php`

```php
return [
    // Base security headers
    'headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
    ],
    
    // Performance monitoring
    'performance' => [
        'enabled' => true,
        'thresholds' => [
            'warning_ms' => 10,    // Log warning if processing > 10ms
            'error_ms' => 50,      // Log error if processing > 50ms
        ],
    ],
    
    // CSP configuration
    'csp' => [
        'nonce_enabled' => true,
        'report_uri' => env('CSP_REPORT_URI'),
        'report_only' => env('CSP_REPORT_ONLY', false),
    ],
    
    // Environment-specific settings
    'environments' => [
        'production' => [
            'strict_transport_security' => true,
            'cross_origin_policies' => true,
            'permissions_policy' => true,
        ],
        'development' => [
            'allow_localhost' => true,
            'allow_hmr' => true,
            'debug_headers' => true,
        ],
    ],
];
```

### Environment Variables

```env
# CSP Configuration
CSP_ENABLED=true
CSP_REPORT_URI=https://report-uri.com/r/d/csp/enforce
CSP_REPORT_ONLY=false
CSP_NONCE_ENABLED=true

# Performance Monitoring
SECURITY_PERFORMANCE_ENABLED=true
SECURITY_WARNING_THRESHOLD_MS=10
SECURITY_ERROR_THRESHOLD_MS=50

# Development Settings
CSP_ENABLED_WHILE_HOT_RELOADING=false
```

## Service APIs

### SecurityHeaderService

**Class**: `App\Services\Security\SecurityHeaderService`

#### Method: `applyHeaders(Request $request, BaseResponse $response): BaseResponse`

Applies security headers to a response with performance optimization.

**Parameters:**
- `$request` (Request): The HTTP request context
- `$response` (BaseResponse): Response to modify

**Returns:** Response with applied security headers

**Performance:** Cached nonce per request, < 10ms typical processing

### ViteCSPIntegration

**Class**: `App\Services\Security\ViteCSPIntegration`

#### Method: `initialize(Request $request): SecurityNonce`

Initializes Vite CSP integration for the request.

**Parameters:**
- `$request` (Request): Current HTTP request

**Returns:** SecurityNonce for CSP headers

**Caching:** Nonce cached per request to avoid regeneration

#### Method: `getCurrentNonce(Request $request): ?SecurityNonce`

Retrieves the current CSP nonce for the request.

**Parameters:**
- `$request` (Request): Current HTTP request

**Returns:** SecurityNonce or null if not initialized

### SecurityHeaderFactory

**Class**: `App\Services\Security\SecurityHeaderFactory`

#### Method: `createForContext(string $context, ?SecurityNonce $nonce = null): SecurityHeaderSet`

Creates headers based on request context.

**Parameters:**
- `$context` (string): Context ('api', 'admin', 'tenant', 'production', 'development')
- `$nonce` (?SecurityNonce): Optional nonce for CSP headers

**Returns:** SecurityHeaderSet with appropriate headers

**Contexts:**
- `api`: Strict headers for API endpoints
- `admin`: Enhanced security for admin panel
- `tenant`: Balanced security for tenant portal
- `production`: Strict production headers
- `development`: Development-friendly headers

## Value Object APIs

### SecurityNonce

**Class**: `App\ValueObjects\SecurityNonce`

#### Static Method: `generate(int $bytes = 16): SecurityNonce`

Generates a cryptographically secure nonce.

**Parameters:**
- `$bytes` (int): Number of random bytes (minimum 16)

**Returns:** SecurityNonce instance

**Security:** Uses `random_bytes()` for cryptographic security

#### Method: `forCsp(): string`

Formats nonce for CSP header usage.

**Returns:** String in format `'nonce-{base64_encoded_value}'`

#### Method: `isValid(int $maxAge = 3600): bool`

Checks if nonce is still valid (not expired).

**Parameters:**
- `$maxAge` (int): Maximum age in seconds

**Returns:** Boolean validity status

### SecurityHeaderSet

**Class**: `App\ValueObjects\SecurityHeaderSet`

#### Static Method: `create(array $headers): SecurityHeaderSet`

Creates a new security header set.

**Parameters:**
- `$headers` (array): Associative array of header name => value

**Returns:** SecurityHeaderSet instance

**Validation:** Validates header names and values per RFC 7230

#### Method: `merge(SecurityHeaderSet $other): SecurityHeaderSet`

Merges with another header set.

**Parameters:**
- `$other` (SecurityHeaderSet): Headers to merge

**Returns:** New SecurityHeaderSet with merged headers

**Behavior:** Later headers override earlier ones

## Error Handling API

### Fallback Headers

When the main security service fails, minimal fallback headers are applied:

```php
$fallbackHeaders = [
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'SAMEORIGIN',
    'X-XSS-Protection' => '1; mode=block',
];
```

### Error Logging

All errors are logged with structured context:

```php
Log::error('SecurityHeaders middleware error', [
    'error' => $exception->getMessage(),
    'path' => $request->getPathInfo(),
    'method' => $request->getMethod(),
    'trace' => $exception->getTraceAsString(),
    'user_id' => auth()->id(),
    'tenant_id' => tenant()?->id,
]);
```

## Performance API

### Monitoring

Performance metrics are automatically logged:

```php
Log::info('SecurityHeaders performance', [
    'duration_ms' => $processingTime,
    'path' => $request->getPathInfo(),
    'method' => $request->getMethod(),
    'nonce_cached' => $nonceCached,
    'headers_applied' => $headerCount,
]);
```

### Thresholds

- **Warning**: > 10ms processing time
- **Error**: > 50ms processing time
- **Typical**: < 5ms processing time
- **Memory**: < 1MB overhead per request

## Integration Patterns

### Blade Template Integration

```blade
{{-- CSP nonce component --}}
<script <x-security.csp-nonce>>
    // Inline script with CSP compliance
</script>

{{-- Vite integration --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

### Custom Middleware Integration

```php
class CustomSecurityMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Custom logic before SecurityHeaders
        $response = $next($request);
        
        // SecurityHeaders middleware has already applied headers
        // Add custom headers if needed
        $response->headers->set('X-Custom-Header', 'value');
        
        return $response;
    }
}
```

### Service Container Integration

```php
// Resolve services for custom usage
$headerService = app(SecurityHeaderService::class);
$viteIntegration = app(ViteCSPIntegration::class);
$headerFactory = app(SecurityHeaderFactory::class);

// Apply headers manually (not recommended)
$headers = $headerFactory->createForContext('api');
foreach ($headers->toArray() as $name => $value) {
    $response->headers->set($name, $value);
}
```

## Testing API

### Property-Based Testing

```php
// Test nonce uniqueness
public function nonce_uniqueness_property(): void
{
    // Generates 100+ nonces and verifies uniqueness
}

// Test header consistency
public function header_consistency_property(): void
{
    // Verifies consistent headers across identical requests
}

// Test performance bounds
public function performance_bounds_property(): void
{
    // Ensures processing completes within bounds
}
```

### Integration Testing

```php
public function test_api_routes_get_appropriate_headers(): void
{
    $response = $this->withToken($token)->getJson('/api/user');
    
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'DENY');
}
```

## Compliance & Standards

### OWASP Compliance

- **A03:2021 – Injection**: CSP prevents code injection
- **A05:2021 – Security Misconfiguration**: Proper header configuration
- **A06:2021 – Vulnerable Components**: Secure dependency management

### RFC Compliance

- **RFC 7230**: HTTP/1.1 Message Syntax and Routing
- **RFC 7234**: HTTP/1.1 Caching
- **RFC 7235**: HTTP/1.1 Authentication

### Browser Support

- **Chrome**: Full CSP Level 3 support
- **Firefox**: Full CSP Level 3 support
- **Safari**: CSP Level 2 support
- **Edge**: Full CSP Level 3 support

## Migration Guide

### From v1.x to v2.x

1. **Update Configuration**: Add performance monitoring settings
2. **Update Templates**: Use new CSP nonce component
3. **Test Integration**: Verify Vite CSP integration
4. **Monitor Performance**: Enable performance logging

### Breaking Changes

- CSP nonce format changed to base64
- Fallback headers reduced to essential set
- Performance monitoring enabled by default
- Strict type declarations required

## Related APIs

- [Laravel HTTP Middleware](https://laravel.com/docs/12.x/middleware)
- [Laravel Vite CSP](https://laravel.com/docs/12.x/vite#content-security-policy-csp)
- [Symfony Response](https://symfony.com/doc/current/components/http_foundation.html#response)
- [OWASP Security Headers](https://owasp.org/www-project-secure-headers/)