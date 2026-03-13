# MeterReadingPolicy API Documentation

## Overview

The `MeterReadingPolicy` class provides authorization logic for meter reading operations in the multi-tenant utilities billing platform. It implements configurable workflow strategies to balance security with operational efficiency.

## Key Features

- **Configurable Workflow Strategies**: Supports Permissive and Truth-but-Verify workflows
- **Tenant Boundary Validation**: Ensures complete data isolation between tenants
- **Structured Authorization Results**: Uses `PolicyResult` value objects for detailed authorization context
- **Comprehensive Audit Logging**: Logs all authorization decisions with full context
- **Performance Optimized**: Caches authorization decisions where appropriate

## Workflow Strategies

### Permissive Workflow (Default)

The Permissive workflow enables tenant self-service while maintaining security boundaries:

- **Tenant Self-Service**: Tenants can edit/delete their OWN readings ONLY IF status is 'pending'
- **Business Justification**: Allows tenants to correct mistakes before manager approval
- **Security**: Limited to pending status and ownership validation
- **Audit**: All tenant modifications logged for compliance

### Truth-but-Verify Workflow

The Truth-but-Verify workflow prioritizes security over operational efficiency:

- **Strict Control**: Tenants cannot modify readings once submitted
- **Manager Authority**: Only managers and above can modify any readings
- **Use Case**: High-security environments or regulatory compliance requirements

## Authorization Methods

### Core CRUD Operations

#### `viewAny(User $user): bool`

Determines if user can view any meter readings.

**Authorization Rules:**
- All authenticated roles can view meter readings (automatically scoped by tenant)

**Returns:** `true` for all authenticated users

---

#### `view(User $user, MeterReading $meterReading): bool`

Determines if user can view a specific meter reading.

**Authorization Rules:**
- **Superadmin**: Can view any meter reading
- **Admin/Manager**: Can view readings within their tenant
- **Tenant**: Can view readings for their properties only

**Parameters:**
- `$user` - The authenticated user
- `$meterReading` - The meter reading to view

**Returns:** `true` if authorized, `false` otherwise

---

#### `create(User $user): bool`

Determines if user can create meter readings.

**Authorization Rules:**
- **Superadmin/Admin/Manager/Tenant**: Can create meter readings
- **Tenant**: Submissions require manager approval (workflow-dependent)

**Parameters:**
- `$user` - The authenticated user

**Returns:** `true` if authorized, `false` otherwise

---

#### `update(User $user, MeterReading $meterReading): bool`

Determines if user can update a meter reading.

**Authorization Rules:**
- **Superadmin**: Can update any meter reading
- **Admin/Manager**: Can update readings within their tenant
- **Tenant**: Workflow-dependent permissions:
  - **Permissive**: Can update OWN readings IF status is 'pending'
  - **Truth-but-Verify**: Cannot update readings once submitted

**Parameters:**
- `$user` - The authenticated user
- `$meterReading` - The meter reading to update

**Returns:** `true` if authorized, `false` otherwise

**Example Usage:**
```php
// In Filament Resource
public static function canEdit(Model $record): bool
{
    return auth()->user()->can('update', $record);
}

// In Controller
public function update(UpdateMeterReadingRequest $request, MeterReading $meterReading)
{
    $this->authorize('update', $meterReading);
    // Update logic...
}
```

---

#### `delete(User $user, MeterReading $meterReading): bool`

Determines if user can delete a meter reading.

**Authorization Rules:**
- **Superadmin**: Can delete any meter reading
- **Admin**: Can delete readings within their tenant
- **Manager**: Cannot delete readings (business rule)
- **Tenant**: Workflow-dependent permissions:
  - **Permissive**: Can delete OWN readings IF status is 'pending'
  - **Truth-but-Verify**: Cannot delete readings once submitted

**Parameters:**
- `$user` - The authenticated user
- `$meterReading` - The meter reading to delete

**Returns:** `true` if authorized, `false` otherwise

---

### Workflow-Specific Operations

#### `approve(User $user, MeterReading $meterReading): bool`

Determines if user can approve/validate a meter reading.

**Authorization Rules:**
- **Manager/Admin/Superadmin**: Can approve readings within their scope
- **Tenant**: Cannot approve readings (manager privilege)
- **Status Requirement**: Reading must have 'pending' status
- **Validation Requirement**: Reading must require validation

**Parameters:**
- `$user` - The authenticated user
- `$meterReading` - The meter reading to approve

**Returns:** `true` if authorized, `false` otherwise

---

#### `reject(User $user, MeterReading $meterReading): bool`

Determines if user can reject a meter reading.

**Authorization Rules:**
- Same as `approve()` method

**Parameters:**
- `$user` - The authenticated user
- `$meterReading` - The meter reading to reject

**Returns:** `true` if authorized, `false` otherwise

---

### Administrative Operations

#### `forceDelete(User $user, MeterReading $meterReading): bool`

Determines if user can permanently delete a meter reading.

**Authorization Rules:**
- **Superadmin**: Can force delete any meter reading
- **All Others**: Cannot force delete

**Parameters:**
- `$user` - The authenticated user
- `$meterReading` - The meter reading to force delete

**Returns:** `true` if authorized, `false` otherwise

---

#### `export(User $user): bool`

Determines if user can export meter readings.

**Authorization Rules:**
- All authenticated roles can export readings within their scope

**Parameters:**
- `$user` - The authenticated user

**Returns:** `true` if authorized, `false` otherwise

---

#### `import(User $user): bool`

Determines if user can import meter readings.

**Authorization Rules:**
- **Manager/Admin/Superadmin**: Can import readings
- **Tenant**: Cannot import readings

**Parameters:**
- `$user` - The authenticated user

**Returns:** `true` if authorized, `false` otherwise

---

## Configuration

### Workflow Strategy Injection

The policy accepts an optional workflow strategy in its constructor:

```php
// Default to Permissive workflow
$policy = new MeterReadingPolicy($tenantBoundaryService);

// Explicit Permissive workflow
$policy = new MeterReadingPolicy(
    $tenantBoundaryService, 
    new PermissiveWorkflowStrategy()
);

// Truth-but-Verify workflow
$policy = new MeterReadingPolicy(
    $tenantBoundaryService, 
    new TruthButVerifyWorkflowStrategy()
);
```

### Service Provider Registration

Register the policy in `AuthServiceProvider`:

```php
protected $policies = [
    MeterReading::class => MeterReadingPolicy::class,
];
```

## Security Considerations

### Tenant Isolation

- All authorization checks respect tenant boundaries
- Cross-tenant access is prevented at the policy level
- Tenant context is validated for all operations

### Audit Logging

- All authorization decisions are logged with full context
- Sensitive operations include additional metadata
- Failed authorization attempts are tracked

### Workflow Security

- Workflow strategies maintain security boundaries
- Status-based validation prevents invalid state transitions
- Ownership validation ensures tenants can only modify their own data

## Error Handling

The policy uses structured `PolicyResult` objects that include:

- **Authorization Decision**: Boolean result
- **Reason**: Human-readable explanation
- **Context**: Additional metadata for logging

Example error scenarios:

```php
// Cross-tenant access attempt
PolicyResult::deny('Different tenant', ['tenant_mismatch' => true])

// Invalid status for tenant modification
PolicyResult::deny('Workflow denies tenant update', [
    'workflow' => 'permissive',
    'status' => 'validated',
    'owner' => true
])

// Insufficient permissions
PolicyResult::deny('Insufficient role', ['required_role' => 'manager'])
```

## Testing

### Unit Tests

Test all authorization scenarios:

```php
public function test_tenant_can_update_own_pending_reading(): void
{
    $tenant = User::factory()->tenant()->create();
    $reading = MeterReading::factory()->create([
        'entered_by' => $tenant->id,
        'validation_status' => ValidationStatus::PENDING,
    ]);

    $this->assertTrue($this->policy->update($tenant, $reading));
}

public function test_tenant_cannot_update_validated_reading(): void
{
    $tenant = User::factory()->tenant()->create();
    $reading = MeterReading::factory()->create([
        'entered_by' => $tenant->id,
        'validation_status' => ValidationStatus::VALIDATED,
    ]);

    $this->assertFalse($this->policy->update($tenant, $reading));
}
```

### Feature Tests

Test policy integration with controllers and Filament resources:

```php
public function test_tenant_can_edit_own_pending_reading_via_api(): void
{
    $tenant = User::factory()->tenant()->create();
    $reading = MeterReading::factory()->create([
        'entered_by' => $tenant->id,
        'validation_status' => ValidationStatus::PENDING,
    ]);

    $this->actingAs($tenant)
        ->putJson("/api/meter-readings/{$reading->id}", ['value' => 1000])
        ->assertOk();
}
```

## Related Documentation

- [Workflow Strategies](../services/workflow-strategies.md)
- [Tenant Boundary Service](../services/tenant-boundary-service.md)
- [Multi-Tenant Data Management](../../specs/multi-tenant-data-management/design.md)
- [Authorization Context](../value-objects/authorization-context.md)
- [Policy Result](../value-objects/policy-result.md)