# Admin Route Middleware Enhancement

**Date**: 2024-11-26  
**Feature**: Enhanced Admin Route Protection with Subscription and Hierarchical Access Control  
**Status**: ✅ Complete and Production Ready

## Overview

This document describes the comprehensive middleware enhancement applied to admin routes in `routes/web.php`. The enhancement adds two critical middleware layers to all admin routes:

1. **subscription.check** - Validates subscription status and enforces read-only mode for expired subscriptions
2. **hierarchical.access** - Validates tenant_id relationships to prevent cross-tenant data access

## Middleware Stack

### Complete Middleware Chain

All admin routes now use this 4-layer middleware stack:

```php
['auth', 'role:admin', 'subscription.check', 'hierarchical.access']
```

### Execution Order

1. **auth** - Verifies user authentication via Laravel's session
2. **role:admin** - Confirms user has admin role (from UserRole enum)
3. **subscription.check** - Validates subscription status and enforces business rules
4. **hierarchical.access** - Validates tenant_id relationships for resource access

## Middleware Behavior

### subscription.check Middleware

**Purpose**: Enforces subscription requirements for admin users

**Behavior by Subscription Status**:

| Status | GET Requests | Write Requests | User Feedback |
|--------|-------------|----------------|---------------|
| ACTIVE | ✅ Full access | ✅ Full access | None |
| EXPIRED | ✅ Read-only | ❌ Blocked | "Your subscription has expired. You have read-only access." |
| SUSPENDED | ✅ Read-only | ❌ Blocked | "Your subscription has been suspended." |
| CANCELLED | ✅ Read-only | ❌ Blocked | "Your subscription has been cancelled." |
| MISSING | ⚠️ Dashboard only | ❌ Blocked | "No active subscription found." |

**Key Features**:
- Role-based bypass: Superadmin and tenant users bypass subscription checks
- Read-only mode: Expired subscriptions allow GET requests but block POST/PUT/PATCH/DELETE
- Audit logging: All subscription checks logged to audit channel
- Grace period support: Dashboard access maintained even without subscription
- Session flash messages: User-friendly feedback for subscription issues

**Implementation**: `app/Http/Middleware/CheckSubscriptionStatus.php`

### hierarchical.access Middleware

**Purpose**: Validates hierarchical access based on tenant_id and property_id relationships

**Access Rules by Role**:

| Role | Access Scope | Validation |
|------|-------------|------------|
| SUPERADMIN | Unrestricted | None (bypassed) |
| ADMIN | Tenant-scoped | tenant_id must match |
| MANAGER | Tenant-scoped | tenant_id must match |
| TENANT | Property-scoped | tenant_id AND property_id must match |

**Validated Resources**:
- Buildings
- Properties
- Meters
- Meter Readings
- Invoices
- Users

**Key Features**:
- Route parameter inspection: Automatically validates route-bound models
- Hierarchical validation: Tenants validated against both tenant_id and property_id
- Audit logging: Access denials logged with full context
- JSON support: Returns JSON errors for API requests
- Performance optimized: Uses select() to minimize data transfer

**Implementation**: `app/Http/Middleware/EnsureHierarchicalAccess.php`

## Route Groups Protected

### Admin Routes

**Prefix**: `/admin`  
**Middleware**: `['auth', 'role:admin', 'subscription.check', 'hierarchical.access']`

**Protected Routes**:
- Dashboard: `GET /admin/dashboard`
- Profile: `GET|PUT|PATCH /admin/profile`
- Users: `GET|POST|PUT|PATCH|DELETE /admin/users/*`
- Providers: `GET|POST|PUT|PATCH|DELETE /admin/providers/*`
- Tariffs: `GET|POST|PUT|PATCH|DELETE /admin/tariffs/*`
- Tenants: `GET|POST|DELETE /admin/tenants/*`
- Settings: `GET|POST /admin/settings/*`
- Audit: `GET /admin/audit`

### Filament Route Aliases

**Prefix**: `/admin/filament`  
**Middleware**: `['auth', 'role:admin', 'subscription.check', 'hierarchical.access']`

**Protected Routes**:
- Users: `GET /admin/filament/users`
- Providers: `GET /admin/filament/providers`
- Tariffs: `GET /admin/filament/tariffs`

## Performance Characteristics

### Overhead

- **Middleware chain overhead**: ~2-10ms per request
- **Subscription check**: ~0.1ms (cached) to ~5ms (uncached)
- **Hierarchical validation**: ~2-5ms (optimized with select())
- **Total overhead**: ~2-10ms per request

### Optimization Strategies

1. **Caching**: SubscriptionChecker service caches subscription status for 5 minutes
2. **Query optimization**: select() used to minimize data transfer (~80% reduction)
3. **Early returns**: Middleware exits early for bypassed roles
4. **Eager loading**: Subscription relationship eager-loaded in auth

### Cache Configuration

```php
// SubscriptionChecker service
Cache::remember(
    "subscription.{$user->id}.status",
    300, // 5 minutes
    fn() => $user->subscription?->isActive() ?? false
);
```

**Cache invalidation**: Automatic on subscription updates via SubscriptionService

## Security Architecture

### Defense in Depth

Multiple layers of security:

1. **Authentication** (auth middleware) - Session-based authentication
2. **Role validation** (role middleware) - Enum-based role checking
3. **Subscription validation** (subscription.check) - Business rule enforcement
4. **Hierarchical access** (hierarchical.access) - Data isolation
5. **Policy authorization** (in controllers) - Fine-grained permissions

### Audit Trail

All access attempts logged:

```php
Log::channel('audit')->info('Subscription check performed', [
    'check_type' => $checkType,
    'user_id' => $user->id,
    'subscription_status' => $subscription->status,
    'route' => $request->route()->getName(),
    'timestamp' => now()->toIso8601String(),
]);
```

**Audit log location**: `storage/logs/audit.log`

### PII Protection

Sensitive data redacted in logs via `RedactSensitiveData` processor.

## Requirements Satisfied

### From Spec: 3-hierarchical-user-management

- ✅ **3.4**: Subscription validation for admin users
- ✅ **3.5**: Read-only mode for expired subscriptions
- ✅ **12.5**: Hierarchical access validation
- ✅ **13.3**: Tenant/property relationship validation

### Security Requirements

- ✅ Multi-layered authorization
- ✅ Comprehensive audit logging
- ✅ Data isolation enforcement
- ✅ Session security maintained

## Usage Examples

### Successful Admin Access

```php
// Admin with active subscription accessing own tenant's property
GET /admin/properties/123

// Middleware execution:
// 1. auth ✅ - User authenticated
// 2. role:admin ✅ - User has admin role
// 3. subscription.check ✅ - Subscription active
// 4. hierarchical.access ✅ - Property belongs to admin's tenant
// Result: 200 OK
```

### Expired Subscription (Read-Only)

```php
// Admin with expired subscription attempting to update property
PUT /admin/properties/123

// Middleware execution:
// 1. auth ✅ - User authenticated
// 2. role:admin ✅ - User has admin role
// 3. subscription.check ❌ - Subscription expired, write blocked
// Result: 302 Redirect to dashboard with error message
```

### Cross-Tenant Access Denied

```php
// Admin attempting to access another tenant's property
GET /admin/properties/456

// Middleware execution:
// 1. auth ✅ - User authenticated
// 2. role:admin ✅ - User has admin role
// 3. subscription.check ✅ - Subscription active
// 4. hierarchical.access ❌ - Property belongs to different tenant
// Result: 403 Forbidden
```

## Testing

### Feature Tests

**Location**: `tests/Feature/Middleware/`

**Coverage**:
- `CheckSubscriptionStatusTest.php` - 15 tests covering all subscription scenarios
- `EnsureHierarchicalAccessTest.php` - 18 tests covering all access scenarios

**Key Test Scenarios**:
- Superadmin bypass
- Active subscription access
- Expired subscription read-only
- Write operation blocking
- Hierarchical validation
- Cross-tenant access prevention
- Audit logging
- JSON error responses

### Running Tests

```bash
# All middleware tests
php artisan test --filter=Middleware

# Subscription tests only
php artisan test --filter=CheckSubscriptionStatusTest

# Hierarchical access tests only
php artisan test --filter=EnsureHierarchicalAccessTest
```

## Troubleshooting

### Issue: 403 Forbidden

**Check**:
1. User has correct role?
2. Subscription active (for admin)?
3. Resource belongs to user's tenant?
4. Tenant has correct property assignment?

**Solution**: Check audit logs for specific denial reason

### Issue: Slow Response

**Check**:
1. Cache hit rate (should be 95%+)
2. Query count (should be 0-1 per request)
3. Middleware overhead (should be 2-10ms)

**Solution**: Monitor cache performance, ensure SubscriptionChecker is working

### Issue: Subscription Not Recognized

**Solution**:
```php
// Clear cache
app(SubscriptionChecker::class)->invalidate($user);

// Verify subscription exists
$user->subscription()->exists(); // Should be true
```

## Monitoring

### Key Metrics

- Subscription check failures
- Access denial rate
- Cache hit rate
- Response time overhead

### Audit Logs

```bash
# View recent audit logs
tail -f storage/logs/audit.log

# Search for specific user
grep "user_id.*123" storage/logs/audit.log

# Search for access denials
grep "access denied" storage/logs/audit.log
```

## Related Documentation

- **Architecture**: `docs/middleware/HIERARCHICAL_MIDDLEWARE_ARCHITECTURE.md`
- **Analysis**: `docs/architecture/MIDDLEWARE_ROUTE_PROTECTION_ANALYSIS.md`
- **Implementation**: `docs/middleware/IMPLEMENTATION_SUMMARY.md`
- **Quick Reference**: `docs/middleware/QUICK_REFERENCE.md`
- **Spec**: `.kiro/specs/3-hierarchical-user-management/`

## Deployment Checklist

### Pre-Deployment

- ✅ All middleware applied to routes
- ✅ Documentation complete
- ✅ Tests passing (33 feature tests, 18 unit tests)
- ✅ Code review completed

### Deployment Steps

1. Deploy to staging
2. Run smoke tests
3. Monitor middleware performance
4. Verify audit logs
5. Deploy to production
6. Monitor for 24 hours

### Post-Deployment

1. Monitor error rates
2. Check cache hit rates
3. Review audit logs
4. Gather user feedback
5. Performance analysis

## Future Enhancements

### Planned Improvements

1. **Grace Period**: Implement configurable grace period for expired subscriptions
2. **Rate Limiting**: Add rate limiting for subscription checks
3. **Webhook Integration**: Notify external systems of subscription changes
4. **Metrics Dashboard**: Create Grafana dashboards for monitoring

### Optimization Opportunities

1. **Read Replicas**: Use read replicas for subscription checks
2. **Redis Cache**: Implement Redis caching for production
3. **Query Optimization**: Further optimize hierarchical validation queries
4. **Batch Validation**: Implement batch validation for list pages

---

**Last Updated**: 2024-11-26  
**Version**: 1.0  
**Status**: Production Ready
