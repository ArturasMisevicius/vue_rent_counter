# API Authentication Documentation

## Overview

The Vilnius Utilities Billing Platform provides a secure API authentication system using Laravel Sanctum with role-based access control and token management.

## Authentication Flow

### 1. Login and Token Generation

**Endpoint:** `POST /api/auth/login`

**Request:**
```json
{
    "email": "user@example.com",
    "password": "password123",
    "token_name": "mobile-app" // optional
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "user@example.com",
            "role": "admin"
        },
        "token": "1|abc123...",
        "abilities": [
            "meter-reading:read",
            "meter-reading:write",
            "property:read",
            "invoice:read"
        ],
        "expires_at": "2025-12-16T12:00:00.000000Z"
    },
    "message": "Authentication successful"
}
```

### 2. Using API Tokens

Include the token in the Authorization header:

```http
Authorization: Bearer 1|abc123...
```

### 3. Token Management

**Get User Info:** `GET /api/auth/me`
**Refresh Token:** `POST /api/auth/refresh`
**Logout:** `POST /api/auth/logout`

## Role-Based Abilities

### Superadmin
- `*` (all abilities)

### Admin/Manager
- `meter-reading:read`
- `meter-reading:write`
- `meter-reading:validate`
- `property:read`
- `property:write`
- `invoice:read`
- `invoice:write`
- `validation:read`
- `validation:write`
- `tenant:read`
- `tenant:write`
- `building:read`
- `building:write`

### Tenant
- `meter-reading:read`
- `meter-reading:write`
- `validation:read`
- `property:read` (own property only)
- `invoice:read` (own invoices only)

## Security Features

1. **Token Expiration:** Tokens expire after 1 year by default
2. **Rate Limiting:** 60 requests per minute per user
3. **Role-Based Access:** Abilities assigned based on user role
4. **Account Validation:** Inactive/suspended users cannot authenticate
5. **Token Revocation:** Users can revoke all tokens for security

## Error Handling

### Authentication Errors
- `401 Unauthorized`: Invalid credentials or inactive account
- `422 Unprocessable Entity`: Validation errors
- `429 Too Many Requests`: Rate limit exceeded

### Example Error Response
```json
{
    "success": false,
    "message": "The provided credentials are incorrect."
}
```

## Token Management

### Creating Tokens
```php
// Automatic role-based abilities
$user = User::find(1);
$token = $user->createApiToken('mobile-app');

// Custom abilities
$token = $user->createApiToken('limited-access', ['meter-reading:read']);

// Token information
$count = $user->getActiveTokensCount();
$hasPermission = $user->hasApiAbility('meter-reading:write');
```

### Revoking Tokens
```php
// Revoke all tokens (security incident)
$user->revokeAllApiTokens();

// Revoke specific token (via API)
POST /api/auth/logout
Authorization: Bearer {token}
```

### Token Abilities by Role

| Role | Abilities |
|------|-----------|
| **Superadmin** | `*` (all abilities) |
| **Admin/Manager** | `meter-reading:read`, `meter-reading:write`, `property:read`, `invoice:read`, `validation:read`, `validation:write` |
| **Tenant** | `meter-reading:read`, `meter-reading:write`, `validation:read` |

## Usage Examples

### JavaScript/Fetch
```javascript
// Login
const response = await fetch('/api/auth/login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    body: JSON.stringify({
        email: 'user@example.com',
        password: 'password123'
    })
});

const { data } = await response.json();
const token = data.token;

// Use token for API calls
const apiResponse = await fetch('/api/v1/validation/health', {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
    }
});
```

### PHP/Laravel
```php
use App\Services\ApiAuthenticationService;

$authService = app(ApiAuthenticationService::class);

// Authenticate user
$result = $authService->authenticate('user@example.com', 'password123');
$token = $result['token'];

// Use token with HTTP client
$response = Http::withToken($token)
    ->get('/api/v1/validation/health');
```

## Configuration

### Environment Variables
```env
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1,yourdomain.com
SANCTUM_TOKEN_EXPIRATION=525600  # 1 year in minutes
```

### Rate Limiting
Configured in `app/Providers/AppServiceProvider.php`:
- API routes: 60 requests per minute
- Admin routes: 120 requests per minute

## Best Practices

1. **Store tokens securely** (not in localStorage for web apps)
2. **Use HTTPS** in production
3. **Implement token refresh** for long-lived applications
4. **Revoke tokens** when users log out
5. **Monitor token usage** for security
6. **Use appropriate abilities** for each endpoint

## Custom Token Management System

### Overview

The application now uses a custom API token management system that replaces Laravel Sanctum's `HasApiTokens` trait while maintaining full compatibility with the Sanctum authentication infrastructure.

### Key Components

#### ApiTokenManager Service
- **Location**: `app/Services/ApiTokenManager.php`
- **Purpose**: Centralized token management with caching and performance optimizations
- **Features**: Role-based abilities, token lifecycle management, usage analytics

#### PersonalAccessToken Model
- **Location**: `app/Models/PersonalAccessToken.php`
- **Purpose**: Custom token model with enhanced functionality
- **Features**: Token validation, ability checking, expiration handling

### Migration from HasApiTokens

The User model no longer uses the `HasApiTokens` trait but maintains the same public interface:

```php
// These methods work exactly the same as before
$token = $user->createApiToken('mobile-app');
$user->revokeAllApiTokens();
$count = $user->getActiveTokensCount();
$hasAbility = $user->hasApiAbility('meter-reading:write');
```

### Performance Improvements

- **Caching**: Token operations are cached for 15 minutes
- **Bulk Operations**: Optimized for handling multiple tokens
- **Monitoring**: Built-in usage analytics and suspicious activity detection

### Security Enhancements

- **Token Validation**: Enhanced validation with user status checks
- **Activity Monitoring**: Automatic detection of suspicious usage patterns
- **Audit Logging**: Comprehensive logging of all token operations

### Backward Compatibility

All existing API endpoints and authentication flows continue to work without any changes. The custom system is a drop-in replacement that maintains full compatibility with:

- `auth:sanctum` middleware
- Existing API routes
- Token-based authentication
- Ability checking
- Token revocation

### Monitoring and Analytics

The system provides comprehensive monitoring through the `ApiTokenMonitoringService`:

- Token creation rate monitoring
- Usage pattern analysis
- System health checks
- Suspicious activity alerts

### Configuration

No configuration changes are required. The system uses the existing `config/sanctum.php` configuration file for token expiration and other settings.