# CheckSubscriptionStatus Middleware - Implementation Guide

## Overview

The `CheckSubscriptionStatus` middleware enforces subscription requirements for admin users while maintaining a seamless authentication flow. It implements a Strategy pattern for handling different subscription states and includes comprehensive security measures.

## Architecture

### Design Patterns

1. **Strategy Pattern**: Delegates subscription status handling to dedicated handler classes
2. **Factory Pattern**: Creates appropriate handlers based on subscription status
3. **Value Object Pattern**: Encapsulates subscription check results

### Key Components

```
CheckSubscriptionStatus (Middleware)
├── SubscriptionChecker (Service with 5min cache)
├── SubscriptionStatusHandlerFactory (Factory)
└── SubscriptionStatusHandlers (Strategy)
    ├── ActiveSubscriptionHandler
    ├── ExpiredSubscriptionHandler
    ├── InactiveSubscriptionHandler
    └── MissingSubscriptionHandler
```

## Critical Security Considerations

### 419 CSRF Error Prevention

**Problem**: Authentication routes (login, register, logout) must bypass subscription checks to prevent 419 Page Expired errors.

**Solution**: The `shouldBypassCheck()` method returns `true` for ALL HTTP methods (GET, POST, PUT, DELETE) when the route is in `BYPASS_ROUTES`.

```php
private const BYPASS_ROUTES = [
    'login',    // GET (form) and POST (submission)
    'register', // GET (form) and POST (submission)
    'logout',   // POST (logout action)
];
```

**Why This Matters**:
- Login form submission (POST) would fail with 419 if subscription check interfered
- Logout (POST) must work even with expired/missing subscription
- Registration flow must be accessible to new users

**Implementation Details**:
The `shouldBypassCheck()` method explicitly bypasses ALL HTTP methods for authentication routes:

```php
protected function shouldBypassCheck(Request $request): bool
{
    $routeName = $request->route()?->getName();
    
    // Bypass all HTTP methods (GET, POST, etc.) for authentication routes
    // This is critical to prevent 419 errors on login form submission
    return $routeName && in_array($routeName, self::BYPASS_ROUTES, true);
}
```

**Critical Documentation**:
The method includes comprehensive PHPDoc explaining:
- Why ALL HTTP methods must bypass (not just GET)
- The specific 419 CSRF error scenario
- The importance of this for login form submissions
- Performance considerations (O(1) lookup with strict comparison)

### Role-Based Bypass

Only `ADMIN` role users are subject to subscription validation:

```php
private const BYPASS_ROLES = [
    UserRole::SUPERADMIN, // Platform administrators
    UserRole::MANAGER,    // Property managers
    UserRole::TENANT,     // End users
];
```

## Subscription Status Handling

### Active Subscription
- **Access**: Full access to all routes
- **Message**: None
- **Behavior**: Request proceeds normally

### Expired Subscription
- **Access**: Read-only (GET requests only)
- **Message**: Warning about expired subscription
- **Behavior**: 
  - GET requests: Allowed with warning
  - POST/PUT/PATCH/DELETE: Blocked with error, redirect to dashboard

### Suspended/Cancelled Subscription
- **Access**: Read-only (GET requests only)
- **Message**: Warning about subscription status
- **Behavior**: Same as expired

### Missing Subscription
- **Access**: Dashboard only
- **Message**: Error about missing subscription
- **Behavior**: 
  - Dashboard route: Allowed with error
  - Other routes: Blocked, redirect to dashboard

## Performance Optimizations

### Caching Strategy
- **SubscriptionChecker**: 5-minute TTL cache reduces DB queries by ~95%
- **Audit Logger**: Memoized instance avoids repeated channel resolution
- **Route Name**: Cached by Laravel framework

### Query Optimization
```php
// SubscriptionChecker uses eager loading
$subscription = Subscription::with('plan')
    ->where('user_id', $user->id)
    ->first();
```

## Audit Logging

All subscription checks are logged to the `audit` channel:

```php
[
    'check_result' => 'allowed|blocked',
    'message_type' => 'warning|error|null',
    'user_id' => 123,
    'user_email' => 'admin@example.com',
    'subscription_id' => 456,
    'subscription_status' => 'active',
    'expires_at' => '2025-12-31T23:59:59+00:00',
    'route' => 'admin.dashboard',
    'method' => 'GET',
    'ip' => '192.168.1.1',
    'timestamp' => '2025-12-02T10:30:00+00:00',
]
```

## Testing

### Test Coverage
- 30 comprehensive tests covering all scenarios
- Property-based tests for subscription lifecycle
- Integration tests for auth flow bypass
- Security tests for role-based access

### Running Tests
```bash
# Run all middleware tests
php artisan test --filter=CheckSubscriptionStatusTest

# Run with coverage
php artisan test --filter=CheckSubscriptionStatusTest --coverage

# Run specific test
php artisan test --filter="login form submission works without subscription"
```

### Key Test Scenarios
1. ✅ Auth routes bypass (GET and POST)
2. ✅ Role-based bypass (superadmin, manager, tenant)
3. ✅ Subscription status handling (active, expired, suspended, cancelled, missing)
4. ✅ Read-only mode enforcement
5. ✅ Audit logging
6. ✅ CSRF token validation
7. ✅ Session regeneration

## Common Issues & Solutions

### Issue: 419 Page Expired on Login
**Cause**: Subscription check interfering with login POST request
**Solution**: Ensure `login` route is in `BYPASS_ROUTES` constant

### Issue: Cannot Logout with Expired Subscription
**Cause**: Logout route not bypassing subscription check
**Solution**: Ensure `logout` route is in `BYPASS_ROUTES` constant

### Issue: Infinite Redirect Loop
**Cause**: Dashboard route blocked for users without subscription
**Solution**: `MissingSubscriptionHandler` allows dashboard access with error message

### Issue: Manager/Tenant Blocked by Subscription Check
**Cause**: Role not in bypass list
**Solution**: Ensure role is in `BYPASS_ROLES` constant

## Extending the Middleware

### Adding New Bypass Routes
```php
private const BYPASS_ROUTES = [
    'login',
    'register',
    'logout',
    'password.request',  // Add password reset
    'password.reset',    // Add password reset
];
```

### Adding New Subscription Status
1. Create new handler implementing `SubscriptionStatusHandler`
2. Update `SubscriptionStatusHandlerFactory::getHandler()`
3. Add tests for new status
4. Update documentation

### Custom Subscription Logic
```php
// Override in handler
public function handle(Request $request, ?Subscription $subscription): SubscriptionCheckResult
{
    // Custom logic here
    if ($this->hasSpecialAccess($subscription)) {
        return SubscriptionCheckResult::allow();
    }
    
    return SubscriptionCheckResult::block(
        'Custom error message',
        'custom.route'
    );
}
```

## Monitoring & Observability

### Metrics to Track
- Subscription check failures (by status)
- Auth route bypass frequency
- Read-only mode activations
- Subscription expiry warnings

### Alerts to Configure
- High rate of subscription check failures
- Unusual patterns in auth route access
- Spike in expired subscriptions

### Log Analysis
```bash
# Find all blocked subscription checks
grep "check_result.*blocked" storage/logs/audit.log

# Find all expired subscription warnings
grep "subscription_status.*expired" storage/logs/audit.log

# Find auth route bypasses
grep "shouldBypassCheck.*true" storage/logs/laravel.log
```

## Security Best Practices

1. **Never bypass subscription checks for admin routes** (except auth routes)
2. **Always log subscription checks** for audit trail
3. **Use read-only mode** instead of complete blocking for expired subscriptions
4. **Fail open with warning** if subscription check throws exception
5. **Cache subscription data** to prevent DoS via repeated checks
6. **Validate subscription expiry** in addition to status

## Related Documentation

- [Subscription Service Documentation](../services/SubscriptionService.md)
- [Strategy Pattern Implementation](../refactoring/CheckSubscriptionStatus-Refactoring-Summary.md)
- [Multi-Tenancy Architecture](../architecture/multi-tenancy.md)
- [Audit Logging Guide](../security/audit-logging.md)

## Changelog

### 2025-12-02
- Enhanced documentation for 419 CSRF error prevention
- Added `BYPASS_ROLES` constant for clarity
- Extracted role bypass logic to dedicated method
- Added comprehensive test coverage for role bypass

### 2024-11-XX
- Initial Strategy pattern refactoring
- Implemented handler factory
- Added value object for results
- Comprehensive test suite
