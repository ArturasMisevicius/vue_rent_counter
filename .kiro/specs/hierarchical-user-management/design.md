# Design Document

## Overview

The Hierarchical User Management system implements a three-tier user hierarchy (Superadmin → Admin/Owner → User/Tenant) with subscription-based account creation and strict data isolation. The design extends the existing Laravel authentication system with role-based access control, tenant scoping, and property-based user assignment. The system leverages Laravel's existing multi-tenancy infrastructure while adding hierarchical relationships and subscription management.

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      Presentation Layer                      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  Superadmin  │  │    Admin     │  │     User     │      │
│  │  Dashboard   │  │  Dashboard   │  │  Dashboard   │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────────┐
│                    Authorization Layer                       │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  Role-Based Policies + Hierarchical Scope Filtering  │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────────┐
│                      Business Logic Layer                    │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │Subscription  │  │   Account    │  │   Tenant     │      │
│  │  Service     │  │  Management  │  │  Assignment  │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────────┐
│                         Data Layer                           │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │    Users     │  │Subscriptions │  │  Properties  │      │
│  │   (roles)    │  │   (status)   │  │ (assignments)│      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
```

### Role Hierarchy

```
Superadmin (role: superadmin, tenant_id: null)
    │
    ├─→ Admin 1 (role: admin, tenant_id: 1)
    │       ├─→ User 1.1 (role: tenant, tenant_id: 1, property_id: 1)
    │       ├─→ User 1.2 (role: tenant, tenant_id: 1, property_id: 2)
    │       └─→ User 1.3 (role: tenant, tenant_id: 1, property_id: 3)
    │
    └─→ Admin 2 (role: admin, tenant_id: 2)
            ├─→ User 2.1 (role: tenant, tenant_id: 2, property_id: 4)
            └─→ User 2.2 (role: tenant, tenant_id: 2, property_id: 5)
```

## Components and Interfaces

### 1. User Model Extensions

**File**: `app/Models/User.php`

Add new fields and relationships:
- `role`: enum (superadmin, admin, manager, tenant) - extends existing UserRole enum
- `tenant_id`: nullable foreign key - null for superadmin, set for admin and tenant
- `property_id`: nullable foreign key - only set for tenant role
- `parent_user_id`: nullable foreign key - references the admin who created this user
- `is_active`: boolean - account activation status
- `organization_name`: nullable string - only for admin role

Relationships:
- `belongsTo(User, 'parent_user_id')` - the admin who created this tenant
- `hasMany(User, 'parent_user_id')` - tenants created by this admin
- `belongsTo(Property)` - assigned property for tenant role
- `hasOne(Subscription)` - subscription for admin role

### 2. Subscription Model

**File**: `app/Models/Subscription.php`

Fields:
- `id`: primary key
- `user_id`: foreign key to users (admin only)
- `plan_type`: enum (basic, professional, enterprise)
- `status`: enum (active, expired, suspended, cancelled)
- `starts_at`: timestamp
- `expires_at`: timestamp
- `max_properties`: integer - limit based on plan
- `max_tenants`: integer - limit based on plan

Methods:
- `isActive()`: boolean - checks if subscription is currently active
- `isExpired()`: boolean - checks if subscription has expired
- `daysUntilExpiry()`: integer - days remaining
- `canAddProperty()`: boolean - checks against max_properties limit
- `canAddTenant()`: boolean - checks against max_tenants limit

### 3. Subscription Service

**File**: `app/Services/SubscriptionService.php`

Methods:
- `createSubscription(User $admin, string $planType, Carbon $expiresAt): Subscription`
- `renewSubscription(Subscription $subscription, Carbon $newExpiryDate): Subscription`
- `suspendSubscription(Subscription $subscription, string $reason): void`
- `cancelSubscription(Subscription $subscription): void`
- `checkSubscriptionStatus(User $admin): array` - returns status and limits
- `enforceSubscriptionLimits(User $admin): void` - throws exception if limits exceeded

### 4. Account Management Service

**File**: `app/Services/AccountManagementService.php`

Methods:
- `createAdminAccount(array $data, User $superadmin): User` - creates admin with subscription
- `createTenantAccount(array $data, User $admin, Property $property): User` - creates tenant
- `assignTenantToProperty(User $tenant, Property $property, User $admin): void`
- `reassignTenant(User $tenant, Property $newProperty, User $admin): void`
- `deactivateAccount(User $user, string $reason): void`
- `reactivateAccount(User $user): void`
- `deleteAccount(User $user): void` - soft delete with validation

### 5. Hierarchical Scope

**File**: `app/Scopes/HierarchicalScope.php`

A new global scope that applies hierarchical filtering based on user role:
- Superadmin: no filtering (sees all data)
- Admin: filters to their tenant_id
- Tenant: filters to their tenant_id AND property_id

Methods:
- `apply(Builder $builder, Model $model): void` - applies scope based on authenticated user
- `shouldApplyScope(User $user): boolean` - determines if scope should apply
- `getFilterCriteria(User $user): array` - returns filtering criteria

### 6. Enhanced Policies

Update existing policies to include hierarchical checks:

**File**: `app/Policies/UserPolicy.php`
- `viewAny(User $user)`: superadmin sees all, admin sees their tenants
- `view(User $user, User $model)`: checks hierarchical relationship
- `create(User $user)`: superadmin can create admins, admin can create tenants
- `update(User $user, User $model)`: checks ownership and role
- `delete(User $user, User $model)`: prevents deletion with dependencies

**File**: `app/Policies/PropertyPolicy.php`
- Enhanced to check admin ownership via tenant_id
- Tenant can only view their assigned property

**File**: `app/Policies/SubscriptionPolicy.php` (new)
- `view(User $user, Subscription $subscription)`: superadmin or owner
- `update(User $user, Subscription $subscription)`: superadmin only
- `renew(User $user, Subscription $subscription)`: superadmin or owner

### 7. Middleware

**File**: `app/Http/Middleware/CheckSubscriptionStatus.php` (new)

Middleware to verify admin subscription is active:
- Checks if user is admin role
- Verifies subscription exists and is active
- Redirects to subscription expired page if inactive
- Allows read-only access for expired subscriptions

**File**: `app/Http/Middleware/EnsureHierarchicalAccess.php` (new)

Middleware to enforce hierarchical access:
- Validates user can access requested resource based on hierarchy
- Checks tenant_id and property_id relationships
- Returns 403 if access denied

## Data Models

### Updated User Table Migration

```php
Schema::table('users', function (Blueprint $table) {
    $table->enum('role', ['superadmin', 'admin', 'manager', 'tenant'])
          ->default('tenant')
          ->change();
    $table->foreignId('property_id')
          ->nullable()
          ->constrained('properties')
          ->onDelete('set null');
    $table->foreignId('parent_user_id')
          ->nullable()
          ->constrained('users')
          ->onDelete('set null');
    $table->boolean('is_active')->default(true);
    $table->string('organization_name')->nullable();
    
    $table->index(['tenant_id', 'role']);
    $table->index(['parent_user_id']);
    $table->index(['property_id']);
});
```

### Subscriptions Table Migration

```php
Schema::create('subscriptions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')
          ->constrained('users')
          ->onDelete('cascade');
    $table->enum('plan_type', ['basic', 'professional', 'enterprise'])
          ->default('basic');
    $table->enum('status', ['active', 'expired', 'suspended', 'cancelled'])
          ->default('active');
    $table->timestamp('starts_at');
    $table->timestamp('expires_at');
    $table->integer('max_properties')->default(10);
    $table->integer('max_tenants')->default(50);
    $table->timestamps();
    
    $table->index(['user_id', 'status']);
    $table->index('expires_at');
});
```

### User Assignments Audit Table Migration

```php
Schema::create('user_assignments_audit', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')
          ->constrained('users')
          ->onDelete('cascade');
    $table->foreignId('property_id')
          ->nullable()
          ->constrained('properties')
          ->onDelete('set null');
    $table->foreignId('previous_property_id')
          ->nullable()
          ->constrained('properties')
          ->onDelete('set null');
    $table->foreignId('performed_by')
          ->constrained('users')
          ->onDelete('cascade');
    $table->enum('action', ['created', 'assigned', 'reassigned', 'deactivated', 'reactivated']);
    $table->text('reason')->nullable();
    $table->timestamps();
    
    $table->index(['user_id', 'created_at']);
    $table->index('performed_by');
});
```

## Cor
rectness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Superadmin unrestricted access
*For any* resource and any tenant_id, when a superadmin queries or accesses that resource, the system should return it without tenant scope filtering
**Validates: Requirements 1.4, 12.2, 13.1**

### Property 2: Admin tenant isolation
*For any* admin user and any query, all returned data should have a tenant_id matching the admin's tenant_id
**Validates: Requirements 3.3, 4.3, 12.3**

### Property 3: Tenant property isolation
*For any* tenant user and any query, all returned data should have both the tenant's tenant_id and property_id
**Validates: Requirements 8.2, 9.1, 11.1, 12.4**

### Property 4: Unique tenant_id assignment
*For any* two different admin accounts, their tenant_id values should be unique
**Validates: Requirements 2.2, 3.2**

### Property 5: Tenant inherits admin tenant_id
*For any* tenant account created by an admin, the tenant's tenant_id should equal the admin's tenant_id
**Validates: Requirements 5.2**

### Property 6: Resource creation inherits tenant_id
*For any* resource (building, property, meter) created by an admin, the resource's tenant_id should equal the admin's tenant_id
**Validates: Requirements 4.1, 4.4, 13.2**

### Property 7: Cross-tenant access denial
*For any* admin attempting to access a resource with a different tenant_id, the system should return 404 or 403 error
**Validates: Requirements 12.5, 13.3**

### Property 8: Property assignment validation
*For any* tenant assignment to a property, if the property's tenant_id does not match the admin's tenant_id, the assignment should fail
**Validates: Requirements 5.3, 6.1**

### Property 9: Subscription status affects access
*For any* admin with an expired subscription, write operations should fail while read operations succeed
**Validates: Requirements 3.4**

### Property 10: Subscription renewal restores access
*For any* admin whose subscription is renewed, all operations that were previously restricted should now succeed
**Validates: Requirements 3.5**

### Property 11: Account deactivation prevents login
*For any* user account that is deactivated, login attempts should fail with an appropriate error message
**Validates: Requirements 7.1, 8.4**

### Property 12: Account reactivation restores login
*For any* user account that is reactivated after being deactivated, login attempts should succeed
**Validates: Requirements 7.3**

### Property 13: Audit logging completeness
*For any* account management action (create, assign, reassign, deactivate), an audit log entry should exist with timestamp, actor, and action details
**Validates: Requirements 1.5, 14.1, 14.2, 14.3, 14.4**

### Property 14: Historical data preservation
*For any* tenant reassignment or account deactivation, all historical meter readings and invoices should remain accessible
**Validates: Requirements 6.3, 7.2**

### Property 15: Referential integrity enforcement
*For any* attempt to delete a building with properties or a tenant with historical data, the deletion should fail with an appropriate error
**Validates: Requirements 4.5, 7.5**

### Property 16: Email notification on account actions
*For any* tenant account creation or reassignment, an email notification job should be queued
**Validates: Requirements 5.4, 6.5**

### Property 17: Subscription limits enforcement
*For any* admin attempting to create a property or tenant beyond their subscription limits, the creation should fail
**Validates: Requirements 2.5**

### Property 18: User role-based permissions
*For any* tenant user attempting to perform an action other than meter reading submission, the action should be denied
**Validates: Requirements 13.4**

### Property 19: Data aggregation accuracy
*For any* dashboard displaying counts (properties, tenants, invoices), the displayed count should equal the actual count of matching records
**Validates: Requirements 17.1, 17.3, 18.1**

### Property 20: Profile data completeness
*For any* user viewing their profile, the response should include all required fields for their role (organization name for admin, assigned property for tenant)
**Validates: Requirements 15.1, 16.1**

## Error Handling

### Subscription Errors

**SubscriptionExpiredException**
- Thrown when: Admin attempts write operation with expired subscription
- HTTP Status: 403 Forbidden
- Message: "Your subscription has expired. Please renew to continue managing your properties."
- Recovery: Redirect to subscription renewal page

**SubscriptionLimitExceededException**
- Thrown when: Admin attempts to create resource beyond subscription limits
- HTTP Status: 422 Unprocessable Entity
- Message: "You have reached the maximum number of [properties/tenants] for your plan. Please upgrade your subscription."
- Recovery: Display upgrade options

### Authorization Errors

**UnauthorizedAccessException**
- Thrown when: User attempts to access resource outside their scope
- HTTP Status: 403 Forbidden
- Message: "You do not have permission to access this resource."
- Recovery: Redirect to appropriate dashboard

**CrossTenantAccessException**
- Thrown when: Admin attempts to access another admin's data
- HTTP Status: 404 Not Found
- Message: "Resource not found."
- Recovery: Return to previous page

### Account Management Errors

**AccountDeactivatedException**
- Thrown when: Deactivated user attempts to login
- HTTP Status: 403 Forbidden
- Message: "Your account has been deactivated. Please contact your administrator."
- Recovery: Display admin contact information

**InvalidPropertyAssignmentException**
- Thrown when: Tenant assigned to property from different tenant_id
- HTTP Status: 422 Unprocessable Entity
- Message: "Cannot assign tenant to property from different organization."
- Recovery: Display validation error

**CannotDeleteWithDependenciesException**
- Thrown when: Attempt to delete resource with dependencies
- HTTP Status: 422 Unprocessable Entity
- Message: "Cannot delete [resource] because it has associated [dependencies]. Please deactivate instead."
- Recovery: Suggest deactivation option

### Validation Errors

**DuplicateEmailException**
- Thrown when: Email already exists in system
- HTTP Status: 422 Unprocessable Entity
- Message: "This email address is already registered."
- Recovery: Display validation error on form

**InvalidRoleAssignmentException**
- Thrown when: Invalid role assignment attempted
- HTTP Status: 422 Unprocessable Entity
- Message: "Cannot assign [role] to user in this context."
- Recovery: Display validation error

## Testing Strategy

### Unit Testing

Unit tests will verify individual components and methods:

**Subscription Service Tests:**
- Test subscription creation with valid data
- Test subscription expiry checking logic
- Test subscription limit enforcement
- Test subscription renewal logic

**Account Management Service Tests:**
- Test admin account creation with tenant_id assignment
- Test tenant account creation with property assignment
- Test tenant reassignment logic
- Test account activation/deactivation

**Policy Tests:**
- Test each policy method with different user roles
- Test hierarchical access checks
- Test cross-tenant access denial

**Scope Tests:**
- Test HierarchicalScope applies correct filtering
- Test superadmin bypass logic
- Test admin tenant_id filtering
- Test tenant property_id filtering

### Property-Based Testing

Property-based tests will verify universal properties across randomized inputs using Pest PHP with 100+ iterations:

**Property Test Configuration:**
- Each property test will run 100 iterations minimum
- Tests will use factories to generate random users, properties, and subscriptions
- Tests will be tagged with comments referencing design document properties

**Key Property Tests:**

1. **Tenant Isolation Property Test**: Generate random admins with random properties, verify each admin only sees their own data
2. **Superadmin Access Property Test**: Generate random resources across tenants, verify superadmin sees all
3. **Unique Tenant ID Property Test**: Generate multiple admins, verify all tenant_ids are unique
4. **Inheritance Property Test**: Generate admin and tenants, verify tenants inherit admin's tenant_id
5. **Cross-Tenant Denial Property Test**: Generate two admins with properties, verify admin A cannot access admin B's data
6. **Subscription Limits Property Test**: Generate admin with subscription limits, verify creation fails when limits exceeded
7. **Audit Logging Property Test**: Perform random account actions, verify audit logs exist for all
8. **Data Preservation Property Test**: Reassign tenants, verify historical data remains accessible

### Integration Testing

Integration tests will verify complete workflows:

**Superadmin Workflows:**
- Create admin account → activate subscription → verify admin can login
- View all organizations → verify all admins displayed
- Deactivate subscription → verify admin access restricted

**Admin Workflows:**
- Create building → create properties → create meters → verify all have correct tenant_id
- Create tenant → assign to property → verify tenant can login and see property
- Reassign tenant → verify audit log created and data preserved
- Attempt to exceed subscription limits → verify error thrown

**Tenant Workflows:**
- Login → view property → verify only assigned property visible
- Submit meter reading → verify stored with correct property_id
- View invoices → verify only own property invoices visible
- Attempt to access another property → verify 403 error

### Feature Testing

Feature tests will verify HTTP request/response cycles:

**Authentication Tests:**
- Test login for each role redirects to correct dashboard
- Test deactivated account cannot login
- Test expired subscription shows appropriate message

**Authorization Tests:**
- Test superadmin can access all resources
- Test admin can only access their tenant_id resources
- Test tenant can only access their property_id resources
- Test cross-tenant access returns 404

**Subscription Tests:**
- Test subscription expiry restricts write access
- Test subscription renewal restores access
- Test subscription limits prevent resource creation

**Audit Tests:**
- Test all account actions create audit logs
- Test audit logs contain complete information

## Implementation Notes

### Migration Strategy

1. Add new columns to users table (property_id, parent_user_id, is_active, organization_name)
2. Create subscriptions table
3. Create user_assignments_audit table
4. Update UserRole enum to include 'superadmin'
5. Migrate existing users to new structure (all existing users become 'admin' role)

### Backward Compatibility

- Existing 'admin' role users will be migrated to new 'admin' role with tenant_id
- Existing 'manager' role will be deprecated in favor of 'admin'
- Existing 'tenant' role users will need property_id assignment
- All existing data will be assigned to a default tenant_id during migration

### Performance Considerations

- Index on (tenant_id, role) for efficient user queries
- Index on (user_id, status) for subscription lookups
- Index on (user_id, created_at) for audit log queries
- Consider caching subscription status to reduce database queries
- Use eager loading for user relationships to avoid N+1 queries

### Security Considerations

- Always validate tenant_id matches authenticated user before operations
- Use policies for all authorization checks
- Log all failed authorization attempts
- Encrypt sensitive subscription data
- Rate limit account creation endpoints
- Implement CSRF protection on all forms
- Use secure password hashing (bcrypt with appropriate cost)

### UI/UX Considerations

- Clear visual distinction between user roles in navigation
- Prominent subscription status display for admins
- Easy-to-understand error messages for authorization failures
- Breadcrumbs showing hierarchical relationships
- Confirmation dialogs for destructive actions
- Inline help text for subscription limits
- Mobile-responsive design for all dashboards

## Dependencies

### External Dependencies

- Laravel 11 framework
- Laravel Sanctum or Passport for API authentication (if needed)
- Laravel Notifications for email sending
- Laravel Queue for background job processing

### Internal Dependencies

- Existing User model and authentication system
- Existing Property and Building models
- Existing TenantScope for multi-tenancy
- Existing Policy infrastructure
- Existing Meter and Invoice models

### Database Dependencies

- SQLite with foreign key constraints enabled
- Write-Ahead Logging (WAL) mode for concurrent access

## Deployment Considerations

### Database Migrations

1. Run migrations in order: users table updates, subscriptions table, audit table
2. Seed initial superadmin account
3. Migrate existing users to new structure
4. Verify data integrity after migration

### Configuration

Add to `.env`:
```
SUBSCRIPTION_GRACE_PERIOD_DAYS=7
SUBSCRIPTION_EXPIRY_WARNING_DAYS=14
MAX_PROPERTIES_BASIC=10
MAX_PROPERTIES_PROFESSIONAL=50
MAX_PROPERTIES_ENTERPRISE=unlimited
MAX_TENANTS_BASIC=50
MAX_TENANTS_PROFESSIONAL=200
MAX_TENANTS_ENTERPRISE=unlimited
```

### Monitoring

- Monitor subscription expiry dates
- Track subscription limit usage
- Monitor failed authorization attempts
- Track account creation rates
- Monitor audit log growth

### Rollback Plan

If issues arise:
1. Disable new hierarchical features via feature flag
2. Revert to previous user role system
3. Restore database from backup if necessary
4. Investigate and fix issues
5. Re-enable features after validation
