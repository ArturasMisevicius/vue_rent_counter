# Security Audit Implementation Guide

## Step-by-Step Implementation

### Step 1: Register Rate Limiting Middleware

**File:** `bootstrap/app.php`

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'rate.limit.filament' => \App\Http\Middleware\RateLimitFilamentAccess::class,
    ]);
})
```

**File:** `app/Providers/Filament/AdminPanelProvider.php`

```php
public function panel(Panel $panel): Panel
{
    return $panel
        ->middleware([
            'rate.limit.filament',
            // ... other middleware
        ]);
}
```

### Step 2: Run Security Tests

```bash
# Run all security tests
php artisan test --filter=Security

# Expected output:
# ✓ FilamentCsrfProtectionTest (3 tests)
# ✓ FilamentSecurityHeadersTest (6 tests)
# ✓ UserResourceAuthorizationTest (8 tests)
# ✓ PiiProtectionTest (5 tests)
```

### Step 3: Verify Security Configuration

```bash
# Check environment variables
grep "APP_DEBUG" .env
# Should be: APP_DEBUG=false (in production)

grep "APP_ENV" .env
# Should be: APP_ENV=production (in production)

grep "SESSION_SECURE_COOKIE" .env
# Should be: SESSION_SECURE_COOKIE=true (in production)
```

### Step 4: Test Rate Limiting

```bash
# Test rate limiting (should get 429 after 60 requests)
for i in {1..65}; do
    curl -I http://localhost/admin
done
```

### Step 5: Verify Security Headers

```bash
# Check security headers
curl -I https://your-domain.com/admin

# Should include:
# X-Frame-Options: SAMEORIGIN
# X-Content-Type-Options: nosniff
# X-XSS-Protection: 1; mode=block
# Content-Security-Policy: ...
# Strict-Transport-Security: ... (production only)
```

### Step 6: Monitor Logs

```bash
# Watch security log
tail -f storage/logs/security.log

# Watch audit log
tail -f storage/logs/audit.log

# Check for authorization failures
grep "access denied" storage/logs/security.log
```

### Step 7: Run Full Test Suite

```bash
# Run all tests
php artisan test

# Run authorization tests
php artisan test tests/Unit/AuthorizationPolicyTest.php

# Run performance tests
php artisan test tests/Performance/UserResourcePerformanceTest.php

# Check for vulnerabilities
composer audit

# Static analysis
./vendor/bin/phpstan analyse
```

## Verification Checklist

### Pre-Deployment

- [ ] Rate limiting middleware registered
- [ ] Security tests passing
- [ ] Authorization tests passing
- [ ] Performance tests passing
- [ ] Static analysis clean
- [ ] No composer vulnerabilities
- [ ] Environment variables correct
- [ ] Security headers configured
- [ ] Audit logging enabled

### Post-Deployment

- [ ] Security headers verified in production
- [ ] Rate limiting working
- [ ] Authorization checks working
- [ ] Audit logs being written
- [ ] No errors in logs
- [ ] Performance acceptable
- [ ] HTTPS enforced
- [ ] Cookies secure

## Rollback Plan

If issues arise after deployment:

```bash
# 1. Revert code changes
git revert <commit-hash>

# 2. Disable rate limiting temporarily
# Comment out in AdminPanelProvider.php:
# 'rate.limit.filament',

# 3. Clear caches
php artisan optimize:clear

# 4. Restart services
php artisan queue:restart

# 5. Monitor logs
tail -f storage/logs/laravel.log
```

## Troubleshooting

### Issue: Rate limiting too aggressive

**Solution:** Adjust limit in `RateLimitFilamentAccess.php`:

```php
// Change from 60 to higher value
if (RateLimiter::tooManyAttempts($key, 120)) {
```

### Issue: Security tests failing

**Solution:** Check middleware configuration:

```bash
# Verify middleware is registered
php artisan route:list --middleware=rate.limit.filament

# Check CSRF middleware
php artisan route:list --middleware=web
```

### Issue: Authorization failures not logged

**Solution:** Verify logging configuration:

```bash
# Check log channel exists
grep "security" config/logging.php

# Check log file permissions
ls -la storage/logs/security.log
```

## Performance Impact

Expected performance impact of security enhancements:

| Enhancement | Impact | Mitigation |
|-------------|--------|------------|
| Rate Limiting | +2-5ms per request | Redis caching |
| Authorization Logging | +1-3ms per failure | Async logging |
| Security Headers | +0.5ms per request | Minimal |
| CSRF Verification | +1ms per request | Built-in |

**Total Impact:** ~5-10ms per request (acceptable)

## Monitoring Setup

### 1. Set up log rotation

**File:** `/etc/logrotate.d/laravel`

```
/path/to/storage/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
    sharedscripts
}
```

### 2. Set up alerting

Configure alerts for:
- >10 authorization failures per IP in 5 minutes
- >100ms average authorization time
- Security header missing
- CSRF token mismatch spike

### 3. Dashboard metrics

Track:
- Authorization success/failure rate
- Rate limit hit rate
- Average authorization time
- Security log volume

## Support

For questions or issues:

1. Check full audit: [docs/security/USERRESOURCE_SECURITY_AUDIT_2024-12-02.md](USERRESOURCE_SECURITY_AUDIT_2024-12-02.md)
2. Check quick reference: [docs/security/SECURITY_QUICK_REFERENCE.md](SECURITY_QUICK_REFERENCE.md)
3. Review test output: `php artisan test --filter=Security`
4. Check logs: `storage/logs/security.log`

---

**Implementation Guide Version:** 1.0  
**Last Updated:** 2024-12-02
