# Middleware API Reference

## Overview

Comprehensive API documentation for custom middleware in the Vilnius Utilities Billing Platform.

## Table of Contents

- [EnsureUserIsAdminOrManager](#ensureuserisadminormanager)
- [CheckSubscriptionStatus](#checksubscriptionstatus)
- [EnsureTenantContext](#ensuretenantcontext)
- [EnsureHierarchicalAccess](#ensurehierarchicalaccess)
- [RoleMiddleware](#rolemiddleware)

---

## EnsureUserIsAdminOrManager

**Path:** `app/Http/Middleware/EnsureUserIsAdminOrManager.php`  
**Purpose:** Restrict Filament admin panel access to admin and manager roles  
**Applied To:** All `/admin` routes via Filament panel configuration

### Authorization Rules

| Role | Access | Behavior |
|------|--------|----------|
| Admin | ✅ Allow | Full access to admin panel |
| Manager | ✅ Allow | Full access to admin panel |
| Tenant | ❌ Deny | 403 with error message |
| Superadmin | ❌ Deny | 403 (has separate `/superadmin` routes) |
| Unauthenticated | ❌ Deny | 403 with authentication message |

### Request Flow

```
┌─────────────────────┐
│  Incoming Request   │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│ Get authenticated   │
│ user from request   │
└──────────┬──────────┘
           │
           ▼
    ┌──────────────┐
    │ User exists? │
    └──────┬───────┘
           │
     ┌─────┴─────┐
     │           │
    No          Yes
     │           │
     ▼           ▼
┌─────────┐  ┌──────────────────┐
│  Log    │  │ isAdmin() OR     │
│ failure │  │ isManager()?     │
│ Abort   │  └────────┬─────────┘
│  403    │           │
└─────────┘     ┌─────┴─────┐
                │           │
               Yes          No
                │           │
                ▼           ▼
         ┌──────────┐  ┌─────────┐
         │ Continue │  │  Log    │
         │ request  │  │ failure │
         └──────────┘  │ Abort   │
                       │  403    │
                       └─────────┘
```

### HTTP Responses

#### Success (200)
```http
HTTP/1.1 200 OK
Content-Type: text/html

[Filament admin panel content]
```

#### Unauthenticated (403)
```http
HTTP/1.1 403 Forbidden
Content-Type: application/json

{
  "message": "Authentication required."
}
```

#### Unauthorized (403)
```http
HTTP/1.1 403 Forbidden
Content-Type: application/json

{
  "message": "You do not have permission to access the admin panel."
}
```

### Security Logging

All authorization failures are logged to `storage/logs/laravel.log`:

```json
{
  "level": "warning",
  "message": "Admin panel access denied",
  "context": {
    "user_id": 123,
    "user_email": "tenant@example.com",
    "user_role": "tenant",
    "reason": "Insufficient role privileges",
    "url": "http://example.com/admin/properties",
    "ip": "192.168.1.1",
    "user_agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)...",
    "timestamp": "2025-11-24 12:34:56"
  }
}
```

### Code Examples

#### Testing Authorization

```php
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    public function test_admin_can_access_panel(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        
        $response = $this->actingAs($admin)->get('/admin');
        
        $response->assertStatus(200);
    }
    
    public function test_tenant_cannot_access_panel(): void
    {
        $tenant = User::factory()->create(['role' => UserRole::TENANT]);
        
        $response = $this->actingAs($tenant)->get('/admin');
        
        $response->assertStatus(403)
                 ->assertSee('You do not have permission');
    }
}
```

#### Monitoring Logs

```bash
# Real-time monitoring
tail -f storage/logs/laravel.log | grep "Admin panel access denied"

# Count by role
grep "Admin panel access denied" storage/logs/laravel.log \
  | jq '.context.user_role' \
  | sort | uniq -c

# Top denied IPs
grep "Admin panel access denied" storage/logs/laravel.log \
  | jq '.context.ip' \
  | sort | uniq -c | sort -rn | head -10
```

### Performance Metrics

- **Execution Time:** <1ms per request
- **Database Queries:** 0 (uses cached user object)
- **Memory Usage:** <1KB per request
- **Logging Overhead:** ~2ms on failure (async recommended)

### Related Endpoints

- `GET /admin` - Filament dashboard
- `GET /admin/properties` - Property management
- `GET /admin/buildings` - Building management
- `GET /admin/meters` - Meter management
- `GET /admin/invoices` - Invoice management

---

## CheckSubscriptionStatus

**Path:** `app/Http/Middleware/CheckSubscriptionStatus.php`  
**Purpose:** Enforce subscription limits and expiry policies  
**Applied To:** Admin and manager routes

### Authorization Rules

| Subscription Status | Access | Behavior |
|---------------------|--------|----------|
| Active | ✅ Full | All operations allowed |
| Expired (grace period) | ⚠️ Read-only | View only, no mutations |
| Expired (past grace) | ❌ Blocked | Redirect to renewal page |
| Suspended | ❌ Blocked | Contact support message |

### Request Flow

```
Request → Check subscription → Active? → Continue
                              ↓
                           Expired?
                              ↓
                    ┌─────────┴─────────┐
                    │                   │
              Grace period?        Past grace?
                    │                   │
              Read-only mode      Redirect to renewal
```

### HTTP Responses

#### Active Subscription (200)
```http
HTTP/1.1 200 OK
```

#### Expired - Read Only (200)
```http
HTTP/1.1 200 OK
X-Subscription-Status: expired
X-Subscription-Mode: read-only

[Content with warning banner]
```

#### Expired - Blocked (302)
```http
HTTP/1.1 302 Found
Location: /subscription/renew
```

---

## EnsureTenantContext

**Path:** `app/Http/Middleware/EnsureTenantContext.php`  
**Purpose:** Set tenant context for multi-tenancy isolation  
**Applied To:** All authenticated routes

### Tenant Resolution

```php
// Resolves tenant_id from:
1. Authenticated user's tenant_id
2. Session tenant_id (for superadmin switching)
3. Request parameter (for API calls)
```

### Request Flow

```
Request → Get user → Extract tenant_id → Set TenantContext → Continue
                                              ↓
                                    Apply TenantScope globally
```

### Security Features

- Prevents cross-tenant data leakage
- Validates tenant_id exists
- Logs tenant switches
- Clears context on logout

---

## EnsureHierarchicalAccess

**Path:** `app/Http/Middleware/EnsureHierarchicalAccess.php`  
**Purpose:** Enforce hierarchical user relationships  
**Applied To:** User management routes

### Access Rules

| Actor | Can Manage | Cannot Manage |
|-------|------------|---------------|
| Superadmin | All users | - |
| Admin | Own tenant users | Other tenant users |
| Manager | Assigned property tenants | Other properties |
| Tenant | Own profile only | Other users |

---

## RoleMiddleware

**Path:** `app/Http/Middleware/RoleMiddleware.php`  
**Purpose:** Generic role-based access control  
**Applied To:** Custom routes with role requirements

### Usage

```php
// In routes/web.php
Route::middleware(['auth', 'role:admin,manager'])->group(function () {
    Route::get('/reports', [ReportController::class, 'index']);
});
```

### Authorization Rules

```php
// Single role
Route::middleware('role:admin')

// Multiple roles (OR logic)
Route::middleware('role:admin,manager')

// Nested groups
Route::middleware('role:admin')->group(function () {
    Route::middleware('role:manager')->group(function () {
        // Requires BOTH admin AND manager (AND logic)
    });
});
```

---

## Common Patterns

### Middleware Stacking

```php
// Multiple middleware in order
Route::middleware([
    'auth',                              // 1. Authenticate
    'role:admin,manager',                // 2. Check role
    'subscription',                      // 3. Check subscription
    'tenant.context',                    // 4. Set tenant context
])->group(function () {
    // Protected routes
});
```

### Custom Middleware Registration

```php
// app/Http/Kernel.php
protected $middlewareAliases = [
    'admin.access' => \App\Http\Middleware\EnsureUserIsAdminOrManager::class,
    'subscription' => \App\Http\Middleware\CheckSubscriptionStatus::class,
    'tenant.context' => \App\Http\Middleware\EnsureTenantContext::class,
    'hierarchical' => \App\Http\Middleware\EnsureHierarchicalAccess::class,
    'role' => \App\Http\Middleware\RoleMiddleware::class,
];
```

### Testing Middleware

```php
use Tests\TestCase;

class MiddlewareTest extends TestCase
{
    public function test_middleware_blocks_unauthorized(): void
    {
        $response = $this->get('/admin');
        
        $response->assertStatus(403);
    }
    
    public function test_middleware_allows_authorized(): void
    {
        $admin = $this->actingAsAdmin();
        
        $response = $this->get('/admin');
        
        $response->assertStatus(200);
    }
}
```

---

## Error Handling

### Standard Error Responses

All middleware follows consistent error response format:

```json
{
  "message": "Human-readable error message",
  "errors": {
    "field": ["Validation error details"]
  }
}
```

### HTTP Status Codes

| Code | Meaning | Usage |
|------|---------|-------|
| 401 | Unauthenticated | No valid session/token |
| 403 | Forbidden | Authenticated but unauthorized |
| 302 | Redirect | Subscription expired, redirect to renewal |
| 429 | Too Many Requests | Rate limit exceeded |

---

## Monitoring & Observability

### Log Levels

- `emergency` - System unusable
- `alert` - Immediate action required
- `critical` - Critical conditions
- `error` - Error conditions
- `warning` - Authorization failures ← Most middleware logs here
- `notice` - Normal but significant
- `info` - Informational messages
- `debug` - Debug-level messages

### Metrics to Track

1. **Authorization Failure Rate**
   - Target: <1% of requests
   - Alert: >5% sustained

2. **Middleware Execution Time**
   - Target: <5ms per middleware
   - Alert: >50ms sustained

3. **Subscription Expiry Warnings**
   - Track: 14-day, 7-day, 1-day warnings
   - Alert: Renewal rate <80%

4. **Tenant Context Errors**
   - Target: 0 errors
   - Alert: Any occurrence

---

## Best Practices

### 1. Middleware Order Matters

```php
// ✅ Correct order
Route::middleware([
    'auth',           // First: authenticate
    'tenant.context', // Second: set context
    'subscription',   // Third: check subscription
    'role:admin',     // Fourth: check role
])

// ❌ Wrong order
Route::middleware([
    'role:admin',     // Can't check role without auth
    'auth',           // Too late
])
```

### 2. Use Specific Middleware

```php
// ✅ Good - specific middleware
Route::middleware('admin.access')

// ❌ Bad - generic with parameters
Route::middleware('role:admin,manager')
```

### 3. Test Edge Cases

```php
// Test all scenarios
- Unauthenticated users
- Wrong role users
- Expired subscriptions
- Missing tenant context
- Concurrent requests
```

### 4. Log Appropriately

```php
// ✅ Good - structured logging
Log::warning('Access denied', [
    'user_id' => $user->id,
    'reason' => 'insufficient_role',
    'url' => $request->url(),
]);

// ❌ Bad - unstructured
Log::warning('User ' . $user->id . ' denied');
```

---

## Related Documentation

- [EnsureUserIsAdminOrManager Details](../middleware/ENSURE_USER_IS_ADMIN_OR_MANAGER.md)
- [Middleware Refactoring Summary](../middleware/REFACTORING_SUMMARY.md)
- [Security Implementation](../security/SECURITY_IMPLEMENTATION_CHECKLIST.md)
- [Admin Panel Guide](../admin/ADMIN_PANEL_GUIDE.md)
