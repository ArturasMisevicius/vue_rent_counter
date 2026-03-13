# HierarchicalScope Guest Access Fix

**Date**: 2024-12-01  
**Priority**: 🔴 CRITICAL  
**Status**: ✅ Fixed

## Problem

Главная страница (/) и форма входа падали для неавторизованных пользователей (гостей) с ошибкой:

```
Query executed without tenant context {user_id: null}
```

### Root Cause

`HierarchicalScope` пытался фильтровать данные по `tenant_id` даже когда пользователь НЕ залогинен. Это происходило потому что в методе `apply()` не было проверки на гостя в самом начале.

### Impact

- ❌ Гости не могли открыть главную страницу
- ❌ Форма входа не загружалась
- ❌ Система была полностью недоступна для новых пользователей

## Solution

### 1. Добавлена проверка гостя в HierarchicalScope

**File**: `app/Scopes/HierarchicalScope.php`

**Change**:
```php
public function apply(Builder $builder, Model $model): void
{
    try {
        // CRITICAL: Skip filtering for guests (unauthenticated users)
        // This prevents errors on public pages like login form
        if (!Auth::check()) {
            return;
        }
        
        // ... rest of the code
    }
}
```

**Explanation**:
- Проверка `Auth::check()` добавлена в самом начале метода
- Если пользователь не авторизован, scope просто возвращается без фильтрации
- Это позволяет гостям получать доступ к публичным страницам

### 2. Verified CSRF Token

**File**: `resources/views/auth/login.blade.php`

**Status**: ✅ Already present

CSRF токен уже присутствует в форме входа:
```blade
<form method="POST" action="{{ route('login') }}" class="space-y-6">
    @csrf
    <!-- form fields -->
</form>
```

## Testing

### Created Tests

**File**: `tests/Feature/GuestAccessTest.php`

**Test Coverage**:
1. ✅ Guests can access home page
2. ✅ Guests can access login page
3. ✅ HierarchicalScope doesn't filter for guests
4. ✅ Guests can see user list on login page
5. ✅ Login form has CSRF token

### Manual Testing

```bash
# Test home page
curl http://localhost/

# Test login page
curl http://localhost/login

# Both should return 200 OK without errors
```

## Security Considerations

### Why This Is Safe

1. **Public Pages Only**: Гости могут видеть только публичные страницы (/, /login)
2. **No Data Leakage**: User list на странице входа показывает только активных пользователей
3. **CSRF Protection**: Форма входа защищена CSRF токеном
4. **Authentication Required**: Все остальные страницы требуют авторизации

### What Guests Can Access

✅ **Allowed**:
- Home page (/)
- Login page (/login)
- User list on login page (for demo purposes)

❌ **Not Allowed**:
- Admin dashboard
- Manager dashboard
- Tenant dashboard
- Any data modification
- Any protected routes

## Architecture Impact

### Multi-Tenancy Preserved

- ✅ Tenant isolation сохранена для авторизованных пользователей
- ✅ Superadmin bypass работает как и раньше
- ✅ Admin/Manager filtering работает корректно
- ✅ Tenant property filtering работает корректно

### Performance Impact

- ✅ No performance degradation
- ✅ Early return for guests (minimal overhead)
- ✅ No additional database queries

## Related Issues

### Previous Refactoring

This fix was needed after the LoginController refactoring where we:
- Created `AuthenticationService`
- Added query scopes to User model
- Optimized user listing query

The refactoring exposed the issue because it changed how users are loaded on the login page.

## Verification Checklist

- [x] HierarchicalScope updated with guest check
- [x] CSRF token verified in login form
- [x] Tests created for guest access
- [x] Manual testing performed
- [x] Security implications reviewed
- [x] Multi-tenancy architecture preserved
- [x] Documentation updated

## Deployment Notes

### No Migration Required

This is a code-only fix, no database changes needed.

### Rollout Steps

1. Deploy updated `HierarchicalScope.php`
2. Verify home page loads for guests
3. Verify login page loads for guests
4. Run test suite to confirm no regressions

### Rollback Plan

If issues occur, revert the commit:
```bash
git revert <commit-hash>
```

## Lessons Learned

1. **Always Test Guest Access**: When refactoring authentication, always test guest access
2. **Early Returns**: Global scopes should check authentication status early
3. **Public Pages**: Consider public pages when implementing global scopes
4. **Test Coverage**: Add tests for guest access scenarios

## Future Improvements

### Recommended Enhancements

1. **Remove User List in Production**: User list on login page should be feature-flagged
2. **Add Rate Limiting**: Add rate limiting to login attempts
3. **Add Monitoring**: Monitor failed login attempts
4. **Add Audit Logging**: Log all authentication attempts

### Security Hardening

```php
// Future: Add feature flag for user list
if (config('app.show_user_list_on_login', false)) {
    $users = $this->authService->getActiveUsersForLoginDisplay();
} else {
    $users = collect();
}
```

## References

- [HierarchicalScope Documentation](../architecture/HIERARCHICAL_SCOPE.md)
- [Multi-Tenancy Architecture](../architecture/MULTI_TENANT_ARCHITECTURE.md)
- [Authentication Architecture](../architecture/AUTHENTICATION_ARCHITECTURE.md)
- [LoginController Refactoring](../refactoring/login-controller-refactoring.md)

---

**Fix Status**: ✅ Complete and Verified  
**Severity**: Critical  
**Impact**: High (System was inaccessible to guests)  
**Resolution Time**: Immediate
