# Middleware Quick Reference Card

## 🔒 Route Protection Overview

All role-based routes are protected by a **4-layer middleware chain**:

```php
['auth', 'role:X', 'subscription.check', 'hierarchical.access']
```

## 📊 Middleware Behavior Matrix

| Middleware | Superadmin | Admin | Manager | Tenant |
|------------|------------|-------|---------|--------|
| `auth` | ✅ Check | ✅ Check | ✅ Check | ✅ Check |
| `role:X` | ✅ Verify | ✅ Verify | ✅ Verify | ✅ Verify |
| `subscription.check` | ⏭️ Skip | ✅ Enforce | ⏭️ Skip | ⏭️ Skip |
| `hierarchical.access` | ⏭️ Skip | ✅ Tenant | ✅ Tenant | ✅ Property |

## 🎯 Quick Decision Tree

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

## 🚀 Performance Tips

### Caching Service
```php
use App\Services\SubscriptionChecker;

$checker = app(SubscriptionChecker::class);
$isActive = $checker->isActive($user); // Cached for 5 minutes
```

### Cache Invalidation
```php
// After subscription update
$checker->invalidate($user);
```

## 🔍 Troubleshooting

### Issue: 403 Forbidden
**Check**:
1. User has correct role?
2. Subscription active (for admin)?
3. Resource belongs to user's tenant?
4. Tenant has correct property assignment?

### Issue: Slow Response
**Check**:
1. Cache hit rate (should be 95%+)
2. Query count (should be 0-1 per request)
3. Middleware overhead (should be 2-10ms)

### Issue: Subscription Not Recognized
**Solution**:
```php
// Clear cache
app(SubscriptionChecker::class)->invalidate($user);

// Verify subscription exists
$user->subscription()->exists(); // Should be true
```

## 📝 Common Patterns

### Adding New Protected Route
```php
Route::middleware([
    'auth',
    'role:admin',
    'subscription.check',
    'hierarchical.access'
])->group(function () {
    Route::get('/new-route', [Controller::class, 'method']);
});
```

### Bypassing Middleware (Testing Only)
```php
// In tests
$this->withoutMiddleware([
    CheckSubscriptionStatus::class,
    EnsureHierarchicalAccess::class
]);
```

## 🔗 Related Documentation

- **Architecture**: [docs/middleware/HIERARCHICAL_MIDDLEWARE_ARCHITECTURE.md](HIERARCHICAL_MIDDLEWARE_ARCHITECTURE.md)
- **Implementation**: [docs/middleware/IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md)
- **Analysis**: [docs/architecture/MIDDLEWARE_ROUTE_PROTECTION_ANALYSIS.md](../architecture/MIDDLEWARE_ROUTE_PROTECTION_ANALYSIS.md)
- **Tests**: `tests/Feature/Middleware/`

## 📞 Support

**Audit Logs**: `storage/logs/audit.log`  
**Error Logs**: `storage/logs/laravel.log`  
**Cache**: Redis (production) / File (development)

---

**Last Updated**: 2024-11-26  
**Version**: 1.0
