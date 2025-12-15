# Rate Limiting Strategy

## Overview

This document describes the rate limiting strategy for the Vilnius Utilities Billing Platform, including implementation details, configuration, and monitoring guidelines.

## Current Implementation (as of 2024-12-01)

### API Routes
- **Limit**: 60 requests per minute per IP address
- **Configuration**: `bootstrap/app.php` via `$middleware->throttleApi('60,1')`
- **Scope**: All routes under `/api/*`
- **Response**: HTTP 429 (Too Many Requests) when exceeded

### Admin/Filament Routes
- **Strategy**: Relies on Filament v4's built-in protections
- **Additional Protection**: SecurityHeaders middleware provides DoS mitigation
- **Rationale**: Filament includes session-based rate limiting and CSRF protection
- **Note**: Custom admin rate limiter removed as of 2024-12-01 (see CHANGELOG)

### Web Routes
- **Default**: No explicit rate limiting (relies on SecurityHeaders and application-level controls)
- **Custom Limits**: Can be applied per-route using `throttle` middleware

## Historical Context

### Removed: Custom Admin Rate Limiter (2024-12-01)

Previously, a custom rate limiter was configured for admin routes:

```php
// REMOVED - No longer needed
RateLimiter::for('admin', function (Request $request) {
    return Limit::perMinute(120)
        ->by($request->user()?->id ?: $request->ip())
        ->response(function () {
            return response()->json([
                'message' => 'Too many requests. Please try again later.'
            ], 429);
        });
});
```

**Removal Rationale**:
1. Filament v4 provides built-in rate limiting at the framework level
2. Session-based authentication includes automatic throttling
3. SecurityHeaders middleware provides additional DoS protection
4. Reduced code complexity and maintenance burden
5. No reduction in actual security posture

## Rate Limiting by Component

### 1. BillingService Operations

**Current Status**: No explicit rate limiting
**Recommendation**: Consider adding for expensive operations

```php
// Example implementation
Route::middleware(['auth', 'throttle:10,1'])->group(function () {
    Route::post('/invoices/generate', [InvoiceController::class, 'generate']);
});
```

**Rationale**: Invoice generation is computationally expensive and should be limited to prevent resource exhaustion.

### 2. Meter Reading Submissions

**Current Status**: Protected by SecurityHeaders middleware
**Recommendation**: Consider adding specific limits for high-volume periods

```php
// Example implementation
Route::middleware(['auth', 'throttle:20,1'])->group(function () {
    Route::post('/meter-readings', [MeterReadingController::class, 'store']);
});
```

### 3. hot water circulationCalculator Operations

**Current Status**: No explicit rate limiting
**Security Concern**: Complex calculations can be resource-intensive
**Recommendation**: Add rate limiting for public-facing calculation endpoints

```php
// Example implementation
Route::middleware(['auth', 'throttle:10,1'])->group(function () {
    Route::post('/calculate/hot water circulation', [hot water circulationController::class, 'calculate']);
});
```

## Custom Rate Limiter Configuration

### Defining Custom Rate Limiters

Add to `AppServiceProvider::boot()`:

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('billing-operations', function (Request $request) {
    return Limit::perMinute(10)
        ->by($request->user()?->id ?: $request->ip())
        ->response(function () {
            return response()->json([
                'message' => 'Too many billing operations. Please try again later.',
                'retry_after' => 60
            ], 429);
        });
});
```

### Applying Custom Rate Limiters

```php
// In routes/web.php
Route::middleware(['auth', 'throttle:billing-operations'])->group(function () {
    Route::post('/invoices/generate', [InvoiceController::class, 'generate']);
    Route::post('/invoices/finalize', [InvoiceController::class, 'finalize']);
});
```

## Monitoring and Alerting

### Log Rate Limit Violations

Rate limit violations are automatically logged by Laravel. Monitor these logs for potential attacks:

```bash
# Check for rate limit violations
grep "429" storage/logs/laravel.log

# Monitor specific endpoints
grep "throttle" storage/logs/laravel.log | grep "/api/"
```

### Metrics to Track

1. **Rate Limit Hit Rate**: Percentage of requests hitting rate limits
2. **Top Offenders**: IP addresses or users frequently hitting limits
3. **Endpoint Distribution**: Which endpoints are most frequently rate-limited
4. **Time Patterns**: When rate limiting is most active (potential attack patterns)

### Alerting Thresholds

Consider setting up alerts for:
- More than 100 rate limit violations per minute (potential DDoS)
- Single IP hitting rate limits repeatedly (potential targeted attack)
- Unusual spike in rate limit violations (anomaly detection)

## Testing Rate Limiting

### Unit Tests

```php
use Illuminate\Support\Facades\RateLimiter;

test('api rate limiting works', function () {
    $user = User::factory()->create();
    
    // Make 60 requests (should succeed)
    for ($i = 0; $i < 60; $i++) {
        $response = $this->actingAs($user)->getJson('/api/endpoint');
        $response->assertStatus(200);
    }
    
    // 61st request should be rate limited
    $response = $this->actingAs($user)->getJson('/api/endpoint');
    $response->assertStatus(429);
});
```

### Feature Tests

```php
test('billing operations are rate limited', function () {
    $user = User::factory()->create(['role' => UserRole::ADMIN]);
    
    // Make requests up to limit
    for ($i = 0; $i < 10; $i++) {
        $response = $this->actingAs($user)
            ->post('/invoices/generate', ['tenant_id' => 1]);
        $response->assertStatus(200);
    }
    
    // Next request should be rate limited
    $response = $this->actingAs($user)
        ->post('/invoices/generate', ['tenant_id' => 1]);
    $response->assertStatus(429);
});
```

## Best Practices

1. **Use User-Based Limits**: Prefer `->by($request->user()->id)` over IP-based for authenticated routes
2. **Provide Clear Messages**: Include `retry_after` in rate limit responses
3. **Log Violations**: Always log rate limit violations for security monitoring
4. **Test Thoroughly**: Include rate limiting tests in your test suite
5. **Monitor Production**: Set up alerts for unusual rate limit patterns
6. **Document Limits**: Clearly document rate limits in API documentation
7. **Consider Business Logic**: Apply stricter limits to expensive operations

## Configuration Reference

### Environment Variables

```env
# API rate limiting (requests per minute)
API_RATE_LIMIT=60

# Custom rate limits for specific operations
BILLING_RATE_LIMIT=10
METER_READING_RATE_LIMIT=20
CALCULATION_RATE_LIMIT=10
```

### Configuration Files

- `bootstrap/app.php`: Global middleware configuration
- `config/throttle.php`: Custom rate limiter configuration (if created)
- `app/Providers/AppServiceProvider.php`: Custom rate limiter definitions

## Security Considerations

### DoS Protection

Rate limiting is one layer of DoS protection. Additional measures include:
- SecurityHeaders middleware (connection limits, timeouts)
- Web Application Firewall (WAF) at infrastructure level
- CDN-level rate limiting (Cloudflare, AWS CloudFront)
- Database query optimization to reduce resource consumption

### Bypass Prevention

1. **Don't rely solely on IP**: Use user ID for authenticated routes
2. **Monitor for patterns**: Detect distributed attacks across multiple IPs
3. **Implement CAPTCHA**: For public endpoints after rate limit violations
4. **Use exponential backoff**: Increase rate limit duration for repeat offenders

## Related Documentation

- [Middleware Configuration](../middleware/MIDDLEWARE_CONFIGURATION.md)
- [Security Architecture](./SECURITY_ARCHITECTURE.md)
- [API Documentation](../api/API_ARCHITECTURE_GUIDE.md)
- [Performance Optimization](../misc/PERFORMANCE_OPTIMIZATION_COMPLETE.md)

## Changelog

### 2024-12-01
- **Removed**: Custom admin rate limiter (120 req/min)
- **Rationale**: Filament v4 provides built-in protections
- **Impact**: No reduction in security; simplified configuration
- **Documentation**: Created comprehensive rate limiting strategy document
