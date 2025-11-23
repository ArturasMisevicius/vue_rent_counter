# Deployment Security Checklist

**Version:** 1.0  
**Last Updated:** November 24, 2025  
**Status:** Production Deployment Guide

## Pre-Deployment Security Audit

### 1. Environment Configuration ✅

#### Critical Settings
- [ ] `APP_DEBUG=false` (CRITICAL)
- [ ] `APP_ENV=production` (CRITICAL)
- [ ] `APP_URL` uses HTTPS (CRITICAL)
- [ ] `APP_KEY` is set and unique (CRITICAL)

#### Session Security
- [ ] `SESSION_DRIVER=database` or `redis`
- [ ] `SESSION_ENCRYPT=true`
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] `SESSION_HTTP_ONLY=true`
- [ ] `SESSION_SAME_SITE=lax` or `strict`
- [ ] `SESSION_LIFETIME` appropriate (120 minutes recommended)

#### Database Security
- [ ] `DB_CONNECTION` configured for production
- [ ] `DB_SSL_MODE=require` (if supported)
- [ ] Database credentials use strong passwords
- [ ] Database user has minimal required permissions

#### Cache & Queue Security
- [ ] `CACHE_DRIVER=redis` (recommended)
- [ ] `QUEUE_CONNECTION=redis` (recommended)
- [ ] `REDIS_PASSWORD` set with strong password
- [ ] Redis not exposed to public internet

---

### 2. Security Headers ✅

- [ ] `SECURITY_X_FRAME_OPTIONS=DENY`
- [ ] `SECURITY_X_CONTENT_TYPE_OPTIONS=nosniff`
- [ ] `SECURITY_X_XSS_PROTECTION="1; mode=block"`
- [ ] `SECURITY_REFERRER_POLICY=strict-origin-when-cross-origin`
- [ ] `SECURITY_HSTS` configured with `includeSubDomains`
- [ ] Content Security Policy (CSP) configured
- [ ] CSP tested with browser dev tools

---

### 3. Rate Limiting ✅

- [ ] `SECURITY_ADMIN_MAX_ATTEMPTS=10`
- [ ] `SECURITY_ADMIN_DECAY_SECONDS=300`
- [ ] Rate limiting middleware registered
- [ ] Redis configured for rate limiting
- [ ] Rate limiting tested with load tests

---

### 4. Audit Logging ✅

- [ ] `SECURITY_AUDIT_LOGGING=true`
- [ ] Log channel configured (`stack` recommended)
- [ ] Log rotation configured
- [ ] Log retention policy set (90 days recommended)
- [ ] Log aggregation service configured (optional)

---

### 5. Code Security ✅

#### Static Analysis
```bash
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
```
- [ ] Pint passes with no issues
- [ ] PHPStan passes with no errors
- [ ] No diagnostics issues

#### Test Coverage
```bash
php artisan test
php artisan test --filter=Security
php artisan test --filter=Middleware
```
- [ ] All tests passing
- [ ] Security tests passing
- [ ] Middleware tests passing (11/11)
- [ ] Rate limiting tests passing (10/10)

#### Dependency Audit
```bash
composer audit
npm audit
```
- [ ] No critical vulnerabilities
- [ ] No high vulnerabilities
- [ ] Medium vulnerabilities reviewed

---

### 6. Middleware Configuration ✅

#### Filament Admin Panel
- [ ] `EnsureUserIsAdminOrManager` registered
- [ ] `ThrottleAdminAccess` registered
- [ ] `SecurityHeaders` middleware active
- [ ] Middleware order correct

#### Verify Registration
```php
// app/Providers/Filament/AdminPanelProvider.php
->middleware([
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    AuthenticateSession::class,
    ShareErrorsFromSession::class,
    VerifyCsrfToken::class,
    SubstituteBindings::class,
    DisableBladeIconComponents::class,
    DispatchServingFilamentEvent::class,
    \App\Http\Middleware\ThrottleAdminAccess::class,
    \App\Http\Middleware\EnsureUserIsAdminOrManager::class,
])
```

---

### 7. HTTPS & TLS ✅

- [ ] SSL/TLS certificate installed
- [ ] Certificate valid and not expired
- [ ] Certificate chain complete
- [ ] TLS 1.2+ enforced
- [ ] Weak ciphers disabled
- [ ] HSTS header configured
- [ ] HTTP redirects to HTTPS

#### Verify HTTPS
```bash
curl -I https://your-domain.com
openssl s_client -connect your-domain.com:443 -tls1_2
```

---

### 8. Database Security ✅

- [ ] Database not exposed to public internet
- [ ] Firewall rules restrict database access
- [ ] Database user has minimal permissions
- [ ] Prepared statements used (Laravel default)
- [ ] No raw SQL queries without parameter binding
- [ ] Database backups encrypted
- [ ] Backup retention policy configured

---

### 9. File Permissions ✅

```bash
# Set correct permissions
chmod -R 755 storage bootstrap/cache
chmod -R 644 .env
chown -R www-data:www-data storage bootstrap/cache
```

- [ ] `storage/` writable by web server
- [ ] `bootstrap/cache/` writable by web server
- [ ] `.env` not readable by public
- [ ] Sensitive files not in public directory

---

### 10. Monitoring & Alerting ✅

#### Log Monitoring
- [ ] Log aggregation configured (ELK/Splunk/CloudWatch)
- [ ] Real-time alerting configured
- [ ] Authorization failure alerts active
- [ ] Rate limit alerts active

#### Monitoring Queries
```bash
# Monitor authorization failures
tail -f storage/logs/laravel.log | grep "Admin panel access denied"

# Count failures by role
grep "Admin panel access denied" storage/logs/laravel.log | jq '.context.user_role' | sort | uniq -c

# Find suspicious IPs
grep "Admin panel access denied" storage/logs/laravel.log | jq '.context.ip' | sort | uniq -c | sort -rn
```

#### External Monitoring
- [ ] Sentry/Bugsnag configured (optional)
- [ ] Uptime monitoring active
- [ ] Performance monitoring active
- [ ] Error rate alerts configured

---

## Deployment Steps

### 1. Pre-Deployment Backup
```bash
# Backup database
php artisan backup:run

# Backup .env file
cp .env .env.backup-$(date +%Y%m%d)

# Backup current code
git tag deployment-$(date +%Y%m%d-%H%M%S)
```

### 2. Deploy Code
```bash
# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 3. Run Migrations
```bash
# Run migrations
php artisan migrate --force

# Verify migrations
php artisan migrate:status
```

### 4. Optimize for Production
```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

### 5. Verify Deployment
```bash
# Run health checks
php artisan test --filter=Security
php artisan test --filter=Middleware

# Verify middleware registration
php artisan route:list | grep admin

# Check logs
tail -100 storage/logs/laravel.log
```

---

## Post-Deployment Verification

### 1. Functional Testing

#### Admin Access
- [ ] Admin user can access `/admin`
- [ ] Manager user can access `/admin`
- [ ] Tenant user receives 403 error
- [ ] Unauthenticated user receives 403 error

#### Rate Limiting
- [ ] 10 failed attempts trigger rate limit
- [ ] 429 response includes `Retry-After` header
- [ ] Successful access clears rate limit

#### Security Headers
```bash
curl -I https://your-domain.com/admin
```
- [ ] `X-Frame-Options: DENY`
- [ ] `X-Content-Type-Options: nosniff`
- [ ] `Strict-Transport-Security` present
- [ ] `Content-Security-Policy` present

### 2. Security Testing

#### Authorization
```bash
# Test admin access
curl -H "Cookie: session=..." https://your-domain.com/admin

# Test tenant denial
curl -H "Cookie: session=..." https://your-domain.com/admin
```

#### Rate Limiting
```bash
# Test rate limiting
for i in {1..15}; do
  curl -H "Cookie: session=..." https://your-domain.com/admin
done
```

#### HTTPS
```bash
# Verify HTTPS redirect
curl -I http://your-domain.com

# Verify TLS version
openssl s_client -connect your-domain.com:443 -tls1_2
```

### 3. Monitoring Verification

- [ ] Authorization failures logged
- [ ] Log format correct (JSON)
- [ ] Alerts triggered for suspicious activity
- [ ] Monitoring dashboard accessible

---

## Rollback Plan

### If Issues Detected

1. **Immediate Rollback**
```bash
# Revert to previous version
git checkout <previous-tag>

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Restore database (if needed)
php artisan backup:restore
```

2. **Verify Rollback**
```bash
# Run tests
php artisan test

# Check application
curl -I https://your-domain.com
```

3. **Investigate Issues**
```bash
# Check logs
tail -100 storage/logs/laravel.log

# Check error logs
tail -100 storage/logs/laravel-error.log
```

---

## Security Incident Response

### Level 1: Suspicious Activity
**Trigger:** >10 failures from single IP in 5 minutes

**Actions:**
1. Review logs for IP address
2. Check for patterns (user enumeration, brute force)
3. Consider temporary IP block
4. Document findings

### Level 2: Potential Breach
**Trigger:** Authorization bypass detected

**Actions:**
1. Immediate investigation
2. Review all access logs
3. Check for unauthorized data access
4. Notify security team
5. Consider temporary service restriction

### Level 3: Confirmed Breach
**Trigger:** Unauthorized admin access confirmed

**Actions:**
1. Activate incident response protocol
2. Isolate affected systems
3. Preserve forensic evidence
4. Notify stakeholders (CISO, legal)
5. Begin remediation
6. Prepare incident report

---

## Compliance Verification

### GDPR Compliance
- [ ] PII handling documented
- [ ] Data retention policy configured
- [ ] User consent mechanisms in place
- [ ] Data export functionality available
- [ ] Data deletion functionality available

### Security Best Practices
- [ ] Least privilege principle enforced
- [ ] Defense-in-depth implemented
- [ ] Audit trail complete
- [ ] Encryption at rest configured
- [ ] Encryption in transit enforced

### Regulatory Requirements
- [ ] Password complexity enforced
- [ ] Session timeout configured
- [ ] Failed login attempts logged
- [ ] Access control documented
- [ ] Security training completed

---

## Maintenance Schedule

### Daily
- [ ] Review authorization failure logs
- [ ] Check error rate
- [ ] Verify backup completion

### Weekly
- [ ] Review security alerts
- [ ] Analyze access patterns
- [ ] Check for suspicious IPs
- [ ] Update dependencies

### Monthly
- [ ] Security audit
- [ ] Penetration testing (if applicable)
- [ ] Review and update security policies
- [ ] Team security training

### Quarterly
- [ ] Comprehensive security review
- [ ] Update security documentation
- [ ] Review incident response plan
- [ ] Conduct security drills

---

## Contact Information

### Security Team
- **Email:** security@your-domain.com
- **Phone:** +1-XXX-XXX-XXXX
- **On-Call:** PagerDuty/Opsgenie

### Escalation Path
1. Development Team Lead
2. Security Team Lead
3. CISO
4. Legal Team (if data breach)

---

## Sign-Off

- [ ] Security review completed
- [ ] All checklist items verified
- [ ] Deployment approved
- [ ] Rollback plan tested
- [ ] Monitoring configured
- [ ] Team notified

**Deployed By:** _________________  
**Date:** _________________  
**Approved By:** _________________  
**Date:** _________________  

---

**Status:** ✅ READY FOR PRODUCTION DEPLOYMENT
