# AccountManagementService Documentation

## Overview

The `AccountManagementService` is a core service responsible for managing hierarchical user accounts in the multi-tenant utilities billing platform. It handles the complete lifecycle of admin and tenant accounts, including creation, property assignment, activation/deactivation, and deletion with proper audit logging.

**Location**: `app/Services/AccountManagementService.php`

**Dependencies**:
- `SubscriptionService` - Manages subscription lifecycle
- `User` model - User entity
- `Property` model - Property entity
- Custom exceptions for domain-specific errors

## Architecture

### Service Pattern
This service implements the Service Layer pattern, encapsulating complex business logic for account management operations. All operations are wrapped in database transactions to ensure data consistency.

### Hierarchical User Model
The service supports a three-tier user hierarchy:
1. **Superadmin** - Platform-level administrators
2. **Admin** - Organization owners with unique `tenant_id`
3. **Tenant** - Property residents inheriting admin's `tenant_id`

### Multi-Tenancy
All operations enforce tenant isolation through:
- Unique `tenant_id` generation for admins
- Tenant ID inheritance for tenant users
- Property ownership validation
- Cross-tenant operation prevention

### Audit Trail
Every account action is logged to the `user_assignments_audit` table with:
- User being acted upon
- Action performed (created, assigned, reassigned, deactivated, reactivated)
- User performing the action
- Property context (for assignments)
- Optional reason (for deactivations)
- Timestamps

## Public Methods

### createAdminAccount()

Creates a new admin account with optional subscription.

**Signature**:
```php
public function createAdminAccount(array $data, User $superadmin): User
```

**Parameters**:
- `$data` (array) - Account data with keys:
  - `name` (string, required) - Admin's full name
  - `email` (string, required) - Unique email address
  - `password` (string, required) - Plain text password (min 8 chars)
  - `organization_name` (string, required) - Organization name
  - `plan_type` (string, optional) - Subscription plan: basic, professional, enterprise
  - `expires_at` (string, optional) - Subscription expiry date (ISO 8601)
- `$superadmin` (User) - The superadmin creating this account

**Returns**: `User` - The created admin user with loaded subscription relationship

**Throws**:
- `ValidationException` - If validation fails

**Requirements**: 2.1, 2.2, 3.1, 3.2

**Example**:
```php
$accountService = app(AccountManagementService::class);

$admin = $accountService->createAdminAccount([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'SecurePass123',
    'organization_name' => 'Acme Properties',
    'plan_type' => 'professional',
    'expires_at' => '2025-12-31',
], $superadmin);

// Admin now has unique tenant_id and active subscription
echo $admin->tenant_id; // e.g., 347821
echo $admin->subscription->plan_type; // 'professional'
```

**Process Flow**:
1. Validates input data
2. Generates unique random `tenant_id` (6-digit, collision-checked)
3. Creates admin user with `is_active = true`
4. Creates subscription if `plan_type` provided (defaults to 1 year)
5. Logs creation action to audit table
6. Returns admin with loaded subscription

**Security Notes**:
- Tenant ID generation uses random 6-digit numbers to prevent enumeration attacks
- Passwords are hashed using Laravel's `Hash::make()`
- All operations wrapped in database transaction

---

### createTenantAccount()

Creates a tenant account and assigns them to a property.

**Signature**:
```php
public function createTenantAccount(array $data, User $admin): User
```

**Parameters**:
- `$data` (array) - Tenant data with keys:
  - `name` (string, required) - Tenant's full name
  - `email` (string, required) - Unique email address
  - `password` (string, required) - Plain text password (min 8 chars)
  - `property_id` (int, required) - Property to assign tenant to
- `$admin` (User) - The admin creating this tenant

**Returns**: `User` - The created tenant user with loaded property and parentUser relationships

**Throws**:
- `ValidationException` - If validation fails
- `InvalidPropertyAssignmentException` - If property doesn't belong to admin's organization

**Requirements**: 5.1, 5.2, 5.3, 5.4

**Example**:
```php
$tenant = $accountService->createTenantAccount([
    'name' => 'Jane Smith',
    'email' => 'jane@example.com',
    'password' => 'TenantPass123',
    'property_id' => 42,
], $admin);

// Tenant inherits admin's tenant_id
echo $tenant->tenant_id; // Same as $admin->tenant_id
echo $tenant->property_id; // 42
echo $tenant->parent_user_id; // $admin->id
```

**Process Flow**:
1. Validates input data
2. Fetches and validates property ownership
3. Creates tenant user inheriting admin's `tenant_id`
4. Sets `parent_user_id` to admin's ID
5. Logs creation action with property context
6. Queues welcome email notification
7. Returns tenant with loaded relationships

**Security Notes**:
- Validates property belongs to admin's organization
- Prevents cross-tenant property assignments
- Password included in welcome email (optional)

---

### assignTenantToProperty()

Assigns an existing tenant to a property (initial assignment).

**Signature**:
```php
public function assignTenantToProperty(User $tenant, Property $property, User $admin): void
```

**Parameters**:
- `$tenant` (User) - The tenant to assign
- `$property` (Property) - The property to assign to
- `$admin` (User) - The admin performing the assignment

**Throws**:
- `InvalidPropertyAssignmentException` - If property or tenant doesn't belong to admin's organization

**Requirements**: 6.1, 6.2, 6.3, 6.4, 6.5

**Example**:
```php
$accountService->assignTenantToProperty($tenant, $property, $admin);

// Tenant now assigned to property
echo $tenant->fresh()->property_id; // $property->id
```

**Process Flow**:
1. Validates property belongs to admin's organization
2. Validates tenant belongs to admin's organization
3. Updates tenant's `property_id`
4. Logs assignment action with previous property context
5. No email notification sent (use for initial assignments)

---

### reassignTenant()

Reassigns a tenant from one property to another.

**Signature**:
```php
public function reassignTenant(User $tenant, Property $newProperty, User $admin): void
```

**Parameters**:
- `$tenant` (User) - The tenant to reassign
- `$newProperty` (Property) - The new property to assign to
- `$admin` (User) - The admin performing the reassignment

**Throws**:
- `InvalidPropertyAssignmentException` - If property or tenant doesn't belong to admin's organization

**Requirements**: 6.1, 6.2, 6.3, 6.4, 6.5

**Example**:
```php
$accountService->reassignTenant($tenant, $newProperty, $admin);

// Tenant reassigned and notified
echo $tenant->fresh()->property_id; // $newProperty->id
```

**Process Flow**:
1. Validates new property belongs to admin's organization
2. Validates tenant belongs to admin's organization
3. Captures previous property for notification
4. Updates tenant's `property_id`
5. Logs reassignment action with both property IDs
6. Queues reassignment email notification

**Difference from assignTenantToProperty()**:
- Sends email notification to tenant
- Includes previous property information in audit log
- Intended for moving existing tenants between properties

---

### deactivateAccount()

Deactivates a user account (soft disable).

**Signature**:
```php
public function deactivateAccount(User $user, ?string $reason = null): void
```

**Parameters**:
- `$user` (User) - The user to deactivate
- `$reason` (string, optional) - Reason for deactivation

**Requirements**: 7.1, 7.2, 7.3, 7.4

**Example**:
```php
$accountService->deactivateAccount($tenant, 'Lease ended');

// User cannot log in but data preserved
echo $tenant->fresh()->is_active; // false
```

**Process Flow**:
1. Sets `is_active = false`
2. Logs deactivation action with optional reason
3. Preserves all historical data
4. User cannot authenticate but data remains

**Use Cases**:
- Tenant moves out
- Admin account suspended
- Temporary access revocation

---

### reactivateAccount()

Reactivates a previously deactivated account.

**Signature**:
```php
public function reactivateAccount(User $user): void
```

**Parameters**:
- `$user` (User) - The user to reactivate

**Requirements**: 7.1, 7.2, 7.3, 7.4

**Example**:
```php
$accountService->reactivateAccount($tenant);

// User can log in again
echo $tenant->fresh()->is_active; // true
```

**Process Flow**:
1. Sets `is_active = true`
2. Logs reactivation action
3. User can authenticate immediately

---

### deleteAccount()

Permanently deletes a user account with dependency validation.

**Signature**:
```php
public function deleteAccount(User $user): void
```

**Parameters**:
- `$user` (User) - The user to delete

**Throws**:
- `CannotDeleteWithDependenciesException` - If user has meter readings or child users

**Requirements**: 7.5

**Example**:
```php
try {
    $accountService->deleteAccount($tenant);
} catch (CannotDeleteWithDependenciesException $e) {
    // User has dependencies, suggest deactivation instead
    echo $e->getMessage(); // "Cannot delete user because it has associated meter readings. Please deactivate instead."
}
```

**Process Flow**:
1. Checks for meter readings
2. Checks for child users (for admins)
3. Throws exception if dependencies exist
4. Permanently deletes user if no dependencies
5. Does NOT log deletion (user record removed)

**Dependency Checks**:
- Meter readings: Prevents data loss
- Child users: Prevents orphaning tenant accounts

**Best Practice**: Use `deactivateAccount()` instead of deletion to preserve historical data.

---

## Protected Methods

### validateAdminAccountData()

Validates admin account creation data.

**Validation Rules**:
- `name`: required, string, max 255 chars
- `email`: required, valid email, unique in users table
- `password`: required, string, min 8 chars
- `organization_name`: required, string, max 255 chars
- `plan_type`: optional, must be: basic, professional, enterprise
- `expires_at`: optional, valid date, must be after today

---

### validateTenantAccountData()

Validates tenant account creation data.

**Validation Rules**:
- `name`: required, string, max 255 chars
- `email`: required, valid email, unique in users table
- `password`: required, string, min 8 chars
- `property_id`: required, must exist in properties table

---

### generateUniqueTenantId()

Generates a unique 6-digit tenant ID with collision checking.

**Returns**: `int` - Random 6-digit number (100000-999999)

**Security Features**:
- Uses `random_int()` for cryptographically secure randomness
- Checks for collisions before returning
- Prevents tenant enumeration attacks
- Hides total tenant count

**Example Output**: 347821, 892341, 156789

---

### logAccountAction()

Logs account management actions to audit table.

**Parameters**:
- `$user` (User) - User being acted upon
- `$action` (string) - Action: created, assigned, reassigned, deactivated, reactivated
- `$performedBy` (User|null) - User performing action (defaults to user's own ID)
- `$propertyId` (int|null) - Property context for assignments
- `$previousPropertyId` (int|null) - Previous property for reassignments
- `$reason` (string|null) - Optional reason for action

**Audit Table**: `user_assignments_audit`

**Logged Data**:
- User ID
- Action performed
- Performer ID
- Property context
- Reason (if provided)
- Timestamps

---

## Error Handling

### ValidationException

Thrown when input data fails validation.

**Common Causes**:
- Invalid email format
- Duplicate email address
- Password too short
- Missing required fields
- Invalid subscription plan type

**Example**:
```php
try {
    $admin = $accountService->createAdminAccount($data, $superadmin);
} catch (ValidationException $e) {
    $errors = $e->errors();
    // ['email' => ['The email has already been taken.']]
}
```

---

### InvalidPropertyAssignmentException

Thrown when attempting cross-tenant operations.

**Common Causes**:
- Property belongs to different organization
- Tenant belongs to different organization
- Property doesn't exist

**Example**:
```php
try {
    $accountService->createTenantAccount($data, $admin);
} catch (InvalidPropertyAssignmentException $e) {
    echo $e->getMessage(); // "Cannot assign tenant to property from different organization."
}
```

---

### CannotDeleteWithDependenciesException

Thrown when attempting to delete user with dependencies.

**Dependencies Checked**:
- Meter readings
- Child users (for admins)

**Example**:
```php
try {
    $accountService->deleteAccount($tenant);
} catch (CannotDeleteWithDependenciesException $e) {
    // Suggest deactivation instead
    $accountService->deactivateAccount($tenant, 'Account closure requested');
}
```

---

## Usage Examples

### Complete Admin Onboarding Flow

```php
use App\Services\AccountManagementService;

$accountService = app(AccountManagementService::class);

// 1. Superadmin creates admin account
$admin = $accountService->createAdminAccount([
    'name' => 'Property Manager',
    'email' => 'manager@properties.com',
    'password' => 'SecurePass123',
    'organization_name' => 'Downtown Properties LLC',
    'plan_type' => 'professional',
    'expires_at' => '2025-12-31',
], $superadmin);

// 2. Admin creates tenant for property
$tenant = $accountService->createTenantAccount([
    'name' => 'John Tenant',
    'email' => 'john@example.com',
    'password' => 'TenantPass123',
    'property_id' => $property->id,
], $admin);

// 3. Tenant receives welcome email with credentials
// Email queued automatically

// 4. Later, reassign tenant to different property
$accountService->reassignTenant($tenant, $newProperty, $admin);
// Tenant receives reassignment notification
```

---

### Account Lifecycle Management

```php
// Deactivate tenant when lease ends
$accountService->deactivateAccount($tenant, 'Lease ended - moved out');

// Reactivate if tenant returns
$accountService->reactivateAccount($tenant);

// Attempt deletion (will fail if has meter readings)
try {
    $accountService->deleteAccount($tenant);
} catch (CannotDeleteWithDependenciesException $e) {
    // Keep deactivated instead
    Log::info('Cannot delete tenant with meter readings', [
        'tenant_id' => $tenant->id,
        'reason' => $e->getMessage(),
    ]);
}
```

---

### Bulk Tenant Creation

```php
$tenants = [
    ['name' => 'Tenant 1', 'email' => 'tenant1@example.com', 'property_id' => 1],
    ['name' => 'Tenant 2', 'email' => 'tenant2@example.com', 'property_id' => 2],
    ['name' => 'Tenant 3', 'email' => 'tenant3@example.com', 'property_id' => 3],
];

foreach ($tenants as $tenantData) {
    $tenantData['password'] = Str::random(12); // Generate random password
    
    try {
        $tenant = $accountService->createTenantAccount($tenantData, $admin);
        Log::info('Tenant created', ['tenant_id' => $tenant->id]);
    } catch (InvalidPropertyAssignmentException $e) {
        Log::error('Failed to create tenant', [
            'data' => $tenantData,
            'error' => $e->getMessage(),
        ]);
    }
}
```

---

## Testing

### Unit Tests

Located in: `tests/Unit/AccountManagementServiceTest.php`

**Test Coverage**:
- Admin account creation with subscription
- Tenant account creation with property assignment
- Cross-tenant property assignment prevention
- Tenant reassignment with notification
- Account deactivation and reactivation
- Account deletion with dependency validation

**Example Test**:
```php
public function test_creates_admin_with_unique_tenant_id(): void
{
    $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
    
    $admin = $this->service->createAdminAccount([
        'name' => 'Test Admin',
        'email' => 'admin@test.com',
        'password' => 'password123',
        'organization_name' => 'Test Org',
        'plan_type' => 'professional',
        'expires_at' => '2025-12-31',
    ], $superadmin);
    
    $this->assertNotNull($admin->tenant_id);
    $this->assertEquals('admin', $admin->role->value);
    $this->assertTrue($admin->is_active);
    $this->assertNotNull($admin->subscription);
}
```

---

### Feature Tests

Located in: `tests/Feature/HierarchicalUserManagementTest.php`

**Integration Tests**:
- Complete hierarchical user structure creation
- Multi-tenant isolation verification
- Audit logging verification
- Email notification verification

---

## Security Considerations

### Tenant ID Generation
- Uses cryptographically secure `random_int()`
- 6-digit range provides 1 million possible values
- Collision checking prevents duplicates
- Prevents tenant enumeration attacks
- Hides total tenant count from potential attackers

### Password Handling
- Passwords hashed using `Hash::make()` (bcrypt)
- Plain text passwords never stored
- Passwords optionally included in welcome emails

### Multi-Tenancy Enforcement
- All operations validate tenant ownership
- Cross-tenant operations explicitly prevented
- Property ownership checked before assignments
- Tenant ownership checked before reassignments

### Audit Trail
- All actions logged with performer context
- Property context preserved for assignments
- Timestamps recorded for all actions
- Audit logs cannot be deleted (user deletion doesn't remove logs)

### Transaction Safety
- All operations wrapped in database transactions
- Rollback on any failure
- Atomic operations ensure data consistency

---

## Performance Considerations

### Database Queries
- Uses `exists()` instead of `count()` for dependency checks
- Eager loads relationships with `fresh(['subscription'])`
- Single query for tenant ID collision check
- Batch operations should use queues for large datasets

### Optimization Tips
```php
// Good: Check existence only
$hasMeterReadings = $user->meterReadings()->exists();

// Bad: Count all records
$hasMeterReadings = $user->meterReadings()->count() > 0;
```

---

## Related Documentation

- [Hierarchical User Management Spec](.kiro/specs/3-hierarchical-user-management/)
- [SubscriptionService Documentation](./SubscriptionService.md)
- [User Model Documentation](../models/User.md)
- [Multi-Tenancy Architecture](../architecture/multi-tenancy.md)
- [Audit Logging Guide](../guides/audit-logging.md)

---

## Changelog

### 2025-11-25 - Security Improvements
- Changed tenant ID generation from sequential to random (security fix)
- Prevents tenant enumeration attacks
- Hides total tenant count
- Maintains uniqueness through collision checking

### 2025-11-25 - Refactoring
- Extracted validation to dedicated methods
- Improved error messages with specific dependency details
- Added requirement references to all methods
- Simplified subscription creation logic
- Enhanced audit logging with fallback for performer ID

---

## API Reference

### Method Summary

| Method | Purpose | Transaction | Notifications | Audit Log |
|--------|---------|-------------|---------------|-----------|
| `createAdminAccount()` | Create admin with subscription | Yes | No | Yes |
| `createTenantAccount()` | Create tenant for property | Yes | Welcome email | Yes |
| `assignTenantToProperty()` | Initial property assignment | Yes | No | Yes |
| `reassignTenant()` | Move tenant to new property | Yes | Reassignment email | Yes |
| `deactivateAccount()` | Soft disable account | Yes | No | Yes |
| `reactivateAccount()` | Re-enable account | Yes | No | Yes |
| `deleteAccount()` | Permanently delete account | No | No | No |

---

## Support

For questions or issues:
- Review the [Hierarchical User Management Spec](.kiro/specs/3-hierarchical-user-management/)
- Check [Feature Tests](../../tests/Feature/HierarchicalUserManagementTest.php)
- Consult [Multi-Tenancy Documentation](../architecture/multi-tenancy.md)
