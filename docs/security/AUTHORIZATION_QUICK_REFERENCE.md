# Authorization Quick Reference

**Last Updated**: 2025-12-02

---

## Panel Access Rules

### Admin Panel (`/admin`)

| Role | Access | Notes |
|------|--------|-------|
| **SUPERADMIN** | ✅ Full Access | System-wide access, no tenant restrictions |
| **ADMIN** | ✅ Full Access | Limited to own tenant_id scope |
| **MANAGER** | ✅ Full Access | Limited to own tenant_id scope (legacy role) |
| **TENANT** | ❌ DENIED | Must NEVER access admin panel |

### Other Panels

| Role | Access | Notes |
|------|--------|-------|
| **SUPERADMIN** | ✅ Full Access | Only role with access |
| **ADMIN** | ❌ DENIED | Admin panel only |
| **MANAGER** | ❌ DENIED | Admin panel only |
| **TENANT** | ❌ DENIED | No panel access |

---

## Authorization Layers

### Layer 1: User Model (`canAccessPanel()`)

```php
// app/Models/User.php
public function canAccessPanel(Panel $panel): bool
{
    // Check 1: User must be active
    if (!$this->is_active) {
        return false;
    }

    // Check 2: Role-based access
    if ($panel->getId() === 'admin') {
        return in_array($this->role, [
            UserRole::ADMIN,
            UserRole::MANAGER,
            UserRole::SUPERADMIN,
        ], true);
    }

    return $this->role === UserRole::SUPERADMIN;
}
```

### Layer 2: Middleware (`EnsureUserIsAdminOrManager`)

```php
// app/Http/Middleware/EnsureUserIsAdminOrManager.php
public function handle(Request $request, Closure $next): Response
{
    $user = $request->user();

    if (!$user || !($user->isAdmin() || $user->isManager() || $user->isSuperadmin())) {
        abort(403, __('app.auth.no_permission_admin_panel'));
    }

    return $next($request);
}
```

### Layer 3: Gate Definition

```php
// app/Providers/Filament/AdminPanelProvider.php
Gate::define('access-admin-panel', function ($user) {
    return in_array($user->role, [
        UserRole::ADMIN,
        UserRole::MANAGER,
        UserRole::SUPERADMIN,
    ], true);
});
```

---

## Testing Authorization

### Unit Tests

```bash
# Run all security tests
php artisan test --filter=PanelAccessAuthorizationTest

# Run specific test
php artisan test --filter=tenant_cannot_access_admin_panel
```

### Manual Testing (Tinker)

```php
php artisan tinker

// Test TENANT access (should be FALSE)
$tenant = User::where('role', 'tenant')->first();
$panel = Filament::getPanel('admin');
$tenant->canAccessPanel($panel); // false

// Test ADMIN access (should be TRUE)
$admin = User::where('role', 'admin')->first();
$admin->canAccessPanel($panel); // true

// Test inactive user (should be FALSE)
$inactive = User::factory()->admin(1)->inactive()->create();
$inactive->canAccessPanel($panel); // false
```

### Browser Testing

```bash
# 1. Seed test users
php artisan test:setup --fresh

# 2. Test TENANT login
# Email: tenant@test.com
# Password: password
# Expected: Can login but redirected away from /admin

# 3. Test ADMIN login
# Email: admin@test.com
# Password: password
# Expected: Can access /admin successfully
```

---

## Common Issues & Solutions

### Issue: Users Can't Login

**Symptom**: Valid credentials rejected  
**Cause**: `is_active = false`  
**Solution**:

```php
// Check user status
$user = User::where('email', 'user@example.com')->first();
$user->is_active; // Should be true

// Activate user
$user->update(['is_active' => true]);
```

### Issue: TENANT Accessing Admin Panel

**Symptom**: TENANT role can access `/admin`  
**Cause**: Authorization bypass  
**Solution**: Verify `canAccessPanel()` implementation

```php
// Should return FALSE for TENANT
$tenant->canAccessPanel(Filament::getPanel('admin')); // false
```

### Issue: Inactive Users Logging In

**Symptom**: Deactivated users can access system  
**Cause**: Missing `is_active` check  
**Solution**: Ensure `canAccessPanel()` checks `is_active`

---

## Role Helper Methods

```php
// Use these methods for cleaner code
$user->isSuperadmin();  // true if SUPERADMIN
$user->isAdmin();       // true if ADMIN
$user->isManager();     // true if MANAGER
$user->isTenantUser();  // true if TENANT

// Example usage
if ($user->isSuperadmin()) {
    // Superadmin-only logic
}

if ($user->isAdmin() || $user->isManager()) {
    // Admin/Manager logic
}

if ($user->isTenantUser()) {
    // Tenant-only logic
}
```

---

## Security Checklist

### Before Deploying Authorization Changes

- [ ] All security tests pass
- [ ] Manual testing completed for each role
- [ ] Authorization logs reviewed
- [ ] Code reviewed by security team
- [ ] Documentation updated
- [ ] Rollback plan prepared

### After Deploying Authorization Changes

- [ ] Monitor authorization logs for 24 hours
- [ ] Verify no legitimate users blocked
- [ ] Confirm TENANT users cannot access admin
- [ ] Test session persistence
- [ ] Update security documentation

---

## Emergency Procedures

### If Authorization Bypass Discovered

1. **Immediate**: Disable affected panel/route
2. **Within 1 hour**: Deploy fix
3. **Within 4 hours**: Audit access logs
4. **Within 24 hours**: Complete incident report

### Rollback Procedure

```bash
# 1. Revert code changes
git revert <commit-hash>

# 2. Deploy rollback
git push origin main

# 3. Clear caches
php artisan optimize:clear

# 4. Verify authorization working
php artisan test --filter=PanelAccessAuthorizationTest
```

---

## Monitoring & Alerts

### Authorization Failure Logs

```bash
# View recent authorization failures
tail -f storage/logs/laravel.log | grep "Authorization denied"

# Count failures by user
grep "Authorization denied" storage/logs/laravel.log | \
  grep -oP 'user_id":\K\d+' | sort | uniq -c | sort -rn
```

### Key Metrics to Monitor

- Authorization failure rate (should be < 1% of requests)
- TENANT attempts to access admin panel (should be 0)
- Inactive user login attempts
- Cross-tenant access attempts

---

## Related Documentation

- [Security Incident Report](./SECURITY_INCIDENT_2025_12_02.md)
- [Multi-Tenancy Architecture](../architecture/MULTI_TENANCY.md)
- [User Roles & Permissions](../api/USER_POLICY_API.md)
- [Filament Admin Panel](../admin/ADMIN_PANEL_GUIDE.md)

---

## Contact

**Security Issues**: security@example.com  
**Authorization Questions**: dev-team@example.com  
**Emergency**: oncall@example.com
