# User Model API Documentation

## Overview

The User model provides comprehensive API token management with role-based abilities, hierarchical user relationships, and multi-tenant data isolation.

## API Token Management

### Methods

#### `createApiToken(string $name, ?array $abilities = null): string`

Creates a new API token with role-based or custom abilities.

**Parameters:**
- `$name` (string): Token identifier for management
- `$abilities` (array|null): Custom abilities array, null for role-based defaults

**Returns:** Plain text token string for API authentication

**Example:**
```php
// Role-based abilities (automatic)
$token = $user->createApiToken('mobile-app');

// Custom abilities
$token = $user->createApiToken('limited-access', ['meter-reading:read']);
```

#### `revokeAllApiTokens(): void`

Revokes all active API tokens for security purposes.

**Use Cases:**
- Security incidents
- Password changes
- Account deactivation
- Suspicious activity

**Example:**
```php
$user->revokeAllApiTokens();
```

#### `getActiveTokensCount(): int`

Returns the number of currently active API tokens.

**Returns:** Integer count of active tokens

**Example:**
```php
$count = $user->getActiveTokensCount();
if ($count > 5) {
    // Too many tokens, consider cleanup
}
```

#### `hasApiAbility(string $ability): bool`

Checks if the current access token has a specific ability.

**Parameters:**
- `$ability` (string): The ability to check (e.g., 'meter-reading:write')

**Returns:** Boolean indicating if user has the ability

**Example:**
```php
if ($user->hasApiAbility('meter-reading:write')) {
    // Allow meter reading submission
}
```

## Role-Based Abilities

### Superadmin
- **Abilities:** `['*']` (all abilities)
- **Access:** Full system access across all tenants
- **Use Case:** System administration and management

### Admin/Manager
- **Abilities:** 
  - `meter-reading:read`
  - `meter-reading:write`
  - `property:read`
  - `invoice:read`
  - `validation:read`
  - `validation:write`
- **Access:** Limited to their tenant scope
- **Use Case:** Property management and tenant administration

### Tenant
- **Abilities:**
  - `meter-reading:read`
  - `meter-reading:write`
  - `validation:read`
- **Access:** Limited to their assigned property
- **Use Case:** Meter reading submission and consumption viewing

## Security Features

### Token Security
- **Expiration:** Configurable via `SANCTUM_TOKEN_EXPIRATION`
- **Revocation:** Bulk revoke all tokens for security
- **Abilities:** Fine-grained permission control
- **Rate Limiting:** 60 requests per minute per user

### Account Security
- **Active Status:** Inactive users cannot create tokens
- **Suspension:** Suspended users are denied access
- **Email Verification:** Required for API access

## Integration with Services

### ApiAuthenticationService
```php
use App\Services\ApiAuthenticationService;

$authService = app(ApiAuthenticationService::class);
$result = $authService->authenticate($email, $password, $tokenName);
```

### UserQueryOptimizationService
```php
use App\Services\UserQueryOptimizationService;

$queryService = app(UserQueryOptimizationService::class);
$stats = $queryService->getApiTokenStatistics($tenantId);
```

## Database Schema

### Personal Access Tokens Table
```sql
-- Optimized indexes for token management
CREATE INDEX pat_tokenable_name_idx ON personal_access_tokens (tokenable_type, tokenable_id, name);
CREATE INDEX pat_last_used_idx ON personal_access_tokens (last_used_at);
CREATE INDEX pat_expires_at_idx ON personal_access_tokens (expires_at);
CREATE INDEX pat_user_tokenable_idx ON personal_access_tokens (tokenable_id) WHERE tokenable_type = 'App\\Models\\User';
```

## Caching Strategy

### Cache Keys
```php
// API token statistics (15 minutes TTL)
"user_api_tokens:{tenant_id}:stats"

// User role checks (1 hour TTL)
"user_role:{user_id}:has_role:{role}:{guard}"
```

### Cache Invalidation
- User role changes: Clear role and token caches
- Token operations: Clear token statistics
- Account status changes: Clear all user-related caches

## Testing

### Unit Tests
```php
// Token creation
$token = $user->createApiToken('test-token');
$this->assertIsString($token);

// Token count
$this->assertEquals(1, $user->getActiveTokensCount());

// Token revocation
$user->revokeAllApiTokens();
$this->assertEquals(0, $user->fresh()->getActiveTokensCount());

// Ability checking
$this->assertTrue($user->hasApiAbility('meter-reading:read'));
```

### Feature Tests
```php
// API authentication
$response = $this->postJson('/api/auth/login', [
    'email' => 'user@example.com',
    'password' => 'password'
]);

$token = $response->json('data.token');

// API usage
$response = $this->withToken($token)
    ->getJson('/api/v1/validation/health');
```

## Best Practices

### Token Management
1. **Use descriptive names** for token identification
2. **Revoke tokens** when no longer needed
3. **Monitor token usage** for security
4. **Implement token rotation** for long-lived applications

### Security
1. **Validate abilities** before sensitive operations
2. **Use HTTPS** in production
3. **Implement rate limiting** to prevent abuse
4. **Monitor suspicious activity** patterns

### Performance
1. **Cache token statistics** for dashboard displays
2. **Use eager loading** for token relationships
3. **Implement bulk operations** for token cleanup
4. **Monitor database performance** for token queries

## Related Documentation

- [API Authentication](authentication.md)
- [User Model Architecture](../database/USER_MODEL_ARCHITECTURE.md)
- [User Role Service](../services/USER_ROLE_SERVICE.md)
- [Panel Access Service](../services/PANEL_ACCESS_SERVICE.md)