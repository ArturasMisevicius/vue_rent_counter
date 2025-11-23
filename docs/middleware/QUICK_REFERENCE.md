# Middleware Quick Reference

## EnsureUserIsAdminOrManager

**Purpose:** Restrict Filament admin panel access to admin and manager roles only.

### Quick Facts

- **Location:** `app/Http/Middleware/EnsureUserIsAdminOrManager.php`
- **Applied To:** All `/admin` routes via Filament panel configuration
- **Test Coverage:** 100% (11 tests, 16 assertions)
- **Performance:** <1ms overhead per request
- **Localization:** EN/LT/RU supported

### Authorization Rules

| Role | Access | HTTP Status |
|------|--------|-------------|
| Admin | ✅ Allow | 200 |
| Manager | ✅ Allow | 200 |
| Tenant | ❌ Deny | 403 |
| Superadmin | ❌ Deny | 403 |
| Unauthenticated | ❌ Deny | 403 |

### Error Messages

**English:**
- `Authentication required.`
- `You do not have permission to access the admin panel.`

**Lithuanian:**
- `Reikalinga autentifikacija.`
- `Neturite leidimo pasiekti administravimo skydelį.`

**Russian:**
- `Требуется аутентификация.`
- `У вас нет разрешения на доступ к панели администратора.`

### Security Logging

All authorization failures are logged with:
```json
{
  "message": "Admin panel access denied",
  "user_id": 123,
  "user_email": "user@example.com",
  "user_role": "tenant",
  "reason": "Insufficient role privileges",
  "url": "http://example.com/admin/properties",
  "ip": "192.168.1.1",
  "user_agent": "Mozilla/5.0...",
  "timestamp": "2025-11-24 12:34:56"
}
```

### Testing

```php
// Test admin access
$this->actingAsAdmin()->get('/admin')->assertStatus(200);

// Test tenant blocked
$this->actingAsTenant()->get('/admin')->assertStatus(403);

// Test localized message
$this->actingAsTenant()
    ->get('/admin')
    ->assertSee(__('app.auth.no_permission_admin_panel'));
```

### Monitoring

```bash
# View recent failures
tail -f storage/logs/laravel.log | grep "Admin panel access denied"

# Count by role
grep "Admin panel access denied" storage/logs/laravel.log | jq '.user_role' | sort | uniq -c

# Find suspicious IPs
grep "Admin panel access denied" storage/logs/laravel.log | jq '.ip' | sort | uniq -c | sort -rn
```

### Related Files

- Middleware: `app/Http/Middleware/EnsureUserIsAdminOrManager.php`
- Tests: `tests/Feature/Middleware/EnsureUserIsAdminOrManagerTest.php`
- Translations: `lang/{en,lt,ru}/app.php`
- Config: `app/Providers/Filament/AdminPanelProvider.php`

### Documentation

- [Full Documentation](./ENSURE_USER_IS_ADMIN_OR_MANAGER.md)
- [API Reference](../api/MIDDLEWARE_API.md)
- [Refactoring Summary](./REFACTORING_SUMMARY.md)
- [Complete Report](./MIDDLEWARE_REFACTORING_COMPLETE.md)
