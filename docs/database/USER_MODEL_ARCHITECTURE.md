# User Model Database Architecture

## Overview

The User model implements a hierarchical multi-tenant architecture with role-based access control, API token management, and comprehensive data integrity constraints.

## Schema Design

### Core Fields

```sql
-- Identity & Authentication
id BIGINT PRIMARY KEY
email VARCHAR(255) UNIQUE NOT NULL
password VARCHAR(255) NOT NULL
email_verified_at TIMESTAMP NULL
remember_token VARCHAR(100) NULL

-- Hierarchy & Tenancy
system_tenant_id BIGINT NULL -- For superadmin
tenant_id BIGINT NULL -- Organization identifier
property_id BIGINT NULL -- For tenant role
parent_user_id BIGINT NULL -- Admin who created tenant

-- Role & Status
role ENUM('superadmin', 'admin', 'manager', 'tenant') NOT NULL
is_active BOOLEAN DEFAULT TRUE
is_super_admin BOOLEAN DEFAULT FALSE
suspended_at TIMESTAMP NULL
suspension_reason TEXT NULL

-- Activity Tracking
last_login_at TIMESTAMP NULL
created_at TIMESTAMP NOT NULL
updated_at TIMESTAMP NOT NULL
deleted_at TIMESTAMP NULL -- Soft deletes
```

### Data Integrity Constraints

```sql
-- Role validation
ALTER TABLE users ADD CONSTRAINT users_role_check 
CHECK (role IN ('superadmin', 'admin', 'manager', 'tenant'));

-- Hierarchical constraints
ALTER TABLE users ADD CONSTRAINT users_tenant_hierarchy_check 
CHECK (
    (role = 'superadmin' AND tenant_id IS NULL) OR
    (role IN ('admin', 'manager') AND tenant_id IS NOT NULL) OR
    (role = 'tenant' AND tenant_id IS NOT NULL AND property_id IS NOT NULL)
);

-- Parent relationship constraints
ALTER TABLE users ADD CONSTRAINT users_parent_relationship_check 
CHECK (
    (role = 'tenant' AND parent_user_id IS NOT NULL) OR
    (role IN ('superadmin', 'admin', 'manager') AND parent_user_id IS NULL)
);
```

## Performance Indexes

### Primary Indexes
```sql
-- Tenant isolation (most important)
CREATE INDEX users_tenant_active_idx ON users (tenant_id, is_active);
CREATE INDEX users_tenant_role_idx ON users (tenant_id, role);

-- Authentication
CREATE INDEX users_email_active_idx ON users (email, is_active);
CREATE INDEX users_role_active_idx ON users (role, is_active);

-- Hierarchy
CREATE INDEX users_parent_active_idx ON users (parent_user_id, is_active);
CREATE INDEX users_property_active_idx ON users (property_id, is_active);

-- Activity tracking
CREATE INDEX users_last_login_idx ON users (last_login_at);
CREATE INDEX users_suspended_idx ON users (suspended_at);
CREATE INDEX users_email_verified_idx ON users (email_verified_at);
CREATE INDEX users_deleted_at_idx ON users (deleted_at);
```

### API Token Indexes
```sql
-- Personal access tokens optimization
CREATE INDEX pat_tokenable_name_idx ON personal_access_tokens (tokenable_type, tokenable_id, name);
CREATE INDEX pat_last_used_idx ON personal_access_tokens (last_used_at);
CREATE INDEX pat_expires_at_idx ON personal_access_tokens (expires_at);
CREATE INDEX pat_user_tokenable_idx ON personal_access_tokens (tokenable_id) WHERE tokenable_type = 'App\\Models\\User';
```

### API Token Methods
```php
// Token lifecycle management
public function createApiToken(string $name, ?array $abilities = null): string
public function revokeAllApiTokens(): void
public function getActiveTokensCount(): int
public function hasApiAbility(string $ability): bool

// Sanctum integration
public function tokens() // HasApiTokens trait method
public function currentAccessToken() // HasApiTokens trait method
public function createToken(string $name, array $abilities = ['*']) // HasApiTokens trait method
```

## Relationships

### Core Relationships
- `systemTenant()` - BelongsTo SystemTenant (for superadmin)
- `property()` - BelongsTo Property (for tenant role)
- `parentUser()` - BelongsTo User (admin who created tenant)
- `childUsers()` - HasMany User (tenants created by admin)

### Extended Relationships
- `subscription()` - HasOne Subscription (for admin role)
- `properties()` - HasMany Property (managed by admin)
- `buildings()` - HasMany Building (managed by admin)
- `invoices()` - HasMany Invoice (for admin's organization)
- `meterReadings()` - HasMany MeterReading (entered by user)

### Advanced Relationships
- `organizations()` - BelongsToMany Organization (with roles)
- `taskAssignments()` - BelongsToMany Task (with roles)
- `createdProjects()` - HasMany Project
- `assignedProjects()` - HasMany Project

## Query Scopes

### Basic Scopes
```php
// Status filtering
User::active() // is_active = true AND suspended_at IS NULL
User::suspended() // suspended_at IS NOT NULL
User::unverified() // email_verified_at IS NULL

// Role filtering
User::ofRole(UserRole::ADMIN)
User::admins() // admin, manager, superadmin
User::tenants() // tenant role only

// Tenant filtering
User::ofTenant($tenantId)
User::ofProperty($propertyId)
User::createdBy($parentUserId)
```

### Performance Scopes
```php
// Optimized loading
User::withCommonRelations() // Eager loads property, parentUser, systemTenant
User::apiEligible() // Active, verified, not suspended
User::recentlyActive() // Last login within 30 days

// Ordering
User::orderedByRole() // Superadmin first, then admin, manager, tenant
```

## API Token Management

### Token Creation
```php
// Role-based abilities (automatic based on user role)
$token = $user->createApiToken('mobile-app');

// Custom abilities (override defaults)
$token = $user->createApiToken('limited-access', ['meter-reading:read']);

// Token management
$user->revokeAllApiTokens(); // Security cleanup
$count = $user->getActiveTokensCount(); // Monitor usage
$hasAbility = $user->hasApiAbility('meter-reading:write'); // Check permissions
```

### Token Abilities by Role
- **Superadmin**: `['*']` (all abilities)
- **Admin/Manager**: `['meter-reading:read', 'meter-reading:write', 'property:read', 'invoice:read', 'validation:read', 'validation:write']`
- **Tenant**: `['meter-reading:read', 'meter-reading:write', 'validation:read']`

### Token Security Features
- **Role-based default abilities**: Automatically assigned based on user role
- **Custom ability override**: Specify exact permissions when needed
- **Token revocation**: Bulk revoke all tokens for security incidents
- **Ability checking**: Runtime permission validation for API requests
- **Integration with Sanctum**: Full Laravel Sanctum compatibility

## Caching Strategy

### Cache Keys
```php
// Role checks (1 hour TTL)
"user_role:{user_id}:has_role:{role}:{guard}"

// Panel access (30 minutes TTL)
"panel_access:{user_id}:{panel_id}"

// Query results (15 minutes TTL)
"user_query:tenant:{tenant_id}:{role?}"
"user_query:hierarchy:{admin_id}"
"user_query:stats:{tenant_id}"

// API token statistics (15 minutes TTL)
"user_api_tokens:{tenant_id}:stats"
```

### Cache Invalidation
- User role changes: Clear role and panel access caches
- User status changes: Clear all user-related caches
- Tenant operations: Clear tenant-specific query caches

## Security Considerations

### Data Isolation
- Tenant scoping enforced at query level
- Cross-tenant access prevented by constraints
- Superadmin bypass for system operations

### Authentication Security
- Password hashing with bcrypt
- Email verification required
- Account suspension support
- API token expiration and revocation

### Authorization
- Role-based panel access
- Hierarchical permission inheritance
- Policy-based resource access
- API ability-based restrictions

## Performance Optimizations

### Query Optimization
- Composite indexes for common query patterns
- Eager loading for related data
- Subquery optimization for aggregates
- Bulk operations for mass updates

### Caching
- Multi-layer caching (Redis + application)
- Cache warming for common queries
- Intelligent cache invalidation
- Query result caching

### Database Optimization
- Proper indexing strategy
- Constraint-based data integrity
- Soft deletes for audit trails
- Optimized foreign key relationships

## Testing Strategy

### Unit Tests
- Model relationships and scopes
- Constraint validation
- Cache behavior
- API token functionality

### Integration Tests
- Multi-tenant data isolation
- Hierarchical user creation
- Authentication flows
- Permission inheritance

### Performance Tests
- Query performance under load
- Cache hit rates
- Index effectiveness
- Bulk operation efficiency

## Migration Safety

### Zero-Downtime Considerations
- Additive migrations preferred
- Constraint addition in separate migrations
- Index creation with `ALGORITHM=INPLACE`
- Backfill strategies for data changes

### Rollback Strategy
- All migrations have proper `down()` methods
- Constraint removal before column changes
- Data preservation during schema changes
- Version compatibility maintenance

## Monitoring & Maintenance

### Key Metrics
- User growth by tenant
- Authentication success rates
- API token usage patterns
- Query performance metrics

### Maintenance Tasks
- Expired token cleanup
- Inactive user archival
- Cache optimization
- Index maintenance

## Best Practices

### Development
- Use factories for consistent test data
- Leverage scopes for reusable queries
- Implement proper error handling
- Follow naming conventions

### Production
- Monitor query performance
- Implement proper logging
- Use connection pooling
- Regular backup verification

### Security
- Regular security audits
- Token rotation policies
- Access pattern monitoring
- Vulnerability assessments