# TariffResource Security Implementation Guide

## Overview

This guide documents the security hardening measures implemented for TariffResource following the 2025-11-28 security audit.

## Implemented Security Measures

### 1. Rate Limiting

**File**: `app/Http/Middleware/RateLimitTariffOperations.php`

**Configuration**:
- Authenticated users: 60 requests/minute
- Unauthenticated (IP-based): 10 requests/minute
- Violations logged for security monitoring

**Registration**:
```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'tariff.rate-limit' => \App\Http\Middleware\RateLimitTariffOperations::class,
    ]);
})
```

**Usage**:
Apply to Filament routes in `app/Providers/Filament/AdminPanelProvider.php`:
```php
->middleware(['tariff.rate-limit'])
```

### 2. Security Headers

**File**: `app/Http/Middleware/SecurityHeaders.php`

**Headers Implemented**:
- Content-Security-Policy (CSP)
- X-Frame-Options: SAMEORIGIN
- X-Content-Type-Options: nosniff
- X-XSS-Protection: 1; mode=block
- Referrer-Policy: strict-origin-when-cross-origin
- Permissions-Policy
- Strict-Transport-Security (production only)

**CSP Configuration**:
- Allows Tailwind/Alpine CDN (cdn.jsdelivr.net, unpkg.com)
- Restricts inline scripts (required for Alpine.js)
- Prevents frame embedding except same-origin

**Registration**:
```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
})
```

### 3. Enhanced Input Sanitization

**File**: `app/Services/InputSanitizer.php`

**Features**:
- Comprehensive XSS prevention
- Numeric overflow protection
- Identifier sanitization
- Unicode normalization
- JavaScript protocol removal

**Usage in Forms**:
```php
->dehydrateStateUsing(fn (string $state): string => 
    app(\App\Services\InputSanitizer::class)->sanitizeText($state)
)
```

**Applied to**:
- Tariff name field
- Zone ID field
- All user-provided text inputs

### 4. CSRF Protection

**Status**: ✅ Implemented by Filament

Filament automatically includes CSRF tokens in all forms. No additional configuration needed.

**Verification**:
- All POST/PUT/DELETE requests include `_token` field
- Laravel's `VerifyCsrfToken` middleware active
- Session-based token validation

### 5. Authorization Enforcement

**File**: `app/Policies/TariffPolicy.php`

**Access Control**:
- SUPERADMIN: Full access
- ADMIN: Full access
- MANAGER: No access (403 Forbidden)
- TENANT: No access (403 Forbidden)

**Policy Methods**:
- `viewAny()`: List tariffs
- `view()`: View single tariff
- `create()`: Create tariff
- `update()`: Update tariff
- `delete()`: Delete tariff
- `forceDelete()`: Permanent deletion (SUPERADMIN only)

### 6. Audit Logging

**File**: `app/Observers/TariffObserver.php` (verify implementation)

**Required Events**:
- `created`: Log tariff creation with user_id, IP
- `updated`: Log changes with old/new values
- `deleted`: Log deletion with user_id, reason
- `restored`: Log restoration events

**Log Format**:
```php
logger()->info('Tariff created', [
    'tariff_id' => $tariff->id,
    'user_id' => auth()->id(),
    'ip' => request()->ip(),
    'name' => $tariff->name,
    'provider_id' => $tariff->provider_id,
]);
```

## Security Testing

### Test Suite

**File**: `tests/Feature/Security/TariffResourceSecurityEnhancedTest.php`

**Test Coverage**:
1. Rate limiting enforcement
2. XSS prevention
3. Security headers presence
4. CSRF protection
5. Numeric overflow prevention
6. SQL injection prevention
7. Authorization boundaries
8. Zone ID injection prevention

**Run Tests**:
```bash
php artisan test --filter=TariffResourceSecurityEnhancedTest
```

## Monitoring & Alerting

### Security Events to Monitor

1. **Rate Limit Violations**
   - Log: `Tariff operation rate limit exceeded`
   - Alert threshold: >10 violations/hour from single user
   - Action: Investigate potential abuse

2. **Authorization Failures**
   - Log: `Unauthorized tariff access attempt`
   - Alert threshold: >5 failures/hour from single user
   - Action: Review user permissions

3. **Validation Failures**
   - Log: `Tariff validation failed`
   - Alert threshold: >20 failures/hour
   - Action: Check for attack patterns

4. **Unusual Patterns**
   - Multiple tariff deletions
   - Rapid tariff modifications
   - Off-hours administrative actions

### Log Monitoring Commands

```bash
# View rate limit violations
tail -f storage/logs/laravel.log | grep "rate limit exceeded"

# View authorization failures
tail -f storage/logs/laravel.log | grep "Unauthorized"

# View validation failures
tail -f storage/logs/laravel.log | grep "validation failed"
```

## Deployment Checklist

### Production Environment

- [ ] APP_DEBUG=false
- [ ] APP_ENV=production
- [ ] HTTPS enforced (APP_URL=https://...)
- [ ] Session driver: database or redis
- [ ] Rate limiting middleware registered
- [ ] Security headers middleware registered
- [ ] HSTS enabled (automatic in production)
- [ ] CSP configured for production domains
- [ ] Audit logging active
- [ ] Log monitoring configured
- [ ] Security alerts configured

### Security Configuration

```env
# .env
APP_DEBUG=false
APP_ENV=production
APP_URL=https://yourdomain.com
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict
```

## Compliance Notes

### GDPR Compliance
- Tariff data contains no PII
- Audit logs may contain user IDs (legitimate interest)
- Log retention: 90 days recommended

### OWASP Top 10 Coverage
- ✅ A01:2021 – Broken Access Control (TariffPolicy)
- ✅ A02:2021 – Cryptographic Failures (HTTPS, secure sessions)
- ✅ A03:2021 – Injection (Input sanitization, parameterized queries)
- ✅ A04:2021 – Insecure Design (Security by design)
- ✅ A05:2021 – Security Misconfiguration (Security headers)
- ✅ A06:2021 – Vulnerable Components (Regular updates)
- ✅ A07:2021 – Authentication Failures (Laravel auth)
- ✅ A08:2021 – Software and Data Integrity (CSRF, input validation)
- ✅ A09:2021 – Security Logging Failures (Audit logging)
- ✅ A10:2021 – SSRF (Input validation, URL restrictions)

## Maintenance

### Regular Security Tasks

**Weekly**:
- Review security logs for anomalies
- Check rate limit violation patterns

**Monthly**:
- Review and update CSP directives
- Audit user permissions
- Review security test coverage

**Quarterly**:
- Full security audit
- Penetration testing
- Update security dependencies

### Security Updates

When updating security measures:
1. Update this documentation
2. Add/update security tests
3. Review impact on existing functionality
4. Test in staging environment
5. Deploy during maintenance window
6. Monitor logs post-deployment

## Support

For security concerns:
- Email: security@yourdomain.com
- Slack: #security-alerts
- On-call: Security team rotation

## References

- [OWASP Secure Headers Project](https://owasp.org/www-project-secure-headers/)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [Filament Security Documentation](https://filamentphp.com/docs/security)
- [CSP Reference](https://content-security-policy.com/)
