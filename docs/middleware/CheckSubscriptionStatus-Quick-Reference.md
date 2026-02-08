# CheckSubscriptionStatus Middleware - Quick Reference

**Version:** 2.1  
**Last Updated:** December 2, 2025  
**Status:** ✅ Production Ready

## Overview

Enforces subscription requirements for admin users while maintaining seamless authentication flow.

## Quick Facts

- **Applied To:** All admin routes (except auth routes)
- **Bypasses:** login, register, logout (ALL HTTP methods)
- **Roles Checked:** ADMIN only
- **Roles Bypassed:** SUPERADMIN, MANAGER, TENANT
- **Performance:** ~95% query reduction via caching (5min TTL)
- **Architecture:** Strategy pattern for status handling

## Access Rules

| Subscription Status | Access Level | HTTP Methods | Redirect |
|---------------------|--------------|--------------|----------|
| Active | Full access | All | - |
| Expired | Read-only | GET only | Dashboard (on write) |
| Suspended | Read-only | GET only | Dashboard (on write) |
| Cancelled | Read-only | GET only | Dashboard (on write) |
| Missing | Dashboard only | All | Dashboard (on other routes) |

## Auth Route Bypass

**CRITICAL:** ALL HTTP methods bypass subscription checks for authentication routes.

```php
private const BYPASS_ROUTES = [
    'login',    // GET (form) and POST (submission)
    'register', // GET (form) and POST (submission)
    'logout',   // POST (logout action)
];
```

**Why This Matters:**
- Prevents 419 CSRF errors on login form submission
- Allows logout even with expired/missing subscription
- Enables new user registration flow

## Role-Based Bypass

```php
private const BYPASS_ROLES = [
    UserRole::SUPERADMIN,  // Platform administrators
    UserRole::MANAGER,     // Property managers
    UserRole::TENANT,      // End users
];
```

Only ADMIN role users are subject to subscription validation.

## Common Scenarios

### Scenario 1: Admin with Active Subscription
```
Request: GET /admin/properties
Result: ✅ Full access granted
Message: None
```

### Scenario 2: Admin with Expired Subscription (Read)
```
Request: GET /admin/properties
Result: ⚠️ Read-only access granted
Message: Warning - "Your subscription has expired. You have read-only access."
```

### Scenario 3: Admin with Expired Subscription (Write)
```
Request: POST /admin/properties
Result: ❌ Blocked
Message: Error - "Your subscription has expired. Please renew to continue."
Redirect: /admin/dashboard
```

### Scenario 4: Admin with No Subscription
```
Request: GET /admin/properties
Result: ❌ Blocked
Message: Error - "No active subscription found. Please subscribe to continue."
Redirect: /admin/dashboard
```

### Scenario 5: Admin Login (No Subscription)
```
Request: POST /login
Result: ✅ Bypassed - Authentication proceeds
Message: None
```

## Testing

### Run All Tests
```bash
php artisan test --filter=CheckSubscriptionStatusTest
```

### Run Specific Test Groups
```bash
# Auth route bypass tests
php artisan test --filter="auth route bypass"

# Role bypass tests
php artisan test --filter="role bypass"

# Subscription status tests
php artisan test --filter="subscription status"
```

### Expected Results
- ✅ 30/30 tests passing
- ✅ All auth route bypass tests passing
- ✅ No 419 CSRF errors in login flow

## Troubleshooting

### Issue: 419 Page Expired on Login

**Cause:** Subscription check interfering with login POST request

**Solution:** Verify `login` route is in `BYPASS_ROUTES` constant

**Check:**
```php
// In CheckSubscriptionStatus.php
private const BYPASS_ROUTES = [
    'login',    // ← Must be present
    'register',
    'logout',
];
```

### Issue: Cannot Logout with Expired Subscription

**Cause:** Logout route not bypassing subscription check

**Solution:** Verify `logout` route is in `BYPASS_ROUTES` constant

### Issue: Manager/Tenant Blocked by Subscription Check

**Cause:** Role not in bypass list

**Solution:** Verify role is in `BYPASS_ROLES` constant

```php
private const BYPASS_ROLES = [
    UserRole::SUPERADMIN,
    UserRole::MANAGER,   // ← Must be present
    UserRole::TENANT,    // ← Must be present
];
```

### Issue: Infinite Redirect Loop

**Cause:** Dashboard route blocked for users without subscription

**Solution:** `MissingSubscriptionHandler` allows dashboard access with error message (this is correct behavior)

## Monitoring

### View Subscription Checks
```bash
# Real-time monitoring
tail -f storage/logs/audit.log | grep "Subscription check performed"

# Count by result
grep "Subscription check performed" storage/logs/audit.log | jq '.check_result' | sort | uniq -c

# Find blocked requests
grep "check_result.*blocked" storage/logs/audit.log
```

### Log Structure
```json
{
  "message": "Subscription check performed",
  "check_result": "allowed|blocked",
  "message_type": "warning|error|null",
  "user_id": 123,
  "user_email": "admin@example.com",
  "subscription_id": 456,
  "subscription_status": "active",
  "expires_at": "2025-12-31T23:59:59+00:00",
  "route": "admin.dashboard",
  "method": "GET",
  "ip": "192.168.1.1",
  "timestamp": "2025-12-02T10:30:00+00:00"
}
```

## Performance

### Metrics
- **Execution Time:** <5ms per request (with cache hit)
- **Database Queries:** 0 (with cache hit), 1 (cache miss)
- **Cache TTL:** 5 minutes
- **Query Reduction:** ~95%

### Cache Invalidation
```php
// Invalidate cache when subscription changes
app(SubscriptionChecker::class)->invalidateCache($user);
```

## Security

### Audit Logging
All subscription checks are logged to the `audit` channel with:
- Check result (allowed/blocked)
- User details (ID, email)
- Subscription details (ID, status, expiry)
- Request details (route, method, IP)
- Timestamp

### Fail-Open Strategy
If subscription check throws an exception:
- Request proceeds with warning message
- Error logged to application log
- Prevents service disruption
- User notified to contact support

## Related Documentation

- [Implementation Guide](CheckSubscriptionStatus-Implementation-Guide.md) - Comprehensive guide
- [Refactoring Complete](CheckSubscriptionStatus-Refactoring-Complete-2025-12-02.md) - Detailed refactoring summary
- [CSRF Documentation Enhancement](CHANGELOG_CHECKSUBSCRIPTIONSTATUS_CSRF_DOCS.md) - Recent enhancement details
- [Middleware README](README.md) - All middleware documentation

## Quick Commands

```bash
# Run tests
php artisan test --filter=CheckSubscriptionStatusTest

# View audit logs
tail -f storage/logs/audit.log | grep "Subscription check"

# Clear subscription cache
php artisan cache:clear

# Check middleware configuration
php artisan route:list --path=admin

# Run static analysis
./vendor/bin/phpstan analyse app/Http/Middleware/CheckSubscriptionStatus.php

# Run code style check
./vendor/bin/pint app/Http/Middleware/CheckSubscriptionStatus.php --test
```

## Support

For questions or issues:
1. Check [Implementation Guide](CheckSubscriptionStatus-Implementation-Guide.md)
2. Review test suite for examples
3. Check audit logs for subscription check details
4. Consult [Middleware README](README.md)

---

**Version:** 2.1  
**Status:** ✅ Production Ready  
**Test Coverage:** 30 comprehensive tests  
**Last Updated:** December 2, 2025
