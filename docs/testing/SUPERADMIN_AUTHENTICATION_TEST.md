# Superadmin Authentication Test Documentation

## Overview

The `SuperadminAuthenticationTest` class provides comprehensive test coverage for authentication flows across all user roles (Superadmin, Admin, Manager, Tenant) with a focus on the `is_active` account status check. This test suite validates that the hierarchical user management system correctly handles authentication, role-based redirects, account deactivation, and security features.

## Purpose

This test suite ensures:
- Role-based authentication and redirect behavior
- Account deactivation prevents login for all roles
- Session security (regeneration, remember me)
- Invalid credential handling
- Proper error messaging for deactivated accounts

## Requirements Coverage

This test suite validates the following requirements from `.kiro/specs/3-hierarchical-user-management/requirements.md`:

- **Requirement 1.1**: Superadmin login and dashboard access
- **Requirement 7.1**: Account deactivation prevents login
- **Requirement 8.1**: User authentication and redirect
- **Requirement 8.4**: Deactivated account login prevention with appropriate messaging
- **Requirement 12.1**: Login redirect logic for superadmin

## Test Cases

### 1. Superadmin Login and Redirect

**Test Method**: `test_superadmin_can_login_and_redirects_to_superadmin_dashboard()`

**Purpose**: Validates that an active superadmin user can successfully authenticate and is redirected to the superadmin dashboard.

**Requirements**: 1.1, 8.1

**Test Flow**:
1. Create a superadmin user with `is_active = true` and `tenant_id = null`
2. Submit login credentials via POST to `/login`
3. Assert redirect to `/superadmin/dashboard`
4. Assert user is authenticated

**Expected Behavior**:
- Successful authentication
- Redirect to superadmin-specific dashboard
- User session established

**Key Assertions**:
```php
$response->assertRedirect('/superadmin/dashboard');
$this->assertAuthenticatedAs($superadmin);
```

---

### 2. Deactivated Superadmin Cannot Login

**Test Method**: `test_deactivated_superadmin_cannot_login()`

**Purpose**: Ensures that a deactivated superadmin account cannot authenticate, even with valid credentials.

**Requirements**: 7.1, 8.4

**Test Flow**:
1. Create a superadmin user with `is_active = false`
2. Attempt login with valid credentials
3. Assert redirect back to login page
4. Assert session has validation errors
5. Assert user remains unauthenticated
6. Verify specific error message about deactivation

**Expected Behavior**:
- Authentication fails
- Redirect to login page
- Session contains error: "Your account has been deactivated. Please contact your administrator for assistance."
- User remains guest

**Key Assertions**:
```php
$response->assertRedirect('/login');
$response->assertSessionHasErrors('email');
$this->assertGuest();
$response->assertSessionHasErrorsIn('default', [
    'email' => 'Your account has been deactivated. Please contact your administrator for assistance.',
]);
```

---

### 3. Deactivated Admin Cannot Login

**Test Method**: `test_deactivated_admin_cannot_login()`

**Purpose**: Validates that deactivated admin accounts are prevented from logging in.

**Requirements**: 7.1, 8.4

**Test Flow**:
1. Create an admin user with `is_active = false` and unique `tenant_id = 100`
2. Attempt login with valid credentials
3. Assert authentication failure with appropriate error message

**Expected Behavior**:
- Same as deactivated superadmin test
- Validates tenant_id isolation (uses unique tenant_id to avoid conflicts)

**Implementation Notes**:
- Uses unique `tenant_id` to prevent conflicts with other tests
- Validates that deactivation works across all admin-level roles

---

### 4. Deactivated Tenant Cannot Login

**Test Method**: `test_deactivated_tenant_cannot_login()`

**Purpose**: Ensures tenant accounts respect the `is_active` flag and cannot login when deactivated.

**Requirements**: 7.1, 8.4

**Test Flow**:
1. Create necessary database records (building, property) with `tenant_id = 200`
2. Create a tenant user with `is_active = false` linked to the property
3. Attempt login with valid credentials
4. Assert authentication failure with deactivation message

**Expected Behavior**:
- Authentication fails for deactivated tenant
- Proper error messaging
- Database relationships maintained (building → property → tenant)

**Implementation Notes**:
- Creates full tenant hierarchy (building → property → user)
- Uses unique `tenant_id = 200` for isolation
- Validates that property relationships don't bypass deactivation check

---

### 5. All Active Roles Can Login

**Test Method**: `test_all_active_roles_can_login()`

**Purpose**: Comprehensive test validating that all user roles (superadmin, admin, manager, tenant) can successfully authenticate when active and are redirected to their role-specific dashboards.

**Requirements**: 1.1, 8.1

**Test Flow**:
1. Create building and property with `tenant_id = 300`
2. Create users for all four roles with `is_active = true`
3. For each role:
   - Submit login credentials
   - Assert redirect to role-specific dashboard
   - Assert authentication successful
   - Logout to prepare for next iteration

**Expected Redirects**:
- Superadmin → `/superadmin/dashboard`
- Admin → `/admin/dashboard`
- Manager → `/manager/dashboard`
- Tenant → `/tenant/dashboard`

**Expected Behavior**:
- Each role authenticates successfully
- Role-based redirect logic works correctly
- Session management handles multiple sequential logins
- Logout between iterations prevents session conflicts

**Key Assertions**:
```php
$response->assertRedirect($expectedRedirects[$role]);
$this->assertAuthenticatedAs($user);
```

**Implementation Notes**:
- Tests all four user roles in a single test
- Uses shared `tenant_id = 300` for admin/manager/tenant
- Explicitly logs out between iterations to ensure clean state

---

### 6. Invalid Credentials Are Rejected

**Test Method**: `test_invalid_credentials_are_rejected()`

**Purpose**: Validates that incorrect passwords are rejected even for active accounts.

**Requirements**: 8.4

**Test Flow**:
1. Create an active admin user with password "correct-password"
2. Attempt login with password "wrong-password"
3. Assert authentication failure
4. Verify error message indicates invalid credentials

**Expected Behavior**:
- Authentication fails
- Redirect to login page
- Error message: "The provided credentials do not match our records."
- User remains unauthenticated

**Key Assertions**:
```php
$response->assertRedirect('/login');
$response->assertSessionHasErrors('email');
$this->assertGuest();
$response->assertSessionHasErrorsIn('default', [
    'email' => 'The provided credentials do not match our records.',
]);
```

**Security Notes**:
- Validates that `is_active` check doesn't bypass password validation
- Ensures proper error messaging for invalid credentials
- Tests with active account to isolate credential validation

---

### 7. Session Regeneration on Login

**Test Method**: `test_session_is_regenerated_on_login()`

**Purpose**: Security test ensuring that session IDs are regenerated upon successful login to prevent session fixation attacks.

**Requirements**: 8.1

**Test Flow**:
1. Create an active admin user
2. Capture current session ID
3. Perform successful login
4. Capture new session ID
5. Assert session IDs are different

**Expected Behavior**:
- Session ID changes after successful login
- User is authenticated
- Redirect to appropriate dashboard

**Security Implications**:
- Prevents session fixation attacks
- Ensures fresh session for authenticated user
- Standard Laravel security practice

**Key Assertions**:
```php
$this->assertNotEquals($oldSessionId, $newSessionId, 'Session ID should be regenerated on login');
$this->assertAuthenticatedAs($user);
```

---

### 8. Remember Me Functionality

**Test Method**: `test_remember_me_functionality()`

**Purpose**: Validates that the "remember me" feature correctly sets a remember token for persistent authentication.

**Requirements**: 8.1

**Test Flow**:
1. Create an active admin user
2. Submit login with `remember = true` parameter
3. Assert successful authentication and redirect
4. Refresh user model from database
5. Assert remember token is set

**Expected Behavior**:
- Successful authentication
- Remember token is generated and stored
- Token is not null after login

**Key Assertions**:
```php
$response->assertRedirect('/admin/dashboard');
$this->assertAuthenticatedAs($user);
$user->refresh();
$this->assertNotNull($user->remember_token);
```

**Implementation Notes**:
- Tests Laravel's built-in remember me functionality
- Validates token persistence in database
- Uses `tenant_id = 600` for isolation

---

## Database Setup

### Test Isolation

Each test uses unique `tenant_id` values to prevent conflicts:
- Deactivated admin test: `tenant_id = 100`
- Deactivated tenant test: `tenant_id = 200`
- All active roles test: `tenant_id = 300`
- Invalid credentials test: `tenant_id = 400`
- Session regeneration test: `tenant_id = 500`
- Remember me test: `tenant_id = 600`

### Required Models

Tests create the following models as needed:
- **User**: All tests
- **Building**: Tenant-related tests
- **Property**: Tenant-related tests

### Factory Usage

All models are created using Laravel factories:
```php
User::factory()->create([...])
Building::factory()->create([...])
Property::factory()->create([...])
```

---

## Error Messages

### Deactivated Account
```
Your account has been deactivated. Please contact your administrator for assistance.
```

### Invalid Credentials
```
The provided credentials do not match our records.
```

---

## Running the Tests

### Run All Tests in Suite
```bash
php artisan test tests/Feature/SuperadminAuthenticationTest.php
```

### Run Specific Test
```bash
php artisan test --filter=test_superadmin_can_login_and_redirects_to_superadmin_dashboard
```

### Run with Coverage
```bash
php artisan test tests/Feature/SuperadminAuthenticationTest.php --coverage
```

### Run in Parallel
```bash
php artisan test tests/Feature/SuperadminAuthenticationTest.php --parallel
```

---

## Dependencies

### Models
- `App\Models\User`
- `App\Models\Building`
- `App\Models\Property`

### Enums
- `App\Enums\UserRole`

### Traits
- `Illuminate\Foundation\Testing\RefreshDatabase`

### Facades
- `Illuminate\Support\Facades\Hash`

---

## Test Environment

### Database
- Uses `RefreshDatabase` trait for clean state
- SQLite in-memory database for speed
- Migrations run before each test

### Configuration
- Test environment configured in `phpunit.xml`
- Uses `.env.testing` if present

---

## Security Considerations

### Session Security
- Session regeneration prevents fixation attacks
- Remember tokens properly generated and stored

### Account Status
- `is_active` flag consistently enforced
- Deactivated accounts cannot bypass authentication

### Error Messages
- Generic messages for invalid credentials (security best practice)
- Specific messages for deactivated accounts (user experience)

### Password Handling
- Passwords hashed using `Hash::make()`
- Test passwords never stored in plain text

---

## Maintenance

### Adding New Tests

When adding new authentication tests:

1. **Use Unique tenant_id**: Increment from 600 to avoid conflicts
2. **Create Required Models**: Ensure building/property exist for tenant tests
3. **Document Requirements**: Reference spec requirements in DocBlock
4. **Follow Naming Convention**: Use descriptive test method names with `test_` prefix
5. **Assert Error Messages**: Verify specific error messages when applicable

### Updating Tests

When authentication logic changes:

1. **Update Error Messages**: Ensure test assertions match actual error messages
2. **Update Redirects**: Verify redirect URLs match route definitions
3. **Update Requirements**: Update requirement references if specs change
4. **Run Full Suite**: Ensure no regressions in other tests

---

## Related Documentation

- [Hierarchical User Management Spec](.kiro/specs/3-hierarchical-user-management/)
- [Authentication Testing Spec](.kiro/specs/authentication-testing/)
- [User Model Documentation](../models/USER_MODEL.md)
- [Security Best Practices](../security/BEST_PRACTICES.md)
- [Testing Guide](TESTING_GUIDE.md)

---

## Changelog

### 2024-11-26
- ✅ Initial test suite created
- ✅ All 8 test cases implemented
- ✅ Requirements 1.1, 7.1, 8.1, 8.4, 12.1 covered
- ✅ Documentation completed

---

## Support

For questions or issues with these tests:
1. Review the [Testing Guide](TESTING_GUIDE.md)
2. Check the [Hierarchical User Management Spec](.kiro/specs/3-hierarchical-user-management/)
3. Consult the [Authentication Testing Spec](.kiro/specs/authentication-testing/)
