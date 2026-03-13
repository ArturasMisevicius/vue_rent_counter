# MeterReadingPolicy Documentation

## Overview

The `MeterReadingPolicy` implements authorization for meter reading operations with support for the **Truth-but-Verify workflow** (Gold Master v7.0). This policy enables tenants to submit meter readings that require manager approval, while maintaining strict tenant isolation and role-based access control.

## Key Changes in Truth-but-Verify Implementation

### Tenant Reading Creation Rights

**Previous Behavior:**
- Only Admins and Managers could create meter readings
- Tenants had read-only access

**New Behavior (Gold Master v7.0):**
- **All authenticated roles** can create meter readings
- Tenants can submit readings for manager approval
- Tenant submissions automatically require validation
- Managers approve/reject tenant submissions

### Authorization Matrix

| Action | Superadmin | Admin | Manager | Tenant | Notes |
|--------|------------|-------|---------|--------|-------|
| `viewAny()` | ✅ | ✅ | ✅ | ✅ | All roles can view (scoped) |
| `view()` | ✅ | ✅ | ✅ (tenant-scoped) | ✅ (property-scoped) | Tenant boundary enforced |
| `create()` | ✅ | ✅ | ✅ | ✅ | **NEW**: Tenants can create |
| `createForMeter()` | ✅ | ✅ | ✅ | ✅ (meter-scoped) | Additional tenant validation |
| `update()` | ✅ | ✅ | ✅ (tenant-scoped) | ❌ | Tenants cannot edit after submit |
| `approve()` | ✅ | ✅ | ✅ (tenant-scoped) | ❌ | Manager approval workflow |
| `reject()` | ✅ | ✅ | ✅ (tenant-scoped) | ❌ | Manager rejection workflow |
| `delete()` | ✅ | ✅ | ❌ | ❌ | Admin-only deletion |
| `forceDelete()` | ✅ | ❌ | ❌ | ❌ | Superadmin-only |
| `export()` | ✅ | ✅ | ✅ | ✅ | All roles (scoped) |
| `import()` | ✅ | ✅ | ✅ | ❌ | Manager+ only |

## Truth-but-Verify Workflow

### 1. Tenant Submission
```php
// Tenant creates meter reading
$user = User::factory()->create(['role' => UserRole::TENANT]);
$policy->create($user); // Returns true (NEW)

// Reading is created with PENDING status
$reading = MeterReading::create([
    'validation_status' => ValidationStatus::PENDING,
    'entered_by' => $user->id,
    // ... other fields
]);
```

### 2. Manager Review
```php
// Manager can approve pending readings
$manager = User::factory()->create(['role' => UserRole::MANAGER]);
$policy->approve($manager, $reading); // Returns true if:
// - User is manager+ role
// - Reading is in same tenant
// - Reading requires validation
// - Reading status is PENDING
```

### 3. Approval/Rejection
```php
// Approve reading
$reading->markAsValidated($manager->id);

// Reject reading with reason
$reading->validation_notes = 'Reading seems too high';
$reading->markAsRejected($manager->id);
```

## Policy Methods

### Core Authorization Methods

#### `viewAny(User $user): bool`
- **Purpose**: Determine if user can view meter readings list
- **Authorization**: All authenticated roles
- **Scope**: Results filtered by tenant/property boundaries

#### `view(User $user, MeterReading $meterReading): bool`
- **Purpose**: Determine if user can view specific meter reading
- **Authorization**:
  - Superadmin: All readings
  - Admin: All readings
  - Manager: Same tenant only
  - Tenant: Own property only (via `TenantBoundaryService`)

#### `create(User $user): bool`
- **Purpose**: Determine if user can create meter readings
- **Authorization**: All authenticated roles (**NEW in v7.0**)
- **Audit**: Logs tenant creation attempts with workflow context

#### `createForMeter(User $user, int $meterId): bool`
- **Purpose**: Additional validation for meter-specific creation
- **Authorization**: Delegates to `create()` + meter boundary check for tenants
- **Tenant Validation**: Uses `TenantBoundaryService::canTenantSubmitReadingForMeter()`

### Workflow-Specific Methods

#### `approve(User $user, MeterReading $meterReading): bool`
- **Purpose**: Authorize reading approval in Truth-but-Verify workflow
- **Authorization**: Manager+ roles only
- **Requirements**:
  - User must be manager or higher
  - Reading must be in same tenant (for managers)
  - Reading must require validation
  - Reading status must be PENDING
- **Audit**: Logs approval attempts with validation context

#### `reject(User $user, MeterReading $meterReading): bool`
- **Purpose**: Authorize reading rejection in Truth-but-Verify workflow
- **Authorization**: Same as `approve()` method
- **Usage**: Allows managers to reject readings with validation notes

### Administrative Methods

#### `update(User $user, MeterReading $meterReading): bool`
- **Purpose**: Authorize reading modifications
- **Authorization**: Admin/Manager only (Tenants excluded from editing)
- **Rationale**: Prevents tenant tampering after submission

#### `delete(User $user, MeterReading $meterReading): bool`
- **Purpose**: Authorize reading deletion
- **Authorization**: Admin+ only
- **Rationale**: Tenant readings should be rejected, not deleted

## Tenant Boundary Enforcement

### TenantBoundaryService Integration

The policy integrates with `TenantBoundaryService` for tenant-specific authorization:

```php
// Check if tenant can access meter reading
$canAccess = $this->tenantBoundaryService->canTenantAccessMeterReading($user, $meterReading);

// Check if tenant can submit reading for specific meter
$canSubmit = $this->tenantBoundaryService->canTenantSubmitReadingForMeter($user, $meterId);
```

### Tenant Scoping Rules

1. **Property-Based Access**: Tenants can only access readings for meters on their assigned properties
2. **Meter Validation**: Additional validation ensures tenants can only submit readings for accessible meters
3. **Cross-Tenant Prevention**: Managers cannot access readings outside their tenant scope

## Security Features

### Audit Logging

The policy logs sensitive operations for compliance:

```php
$this->logSensitiveOperation('approve', $user, $meterReading, [
    'validation_status' => $meterReading->validation_status->value,
    'input_method' => $meterReading->input_method->value,
]);
```

**Logged Operations:**
- Tenant creation attempts (with workflow context)
- Reading updates (with user/reading context)
- Approval/rejection actions (with validation context)
- Deletion attempts (with reading metadata)

### Role Constants

The policy uses role constants for maintainability:

```php
private const READING_CREATORS = [UserRole::SUPERADMIN, UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT];
private const READING_MANAGERS = [UserRole::SUPERADMIN, UserRole::ADMIN, UserRole::MANAGER];
```

## Integration with Filament

### Resource Actions

The policy integrates with Filament resource actions:

```php
// Approve action (visible for pending/review readings)
Action::make('approve')
    ->visible(fn (MeterReading $record): bool => 
        $record->validation_status === ValidationStatus::PENDING || 
        $record->validation_status === ValidationStatus::REQUIRES_REVIEW
    )
    ->authorize('approve', $record);

// Reject action with validation notes
Action::make('reject')
    ->form([
        Textarea::make('validation_notes')
            ->label('Rejection Reason')
            ->required()
    ])
    ->authorize('reject', $record);
```

### Navigation Visibility

Resources use policy methods to control navigation:

```php
public static function canViewAny(): bool
{
    return auth()->user()->can('viewAny', MeterReading::class);
}
```

## Testing

### Policy Test Coverage

The policy includes comprehensive test coverage:

```php
/** @test */
public function all_roles_can_create_meter_readings(): void
{
    $roles = [UserRole::SUPERADMIN, UserRole::ADMIN, UserRole::MANAGER, UserRole::TENANT];

    foreach ($roles as $role) {
        $user = User::factory()->create(['role' => $role]);
        $this->assertTrue($this->policy->create($user));
    }
}
```

### Truth-but-Verify Workflow Tests

```php
/** @test */
public function manager_can_approve_pending_reading_in_same_tenant(): void
{
    $user = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 1]);
    $reading = MeterReading::factory()->create([
        'tenant_id' => 1,
        'validation_status' => ValidationStatus::PENDING,
    ]);

    $this->assertTrue($this->policy->approve($user, $reading));
}
```

## Requirements Compliance

### Functional Requirements

- **11.1**: ✅ Role verification using Laravel Policies
- **11.3**: ✅ Manager creation and update capabilities
- **11.4**: ✅ Tenant property-scoped access
- **7.3**: ✅ Cross-tenant access prevention

### Gold Master v7.0 Requirements

- **Tenant Input**: ✅ Tenants can create meter readings
- **Manager Approval**: ✅ Managers can approve/reject tenant submissions
- **Workflow Integrity**: ✅ Tenants cannot edit after submission
- **Audit Trail**: ✅ All workflow actions are logged

## Migration Impact

### Breaking Changes

1. **Tenant Creation Rights**: Tenants can now create meter readings (previously read-only)
2. **Workflow Requirements**: New approval/rejection methods required
3. **Validation Status**: Readings now require validation status tracking

### Backward Compatibility

- Existing admin/manager workflows remain unchanged
- No changes to viewing permissions
- Export/import permissions preserved

## Related Documentation

- [User Model Meter Reading Capabilities](../models/USER_METER_READING_CAPABILITIES.md)
- [TenantBoundaryService](../services/TENANT_BOUNDARY_SERVICE.md)
- [MeterReading Model](../models/METER_READING.md)
- [Truth-but-Verify Workflow](../workflows/TRUTH_BUT_VERIFY.md)
- [Filament MeterReadingResource](../filament/METER_READING_RESOURCE.md)

## Changelog

### v7.0 - Truth-but-Verify Implementation
- **Added**: Tenant creation rights (`create()` method)
- **Added**: Approval workflow methods (`approve()`, `reject()`)
- **Added**: Meter-specific validation (`createForMeter()`)
- **Added**: Audit logging for workflow operations
- **Updated**: Role constants to include tenants
- **Updated**: Documentation and test coverage