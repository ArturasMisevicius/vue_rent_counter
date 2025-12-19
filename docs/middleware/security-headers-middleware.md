# SecurityHeaders Middleware Documentation

## Overview

The `SecurityHeaders` middleware is a comprehensive security solution for Laravel applications that automatically applies security headers to all HTTP responses. It's specifically designed for multi-tenant utility billing platforms with enhanced security requirements.

## Features

### Core Security Protection
- **XSS Prevention**: Content Security Policy with nonce-based inline script/style protection
- **Clickjacking Protection**: X-Frame-Options headers prevent embedding attacks
- **MIME Sniffing Prevention**: X-Content-Type-Options prevents content type confusion
- **Transport Security**: HSTS headers enforce HTTPS in production
- **Cross-Origin Protection**: CORP, COEP, and COOP headers control resource sharing

### Advanced Features
- **Vite Integration**: Seamless CSP nonce sharing with Laravel's Vite system
- **Environment Awareness**: Different security policies for development vs production
- **Performance Monitoring**: Built-in timing and alerting for slow operations
- **Graceful Degradation**: Fallback headers when services fail
- **Multi-Tenant Context**: Appropriate security levels based on user roles and routes

## Installation & Configuration

### Automatic Registration

The middleware is automatically registered in `bootstrap/app.php`:

```php
use App\Http\Middleware\SecurityHeaders;

->withMiddleware(function (Middleware $middleware) {
    $middleware->append(SecurityHeaders::class);
})
```

### Configuration

Configure security settings in `config/security.php`:

```php
return [
    'headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
    ],
    
    'performance' => [
        'enabled' => true,
        'thresholds' => [
            'warning_ms' => 10,
            'error_ms' => 50,
        ],
    ],
    
    'csp' => [
        'nonce_enabled' => true,
        'report_uri' => env('CSP_REPORT_URI'),
    ],
];
```

## Usage Examples

### Basic Usage

The middleware works automatically once registered. No additional code is required:

```php
// All routes automatically get security headers
Route::get('/', function () {
    return view('welcome');
}); // Gets CSP, X-Frame-Options, etc.
```

### Using CSP Nonces in Blade Templates

Use the CSP nonce component for inline scripts and styles:

```blade
{{-- Secure inline script --}}
<script <x-security.csp-nonce>>
    console.log('This script is CSP-compliant');
</script>

{{-- Secure inline style --}}
<style <x-security.csp-nonce>>
    .custom-style { color: red; }
</style>
```

### Vite Integration

The middleware automatically integrates with Vite:

```blade
{{-- Vite assets automatically get CSP nonces --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

### Route-Specific Behavior

Different routes get different security policies:

```php
// API routes get strict headers
Route::prefix('api')->group(function () {
    Route::get('/user', function () {
        // Gets: X-Frame-Options: DENY, strict CORS headers
    });
});

// Admin routes get enhanced security
Route::prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        // Gets: Strict CSP, enhanced frame protection
    });
});

// Tenant routes get balanced security
Route::prefix('tenant')->group(function () {
    Route::get('/dashboard', function () {
        // Gets: User-friendly CSP, standard protection
    });
});
```

## Security Headers Applied

### Production Environment

```http
Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-xyz123'; style-src 'self' 'nonce-xyz123'; frame-ancestors 'none'
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Cross-Origin-Embedder-Policy: require-corp
Cross-Origin-Opener-Policy: same-origin
Cross-Origin-Resource-Policy: same-origin
Permissions-Policy: geolocation=(), microphone=(), camera=()
```

### Development Environment

```http
Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-xyz123' cdn.tailwindcss.com localhost:*; style-src 'self' 'nonce-xyz123' fonts.googleapis.com 'unsafe-inline'; connect-src 'self' ws: wss: localhost:*
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
X-Debug-Mode: enabled
```

## Architecture

### Component Relationships

```
SecurityHeaders Middleware
├── ViteCSPIntegration Service
│   ├── NonceGeneratorService
│   └── SecurityNonce ValueObject
├── SecurityHeaderService
│   ├── SecurityHeaderFactory
│   ├── CspHeaderBuilder
│   └── SecurityHeaderSet ValueObject
└── Fallback Headers (on error)
```

### Data Flow

1. **Request Initialization**: Middleware receives incoming request
2. **Vite Integration**: CSP nonce generated and shared with Vite
3. **Request Processing**: Application processes request normally
4. **Header Application**: Context-appropriate headers applied to response
5. **Performance Monitoring**: Execution time logged if above threshold
6. **Error Handling**: Fallback headers applied if main process fails

## Performance Considerations

### Optimization Features

- **Request-Level Caching**: Nonces cached per request to avoid regeneration
- **Lazy Loading**: Services loaded only when needed
- **Efficient Processing**: Minimal overhead header application
- **Performance Monitoring**: Built-in timing and alerting

### Performance Metrics

- **Typical Processing Time**: < 10ms
- **Nonce Generation**: < 1ms
- **Memory Overhead**: < 1MB per request
- **Warning Threshold**: 10ms (configurable)
- **Error Threshold**: 50ms (configurable)

## Error Handling

### Graceful Degradation

When the main security service fails, minimal fallback headers are applied:

```php
// Fallback headers ensure basic protection
$fallbackHeaders = [
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'SAMEORIGIN',
    'X-XSS-Protection' => '1; mode=block',
];
```

### Error Logging

All errors are logged with context:

```php
Log::error('SecurityHeaders middleware error', [
    'error' => $e->getMessage(),
    'path' => $request->getPathInfo(),
    'method' => $request->getMethod(),
    'trace' => $e->getTraceAsString(),
]);
```

## Testing

### Property-Based Tests

The middleware includes comprehensive property-based tests:

```php
// Test nonce uniqueness across requests
public function nonce_uniqueness_property(): void
{
    // Generates 100 nonces and verifies uniqueness
}

// Test header consistency
public function header_consistency_property(): void
{
    // Verifies consistent headers across identical requests
}

// Test performance bounds
public function performance_bounds_property(): void
{
    // Ensures processing completes within 50ms
}
```

### Integration Tests

```php
public function test_applies_security_headers_with_vite_integration(): void
{
    $response = $this->get('/');
    
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Content-Security-Policy');
    
    // Verify CSP contains nonce
    $csp = $response->headers->get('Content-Security-Policy');
    $this->assertStringContainsString("'nonce-", $csp);
}
```

## Troubleshooting

### Common Issues

1. **CSP Violations**: Check browser console for CSP errors
2. **Performance Issues**: Enable performance monitoring
3. **Vite Integration**: Ensure Vite is properly configured
4. **Missing Headers**: Check service dependencies

### Debug Mode

Enable debug logging in `config/logging.php`:

```php
'security' => [
    'driver' => 'single',
    'path' => storage_path('logs/security.log'),
    'level' => 'debug',
],
```

### Performance Monitoring

Monitor performance metrics:

```bash
# Check security logs for performance warnings
tail -f storage/logs/security.log | grep "SecurityHeaders performance"

# Monitor response times
grep "duration_ms" storage/logs/security.log | awk '{print $NF}' | sort -n
```

## Security Best Practices

### CSP Configuration

1. **Use Nonces**: Always use nonces for inline scripts/styles
2. **Avoid 'unsafe-inline'**: Never allow unsafe inline content in production
3. **Regular Audits**: Review CSP policies regularly
4. **Report Violations**: Configure CSP reporting endpoint

### Header Management

1. **Environment-Specific**: Use different policies for dev/prod
2. **Regular Updates**: Keep security headers current
3. **Monitor Compliance**: Track header application success
4. **Test Thoroughly**: Verify headers across all routes

### Multi-Tenant Considerations

1. **Context Awareness**: Different security levels per tenant type
2. **Data Isolation**: Ensure headers don't leak tenant information
3. **Performance Impact**: Monitor overhead in multi-tenant scenarios
4. **Compliance**: Meet regulatory requirements per tenant

## Related Documentation

- [Security Headers Enhancement](../security/security-headers-enhancement.md)
- [CSP Header Builder](../services/security/csp-header-builder.md)
- [Vite CSP Integration](../services/security/vite-csp-integration.md)
- [Security Value Objects](../value-objects/security-nonce.md)
- [Multi-Tenant Security](../security/multi-tenant-security.md)

## Changelog

### v2.0.0 (Current)
- Enhanced Vite CSP integration
- Performance monitoring and caching
- Environment-aware security policies
- Improved error handling with fallbacks
- Multi-tenant context awareness

### v1.0.0
- Basic security header application
- CSP nonce generation
- Middleware registration