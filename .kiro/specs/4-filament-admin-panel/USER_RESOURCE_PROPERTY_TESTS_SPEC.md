# UserResource Property Tests Specification

**Date**: 2025-11-26  
**Status**: BUILD-READY  
**Complexity**: Level 2 (Simple Enhancement)  
**Related Tasks**: 6.5, 6.6, 6.7 in tasks.md

## Executive Summary

Implement three property-based tests for UserResource to validate validation consistency, conditional tenant requirements, and null tenant allowance across the hierarchical user management system. These tests ensure data integrity, authorization boundaries, and role-based field requirements are enforced correctly.

### Success Metrics
- ✅ All 3 property tests pass with 100+ iterations each
- ✅ Tests complete in <30 seconds total
- ✅ Zero false positives/negatives
- ✅ Coverage for all 4 user roles (Superadmin, Admin, Manager, Tenant)
- ✅ Validation rules match between UserResource and UserPolicy

### Constraints
- Must use existing Pest test framework
- Must reuse TestCase helpers (actingAsAdmin, actingAsManager, etc.)
- Must not modify production code (UserResource, UserPolicy)
- Must follow existing property test patterns
- Must respect multi-tenant isolation

---

## User Stories

### Story 1: Validation Consistency (Property 13)
**As a** system administrator  
**I want** validation rules to be consistent between the Filament form and backend  
**So that** users receive predictable error messages and data integrity is maintained

**Acceptance Criteria:**
- ✅ Name validation: required, string, max 255 characters
- ✅ Email validation: required, email format, unique, max 255 characters
- ✅ Password validation: required on create, min 8 characters, confirmed
- ✅ Role validation: required, valid enum value
- ✅ Tenant validation: required for Manager/Tenant, optional for Admin/Superadmin
- ✅ Validation messages are localized (users.validation.*)
- ✅ Form validation matches FormRequest validation
- ✅ Policy authorization aligns with validation rules

**Performance Targets:**
- Test execution: <10 seconds for 100 iterations
- Memory usage: <50MB per test run

**Accessibility:**
- Validation errors display with ARIA attributes
- Error messages are screen-reader friendly
- Focus moves to first invalid field

**Localization:**
- All validation messages use translation keys
- Supports EN, LT, RU locales
- Fallback to English if translation missing

---

### Story 2: Conditional Tenant Requirement (Property 14)
**As a** system administrator  
**I want** tenant assignment to be required only for Manager and Tenant roles  
**So that** organizational hierarchy is properly enforced

**Acceptance Criteria:**
- ✅ Manager role: tenant_id is required
- ✅ Tenant role: tenant_id is required
- ✅ Admin role: tenant_id is optional
- ✅ Superadmin role: tenant_id is optional (should be null)
- ✅ Form field visibility matches requirement rules
- ✅ Database constraints allow null for Admin/Superadmin
- ✅ Validation errors are clear and actionable
- ✅ Tenant scope is enforced in dropdown options

**Performance Targets:**
- Test execution: <10 seconds for 100 iterations
- Query count: <5 queries per validation check

**Security:**
- Tenant dropdown only shows users within authenticated user's tenant
- Cross-tenant assignment is prevented
- Superadmin can assign any tenant

---

### Story 3: Null Tenant Allowance (Property 15)
**As a** system administrator  
**I want** Admin and Superadmin users to optionally have null tenant_id  
**So that** system-level accounts can operate across all tenants

**Acceptance Criteria:**
- ✅ Admin users can have null tenant_id
- ✅ Superadmin users can have null tenant_id
- ✅ Manager users cannot have null tenant_id
- ✅ Tenant users cannot have null tenant_id
- ✅ Null tenant_id allows access to all tenants (Superadmin)
- ✅ Null tenant_id for Admin creates isolated organization
- ✅ Database schema allows null for tenant_id column
- ✅ Queries handle null tenant_id correctly

**Performance Targets:**
- Test execution: <10 seconds for 100 iterations
- No N+1 queries when checking tenant scope

**Security:**
- Null tenant_id does not bypass authorization
- Policy checks still enforce role-based permissions
- Audit logs capture null tenant_id operations

---

## Data Models

### Existing Schema (No Changes Required)

```sql
-- users table (already exists)
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('superadmin', 'admin', 'manager', 'tenant') NOT NULL,
    tenant_id BIGINT UNSIGNED NULL, -- Allows null for Admin/Superadmin
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX users_tenant_id_index (tenant_id),
    INDEX users_role_index (role),
    INDEX users_is_active_index (is_active),
    INDEX users_tenant_id_role_index (tenant_id, role),
    INDEX users_tenant_id_is_active_index (tenant_id, is_active),
    
    FOREIGN KEY (tenant_id) REFERENCES users(id) ON DELETE SET NULL
);
```

**Validation Rules (Existing):**
```php
// UserResource form validation
'name' => ['required', 'string', 'max:255'],
'email' => ['required', 'email', 'unique:users,email', 'max:255'],
'password' => ['required_if:operation,create', 'string', 'min:8', 'confirmed'],
'role' => ['required', 'enum:UserRole'],
'tenant_id' => [
    'required_if:role,manager,tenant',
    'nullable',
    'integer',
    'exists:users,id'
],
'is_active' => ['boolean']
```

---

## Testing Plan

### Test 1: FilamentUserValidationConsistencyPropertyTest.php

**Purpose:** Validate that UserResource form validation matches backend validation rules

**Test Structure:**
```php
<?php

use App\Enums\UserRole;
use App\Filament\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

test('user validation is consistent between form and backend', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    actingAs($admin);
    
    // Test valid data passes both form and backend validation
    $validData = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => UserRole::MANAGER->value,
        'tenant_id' => 1,
        'is_active' => true,
    ];
    
    // Backend validation
    $validator = Validator::make($validData, [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email', 'max:255'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role' => ['required', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
        'tenant_id' => ['required_if:role,manager,tenant', 'nullable', 'integer', 'exists:users,id'],
    ]);
    
    expect($validator->passes())->toBeTrue();
    
    // Test invalid data fails both validations
    $invalidData = [
        'name' => '', // Required
        'email' => 'invalid-email', // Invalid format
        'password' => 'short', // Too short
        'password_confirmation' => 'different', // Doesn't match
        'role' => 'invalid', // Invalid enum
        'tenant_id' => null, // Required for manager
    ];
    
    $validator = Validator::make($invalidData, [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email', 'max:255'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role' => ['required', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
        'tenant_id' => ['required_if:role,manager,tenant', 'nullable', 'integer', 'exists:users,id'],
    ]);
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('name'))->toBeTrue()
        ->and($validator->errors()->has('email'))->toBeTrue()
        ->and($validator->errors()->has('password'))->toBeTrue()
        ->and($validator->errors()->has('role'))->toBeTrue();
})->group('property', 'user-resource');

test('user validation messages are localized', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    actingAs($admin);
    
    $invalidData = [
        'name' => '',
        'email' => 'invalid',
        'password' => 'short',
        'role' => '',
    ];
    
    $validator = Validator::make($invalidData, [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email', 'max:255'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role' => ['required', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
    ]);
    
    $errors = $validator->errors();
    
    // Verify error messages exist and are strings
    expect($errors->get('name'))->toBeArray()
        ->and($errors->get('email'))->toBeArray()
        ->and($errors->get('password'))->toBeArray()
        ->and($errors->get('role'))->toBeArray();
})->group('property', 'user-resource');
```

**Iterations:** 100+  
**Expected Duration:** <10 seconds  
**Coverage:** Name, email, password, role, tenant_id validation

---

### Test 2: FilamentUserConditionalTenantRequirementPropertyTest.php

**Purpose:** Validate that tenant_id is required only for Manager and Tenant roles

**Test Structure:**
```php
<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

test('tenant field is required for manager role', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    actingAs($admin);
    
    // Manager without tenant_id should fail
    $data = [
        'name' => 'Test Manager',
        'email' => 'manager@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => UserRole::MANAGER->value,
        'tenant_id' => null,
    ];
    
    $validator = Validator::make($data, [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email', 'max:255'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role' => ['required', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
        'tenant_id' => ['required_if:role,manager,tenant', 'nullable', 'integer', 'exists:users,id'],
    ]);
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('tenant_id'))->toBeTrue();
    
    // Manager with tenant_id should pass
    $data['tenant_id'] = 1;
    
    $validator = Validator::make($data, [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email', 'max:255'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role' => ['required', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
        'tenant_id' => ['required_if:role,manager,tenant', 'nullable', 'integer', 'exists:users,id'],
    ]);
    
    expect($validator->passes())->toBeTrue();
})->group('property', 'user-resource');

test('tenant field is required for tenant role', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    actingAs($admin);
    
    // Tenant without tenant_id should fail
    $data = [
        'name' => 'Test Tenant',
        'email' => 'tenant@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => UserRole::TENANT->value,
        'tenant_id' => null,
    ];
    
    $validator = Validator::make($data, [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email', 'max:255'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role' => ['required', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
        'tenant_id' => ['required_if:role,manager,tenant', 'nullable', 'integer', 'exists:users,id'],
    ]);
    
    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('tenant_id'))->toBeTrue();
    
    // Tenant with tenant_id should pass
    $data['tenant_id'] = 1;
    
    $validator = Validator::make($data, [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email', 'max:255'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role' => ['required', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
        'tenant_id' => ['required_if:role,manager,tenant', 'nullable', 'integer', 'exists:users,id'],
    ]);
    
    expect($validator->passes())->toBeTrue();
})->group('property', 'user-resource');

test('tenant field is optional for admin role', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    actingAs($admin);
    
    // Admin without tenant_id should pass
    $data = [
        'name' => 'Test Admin',
        'email' => 'admin@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => UserRole::ADMIN->value,
        'tenant_id' => null,
    ];
    
    $validator = Validator::make($data, [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email', 'max:255'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'role' => ['required', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
        'tenant_id' => ['required_if:role,manager,tenant', 'nullable', 'integer', 'exists:users,id'],
    ]);
    
    expect($validator->passes())->toBeTrue();
})->group('property', 'user-resource');
```

**Iterations:** 100+  
**Expected Duration:** <10 seconds  
**Coverage:** Manager, Tenant, Admin roles with tenant_id variations

---

### Test 3: FilamentUserAdminNullTenantPropertyTest.php

**Purpose:** Validate that Admin and Superadmin users can have null tenant_id

**Test Structure:**
```php
<?php

use App\Enums\UserRole;
use App\Models\User;

test('admin users can have null tenant_id', function () {
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
    ]);
    
    actingAs($superadmin);
    
    // Create admin with null tenant_id
    $admin = User::create([
        'name' => 'Test Admin',
        'email' => 'admin@example.com',
        'password' => bcrypt('password123'),
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    expect($admin)->toBeInstanceOf(User::class)
        ->and($admin->role)->toBe(UserRole::ADMIN)
        ->and($admin->tenant_id)->toBeNull();
    
    // Verify admin can be retrieved
    $retrieved = User::find($admin->id);
    expect($retrieved)->not->toBeNull()
        ->and($retrieved->tenant_id)->toBeNull();
})->group('property', 'user-resource');

test('superadmin users can have null tenant_id', function () {
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
    ]);
    
    actingAs($superadmin);
    
    // Create another superadmin with null tenant_id
    $newSuperadmin = User::create([
        'name' => 'Test Superadmin',
        'email' => 'superadmin2@example.com',
        'password' => bcrypt('password123'),
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);
    
    expect($newSuperadmin)->toBeInstanceOf(User::class)
        ->and($newSuperadmin->role)->toBe(UserRole::SUPERADMIN)
        ->and($newSuperadmin->tenant_id)->toBeNull();
})->group('property', 'user-resource');

test('manager users cannot have null tenant_id', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    actingAs($admin);
    
    // Attempt to create manager with null tenant_id should fail validation
    $this->expectException(\Illuminate\Validation\ValidationException::class);
    
    User::create([
        'name' => 'Test Manager',
        'email' => 'manager@example.com',
        'password' => bcrypt('password123'),
        'role' => UserRole::MANAGER,
        'tenant_id' => null, // Should fail
        'is_active' => true,
    ]);
})->group('property', 'user-resource');

test('tenant users cannot have null tenant_id', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    actingAs($admin);
    
    // Attempt to create tenant with null tenant_id should fail validation
    $this->expectException(\Illuminate\Validation\ValidationException::class);
    
    User::create([
        'name' => 'Test Tenant',
        'email' => 'tenant@example.com',
        'password' => bcrypt('password123'),
        'role' => UserRole::TENANT,
        'tenant_id' => null, // Should fail
        'is_active' => true,
    ]);
})->group('property', 'user-resource');

test('null tenant_id allows superadmin to access all tenants', function () {
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
    ]);
    
    // Create users in different tenants
    User::factory()->count(5)->create(['tenant_id' => 1]);
    User::factory()->count(3)->create(['tenant_id' => 2]);
    
    actingAs($superadmin);
    
    // Superadmin should see all users
    $allUsers = User::all();
    expect($allUsers->count())->toBeGreaterThanOrEqual(9); // 5 + 3 + superadmin
})->group('property', 'user-resource');
```

**Iterations:** 100+  
**Expected Duration:** <10 seconds  
**Coverage:** Admin, Superadmin, Manager, Tenant roles with null tenant_id

---

## Non-Functional Requirements

### Performance
- **Test Execution:** All 3 tests complete in <30 seconds total
- **Query Efficiency:** <10 queries per test iteration
- **Memory Usage:** <100MB total for all tests
- **Database Cleanup:** Automatic rollback after each test

### Security
- **Tenant Isolation:** Tests verify tenant scope is enforced
- **Authorization:** Tests respect UserPolicy rules
- **Password Hashing:** Tests use bcrypt for password storage
- **Audit Logging:** Tests do not trigger audit logs (read-only)

### Accessibility
- **Validation Messages:** All error messages are screen-reader friendly
- **Form Labels:** All form fields have proper labels
- **Focus Management:** Invalid fields receive focus on error

### Localization
- **Translation Keys:** All validation messages use translation keys
- **Locale Support:** Tests verify EN, LT, RU locales
- **Fallback:** Tests verify fallback to English if translation missing

---

## Documentation Updates

### Files to Update

1. **tests/Feature/FilamentUserValidationConsistencyPropertyTest.php** (NEW)
   - Property test for validation consistency
   - 100+ iterations
   - Covers all validation rules

2. **tests/Feature/FilamentUserConditionalTenantRequirementPropertyTest.php** (NEW)
   - Property test for conditional tenant requirement
   - 100+ iterations
   - Covers Manager, Tenant, Admin roles

3. **tests/Feature/FilamentUserAdminNullTenantPropertyTest.php** (NEW)
   - Property test for null tenant allowance
   - 100+ iterations
   - Covers Admin, Superadmin, Manager, Tenant roles

4. **.kiro/specs/4-filament-admin-panel/tasks.md**
   - Mark tasks 6.5, 6.6, 6.7 as complete
   - Add test file references
   - Update completion status

5. **docs/testing/USER_RESOURCE_PROPERTY_TESTS.md** (NEW)
   - Document all 3 property tests
   - Explain test rationale
   - Provide usage examples

6. **README.md**
   - Add property test section
   - Document test execution commands
   - Link to test documentation

---

## Migration & Deployment

### No Database Changes Required
- Existing schema supports all test scenarios
- No migrations needed
- No seeders needed

### Deployment Steps
1. Create 3 new test files
2. Run tests: `php artisan test --filter=UserResource --group=property`
3. Verify all tests pass
4. Update documentation
5. Mark tasks as complete

### Rollback Plan
- Delete test files if needed
- No database rollback required
- No production code changes

---

## Monitoring & Alerting

### Test Execution Monitoring
- **CI/CD Integration:** Tests run on every commit
- **Failure Alerts:** Notify team if tests fail
- **Performance Tracking:** Monitor test execution time
- **Coverage Reports:** Track test coverage percentage

### Metrics to Track
- Test pass/fail rate
- Test execution time
- Memory usage
- Query count per test

---

## Acceptance Checklist

- [ ] All 3 property tests created
- [ ] All tests pass with 100+ iterations
- [ ] Tests complete in <30 seconds total
- [ ] Zero false positives/negatives
- [ ] Coverage for all 4 user roles
- [ ] Validation rules match between form and backend
- [ ] Documentation updated
- [ ] Tasks marked as complete
- [ ] CI/CD integration verified
- [ ] Team review completed

---

## References

- **UserResource:** `app/Filament/Resources/UserResource.php`
- **UserPolicy:** `app/Policies/UserPolicy.php`
- **UserRole Enum:** `app/Enums/UserRole.php`
- **User Model:** `app/Models/User.php`
- **Existing Tests:** `tests/Feature/Performance/UserResourcePerformanceTest.php`
- **Task Tracking:** `.kiro/specs/4-filament-admin-panel/tasks.md`

---

**END OF SPECIFICATION**
