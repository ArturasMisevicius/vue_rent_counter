# Security Headers Quick Reference

## At a Glance

The SecurityHeaders middleware automatically applies security headers to all responses. Here's what you need to know:

## Quick Setup

```php
// Already configured in bootstrap/app.php
use App\Http\Middleware\SecurityHeaders;

->withMiddleware(function (Middleware $middleware) {
    $middleware->append(SecurityHeaders::class);
})
```

## Common Usage

### Blade Templates with CSP Nonces

```blade
{{-- Secure inline script --}}
<script <x-security.csp-nonce>>
    console.log('CSP compliant');
</script>

{{-- Secure inline style --}}
<style <x-security.csp-nonce>>
    .custom { color: red; }
</style>

{{-- Vite assets (automatic) --}}
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

## Headers by Route Type

### API Routes (`/api/*`)
```http
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Cross-Origin-Resource-Policy: same-origin
```

### Admin Routes (`/admin/*`)
```http
Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-xyz'; frame-ancestors 'none'
X-Frame-Options: DENY
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
```

### Tenant Routes (`/tenant/*`)
```http
Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-xyz' cdn.tailwindcss.com
X-Frame-Options: SAMEORIGIN
```

## Environment Differences

### Production
- Strict CSP policies
- HSTS with preload
- Cross-origin policies
- No unsafe-inline allowed

### Development
- Relaxed CSP for localhost
- WebSocket support for HMR
- CDN allowances (Tailwind, etc.)
- Debug headers enabled

## Configuration

### Basic Config (`config/security.php`)

```php
return [
    'performance' => [
        'enabled' => true,
        'thresholds' => [
            'warning_ms' => 10,
            'error_ms' => 50,
        ],
    ],
];
```

### Environment Variables

```env
CSP_ENABLED=true
CSP_REPORT_URI=https://report-uri.com/endpoint
CSP_NONCE_ENABLED=true
```

## Troubleshooting

### CSP Violations
1. Check browser console for CSP errors
2. Add nonce to inline scripts/styles
3. Whitelist external domains if needed

### Performance Issues
1. Enable performance monitoring
2. Check logs for slow operations
3. Optimize nonce generation

### Missing Headers
1. Verify middleware registration
2. Check service dependencies
3. Review error logs

## Performance Metrics

- **Typical**: < 5ms processing
- **Warning**: > 10ms processing
- **Error**: > 50ms processing
- **Memory**: < 1MB per request

## Testing

### Quick Test
```php
public function test_security_headers_applied(): void
{
    $response = $this->get('/');
    
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Content-Security-Policy');
}
```

### CSP Nonce Test
```php
public function test_csp_nonce_present(): void
{
    $response = $this->get('/');
    
    $csp = $response->headers->get('Content-Security-Policy');
    $this->assertStringContainsString("'nonce-", $csp);
}
```

## Common Patterns

### Custom Headers
```php
// Don't modify SecurityHeaders directly
// Use custom middleware after SecurityHeaders
class CustomHeadersMiddleware
{
    public function handle($request, $next)
    {
        $response = $next($request);
        $response->headers->set('X-Custom', 'value');
        return $response;
    }
}
```

### Conditional CSP
```php
// Headers automatically adjust based on:
// - Route context (/api, /admin, /tenant)
// - Environment (production, development)
// - User role (admin, manager, tenant)
```

## Security Best Practices

1. **Always use nonces** for inline scripts/styles
2. **Never allow 'unsafe-inline'** in production
3. **Configure CSP reporting** to monitor violations
4. **Test across environments** before deployment
5. **Monitor performance** impact regularly

## Related Documentation

- [Full Middleware Documentation](../middleware/security-headers-middleware.md)
- [API Reference](../api/security-headers-api.md)
- [Security Enhancement Guide](security-headers-enhancement.md)
- [CSP Header Builder](../services/security/csp-header-builder.md)

## Need Help?

1. Check browser console for CSP violations
2. Review `storage/logs/security.log` for errors
3. Enable debug mode for detailed logging
4. Consult the full documentation for advanced usage