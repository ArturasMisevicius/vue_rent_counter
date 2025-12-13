# SuperAdmin Organizations Create View Documentation

**File:** `resources/views/superadmin/organizations/create.blade.php`  
**Controller:** `App\Http\Controllers\Superadmin\OrganizationController@create`  
**Route:** `GET /superadmin/organizations/create` (name: `superadmin.organizations.create`)  
**Last Updated:** December 13, 2025  
**Status:** ✅ Fully Localized & Documented

---

## Overview

The SuperAdmin Organizations Create view provides a comprehensive form interface for system administrators to create new tenant organizations with associated admin accounts and subscription plans. This is a critical system entry point that initializes multi-tenant data structures and establishes organizational hierarchies.

### Purpose

- Create new tenant organizations with unique `tenant_id` values
- Provision admin user accounts with authentication credentials
- Configure initial subscription plans with expiration dates
- Initialize isolated data structures for multi-tenant architecture

### User Role

**SuperAdmin Only** - This view is restricted to users with `UserRole::SUPERADMIN` via the `role:superadmin` middleware.

---

## Architecture

### Component Relationships

```
┌─────────────────────────────────────────────────────────────┐
│  SuperAdmin Organizations Create View                       │
│  resources/views/superadmin/organizations/create.blade.php  │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ├─ Extends: layouts.app
                     │
                     ├─ Uses Components:
                     │  └─ x-card (Blade component)
                     │
                     ├─ Form Action:
                     │  └─ POST /superadmin/organizations
                     │     (route: superadmin.organizations.store)
                     │
                     └─ Controller Flow:
                        └─ OrganizationController@store
                           └─ StoreOrganizationRequest (validation)
                              └─ AccountManagementService@createAdminAccount
                                 ├─ Generate unique tenant_id
                                 ├─ Create User (role: ADMIN)
                                 ├─ SubscriptionService@createSubscription
                                 └─ Audit logging
```

### Data Flow

1. **View Rendering** (`OrganizationController@create`)
   - No data required (static form)
   - Returns view with empty form

2. **Form Submission** (`OrganizationController@store`)
   - Validates via `StoreOrganizationRequest`
   - Delegates to `AccountManagementService@createAdminAccount`
   - Creates admin user with unique `tenant_id`
   - Creates subscription record
   - Logs action in audit trail
   - Redirects to organization detail view

3. **Service Layer** (`AccountManagementService`)
   - Pre-validates data before transaction
   - Pre-hashes password outside transaction (performance optimization)
   - Generates unique 6-digit `tenant_id` (100000-999999)
   - Creates user and subscription in single transaction
   - Invalidates tenant ID cache
   - Returns fresh user with subscription relationship

---

## Form Structure

### Organization Information Section

**Translation Key:** `superadmin.dashboard.organizations_create.organization_info`

| Field | Name | Type | Required | Validation | Translation Key |
|-------|------|------|----------|------------|-----------------|
| Organization Name | `organization_name` | text | Yes | max:255 | `organization_name` |

**Purpose:** Identifies the tenant organization in the system.

### Admin Contact Information Section

**Translation Key:** `superadmin.dashboard.organizations_create.admin_contact`

| Field | Name | Type | Required | Validation | Translation Key |
|-------|------|------|----------|------------|-----------------|
| Contact Name | `name` | text | Yes | max:255 | `contact_name` |
| Email Address | `email` | email | Yes | unique:users, max:255 | `email` |
| Password | `password` | password | Yes | min:8 | `password` |

**Password Hint:** `superadmin.dashboard.organizations_create.password_hint` → "Minimum 8 characters"

**Purpose:** Creates the admin user account with authentication credentials.

### Subscription Details Section

**Translation Key:** `superadmin.dashboard.organizations_create.subscription_details`

| Field | Name | Type | Required | Validation | Translation Key |
|-------|------|------|----------|------------|-----------------|
| Plan Type | `plan_type` | select | Yes | in:SubscriptionPlanType::values() | `plan_type` |
| Expiry Date | `expires_at` | date | Yes | after:today | `expiry_date` |

**Plan Options:**
- `BASIC` - 10 properties, 50 tenants
- `PROFESSIONAL` - 50 properties, 200 tenants
- `ENTERPRISE` - Unlimited

**Default Expiry:** 1 year from current date (`now()->addYear()`)

**Purpose:** Configures subscription limits and expiration for the organization.

---

## Validation Rules

### Request Validation (`StoreOrganizationRequest`)

```php
[
    'name' => ['required', 'string', 'max:255'],
    'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
    'password' => ['required', 'string', 'min:8'],
    'organization_name' => ['required', 'string', 'max:255'],
    'plan_type' => ['required', Rule::in(SubscriptionPlanType::values())],
    'expires_at' => ['required', 'date', 'after:today'],
]
```

### Service-Level Validation (`AccountManagementService`)

Additional validation performed in `validateAdminAccountData()`:
- Email uniqueness check
- Plan type enum validation
- Expiry date must be in the future

### Error Handling

**Validation Errors:**
- Displayed inline below each field
- Uses `@error('field_name')` Blade directive
- Error messages localized via `organizations.validation.*` keys

**Business Logic Errors:**
- Caught in controller and redirected with flash messages
- Uses `notifications.organization.*` translation keys

---

## Localization

### Translation Keys Used

All translation keys are defined in `lang/{locale}/superadmin.php` under `dashboard.organizations_create.*`:

#### Page Structure
- `title` - "Create New Organization"
- `subtitle` - "Create a new admin account with subscription"

#### Section Headers
- `organization_info` - "Organization Information"
- `admin_contact` - "Admin Contact Information"
- `subscription_details` - "Subscription Details"

#### Form Labels
- `organization_name` - "Organization Name"
- `contact_name` - "Contact Name"
- `email` - "Email Address"
- `password` - "Password"
- `password_hint` - "Minimum 8 characters"
- `plan_type` - "Plan Type"
- `select_plan` - "Select a plan"
- `expiry_date` - "Expiry Date"

#### Plan Limits
- `plan_limits.basic` - "(10 properties, 50 tenants)"
- `plan_limits.professional` - "(50 properties, 200 tenants)"
- `plan_limits.enterprise` - "(Unlimited)"

#### Actions
- `actions.cancel` - "Cancel"
- `actions.create` - "Create Organization"

### Supported Locales

- **English (en)** - Primary locale
- **Lithuanian (lt)** - Full translation available

### Validation Messages

Validation error messages are defined in `lang/{locale}/organizations.php` under `validation.*`:

```php
'organizations.validation.name.required'
'organizations.validation.email.unique'
'organizations.validation.password.min'
'organizations.validation.plan_type.in'
'organizations.validation.expires_at.after'
// ... etc
```

---

## Security Considerations

### Authentication & Authorization

1. **Middleware Stack:**
   - `auth` - Ensures user is authenticated
   - `role:superadmin` - Restricts access to SuperAdmin role only

2. **CSRF Protection:**
   - `@csrf` token included in form
   - Validated by `VerifyCsrfToken` middleware

3. **Password Security:**
   - Minimum 8 characters enforced
   - Hashed using `Hash::make()` before storage
   - Pre-hashed outside transaction for performance

### Data Isolation

1. **Tenant ID Generation:**
   - Random 6-digit ID (100000-999999)
   - Collision detection via database check
   - Prevents tenant enumeration attacks
   - No sequential IDs that expose tenant count

2. **Multi-Tenancy:**
   - Each organization gets unique `tenant_id`
   - All child resources inherit this `tenant_id`
   - Enforced via `TenantScope` global scope

### Audit Trail

All organization creation actions are logged via `AccountManagementService@logAccountAction()`:
- User ID of created admin
- SuperAdmin who performed the action
- Timestamp of creation
- Stored in `user_assignments_audit` table

---

## Performance Optimizations

### Service Layer Optimizations

1. **Pre-Transaction Validation:**
   - Validates data before opening transaction
   - Reduces database lock time

2. **Password Hashing:**
   - Hashed outside transaction (expensive operation)
   - Reduces transaction duration by ~50-100ms

3. **Subscription Data Parsing:**
   - Date parsing done outside transaction
   - Minimizes transaction scope

4. **Cache Management:**
   - Invalidates `max_tenant_id` cache after creation
   - Uses `Cache::forget()` for immediate consistency

### Database Optimizations

1. **Single Transaction:**
   - User and subscription created atomically
   - Prevents orphaned records

2. **Eager Loading:**
   - Returns `fresh(['subscription'])` after creation
   - Avoids N+1 query on redirect

---

## Usage Examples

### Creating a Basic Organization

```http
POST /superadmin/organizations
Content-Type: application/x-www-form-urlencoded

name=John+Doe
email=john@example.com
password=SecurePass123
organization_name=Example+Corp
plan_type=basic
expires_at=2026-12-13
```

### Expected Response

**Success:**
- Redirect to: `/superadmin/organizations/{id}`
- Flash message: `notifications.organization.created`

**Validation Error:**
- Redirect back to form with errors
- Old input preserved via `old()` helper

---

## Related Documentation

### Controllers
- [OrganizationController](../controllers/SUPERADMIN_ORGANIZATION_CONTROLLER.md)
- [SubscriptionController](../controllers/SUPERADMIN_SUBSCRIPTION_CONTROLLER.md)

### Services
- [AccountManagementService](../services/ACCOUNT_MANAGEMENT_SERVICE.md)
- [SubscriptionService](../services/SUBSCRIPTION_SERVICE.md)

### Models
- [User Model](../models/USER_MODEL.md)
- [Subscription Model](../models/SUBSCRIPTION_MODEL.md)

### Requests
- [StoreOrganizationRequest](../requests/STORE_ORGANIZATION_REQUEST.md)

### Views
- [Organizations Index](./SUPERADMIN_ORGANIZATIONS_INDEX_VIEW.md)
- [Organizations Show](./SUPERADMIN_ORGANIZATIONS_SHOW_VIEW.md)
- [Organizations Edit](./SUPERADMIN_ORGANIZATIONS_EDIT_VIEW.md)

### Architecture
- [Multi-Tenancy Architecture](../architecture/MULTI_TENANCY.md)
- [Subscription System](../architecture/SUBSCRIPTION_SYSTEM.md)
- [Hierarchical Access Control](../architecture/HIERARCHICAL_ACCESS.md)

---

## Testing

### Manual Testing Checklist

- [ ] Form renders correctly with all sections
- [ ] All translation keys display properly in EN and LT
- [ ] Required field validation works
- [ ] Email uniqueness validation works
- [ ] Password minimum length validation works
- [ ] Plan type dropdown shows all options with limits
- [ ] Date picker enforces future dates only
- [ ] CSRF token is present in form
- [ ] Successful submission creates user and subscription
- [ ] Unique tenant_id is generated
- [ ] Redirect to organization detail page works
- [ ] Success flash message displays
- [ ] Audit log entry is created

### Automated Testing

**Feature Test:** `tests/Feature/Superadmin/OrganizationCreationTest.php`

```php
test('superadmin can create organization with subscription', function () {
    $superadmin = User::factory()->superadmin()->create();
    
    $response = $this->actingAs($superadmin)
        ->post(route('superadmin.organizations.store'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePass123',
            'organization_name' => 'Example Corp',
            'plan_type' => 'basic',
            'expires_at' => now()->addYear()->format('Y-m-d'),
        ]);
    
    $response->assertRedirect();
    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
        'role' => 'admin',
    ]);
});
```

---

## Changelog

### December 13, 2025
- ✅ Replaced hardcoded strings with translation keys
- ✅ Updated title: `__('superadmin.dashboard.organizations_create.title')`
- ✅ Updated subtitle: `__('superadmin.dashboard.organizations_create.subtitle')`
- ✅ Created comprehensive documentation
- ✅ Documented all form fields and validation rules
- ✅ Documented security considerations and performance optimizations

### Previous Updates
- Initial view creation with full localization support
- Integration with AccountManagementService
- CSRF protection and validation implementation

---

## Future Enhancements

### Planned Features
- [ ] Email verification for new admin accounts
- [ ] Custom subscription plan creation
- [ ] Bulk organization import via CSV
- [ ] Organization templates for faster setup
- [ ] Two-factor authentication setup during creation

### Performance Improvements
- [ ] Async subscription creation via queue
- [ ] Tenant ID pre-generation pool
- [ ] Redis caching for plan type options

---

## Support & Troubleshooting

### Common Issues

**Issue:** "Email already exists" error
- **Cause:** Email is already registered in the system
- **Solution:** Use a different email address or check existing users

**Issue:** "Expiry date must be after today" error
- **Cause:** Selected date is in the past or today
- **Solution:** Select a future date (minimum tomorrow)

**Issue:** Form submission returns 419 error
- **Cause:** CSRF token expired or missing
- **Solution:** Refresh the page and resubmit

### Debug Mode

Enable debug logging in `AccountManagementService`:

```php
// Add to createAdminAccount() method
\Log::channel('superadmin')->info('Creating admin account', [
    'email' => $data['email'],
    'organization' => $data['organization_name'],
    'plan' => $data['plan_type'],
]);
```

---

## Accessibility

### WCAG 2.1 Compliance

- ✅ All form fields have associated `<label>` elements
- ✅ Required fields marked with `<span class="text-red-500">*</span>`
- ✅ Error messages use semantic HTML and ARIA attributes
- ✅ Focus states visible on all interactive elements
- ✅ Color contrast meets AA standards (slate-700 on white)

### Keyboard Navigation

- Tab order follows logical form flow
- Enter key submits form
- Escape key can be used to cancel (via browser default)

---

## Design System Integration

### Tailwind Classes Used

**Layout:**
- `container mx-auto px-4 py-8` - Main container
- `max-w-2xl mx-auto` - Form width constraint

**Typography:**
- `text-3xl font-bold text-slate-900` - Page title
- `text-lg font-semibold text-slate-900` - Section headers
- `text-sm font-medium text-slate-700` - Field labels

**Form Elements:**
- `w-full px-3 py-2 border border-slate-300 rounded` - Input base
- `focus:outline-none focus:ring-2 focus:ring-blue-500` - Focus states
- `@error('field') border-red-500 @enderror` - Error states

**Buttons:**
- `px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700` - Primary action
- `px-4 py-2 bg-slate-300 text-slate-700 rounded hover:bg-slate-400` - Secondary action

### Component Usage

**x-card Component:**
- Provides consistent card styling across admin interface
- Includes padding and shadow effects
- Responsive design built-in

---

**Document Version:** 1.0  
**Maintained By:** Development Team  
**Review Cycle:** Quarterly or on major changes
