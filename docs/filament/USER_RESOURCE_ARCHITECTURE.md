# UserResource Architecture

**Date**: 2025-11-26  
**Version**: Filament v4  
**Status**: ✅ PRODUCTION READY

## Overview

The UserResource is a Filament v4 admin resource that provides complete CRUD operations for user management in the Vilnius Utilities Billing Platform. It implements hierarchical role-based access control with strict tenant isolation.

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                      UserResource                            │
│  (Filament v4 Resource - Admin Panel Interface)             │
└─────────────────────────────────────────────────────────────┘
                            │
                            │ uses
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    HasTranslatedValidation                   │
│         (Trait - Localized Validation Messages)             │
└─────────────────────────────────────────────────────────────┘
                            │
        ┌───────────────────┼───────────────────┐
        │                   │                   │
        ▼                   ▼                   ▼
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│  User Model  │    │  UserPolicy  │    │ Translations │
│              │    │              │    │              │
│ - Eloquent   │    │ - viewAny()  │    │ users.php    │
│ - BelongsTo  │    │ - view()     │    │ - labels     │
│   Tenant     │    │ - create()   │    │ - validation │
│ - Roles      │    │ - update()   │    │ - messages   │
│ - Scopes     │    │ - delete()   │    │              │
└──────────────┘    └──────────────┘    └──────────────┘
        │                   │
        │                   │ logs to
        │                   ▼
        │           ┌──────────────┐
        │           │ Audit Channel│
        │           │              │
        │           │ - Operations │
        │           │ - Actor Info │
        │           │ - Target Info│
        │           │ - IP/UA      │
        │           └──────────────┘
        │
        ▼
┌─────────────────────────────────────────────────────────────┐
│                    Database (users table)                    │
│                                                              │
│  Indexes:                                                    │
│  - tenant_id (for scoping)                                  │
│  - role (for filtering)                                     │
│  - is_active (for filtering)                                │
│  - email (unique)                                           │
└─────────────────────────────────────────────────────────────┘
```

## Component Relationships

### Core Components

#### 1. UserResource (Filament Resource)
- **Location**: `app/Filament/Resources/UserResource.php`
- **Purpose**: Provides admin interface for user management
- **Dependencies**:
  - User Model
  - UserPolicy
  - HasTranslatedValidation trait
  - UserRole enum
  - Filament v4 components

#### 2. User Model
- **Location**: `app/Models/User.php`
- **Purpose**: Eloquent model representing users
- **Traits**:
  - `BelongsToTenant` - Automatic tenant scoping
  - `HasFactory` - Factory support for testing
  - `Notifiable` - Email notifications
- **Relationships**:
  - `belongsTo(User::class, 'tenant_id')` - Parent user (organization)
  - `hasMany(User::class, 'tenant_id')` - Child users

#### 3. UserPolicy
- **Location**: `app/Policies/UserPolicy.php`
- **Purpose**: Authorization logic for user operations
- **Methods**:
  - `viewAny()` - List users
  - `view()` - View user details
  - `create()` - Create new users
  - `update()` - Edit user details
  - `delete()` - Delete users
  - `restore()` - Restore soft-deleted users
  - `forceDelete()` - Permanently delete users
  - `impersonate()` - Impersonate users (Superadmin only)

#### 4. Resource Pages
- **ListUsers**: Table view with search, sort, filter
- **CreateUser**: Form for creating new users
- **ViewUser**: Read-only infolist view
- **EditUser**: Form for editing existing users

## Data Flow

### Creating a User

```
User Input (Filament Form)
        │
        ▼
Form Validation (HasTranslatedValidation)
        │
        ▼
Authorization Check (UserPolicy::create)
        │
        ▼
Password Hashing (Hash::make)
        │
        ▼
Tenant Assignment (based on role)
        │
        ▼
Database Insert (users table)
        │
        ▼
Success Notification
```

### Viewing Users (List)

```
Page Load
        │
        ▼
Authorization Check (UserPolicy::viewAny)
        │
        ▼
Query Building (getEloquentQuery)
        │
        ├─ Superadmin: No scoping
        │
        └─ Admin/Manager: Tenant scoping
        │
        ▼
Apply Filters (role, is_active)
        │
        ▼
Apply Search (name, email, tenant)
        │
        ▼
Apply Sort (default: name ASC)
        │
        ▼
Paginate Results
        │
        ▼
Render Table
```

### Updating a User

```
User Input (Filament Form)
        │
        ▼
Form Validation (HasTranslatedValidation)
        │
        ▼
Authorization Check (UserPolicy::update)
        │
        ▼
Password Hashing (if password changed)
        │
        ▼
Tenant Validation (based on role)
        │
        ▼
Database Update (users table)
        │
        ▼
Audit Logging (if sensitive operation)
        │
        ▼
Success Notification
```

## Security Architecture

### Multi-Tenant Isolation

```
┌─────────────────────────────────────────────────────────────┐
│                    Request Flow                              │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
                    ┌──────────────┐
                    │ Authenticate │
                    └──────────────┘
                            │
                            ▼
                    ┌──────────────┐
                    │ Get User Role│
                    └──────────────┘
                            │
        ┌───────────────────┼───────────────────┐
        │                   │                   │
        ▼                   ▼                   ▼
┌──────────────┐    ┌──────────────┐    ┌──────────────┐
│  Superadmin  │    │ Admin/Manager│    │    Tenant    │
│              │    │              │    │              │
│ No Scoping   │    │ Tenant Scope │    │ No Access    │
│ See All      │    │ tenant_id=X  │    │ (Hidden Nav) │
└──────────────┘    └──────────────┘    └──────────────┘
        │                   │
        └───────────────────┘
                            │
                            ▼
                    ┌──────────────┐
                    │ Apply Policy │
                    └──────────────┘
                            │
                            ▼
                    ┌──────────────┐
                    │ Execute Query│
                    └──────────────┘
                            │
                            ▼
                    ┌──────────────┐
                    │ Return Results│
                    └──────────────┘
```

### Authorization Layers

1. **Navigation Layer**: `shouldRegisterNavigation()`
   - Hides resource from Tenant users
   - Shows to Superadmin, Admin, Manager

2. **Policy Layer**: `UserPolicy`
   - Enforces role-based permissions
   - Validates tenant boundaries
   - Prevents self-deletion
   - Logs sensitive operations

3. **Query Layer**: `getEloquentQuery()`
   - Applies tenant scoping
   - Filters results by tenant_id
   - Bypasses scoping for Superadmin

4. **Form Layer**: Conditional field visibility
   - Shows/hides tenant field based on role
   - Requires tenant for Manager/Tenant roles
   - Optional tenant for Admin/Superadmin

### Audit Logging

All sensitive operations are logged to the audit channel:

```php
Log::channel('audit')->info("User {$operation} operation", [
    'operation' => $operation,
    'actor_id' => $user->id,
    'actor_email' => $user->email,
    'actor_role' => $user->role->value,
    'target_id' => $model->id,
    'target_email' => $model->email,
    'target_role' => $model->role->value,
    'actor_tenant_id' => $user->tenant_id,
    'target_tenant_id' => $model->tenant_id,
    'ip' => $request->ip(),
    'user_agent' => $request->userAgent(),
    'timestamp' => now()->toIso8601String(),
]);
```

**Logged Operations**:
- Update user
- Delete user
- Restore user
- Force delete user
- Impersonate user

## Form Architecture

### Section-Based Layout

The form uses a two-section layout for better organization:

#### Section 1: User Details
- Name (required, max 255)
- Email (required, unique, email format)
- Password (required on create, min 8, confirmed)
- Password Confirmation (validation only)

#### Section 2: Role and Access
- Role (required, enum, live updates)
- Tenant (conditional visibility/requirement)
- Is Active (toggle, default true)

### Conditional Field Logic

```php
// Tenant field visibility
->visible(fn (Forms\Get $get): bool => in_array($get('role'), [
    UserRole::MANAGER->value,
    UserRole::TENANT->value,
    UserRole::ADMIN->value,
], true))

// Tenant field requirement
->required(fn (Forms\Get $get): bool => in_array($get('role'), [
    UserRole::MANAGER->value,
    UserRole::TENANT->value,
], true))
```

### Password Handling

```php
Forms\Components\TextInput::make('password')
    ->password()
    ->required(fn (string $operation): bool => $operation === 'create')
    ->dehydrated(fn (?string $state): bool => filled($state))
    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
    ->minLength(8)
    ->confirmed()
```

**Security Features**:
- Hashed before storage using `Hash::make()`
- Only dehydrated if filled (allows optional password updates)
- Never displayed in table or view
- Requires confirmation

## Table Architecture

### Column Configuration

| Column | Type | Features |
|--------|------|----------|
| Name | TextColumn | Searchable, Sortable, Medium weight |
| Email | TextColumn | Searchable, Sortable, Copyable |
| Role | TextColumn | Badge with color coding, Sortable |
| Tenant | TextColumn | Searchable, Sortable, Toggleable |
| Is Active | IconColumn | Boolean display, Sortable, Toggleable |
| Created At | TextColumn | DateTime, Sortable, Hidden by default |

### Filter Configuration

#### Role Filter
- Type: SelectFilter
- Options: All UserRole enum values
- Multi-select: No
- Native: Disabled (better UX)

#### Is Active Filter
- Type: TernaryFilter
- Options: All / Active Only / Inactive Only
- Default: All

### Session Persistence

```php
->persistSortInSession()
->persistSearchInSession()
->persistFiltersInSession()
```

Ensures user preferences are maintained across page loads.

## Performance Considerations

### Query Optimization

1. **Tenant Scoping at Query Level**
   ```php
   $query->where('tenant_id', $user->tenant_id);
   ```
   - Applied before pagination
   - Uses indexed column
   - Reduces result set early

2. **Relationship Preloading**
   ```php
   ->relationship('parentUser', 'name', 
       modifyQueryUsing: fn ($query) => self::scopeToUserTenant($query)
   )
   ->preload()
   ```
   - Prevents N+1 queries
   - Scoped to user's tenant
   - Cached for form rendering

3. **Navigation Badge Caching**
   - Consider caching badge count for high-traffic scenarios
   - Current implementation queries on each request
   - Potential optimization: Cache with tenant_id key

### Database Indexes

Required indexes for optimal performance:

```sql
-- Tenant scoping (most important)
CREATE INDEX idx_users_tenant_id ON users(tenant_id);

-- Role filtering
CREATE INDEX idx_users_role ON users(role);

-- Active status filtering
CREATE INDEX idx_users_is_active ON users(is_active);

-- Email uniqueness (already exists)
CREATE UNIQUE INDEX idx_users_email ON users(email);

-- Composite index for common queries
CREATE INDEX idx_users_tenant_role ON users(tenant_id, role);
```

## Localization Architecture

### Translation Structure

All user-facing text is localized using Laravel's translation system:

```
lang/
└── {locale}/
    └── users.php
        ├── labels
        ├── placeholders
        ├── helper_text
        ├── sections
        ├── filters
        ├── tooltips
        ├── empty_state
        └── validation
```

### Translation Loading

```php
protected static string $translationPrefix = 'users.validation';
```

The `HasTranslatedValidation` trait automatically loads validation messages from the specified prefix.

### Supported Locales

- English (en) - Primary
- Lithuanian (lt) - Available
- Russian (ru) - Available

## Testing Architecture

### Test Coverage

1. **Unit Tests**
   - Form validation rules
   - Password hashing
   - Tenant scoping logic
   - Helper methods

2. **Feature Tests**
   - CRUD operations
   - Authorization checks
   - Tenant isolation
   - Role-based access

3. **Property Tests** (Planned)
   - Property 13: Validation consistency
   - Property 14: Conditional tenant requirement
   - Property 15: Null tenant allowance

### Test Helpers

```php
// From TestCase.php
$this->actingAsAdmin();
$this->actingAsManager();
$this->actingAsTenant();
$this->actingAsSuperadmin();
```

## Integration Points

### Upstream Dependencies

- **Filament v4**: Admin panel framework
- **Laravel 12**: Application framework
- **Livewire 3**: Real-time form updates
- **Tailwind CSS 4**: Styling (via CDN)

### Downstream Consumers

- **Property Management**: Users assigned to properties
- **Meter Reading**: Users submit readings
- **Invoice Management**: Users view/manage invoices
- **Audit Logging**: User actions tracked
- **Notifications**: Users receive emails

## Deployment Considerations

### Database Migrations

Ensure these migrations are run:
- `create_users_table` - Base user table
- `add_hierarchical_columns_to_users_table` - Tenant support

### Configuration

No additional configuration required. Uses standard Laravel auth configuration.

### Monitoring

Monitor these metrics:
- User creation rate
- Failed login attempts
- Authorization failures
- Audit log volume
- Query performance (tenant scoping)

## Future Enhancements

### Planned Features

1. **Bulk Actions**
   - Activate/deactivate multiple users
   - Bulk role changes
   - Bulk tenant reassignment

2. **Advanced Filtering**
   - Filter by creation date range
   - Filter by last login date
   - Filter by tenant hierarchy

3. **Export Functionality**
   - Export user list to CSV/Excel
   - Include role and tenant information
   - Respect tenant scoping

4. **Impersonation UI**
   - Impersonate user from table actions
   - Visual indicator when impersonating
   - Easy switch back to original user

5. **Password Reset**
   - Send password reset email from UI
   - Track password reset requests
   - Enforce password expiry

### Technical Debt

None identified. Code is production-ready.

## Related Documentation

- [UserResource API Documentation](./USER_RESOURCE_API.md)
- [UserResource Usage Guide](./USER_RESOURCE_USAGE_GUIDE.md)
- [User Model Documentation](../../app/Models/User.php)
- [UserPolicy Documentation](../../app/Policies/UserPolicy.php)
- [Filament v4 Documentation](https://filamentphp.com/docs/4.x)
- [Laravel 12 Documentation](https://laravel.com/docs/12.x)

## Changelog

### 2025-11-26
- ✅ Initial architecture documentation
- ✅ Documented all components and relationships
- ✅ Documented security architecture
- ✅ Documented performance considerations
- ✅ Documented testing strategy
