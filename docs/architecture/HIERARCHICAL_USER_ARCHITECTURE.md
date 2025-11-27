# Hierarchical User Management Architecture

## Overview

The Vilnius Utilities Billing System implements a three-tier hierarchical user management system with role-based access control and subscription-based limits. This document describes the architecture, data flow, and design patterns used.

**Date**: 2024-11-26  
**Status**: ✅ PRODUCTION READY

## System Architecture

### Three-Tier Hierarchy

```
┌─────────────────────────────────────────────────────────────┐
│                      SUPERADMIN                              │
│  (System Owner - Global Access)                             │
│  • tenant_id: null                                           │
│  • Access: All organizations                                │
│  • Permissions: Full system control                         │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       │ Creates & Manages
                       │
         ┌─────────────┴─────────────┬─────────────────┐
         │                           │                  │
┌────────▼────────┐         ┌────────▼────────┐      ┌▼─────────┐
│   ADMIN (Org 1) │         │   ADMIN (Org 2) │      │  ADMIN   │
│  tenant_id: 1   │         │  tenant_id: 2   │      │  (Org N) │
│  + Subscription │         │  + Subscription │      │  ...     │
└────────┬────────┘         └────────┬────────┘      └──────────┘
         │                           │
         │ Creates & Manages         │ Creates & Manages
         │                           │
    ┌────┴────┬────────┐        ┌───┴────┬────────┐
    │         │        │        │        │        │
┌───▼──┐  ┌──▼──┐  ┌──▼──┐  ┌──▼──┐  ┌──▼──┐  ┌──▼──┐
│TENANT│  │TENANT│  │TENANT│  │TENANT│  │TENANT│  │TENANT│
│Prop A│  │Prop B│  │Prop C│  │Prop D│  │Prop E│  │Prop F│
│t_id:1│  │t_id:1│  │t_id:1│  │t_id:2│  │t_id:2│  │t_id:2│
│p_id:1│  │p_id:2│  │p_id:3│  │p_id:4│  │p_id:5│  │p_id:6│
└──────┘  └──────┘  └──────┘  └──────┘  └──────┘  └──────┘
```

## Data Isolation Strategy

### Multi-Level Scoping

The system implements multi-level data isolation using two key identifiers:

1. **tenant_id**: Organization-level isolation
2. **property_id**: Property-level isolation (for Tenants)

### Scoping Rules

| Role | tenant_id | property_id | Data Access |
|------|-----------|-------------|-------------|
| Superadmin | `null` | `null` | All data (bypasses scopes) |
| Admin | Unique ID | `null` | All data with matching tenant_id |
| Manager | Unique ID | `null` | All data with matching tenant_id |
| Tenant | Inherited | Assigned | Only data for assigned property |

### Implementation

```php
// HierarchicalScope.php
public function apply(Builder $builder, Model $model): void
{
    $user = auth()->user();
    
    if (!$user) {
        return;
    }
    
    // Superadmin bypasses all scopes
    if ($user->role === UserRole::SUPERADMIN) {
        return;
    }
    
    // Admin/Manager: Filter by tenant_id
    if (in_array($user->role, [UserRole::ADMIN, UserRole::MANAGER])) {
        $builder->where($model->getTable() . '.tenant_id', $user->tenant_id);
        return;
    }
    
    // Tenant: Filter by tenant_id AND property_id
    if ($user->role === UserRole::TENANT && $user->property_id) {
        $builder->where($model->getTable() . '.tenant_id', $user->tenant_id)
                ->where($model->getTable() . '.property_id', $user->property_id);
    }
}
```

## Subscription Architecture

### Subscription Model

```
┌─────────────────────────────────────────────────────────────┐
│                    SUBSCRIPTION                              │
├─────────────────────────────────────────────────────────────┤
│  id: int                                                     │
│  user_id: int (FK → users.id)                               │
│  plan_type: enum (basic, professional, enterprise)          │
│  status: enum (active, expired, suspended, cancelled)       │
│  starts_at: timestamp                                        │
│  expires_at: timestamp                                       │
│  max_properties: int                                         │
│  max_tenants: int                                            │
└─────────────────────────────────────────────────────────────┘
```

### Subscription Plans

| Plan | Max Properties | Max Tenants | Features |
|------|---------------|-------------|----------|
| Basic | 10 | 50 | Core billing features |
| Professional | 50 | 200 | Advanced reporting, bulk operations |
| Enterprise | 9999 | 9999 | Custom features, priority support |

### Subscription Lifecycle

```
┌──────────┐
│  ACTIVE  │ ◄─────────────────────────────┐
└────┬─────┘                                │
     │                                      │
     │ expires_at < now()                   │ Renew
     │                                      │
     ▼                                      │
┌──────────┐                          ┌─────┴────┐
│ EXPIRED  │ ─────────────────────────► RENEWED  │
└────┬─────┘  Grace Period (7 days)   └──────────┘
     │         Read-only access
     │
     │ Superadmin action
     │
     ▼
┌──────────┐
│SUSPENDED │
└────┬─────┘
     │
     │ Superadmin action
     │
     ▼
┌──────────┐
│CANCELLED │
└──────────┘
```

### Subscription Enforcement

Subscription limits are enforced at multiple levels:

1. **Middleware Level**: `CheckSubscriptionStatus` middleware
   - Checks subscription status on every request
   - Allows read operations for expired subscriptions (grace period)
   - Blocks write operations for expired/suspended subscriptions

2. **Service Level**: `SubscriptionService::enforceSubscriptionLimits()`
   - Checks before creating properties or tenants
   - Throws exceptions if limits exceeded
   - Validates subscription is active

3. **Model Level**: `Subscription::canAddProperty()` / `canAddTenant()`
   - Helper methods for checking limits
   - Used in forms and validation

## User Creation Flow

### Admin Account Creation

```
┌──────────────┐
│  Superadmin  │
└──────┬───────┘
       │
       │ 1. Create Admin Account
       │    - Generate unique tenant_id
       │    - Set organization_name
       │    - Set role = 'admin'
       │
       ▼
┌──────────────────────────────────────┐
│  AccountManagementService            │
│  ::createAdminAccount()              │
└──────┬───────────────────────────────┘
       │
       │ 2. Create Subscription
       │    - Set plan_type
       │    - Set max_properties/max_tenants
       │    - Set expires_at
       │
       ▼
┌──────────────────────────────────────┐
│  SubscriptionService                 │
│  ::createSubscription()              │
└──────┬───────────────────────────────┘
       │
       │ 3. Log Action
       │    - Create audit log entry
       │
       ▼
┌──────────────────────────────────────┐
│  user_assignments_audit              │
└──────────────────────────────────────┘
```

### Tenant Account Creation

```
┌──────────────┐
│    Admin     │
└──────┬───────┘
       │
       │ 1. Create Tenant Account
       │    - Inherit admin's tenant_id
       │    - Assign property_id
       │    - Set parent_user_id = admin.id
       │    - Set role = 'tenant'
       │
       ▼
┌──────────────────────────────────────┐
│  AccountManagementService            │
│  ::createTenantAccount()             │
└──────┬───────────────────────────────┘
       │
       │ 2. Validate Property Ownership
       │    - Ensure property.tenant_id = admin.tenant_id
       │
       ▼
┌──────────────────────────────────────┐
│  Property Validation                 │
└──────┬───────────────────────────────┘
       │
       │ 3. Check Subscription Limits
       │    - Ensure current_tenants < max_tenants
       │
       ▼
┌──────────────────────────────────────┐
│  SubscriptionService                 │
│  ::enforceSubscriptionLimits()       │
└──────┬───────────────────────────────┘
       │
       │ 4. Create User & Log Action
       │
       ▼
┌──────────────────────────────────────┐
│  User Created + Audit Log            │
└──────┬───────────────────────────────┘
       │
       │ 5. Send Welcome Email
       │
       ▼
┌──────────────────────────────────────┐
│  WelcomeEmail Notification           │
└──────────────────────────────────────┘
```

## Authorization Architecture

### Policy-Based Authorization

All resource access is controlled through Laravel policies:

```php
// UserPolicy.php
public function create(User $user): bool
{
    // Superadmin can create any user
    if ($user->isSuperadmin()) {
        return true;
    }
    
    // Admin can create tenants
    if ($user->isAdmin() || $user->isManager()) {
        return true;
    }
    
    return false;
}

public function update(User $user, User $model): bool
{
    // Superadmin can update any user
    if ($user->isSuperadmin()) {
        return true;
    }
    
    // Admin can update their own tenants
    if (($user->isAdmin() || $user->isManager()) 
        && $model->parent_user_id === $user->id) {
        return true;
    }
    
    // Users can update themselves
    return $user->id === $model->id;
}
```

### Filament Integration

Filament resources use policies for authorization:

```php
// UserResource.php
public static function canViewAny(): bool
{
    return auth()->user()->can('viewAny', User::class);
}

public static function canCreate(): bool
{
    return auth()->user()->can('create', User::class);
}

public static function canEdit(Model $record): bool
{
    return auth()->user()->can('update', $record);
}
```

## Data Flow Patterns

### Property Creation Flow

```
Admin → Check Subscription Limits → Create Property → Apply tenant_id
  │                                        │
  │                                        ▼
  │                                  Audit Log Entry
  │
  └─────────────────────────────────────────────────────────┐
                                                             │
                                                             ▼
                                                    Update Usage Stats
```

### Tenant Reassignment Flow

```
Admin → Validate Property Ownership → Update property_id → Audit Log
  │                                          │
  │                                          ▼
  │                                   Send Notification
  │
  └─────────────────────────────────────────────────────────┐
                                                             │
                                                             ▼
                                                    Preserve History
```

## Database Schema

### Users Table

```sql
CREATE TABLE users (
    id BIGINT PRIMARY KEY,
    tenant_id INT NULL,              -- Organization ID (null for Superadmin)
    property_id BIGINT NULL,         -- Assigned property (for Tenant)
    parent_user_id BIGINT NULL,      -- Admin who created this user
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('superadmin', 'admin', 'manager', 'tenant') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    organization_name VARCHAR(255) NULL,  -- Organization name (for Admin)
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (property_id) REFERENCES properties(id),
    FOREIGN KEY (parent_user_id) REFERENCES users(id),
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_property_id (property_id),
    INDEX idx_parent_user_id (parent_user_id),
    INDEX idx_role (role)
);
```

### Subscriptions Table

```sql
CREATE TABLE subscriptions (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    plan_type ENUM('basic', 'professional', 'enterprise') NOT NULL,
    status ENUM('active', 'expired', 'suspended', 'cancelled') NOT NULL,
    starts_at TIMESTAMP NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    max_properties INT NOT NULL,
    max_tenants INT NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_expires_at (expires_at)
);
```

### User Assignments Audit Table

```sql
CREATE TABLE user_assignments_audit (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    property_id BIGINT NULL,
    previous_property_id BIGINT NULL,
    performed_by BIGINT NOT NULL,
    action VARCHAR(50) NOT NULL,  -- 'created', 'assigned', 'reassigned', 'deactivated', 'reactivated'
    reason TEXT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (property_id) REFERENCES properties(id),
    FOREIGN KEY (previous_property_id) REFERENCES properties(id),
    FOREIGN KEY (performed_by) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_performed_by (performed_by),
    INDEX idx_action (action)
);
```

## Design Patterns

### 1. Service Layer Pattern

Business logic is encapsulated in service classes:

- `AccountManagementService`: User account operations
- `SubscriptionService`: Subscription management
- `TenantContext`: Tenant context management

### 2. Repository Pattern (Implicit)

Eloquent models act as repositories with scopes:

- `HierarchicalScope`: Automatic data filtering
- `TenantScope`: Legacy tenant filtering

### 3. Observer Pattern

Model observers handle side effects:

- Audit logging
- Notifications
- Cache invalidation

### 4. Policy Pattern

Authorization is centralized in policy classes:

- `UserPolicy`
- `PropertyPolicy`
- `SubscriptionPolicy`

### 5. Factory Pattern

Factories create test data with proper relationships:

- `UserFactory`: Creates users with roles
- `SubscriptionFactory`: Creates subscriptions with plans

## Security Considerations

### 1. Data Isolation

- **tenant_id** ensures organization-level isolation
- **property_id** ensures property-level isolation for tenants
- Global scopes automatically filter queries

### 2. Authorization

- All operations checked through policies
- Filament resources respect policy methods
- Middleware enforces subscription status

### 3. Audit Logging

- All account management actions logged
- Audit trail preserved for compliance
- Cannot be deleted or modified

### 4. Password Security

- Passwords hashed using bcrypt
- Password reset tokens expire
- Session regeneration on login

### 5. CSRF Protection

- All forms protected with CSRF tokens
- API endpoints use token authentication

## Performance Optimizations

### 1. Query Optimization

```php
// Eager loading relationships
$users = User::with(['property', 'subscription', 'parentUser'])->get();

// Select only needed columns
$subscription = Subscription::select('id', 'status', 'expires_at')
    ->where('user_id', $userId)
    ->first();
```

### 2. Caching

```php
// Cache tenant ID generation
$maxTenantId = Cache::remember('max_tenant_id', 3600, function () {
    return User::max('tenant_id') ?? 0;
});
```

### 3. Database Indexing

- Indexes on `tenant_id`, `property_id`, `parent_user_id`
- Composite indexes for common queries
- Foreign key indexes for joins

### 4. Lazy Loading Prevention

- Eager load relationships in controllers
- Use `select()` to limit columns
- Avoid N+1 queries with `with()`

## Testing Strategy

### 1. Unit Tests

- Test individual methods in services
- Test model relationships
- Test helper methods

### 2. Feature Tests

- Test complete user flows
- Test authorization
- Test data isolation

### 3. Property-Based Tests

- Test invariants (e.g., tenant isolation)
- Test subscription limits
- Test hierarchical relationships

## Related Documentation

- [Subscription API](../api/SUBSCRIPTION_API.md)
- [User Model API](../api/USER_MODEL_API.md)
- [Account Management Service API](../api/ACCOUNT_MANAGEMENT_API.md)
- [Hierarchical User Guide](../guides/HIERARCHICAL_USER_GUIDE.md)
- [Multi-Tenancy Architecture](./MULTI_TENANCY_ARCHITECTURE.md)

## Changelog

### 2024-11-26 - Initial Documentation
- Created comprehensive hierarchical user architecture documentation
- Documented three-tier hierarchy and data isolation strategy
- Documented subscription architecture and lifecycle
- Added user creation flows and authorization patterns
- Documented database schema and design patterns
- Added security considerations and performance optimizations
