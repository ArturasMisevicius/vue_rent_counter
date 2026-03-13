# Security Testing Guide

## Overview

This guide provides comprehensive instructions for testing security features in the Vilnius Utilities Billing Platform, with specific focus on the invoice finalization feature.

---

## Table of Contents

1. [Pre-Testing Setup](#pre-testing-setup)
2. [Automated Testing](#automated-testing)
3. [Manual Testing](#manual-testing)
4. [Security Header Verification](#security-header-verification)
5. [Penetration Testing](#penetration-testing)
6. [Compliance Testing](#compliance-testing)
7. [Monitoring & Alerting](#monitoring--alerting)

---

## Pre-Testing Setup

### 1. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure database
php artisan migrate:fresh --seed

# Set up test data
php artisan test:setup --fresh
```

### 2. Enable Security Features

```env
# .env
APP_DEBUG=false
APP_ENV=testing
SESSION_SECURE_COOKIE=true
SECURITY_CSP_ENABLED=true
SECURITY_AUDIT_LOGGING_ENABLED=true
SECURITY_DEMO_MODE_ENABLED=false
```

### 3. Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

---

## Automated Testing

### Run Full Security Test Suite

```bash
# Run all security tests
php artisan test --testsuite=Feature --filter=Security

# Run invoice finalization security tests
php artisan test tests/Feature/Filament/InvoiceFinalizationSecurityTest.php

# Run with coverage
php artisan test --coverage --min=80
```

### Individual Test Categories

#### 1. Rate Limiting Tests

```bash
php artisan test --filter=rate_limiting
```

**Expected Results:**
- ✅ 10 attempts allowed per minute per user
- ✅ 11th attempt blocked with "Too many attempts" message
- ✅ Rate limit key is user-specific
- ✅ Different users have independent rate limits

#### 2. Audit Logging Tests

```bash
php artisan test --filter=audit_log
```

**Expected Results:**
- ✅ All finalization attempts logged
- ✅ Successful finalizations logged with timestamp
- ✅ Validation failures logged with error details
- ✅ Unexpected errors logged with stack trace

#### 3. Authorization Tests

```bash
php artisan test --filter=authorization
```

**Expected Results:**
- ✅ Tenant cannot finalize invoices
- ✅ Admin can only finalize own tenant's invoices
- ✅ Manager can only finalize own tenant's invoices
- ✅ Superadmin can finalize any invoice
- ✅ Double authorization check prevents bypass

#### 4. Tenant Isolation Tests

```bash
php artisan test --filter=tenant_isolation
```

**Expected Results:**
- ✅ Cross-tenant finalization blocked
- ✅ Tenant ID verified in policy
- ✅ Audit logs include tenant context
- ✅ TenantScope applied to queries

#### 5. Input Validation Tests

```bash
php artisan test --filter=validation
```

**Expected Results:**
- ✅ Invoice must have items
- ✅ Total amount must be > 0
- ✅ Billing period must be valid
- ✅ Items must have valid data
- ✅ Invoice must be in DRAFT status

#### 6. Information Leakage Tests

```bash
php artisan test --filter=information_leakage
```

**Expected Results:**
- ✅ No database column names in errors
- ✅ No file paths in responses
- ✅ No stack traces in user messages
- ✅ Generic error messages for unexpected errors
- ✅ Detailed errors only in server logs

---

## Manual Testing

### 1. Rate Limiting Verification

**Test Steps:**
1. Log in as Admin
2. Navigate to a draft invoice
3. Click "Finalize Invoice" 11 times rapidly
4. Verify rate limit message appears on 11th attempt

**Expected Behavior:**
- First 10 attempts process normally
- 11th attempt shows: "Too many attempts. Please wait X seconds before trying again."
- Rate limit resets after 60 seconds

**Verification:**
```bash
# Check rate limiter state
php artisan tinker
>>> RateLimiter::tooManyAttempts('invoice-finalize:1', 10)
=> true
```

### 2. Audit Log Verification

**Test Steps:**
1. Finalize an invoice
2. Check application logs

**Expected Log Entries:**
```
[INFO] Invoice finalization attempt
{
  "user_id": 1,
  "user_role": "admin",
  "invoice_id": 123,
  "invoice_status": "draft",
  "tenant_id": 1,
  "total_amount": "100.00"
}

[INFO] Invoice finalized successfully
{
  "user_id": 1,
  "invoice_id": 123,
  "finalized_at": "2025-11-23 10:30:00"
}
```

**Verification:**
```bash
# Tail logs in real-time
php artisan pail --filter="finalization"

# Search logs
grep "Invoice finalization" storage/logs/laravel.log
```

### 3. Authorization Matrix Testing

| User Role | Invoice Tenant | Expected Result |
|-----------|----------------|-----------------|
| Superadmin | Any | ✅ Can finalize |
| Admin | Same tenant | ✅ Can finalize |
| Admin | Different tenant | ❌ 403 Forbidden |
| Manager | Same tenant | ✅ Can finalize |
| Manager | Different tenant | ❌ 403 Forbidden |
| Tenant | Any | ❌ Action hidden |

**Test Each Scenario:**
```bash
# Test as different users
php artisan tinker
>>> $admin = User::find(1);
>>> $invoice = Invoice::find(1);
>>> Gate::forUser($admin)->allows('finalize', $invoice)
=> true/false
```

### 4. Concurrent Finalization Testing

**Test Steps:**
1. Open invoice in two browser tabs
2. Click "Finalize" in both tabs simultaneously
3. Verify only one succeeds

**Expected Behavior:**
- First request: Success
- Second request: "Invoice is already finalized" error

### 5. Error Message Sanitization

**Test Invalid Scenarios:**

| Scenario | Expected Error Message |
|----------|------------------------|
| No items | "Cannot finalize invoice" (generic) |
| Zero amount | "Cannot finalize invoice" (generic) |
| Invalid period | "Cannot finalize invoice" (generic) |
| Already finalized | "Invoice is already finalized" |
| Unexpected error | "An unexpected error occurred. Please try again or contact support." |

**Verify NO Leakage:**
- ❌ No "total_amount" in message
- ❌ No "/var/www/html" paths
- ❌ No "Stack trace:" text
- ❌ No SQL queries

---

## Security Header Verification

### 1. Automated Header Check

```bash
# Run header tests
php artisan test tests/Feature/SecurityHeadersTest.php
```

### 2. Manual Header Inspection

```bash
# Check headers with curl
curl -I https://yourdomain.com/admin

# Expected headers:
# X-Frame-Options: SAMEORIGIN
# X-Content-Type-Options: nosniff
# X-XSS-Protection: 1; mode=block
# Referrer-Policy: strict-origin-when-cross-origin
# Content-Security-Policy: default-src 'self'; ...
# Strict-Transport-Security: max-age=31536000; includeSubDomains; preload
```

### 3. Browser DevTools Check

1. Open browser DevTools (F12)
2. Navigate to Network tab
3. Reload page
4. Click on main document request
5. Check Response Headers section

**Required Headers:**
- ✅ X-Frame-Options
- ✅ X-Content-Type-Options
- ✅ Content-Security-Policy
- ✅ Strict-Transport-Security (production only)

### 4. CSP Violation Testing

**Test Steps:**
1. Enable CSP report-only mode
2. Inject test script: `<script>alert('XSS')</script>`
3. Check browser console for CSP violations
4. Verify violations are reported (if report-uri configured)

---

## Penetration Testing

### 1. OWASP Top 10 Checklist

#### A01: Broken Access Control
- [ ] Test cross-tenant access
- [ ] Test privilege escalation
- [ ] Test direct object references
- [ ] Test forced browsing

**Test Commands:**
```bash
# Attempt to access another tenant's invoice
curl -X POST https://yourdomain.com/admin/invoices/999/finalize \
  -H "Cookie: session=..." \
  -H "X-CSRF-TOKEN: ..."

# Expected: 403 Forbidden
```

#### A02: Cryptographic Failures
- [ ] Verify HTTPS enforcement
- [ ] Check session cookie security
- [ ] Verify password hashing
- [ ] Check sensitive data encryption

**Test Commands:**
```bash
# Verify HTTPS redirect
curl -I http://yourdomain.com
# Expected: 301 Moved Permanently to https://

# Check cookie flags
curl -I https://yourdomain.com/login
# Expected: Set-Cookie: ...; Secure; HttpOnly; SameSite=Lax
```

#### A03: Injection
- [ ] Test SQL injection
- [ ] Test XSS injection
- [ ] Test command injection
- [ ] Test LDAP injection

**Test Payloads:**
```sql
-- SQL Injection
' OR '1'='1
'; DROP TABLE invoices; --

-- XSS Injection
<script>alert('XSS')</script>
<img src=x onerror=alert('XSS')>
```

#### A04: Insecure Design
- [ ] Test rate limiting bypass
- [ ] Test business logic flaws
- [ ] Test workflow violations
- [ ] Test state manipulation

#### A05: Security Misconfiguration
- [ ] Verify APP_DEBUG=false
- [ ] Check error messages
- [ ] Verify default credentials disabled
- [ ] Check unnecessary features disabled

#### A06: Vulnerable Components
- [ ] Run `composer audit`
- [ ] Check for outdated packages
- [ ] Verify security patches applied

```bash
# Check for vulnerabilities
composer audit

# Update dependencies
composer update --with-all-dependencies
```

#### A07: Authentication Failures
- [ ] Test brute force protection
- [ ] Test session fixation
- [ ] Test credential stuffing
- [ ] Test weak password policy

#### A08: Software and Data Integrity
- [ ] Verify CSRF protection
- [ ] Check code signing
- [ ] Verify update mechanisms
- [ ] Check CI/CD pipeline security

#### A09: Logging Failures
- [ ] Verify audit logging
- [ ] Check log retention
- [ ] Test log tampering protection
- [ ] Verify sensitive data redaction

#### A10: Server-Side Request Forgery
- [ ] Test SSRF vulnerabilities
- [ ] Check URL validation
- [ ] Verify network segmentation

### 2. Automated Vulnerability Scanning

```bash
# Install OWASP ZAP
docker pull owasp/zap2docker-stable

# Run baseline scan
docker run -t owasp/zap2docker-stable zap-baseline.py \
  -t https://yourdomain.com

# Run full scan
docker run -t owasp/zap2docker-stable zap-full-scan.py \
  -t https://yourdomain.com
```

### 3. Manual Penetration Testing

**Tools:**
- Burp Suite Professional
- OWASP ZAP
- Nikto
- SQLMap
- Nmap

**Test Scenarios:**
1. Session hijacking
2. CSRF bypass
3. Authorization bypass
4. Rate limit bypass
5. Input validation bypass

---

## Compliance Testing

### 1. GDPR Compliance

**Data Protection Checklist:**
- [ ] PII encrypted at rest
- [ ] PII encrypted in transit
- [ ] Audit logs don't contain PII
- [ ] Right to erasure implemented
- [ ] Data breach notification process
- [ ] Privacy policy accessible

**Test Commands:**
```bash
# Verify no PII in logs
grep -r "email\|password\|ssn" storage/logs/
# Expected: No matches (or only hashed values)

# Check encryption
php artisan tinker
>>> encrypt('sensitive data')
>>> decrypt('encrypted value')
```

### 2. SOC 2 Compliance

**Security Controls:**
- [ ] Access controls implemented
- [ ] Audit logging enabled
- [ ] Change management process
- [ ] Incident response plan
- [ ] Backup and recovery tested
- [ ] Vendor management

**Audit Evidence:**
```bash
# Generate audit report
php artisan audit:report --from=2025-01-01 --to=2025-12-31

# Export logs
php artisan logs:export --format=json --output=audit-logs.json
```

### 3. PCI DSS (if handling payments)

**Requirements:**
- [ ] Cardholder data encrypted
- [ ] Access logs maintained
- [ ] Security testing performed
- [ ] Vulnerability management
- [ ] Network segmentation

---

## Monitoring & Alerting

### 1. Real-Time Monitoring

```bash
# Monitor logs in real-time
php artisan pail

# Filter security events
php artisan pail --filter="finalization|rate limit|unauthorized"

# Monitor specific user
php artisan pail --filter="user_id:1"
```

### 2. Metrics to Track

**Application Metrics:**
- Finalization attempts per minute
- Finalization success rate
- Finalization failure rate
- Rate limit violations per user
- Authorization failures
- Unexpected errors

**System Metrics:**
- CPU usage during finalization
- Memory usage
- Database query time
- Response time
- Error rate

### 3. Alert Configuration

**Critical Alerts (Immediate):**
- Cross-tenant access attempt
- Unexpected error rate > 1%
- Rate limit violations > 10/min
- Authorization bypass attempt

**Warning Alerts (5 minutes):**
- Finalization failure rate > 10%
- Response time > 2 seconds
- Database query time > 1 second

**Info Alerts (1 hour):**
- Finalization volume spike
- New user registration
- Configuration change

### 4. Log Aggregation

**Recommended Tools:**
- Sentry (error tracking)
- Datadog (APM)
- ELK Stack (log aggregation)
- Grafana (visualization)

**Example Sentry Configuration:**
```php
// config/sentry.php
'dsn' => env('SENTRY_LARAVEL_DSN'),
'traces_sample_rate' => 1.0,
'profiles_sample_rate' => 1.0,
```

---

## Test Execution Checklist

### Pre-Deployment Testing

- [ ] Run full test suite: `php artisan test`
- [ ] Run security tests: `php artisan test --filter=Security`
- [ ] Verify security headers
- [ ] Check audit logging
- [ ] Test rate limiting
- [ ] Verify tenant isolation
- [ ] Test authorization matrix
- [ ] Check error messages
- [ ] Run vulnerability scan
- [ ] Review audit logs

### Post-Deployment Verification

- [ ] Verify HTTPS enforcement
- [ ] Check security headers in production
- [ ] Test rate limiting in production
- [ ] Verify audit logging active
- [ ] Check monitoring dashboards
- [ ] Test alerting rules
- [ ] Verify backup encryption
- [ ] Review access logs

### Ongoing Security Testing

**Weekly:**
- [ ] Review audit logs
- [ ] Check for failed login attempts
- [ ] Monitor rate limit violations
- [ ] Review error logs

**Monthly:**
- [ ] Run vulnerability scan
- [ ] Update dependencies
- [ ] Review access controls
- [ ] Test backup restoration

**Quarterly:**
- [ ] Penetration testing
- [ ] Security training
- [ ] Policy review
- [ ] Compliance audit

---

## Troubleshooting

### Common Issues

#### 1. Rate Limiting Not Working

**Symptoms:**
- Users can make unlimited attempts
- No rate limit messages

**Diagnosis:**
```bash
# Check rate limiter configuration
php artisan config:show throttle

# Check Redis/cache connection
php artisan cache:clear
php artisan tinker
>>> Cache::get('test')
```

**Solution:**
- Verify `CACHE_STORE` is configured
- Check Redis/database connection
- Clear rate limiter: `RateLimiter::clear('invoice-finalize:*')`

#### 2. Audit Logs Not Appearing

**Symptoms:**
- No logs in `storage/logs/laravel.log`
- Missing audit events

**Diagnosis:**
```bash
# Check log configuration
php artisan config:show logging

# Check log permissions
ls -la storage/logs/

# Test logging
php artisan tinker
>>> Log::info('Test log entry')
```

**Solution:**
- Verify `LOG_CHANNEL` is configured
- Check file permissions: `chmod -R 775 storage/logs`
- Verify disk space: `df -h`

#### 3. Security Headers Missing

**Symptoms:**
- Headers not present in response
- CSP not enforced

**Diagnosis:**
```bash
# Check middleware registration
php artisan route:list --middleware=SecurityHeaders

# Test headers
curl -I https://yourdomain.com
```

**Solution:**
- Register middleware in `bootstrap/app.php`
- Clear config cache: `php artisan config:clear`
- Verify `config/security.php` exists

---

## Resources

### Documentation
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [Filament Security](https://filamentphp.com/docs/panels/users)

### Tools
- [OWASP ZAP](https://www.zaproxy.org/)
- [Burp Suite](https://portswigger.net/burp)
- [Nikto](https://cirt.net/Nikto2)
- [SQLMap](https://sqlmap.org/)

### Training
- [OWASP WebGoat](https://owasp.org/www-project-webgoat/)
- [HackTheBox](https://www.hackthebox.com/)
- [TryHackMe](https://tryhackme.com/)

---

## Conclusion

This guide provides comprehensive security testing procedures for the invoice finalization feature. Follow these steps before each deployment and maintain ongoing security testing to ensure the platform remains secure.

**Remember:**
- Security is an ongoing process, not a one-time task
- Test early, test often
- Keep dependencies updated
- Monitor logs continuously
- Respond to incidents quickly

For questions or security concerns, contact: security@example.com
