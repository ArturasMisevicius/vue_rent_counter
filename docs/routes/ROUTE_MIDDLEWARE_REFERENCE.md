# Route Middleware Reference Guide

**Last Updated**: 2024-11-26  
**Laravel Version**: 12.x  
**Application**: Vilnius Utilities Billing Platform

## Overview

This document provides a comprehensive reference for all middleware applied to routes in the Vilnius Utilities Billing Platform. It covers middleware behavior, configuration, and usage patterns across all route groups.

## Middleware Inventory

### Core Middleware

| Middleware | Alias | Purpose | Applied To |
|------------|-------|---------|------------|
| Authenticate | `auth` | Verify user authentication | All protected routes |
| EnsureUserHasRole | `role:X` | Verify user role | Role-specific routes |
| CheckSubscriptionStatus | `subscription.check` | Validate subscription | Admin, Manager, Tenant routes |
| EnsureHierarchicalAccess | `hierarchical.access` | Validate tenant/property access | Admin, Manager, Tenant routes |
| VerifyCsrfToken | (automatic) | CSRF protection | All POST/PUT/PATCH/DELETE |
| ThrottleRequests | `throttle:X,Y` | Rate limiting | API routes |

### Custom Middleware

| Middleware | Location | Purpose |
|------------|----------|---------|
| CheckSubscriptionStatus | `app/Http/Middleware/CheckSubscriptionStatus.php` | Subscription validation |
| EnsureHierarchicalAccess | `app/Http/Middleware/EnsureHierarchicalAccess.php` | Hierarchical access control |
| SecurityHeaders | `app/Http/Middleware/SecurityHeaders.php` | Security headers (CSP, HSTS) |
| RedirectIfAuthenticated | `app/Http/Middleware/RedirectIfAuthenticated.php` | Guest route protection |

## Route Group Middleware Stacks

### Superadmin Routes

**Prefix**: `/superadmin`  
**Middleware**: `['auth', 'role:superadmin']`

```php
Route::middleware(['auth', 'role:superadmin'])
    ->prefix('superadmin')
    ->name('superadmin.')
    ->group(function () {
        // Superadmin routes
    });
```

**Behavior**:
- ✅ Authentication required
- ✅ Superadmin role required
- ⏭️ Subscription checks bypassed
- ⏭️ Hierarchical access bypassed (unrestricted)

**Access Level**: Unrestricted access to all resources

### Admin Routes

**Prefix**: `/admin`  
**Middleware**: `['auth', 'role:admin', 'subscription.check', 'hierarchical.access']`

```php
Route::middleware(['auth', 'role:admin', 'subscription.check', 'hierarchical.access'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Admin routes
    });
```

**Behavior**:
- ✅ Authentication required
- ✅ Admin role required
- ✅ Subscription validation enforced
- ✅ Tenant-scoped access (tenant_id)

**Access Level**: Tenant-scoped with subscription requirements

### Manager Routes

**Prefix**: `/manager`  
**Middleware**: `['auth', 'role:manager', 'subscription.check', 'hierarchical.access']`

```php
Route::middleware(['auth', 'role:manager', 'subscription.check', 'hierarchical.access'])
    ->prefix('manager')
    ->name('manager.')
    ->group(function () {
        // Manager routes
    });
```

**Behavior**:
- ✅ Authentication required
- ✅ Manager role required
- ⏭️ Subscription checks bypassed (works under admin's subscription)
- ✅ Tenant-scoped access (tenant_id)

**Access Level**: Tenant-scoped without subscription requirements

### Tenant Routes

**Prefix**: `/tenant`  
**Middleware**: `['auth', 'role:tenant', 'subscription.check', 'hierarchical.access']`

```php
Route::middleware(['auth', 'role:tenant', 'subscription.check', 'hierarchical.access'])
    ->prefix('tenant')
    ->name('tenant.')
    ->group(function () {
        // Tenant routes
    });
```

**Behavior**:
- ✅ Authentication required
- ✅ Tenant role required
- ⏭️ Subscription checks bypassed (works under admin's subscription)
- ✅ Property-scoped access (tenant_id AND property_id)

**Access Level**: Property-scoped without subscription requirements

### Guest Routes

**Middleware**: `['guest']`

```php
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm']);
});
```

**Behavior**:
- ❌ Authenticated users redirected to dashboard
- ✅ Unauthenticated users allowed

## Middleware Behavior Matrix

| Middleware | Superadmin | Admin | Manager | Tenant | Guest |
|------------|------------|-------|---------|--------|-------|
| `auth` | ✅ Check | ✅ Check | ✅ Check | ✅ Check | ❌ Redirect |
| `role:X` | ✅ Verify | ✅ Verify | ✅ Verify | ✅ Verify | N/A |
| `subscription.check` | ⏭️ Skip | ✅ Enforce | ⏭️ Skip | ⏭️ Skip | N/A |
| `hierarchical.access` | ⏭️ Skip | ✅ Tenant | ✅ Tenant | ✅ Property | N/A |

**Legend**:
- ✅ = Middleware executes and validates
- ⏭️ = Middleware executes but bypasses validation
- ❌ = Middleware blocks access
- N/A = Middleware not applied

## Middleware Execution Flow

### Request Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│ 1. auth          → Verify authentication                    │
│ 2. role:X        → Verify role authorization                │
│ 3. subscription  → Validate subscription (admin only)       │
│ 4. hierarchical  → Validate tenant/property relationships   │
│ 5. Controller    → Business logic                           │
│ 6. Policy        → Final authorization check                │
└─────────────────────────────────────────────────────────────┘
```

### Decision Tree

```
Is user authenticated?
├─ NO → Redirect to login
└─ YES → Check role
    ├─ Superadmin → Full access (no restrictions)
    ├─ Admin → Check subscription
    │   ├─ Active → Check tenant_id
    │   ├─ Expired → Read-only + Check tenant_id
    │   └─ None → Dashboard only
    ├─ Manager → Check tenant_id (no subscription check)
    └─ Tenant → Check tenant_id + property_id
```

## Subscription Middleware Details

### Subscription Status Handling

| Status | GET | POST/PUT/DELETE | Redirect | Message |
|--------|-----|-----------------|----------|---------|
| ACTIVE | ✅ | ✅ | None | None |
| EXPIRED | ✅ | ❌ | Dashboard | "Your subscription has expired. You have read-only access." |
| SUSPENDED | ✅ | ❌ | Dashboard | "Your subscription has been suspended." |
| CANCELLED | ✅ | ❌ | Dashboard | "Your subscription has been cancelled." |
| MISSING | ⚠️ | ❌ | Dashboard | "No active subscription found." |

### Subscription Check Bypass

The following roles bypass subscription checks:
- **Superadmin**: Complete bypass
- **Manager**: Bypass (works under admin's subscription)
- **Tenant**: Bypass (works under admin's subscription)

### Subscription Caching

```php
// Cache configuration
Cache::remember(
    "subscription.{$user->id}.status",
    300, // 5 minutes TTL
    fn() => $user->subscription?->isActive() ?? false
);
```

**Cache invalidation**:
```php
// After subscription update
app(SubscriptionChecker::class)->invalidate($user);
```

## Hierarchical Access Middleware Details

### Access Validation Rules

| Role | Validation | Scope |
|------|------------|-------|
| Superadmin | None | Unrestricted |
| Admin | `tenant_id` match | Tenant-scoped |
| Manager | `tenant_id` match | Tenant-scoped |
| Tenant | `tenant_id` AND `property_id` match | Property-scoped |

### Validated Resources

The following models are validated:
- Buildings
- Properties
- Meters
- Meter Readings
- Invoices
- Users

### Validation Process

```php
// For admin/manager
if ($resource->tenant_id !== $user->tenant_id) {
    abort(403, 'You do not have permission to access this resource.');
}

// For tenant (additional check)
if ($user->property_id && $resource->property_id !== $user->property_id) {
    abort(403, 'You do not have permission to access this resource.');
}
```

## Performance Considerations

### Middleware Overhead

| Middleware | Overhead | Optimization |
|------------|----------|--------------|
| auth | ~1ms | Session-based |
| role:X | ~0.1ms | Enum comparison |
| subscription.check | ~0.1-5ms | Cached (5min TTL) |
| hierarchical.access | ~2-5ms | select() optimization |
| **Total** | **~2-10ms** | **Optimized** |

### Optimization Strategies

1. **Caching**: SubscriptionChecker caches subscription status
2. **Query optimization**: select() used to minimize data transfer
3. **Early returns**: Middleware exits early for bypassed roles
4. **Eager loading**: Relationships eager-loaded where possible

### Cache Hit Rates

Expected cache performance:
- Subscription status: ~95% hit rate
- Query reduction: ~95% fewer database queries
- Response time improvement: ~60-80% faster

## Security Considerations

### Defense in Depth

Multiple security layers:
1. **Authentication** - Session-based verification
2. **Role validation** - Enum-based role checking
3. **Subscription validation** - Business rule enforcement
4. **Hierarchical access** - Data isolation
5. **Policy authorization** - Fine-grained permissions
6. **CSRF protection** - Token validation on writes

### Audit Logging

All middleware actions are logged:

```php
Log::channel('audit')->info('Middleware action', [
    'middleware' => 'subscription.check',
    'user_id' => $user->id,
    'action' => 'check_performed',
    'result' => 'active',
    'route' => $request->route()->getName(),
]);
```

**Audit log location**: `storage/logs/audit.log`

### PII Protection

Sensitive data is redacted via `RedactSensitiveData` processor:
- Email addresses
- Phone numbers
- Personal identifiers
- Payment information

## Common Patterns

### Adding Middleware to New Routes

```php
// Single route
Route::get('/new-route', [Controller::class, 'method'])
    ->middleware(['auth', 'role:admin', 'subscription.check', 'hierarchical.access']);

// Route group
Route::middleware(['auth', 'role:admin', 'subscription.check', 'hierarchical.access'])
    ->group(function () {
        Route::get('/route1', [Controller::class, 'method1']);
        Route::get('/route2', [Controller::class, 'method2']);
    });
```

### Bypassing Middleware in Tests

```php
// Bypass specific middleware
$this->withoutMiddleware([
    CheckSubscriptionStatus::class,
    EnsureHierarchicalAccess::class
]);

// Bypass all middleware
$this->withoutMiddleware();
```

### Custom Middleware Order

```php
// Middleware order matters
Route::middleware([
    'auth',              // 1. Authenticate first
    'role:admin',        // 2. Then check role
    'subscription.check', // 3. Then check subscription
    'hierarchical.access' // 4. Finally check access
])->group(function () {
    // Routes
});
```

## Troubleshooting

### Common Issues

#### Issue: 403 Forbidden

**Possible Causes**:
1. User doesn't have correct role
2. Subscription expired (for admin)
3. Resource belongs to different tenant
4. Tenant not assigned to property

**Solution**: Check audit logs for specific denial reason

#### Issue: Slow Response Times

**Possible Causes**:
1. Cache not working
2. Too many database queries
3. Middleware overhead too high

**Solution**: Monitor cache hit rates and query counts

#### Issue: Subscription Not Recognized

**Possible Causes**:
1. Cache stale
2. Subscription not properly created
3. User relationship not loaded

**Solution**: Clear cache and verify subscription exists

### Debugging Commands

```bash
# View middleware for a route
php artisan route:list --name=admin.dashboard

# Clear cache
php artisan cache:clear

# View audit logs
tail -f storage/logs/audit.log

# Test subscription status
php artisan tinker
>>> $user = User::find(1);
>>> $user->subscription;
>>> app(SubscriptionChecker::class)->isActive($user);
```

## Related Documentation

- **Admin Route Enhancement**: `docs/routes/ADMIN_ROUTE_MIDDLEWARE_ENHANCEMENT.md`
- **Middleware Architecture**: `docs/middleware/HIERARCHICAL_MIDDLEWARE_ARCHITECTURE.md`
- **Implementation Summary**: `docs/middleware/IMPLEMENTATION_SUMMARY.md`
- **Quick Reference**: `docs/middleware/QUICK_REFERENCE.md`
- **Spec**: `.kiro/specs/3-hierarchical-user-management/`

---

**Maintained By**: Development Team  
**Review Frequency**: Quarterly  
**Next Review**: 2025-02-26
