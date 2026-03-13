# Tenant Profile API Documentation

## Overview

The Tenant Profile API provides endpoints for tenants to view and update their profile information, including name, email, and password changes.

**Base Path**: `/tenant/profile`  
**Authentication**: Required (session-based)  
**Authorization**: Tenant role only  
**Middleware Stack**: `auth`, `role:tenant`, `subscription.check`, `hierarchical.access`

## Endpoints

### GET /tenant/profile

Display the tenant's profile page.

**Route Name**: `tenant.profile.show`

#### Request

**Method**: `GET`  
**URL**: `/tenant/profile`  
**Headers**:
- `Accept: text/html`
- `Cookie: laravel_session={session_token}`

**Authentication**: Required (session-based)

#### Response

**Success (200 OK)**:
```html
<!-- Returns rendered Blade view with user data -->
```

**View Data**:
```php
[
    'user' => User {
        id: int,
        name: string,
        email: string,
        role: UserRole,
        created_at: Carbon,
        property: Property|null {
            id: int,
            address: string,
            type: PropertyType,
            area_sqm: float,
            building: Building|null {
                id: int,
                name: string,
                address: string
            }
        },
        parentUser: User|null {
            id: int,
            name: string,
            email: string,
            organization_name: string|null
        }
    }
]
```

**Error Responses**:
- `401 Unauthorized`: User not authenticated
- `403 Forbidden`: User is not a tenant

#### Example

```bash
curl -X GET \
  https://example.com/tenant/profile \
  -H 'Cookie: laravel_session=...' \
  -H 'Accept: text/html'
```

---

### PUT /tenant/profile

Update the tenant's profile information.

**Route Name**: `tenant.profile.update`

#### Request

**Method**: `PUT`  
**URL**: `/tenant/profile`  
**Headers**:
- `Content-Type: application/x-www-form-urlencoded`
- `Cookie: laravel_session={session_token}`
- `X-CSRF-TOKEN: {csrf_token}`

**Authentication**: Required (session-based)  
**CSRF Protection**: Required

**Body Parameters**:

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `name` | string | Yes | User's full name (max 255 chars) |
| `email` | string | Yes | User's email address (must be unique) |
| `current_password` | string | Conditional | Required when changing password |
| `password` | string | No | New password (min 8 chars) |
| `password_confirmation` | string | Conditional | Required when password is provided |

**Validation Rules**:
```php
[
    'name' => ['required', 'string', 'max:255'],
    'email' => ['required', 'email', 'unique:users,email,{user_id}'],
    'current_password' => ['nullable', 'required_with:password', 'string', 'current_password'],
    'password' => ['nullable', 'string', 'min:8', 'confirmed'],
]
```

#### Response

**Success (302 Redirect)**:
```
Location: /tenant/profile
Set-Cookie: laravel_session=...
```

**Flash Message**:
```php
[
    'success' => 'Profile updated successfully' // Localized
]
```

**Validation Error (302 Redirect with errors)**:
```
Location: /tenant/profile
Set-Cookie: laravel_session=...
```

**Session Errors**:
```php
[
    'errors' => [
        'name' => ['The name field is required.'],
        'email' => ['The email has already been taken.'],
        'current_password' => ['The current password is incorrect.'],
        'password' => ['The password must be at least 8 characters.']
    ]
]
```

**Error Responses**:
- `401 Unauthorized`: User not authenticated
- `403 Forbidden`: User is not a tenant
- `419 Page Expired`: CSRF token invalid or missing
- `422 Unprocessable Entity`: Validation failed (if API request)

#### Example Requests

**Update Name and Email Only**:
```bash
curl -X PUT \
  https://example.com/tenant/profile \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -H 'Cookie: laravel_session=...' \
  -H 'X-CSRF-TOKEN: ...' \
  -d 'name=John Doe' \
  -d 'email=john.doe@example.com'
```

**Update Name, Email, and Password**:
```bash
curl -X PUT \
  https://example.com/tenant/profile \
  -H 'Content-Type: application/x-www-form-urlencoded' \
  -H 'Cookie: laravel_session=...' \
  -H 'X-CSRF-TOKEN: ...' \
  -d 'name=John Doe' \
  -d 'email=john.doe@example.com' \
  -d 'current_password=oldpassword123' \
  -d 'password=newpassword123' \
  -d 'password_confirmation=newpassword123'
```

**HTML Form Example**:
```html
<form method="POST" action="{{ route('tenant.profile.update') }}">
    @csrf
    @method('PUT')
    
    <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
    <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
    
    <!-- Optional password change -->
    <input type="password" name="current_password">
    <input type="password" name="password">
    <input type="password" name="password_confirmation">
    
    <button type="submit">Update Profile</button>
</form>
```

## Validation Rules Details

### Name Validation
- **Required**: Yes
- **Type**: String
- **Max Length**: 255 characters
- **Error Messages**:
  - `required`: "The name field is required."
  - `string`: "The name must be a string."
  - `max`: "The name may not be greater than 255 characters."

### Email Validation
- **Required**: Yes
- **Type**: String (valid email format)
- **Unique**: Must be unique in users table (excluding current user)
- **Error Messages**:
  - `required`: "The email field is required."
  - `email`: "The email must be a valid email address."
  - `unique`: "The email has already been taken."

### Current Password Validation
- **Required**: Only when `password` field is present
- **Type**: String
- **Verification**: Must match user's current password (Laravel's `current_password` rule)
- **Error Messages**:
  - `required_with`: "The current password field is required when password is present."
  - `current_password`: "The current password is incorrect."

### New Password Validation
- **Required**: No (optional)
- **Type**: String
- **Min Length**: 8 characters
- **Confirmation**: Must match `password_confirmation` field
- **Error Messages**:
  - `min`: "The password must be at least 8 characters."
  - `confirmed`: "The password confirmation does not match."

## Security Considerations

### Authentication & Authorization
- **Session-based authentication**: Uses Laravel's session guard
- **Role-based access control**: Only users with `tenant` role can access
- **Middleware stack**: `auth`, `role:tenant`, `subscription.check`, `hierarchical.access`

### CSRF Protection
- **Required**: All POST/PUT/PATCH/DELETE requests must include CSRF token
- **Token Location**: `X-CSRF-TOKEN` header or `_token` form field
- **Token Generation**: Available via `@csrf` Blade directive or `csrf_token()` helper

### Password Security
- **Current password verification**: Uses Laravel 12's `current_password` rule
- **Password hashing**: Bcrypt algorithm (default Laravel hashing)
- **Password confirmation**: Required to prevent typos
- **Minimum length**: 8 characters enforced

### Data Protection
- **Email uniqueness**: Prevents account conflicts
- **Input sanitization**: Laravel's validation handles sanitization
- **SQL injection prevention**: Eloquent ORM with parameter binding
- **XSS prevention**: Blade's `{{ }}` syntax auto-escapes output

## Error Handling

### Validation Errors
Validation errors are returned as flash session data and displayed inline in the form:

```php
@error('name')
    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
@enderror
```

### Common Error Scenarios

1. **Missing CSRF Token**:
   - Status: 419 Page Expired
   - Solution: Include `@csrf` in form or `X-CSRF-TOKEN` header

2. **Invalid Current Password**:
   - Field: `current_password`
   - Message: "The current password is incorrect."
   - Solution: Verify user entered correct current password

3. **Email Already Taken**:
   - Field: `email`
   - Message: "The email has already been taken."
   - Solution: Choose a different email address

4. **Password Too Short**:
   - Field: `password`
   - Message: "The password must be at least 8 characters."
   - Solution: Use a password with at least 8 characters

5. **Password Confirmation Mismatch**:
   - Field: `password`
   - Message: "The password confirmation does not match."
   - Solution: Ensure `password` and `password_confirmation` match

## Localization

All error messages and UI text support multiple languages:

**Supported Languages**:
- English (en)
- Lithuanian (lt)
- Russian (ru)

**Translation Files**:
- `lang/en/tenant.php` - UI labels and messages
- `lang/en/users.php` - Validation messages
- `lang/lt/tenant.php` - Lithuanian translations
- `lang/lt/users.php` - Lithuanian validation
- `lang/ru/tenant.php` - Russian translations
- `lang/ru/users.php` - Russian validation

**Example Translation Keys**:
```php
// UI Labels
__('tenant.profile.update_profile')
__('tenant.profile.labels.name')
__('tenant.profile.labels.email')
__('tenant.profile.save_changes')

// Validation Messages
__('users.validation.name.required')
__('users.validation.email.unique')
__('users.validation.password.min')
```

## Performance Considerations

### Database Queries
- **Profile Show**: 1 query with eager loading (property.building, parentUser)
- **Profile Update**: 1 query (update user record)
- **No N+1 Issues**: Relationships are eager loaded

### Caching
- No caching implemented (user data changes frequently)
- Session data is cached by Laravel's session driver

### Response Time
- **Profile Show**: < 100ms (typical)
- **Profile Update**: < 150ms (typical)

## Testing

### Test Coverage
Comprehensive test suite in `tests/Feature/Tenant/ProfileUpdateTest.php`:

**Test Cases** (14 total):
1. Tenant can view profile page
2. Tenant can update name and email
3. Tenant can update password with correct current password
4. Tenant cannot update password with incorrect current password
5. Tenant cannot update password without current password
6. Tenant cannot update with invalid email
7. Tenant cannot update with duplicate email
8. Tenant cannot update with short password
9. Tenant cannot update with mismatched password confirmation
10. Tenant can update profile without changing password
11. Unauthenticated user cannot access profile
12. Non-tenant user cannot access tenant profile
13. Validation error messages are displayed
14. Success message is displayed on update

### Running Tests
```bash
# Run all profile update tests
php artisan test --filter=ProfileUpdateTest

# Run specific test
php artisan test --filter="tenant can update name and email"

# Run with coverage
php artisan test --filter=ProfileUpdateTest --coverage
```

## Related Documentation

- [Hierarchical User Management Spec](.kiro/specs/3-hierarchical-user-management/)
- [Tenant Profile Update Feature](../features/TENANT_PROFILE_UPDATE.md)
- [Authentication Testing](docs/testing/AUTHENTICATION_TESTING.md)
- [Localization Guide](docs/frontend/LOCALIZATION.md)
- [Security Best Practices](docs/security/BEST_PRACTICES.md)

## Changelog

### 2024-11-26
- ✅ Initial implementation of tenant profile update API
- ✅ Added comprehensive validation rules
- ✅ Implemented password change with current password verification
- ✅ Added localization support (EN/LT/RU)
- ✅ Created comprehensive test suite
- ✅ Added API documentation
