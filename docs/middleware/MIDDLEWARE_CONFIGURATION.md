# Middleware Configuration

## Overview

The application's middleware configuration is centralized in `bootstrap/app.php` using Laravel 12's fluent configuration API. This document describes the middleware stack, rate limiting strategy, and security measures.

## Middleware Aliases

Middleware aliases are registered for convenient route-level application:

```php
$middleware->alias([
    'auth' => \App\Http\Middleware\Authenticate::class,
    'tenant.context' => \App\Http\Middleware\EnsureTenantContext::class,
    'role' => \App\Http\Middleware\RoleMiddleware::class,
    'subscription.check' => \App\Http\Middleware\CheckSubscriptionStatus::class,
    'hierarchical.access' => \App\Http\Middleware\EnsureHierarchicalAccess::class,
    'locale' => \App\Http\Middleware\SetLocale::class,
    'impersonation' => \App\Http\Middleware\HandleImpersonation::class,
]);
```

### Usage in Routes

```php
// Single middleware
Route::get('/dashboard', DashboardController::class)
    ->middleware('auth');

// Multiple middleware
Route::resource('properties', PropertyController::class)
    ->middleware(['auth', 'tenant.context', 'role:admin,manager']);

// Middleware groups
Route::middleware(['auth', 'tenant.context'])->group(function () {
    Route::get('/meters', [MeterController::class, 'index']);
    Route::get('/invoices', [InvoiceController::class, 'index']);
});
```

## Web Middleware Group

The following middleware are automatically applied to all web routes:

### SetLocale
- **Purpose**: Handles internationalization (i18n)
- **Behavior**: Sets application locale based on session or user preference
- **Supported Locales**: EN, LT, RU
- **Implementation**: `App\Http\Middleware\SetLocale`

### HandleImpersonation
- **Purpose**: Manages superadmin impersonation sessions
- **Behavior**: Allows superadmins to impersonate other users for support/debugging
- **Security**: Logs all impersonation events, requires superadmin role
- **Implementation**: `App\Http\Middleware\HandleImpersonation`

### SecurityHeaders
- **Purpose**: Applies security headers to all responses
- **Headers Applied**:
  - `Content-Security-Policy`: Prevents XSS attacks
  - `X-Frame-Options`: Prevents clickjacking
  - `X-Content-Type-Options`: Prevents MIME sniffing
  - `Strict-Transport-Security`: Enforces HTTPS
  - `Referrer-Policy`: Controls referrer information
- **Implementation**: `App\Http\Middleware\SecurityHeaders`

## Rate Limiting Strategy

### API Routes
- **Limit**: 60 requests per minute per IP address
- **Configuration**: `$middleware->throttleApi('60,1')`
- **Response**: HTTP 429 (Too Many Requests) when exceeded
- **Use Case**: Protects API endpoints from abuse

### Admin/Filament Routes
- **Strategy**: Relies on Filament v4's built-in protections
- **Additional Protection**: SecurityHeaders middleware provides DoS mitigation
- **Rationale**: Filament includes session-based rate limiting and CSRF protection
- **Note**: Custom admin rate limiter removed as of 2024-12 (see CHANGELOG)

### Custom Rate Limiting

For specific routes requiring custom rate limits, use the `throttle` middleware:

```php
// Custom rate limit: 10 requests per minute
Route::post('/api/heavy-operation', HeavyOperationController::class)
    ->middleware('throttle:10,1');

// Named rate limiter (define in AppServiceProvider)
Route::post('/api/sensitive', SensitiveController::class)
    ->middleware('throttle:sensitive');
```

## Exception Handling

### Authorization Exceptions

Authorization failures are handled with:
- **Logging**: All failed authorization attempts are logged with user context
- **JSON Response**: API requests receive structured JSON error (HTTP 403)
- **Web Response**: Browser requests see custom 403 error page
- **Audit Trail**: Logs include user ID, email, role, URL, and timestamp

```php
// Example log entry
[
    'user_id' => 123,
    'user_email' => 'user@example.com',
    'user_role' => 'manager',
    'url' => 'https://app.example.com/admin/users',
    'message' => 'This action is unauthorized.',
    'timestamp' => '2024-12-01 10:30:45'
]
```

## Testing Considerations

### Unit Tests
- CSRF protection is automatically disabled during unit tests
- Allows easier testing without token management
- Configured via `app()->runningUnitTests()` check

### Feature Tests
- Use `actingAs()` helper to authenticate test users
- Middleware stack is fully active in feature tests
- Test rate limiting with `$this->withoutMiddleware()`

## Security Best Practices

1. **Always use HTTPS in production** - Enforced by SecurityHeaders middleware
2. **Apply tenant.context middleware** - Ensures multi-tenancy isolation
3. **Use role middleware** - Restricts access based on user roles
4. **Monitor rate limit violations** - Check logs for potential attacks
5. **Keep middleware order correct** - Authentication before authorization

## Related Documentation

- [Security Architecture](../security/SECURITY_ARCHITECTURE.md)
- [Multi-Tenancy Implementation](../architecture/MULTI_TENANT_ARCHITECTURE.md)
- [Filament Admin Panel](../admin/ADMIN_PANEL_GUIDE.md)
- [API Documentation](../api/API_ARCHITECTURE_GUIDE.md)

## Changelog

### 2024-12-01
- **Removed**: Custom admin rate limiter (120 req/min)
- **Rationale**: Filament v4 provides built-in rate limiting and session protection
- **Impact**: Admin routes now rely on Filament's protections + SecurityHeaders middleware
- **Migration**: No action required; Filament handles rate limiting internally
