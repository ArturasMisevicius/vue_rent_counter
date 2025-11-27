# README Update Suggestions for Tenant Profile Feature

## Suggested Addition to Main README.md

Add the following section under "Features" or "User Management":

### Tenant Self-Service Profile Management

Tenants can manage their own profile information through a secure, user-friendly interface:

**Profile Management Features**:
- View profile information (name, email, role, account creation date)
- Update name and email address
- Change password with current password verification
- View assigned property details and building information
- Access manager/admin contact information

**Security Features**:
- Current password verification required for password changes
- Email uniqueness validation
- Password strength requirements (minimum 8 characters)
- CSRF protection on all updates
- Session-based authentication

**Localization**:
- Full support for English, Lithuanian, and Russian
- Localized error messages and UI labels
- Language switcher in profile interface

**Access**: Available at `/tenant/profile` for authenticated tenant users.

## Suggested Addition to SETUP.md or Installation Guide

### Tenant Profile Configuration

The tenant profile feature requires no additional configuration. It uses the following existing systems:

**Required Components**:
- Laravel 12 authentication system
- Session management
- Localization files (lang/en, lang/lt, lang/ru)
- User model with relationships

**Translation Files**:
Ensure the following translation files exist:
- `lang/en/tenant.php` - Tenant UI labels
- `lang/en/users.php` - Validation messages
- `lang/lt/tenant.php` - Lithuanian translations
- `lang/lt/users.php` - Lithuanian validation
- `lang/ru/tenant.php` - Russian translations
- `lang/ru/users.php` - Russian validation

**Testing**:
```bash
# Run tenant profile tests
php artisan test --filter=ProfileUpdateTest

# Verify all 14 tests pass
```

## Suggested Addition to User Guide

### For Tenants: Managing Your Profile

#### Accessing Your Profile

1. Log in to your tenant account
2. Click on your name in the top navigation
3. Select "Profile" from the dropdown menu
4. Or navigate directly to `/tenant/profile`

#### Updating Your Information

**To update your name or email**:
1. Navigate to your profile page
2. Scroll to the "Update Profile" section
3. Modify your name or email in the form
4. Click "Save Changes"
5. You'll see a success message confirming the update

**To change your password**:
1. Navigate to your profile page
2. Scroll to the "Change Password" section
3. Enter your current password
4. Enter your new password (minimum 8 characters)
5. Confirm your new password
6. Click "Save Changes"
7. You'll see a success message confirming the change

#### Profile Information Displayed

Your profile page shows:
- **Account Information**: Name, email, role, account creation date
- **Language Preference**: Current language and option to change
- **Assigned Property**: Your property address, type, and area
- **Building Information**: Building name and address (if applicable)
- **Manager Contact**: Your property manager's name and email

#### Security Notes

- Your current password is required to change your password
- Email addresses must be unique in the system
- Passwords must be at least 8 characters long
- All profile updates are protected by CSRF tokens
- Your session will remain active after profile updates

#### Troubleshooting

**"The current password is incorrect"**:
- Verify you entered your current password correctly
- Password is case-sensitive
- If you've forgotten your password, contact your property manager

**"The email has already been taken"**:
- This email is already registered in the system
- Choose a different email address
- Contact your property manager if you believe this is an error

**"The password must be at least 8 characters"**:
- Your new password is too short
- Use a password with at least 8 characters
- Consider using a mix of letters, numbers, and symbols

## Suggested Addition to API Documentation Index

### Tenant Profile API

**Endpoints**:
- `GET /tenant/profile` - View tenant profile
- `PUT /tenant/profile` - Update tenant profile

**Documentation**: See [Tenant Profile API](docs/api/tenant-profile-api.md)

**Features**:
- Profile information viewing
- Name and email updates
- Secure password changes
- Multi-language support

**Security**:
- Session-based authentication
- CSRF protection
- Current password verification
- Role-based access control

## Suggested Addition to CHANGELOG.md

### [Version X.X.X] - 2024-11-26

#### Added
- **Tenant Profile Management**: Tenants can now update their profile information
  - Update name and email address
  - Change password with current password verification
  - View assigned property and manager contact information
  - Multi-language support (EN/LT/RU)
  - Comprehensive validation and error handling
  - Full test coverage (14 test cases)

#### Security
- Added current password verification for password changes
- Implemented email uniqueness validation
- Enhanced CSRF protection on profile updates
- Added password strength requirements (minimum 8 characters)

#### Documentation
- Added comprehensive API documentation (docs/api/tenant-profile-api.md)
- Added feature documentation (docs/features/TENANT_PROFILE_UPDATE.md)
- Added code-level documentation (DocBlocks)
- Added architecture notes and patterns documentation

#### Testing
- Added 14 comprehensive test cases for profile updates
- 100% test coverage for controller and validation logic
- Tests cover happy paths, validation, and authorization scenarios

## Suggested Addition to Developer Documentation

### Extending the Tenant Profile Feature

#### Adding New Profile Fields

To add new fields to the tenant profile:

1. **Add database column** (if needed):
```bash
php artisan make:migration add_phone_to_users_table
```

2. **Update User model**:
```php
protected $fillable = [
    'name',
    'email',
    'phone', // New field
];
```

3. **Update FormRequest validation**:
```php
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'unique:users,email,' . $userId],
        'phone' => ['nullable', 'string', 'max:20'], // New rule
        // ... existing rules
    ];
}
```

4. **Update controller**:
```php
$user->fill([
    'name' => $validated['name'],
    'email' => $validated['email'],
    'phone' => $validated['phone'] ?? null, // New field
]);
```

5. **Update view**:
```blade
<div>
    <label for="phone">{{ __('tenant.profile.labels.phone') }}</label>
    <input type="tel" name="phone" id="phone" value="{{ old('phone', $user->phone) }}">
    @error('phone')
        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
    @enderror
</div>
```

6. **Add translations**:
```php
// lang/en/tenant.php
'profile' => [
    'labels' => [
        'phone' => 'Phone Number',
    ],
],
```

7. **Add tests**:
```php
test('tenant can update phone number', function () {
    $tenant = User::factory()->create(['role' => UserRole::TENANT]);
    
    $response = $this->actingAs($tenant)
        ->put(route('tenant.profile.update'), [
            'name' => $tenant->name,
            'email' => $tenant->email,
            'phone' => '+1234567890',
        ]);
    
    $response->assertRedirect();
    $tenant->refresh();
    expect($tenant->phone)->toBe('+1234567890');
});
```

#### Adding Profile Validation Rules

Custom validation rules can be added to `TenantUpdateProfileRequest`:

```php
use Illuminate\Validation\Rule;

public function rules(): array
{
    return [
        // ... existing rules
        'phone' => [
            'nullable',
            'string',
            'max:20',
            'regex:/^[+]?[0-9\s\-\(\)]+$/', // Phone format
        ],
        'timezone' => [
            'nullable',
            'string',
            Rule::in(timezone_identifiers_list()), // Valid timezone
        ],
    ];
}
```

#### Adding Profile Actions

To add new actions (e.g., delete account):

1. **Add route**:
```php
Route::delete('profile', [TenantProfileController::class, 'destroy'])
    ->name('tenant.profile.destroy');
```

2. **Add controller method**:
```php
public function destroy(Request $request): RedirectResponse
{
    $user = $request->user();
    
    // Validate user can be deleted
    if ($user->hasActiveInvoices()) {
        return back()->withErrors([
            'delete' => __('tenant.profile.cannot_delete_with_active_invoices')
        ]);
    }
    
    // Soft delete or hard delete
    $user->delete();
    
    // Log out user
    auth()->logout();
    
    return redirect()->route('login')
        ->with('success', __('tenant.profile.account_deleted'));
}
```

3. **Add tests**:
```php
test('tenant can delete their account', function () {
    $tenant = User::factory()->create(['role' => UserRole::TENANT]);
    
    $response = $this->actingAs($tenant)
        ->delete(route('tenant.profile.destroy'));
    
    $response->assertRedirect(route('login'));
    expect(User::find($tenant->id))->toBeNull();
});
```

## Files to Update

Based on this feature implementation, the following files should be updated:

1. **README.md**: Add tenant profile management to features list
2. **SETUP.md**: Add configuration notes (if any)
3. **CHANGELOG.md**: Add entry for this feature
4. **docs/README.md**: Update documentation index
5. **docs/api/README.md**: Add link to tenant profile API docs
6. **docs/testing/README.md**: Add reference to profile update tests

## Related Documentation

- [Tenant Profile Update Feature](docs/features/TENANT_PROFILE_UPDATE.md)
- [Tenant Profile API](docs/api/tenant-profile-api.md)
- [Hierarchical User Management Spec](.kiro/specs/3-hierarchical-user-management/)
- [Test Implementation](tests/Feature/Tenant/ProfileUpdateTest.php)
