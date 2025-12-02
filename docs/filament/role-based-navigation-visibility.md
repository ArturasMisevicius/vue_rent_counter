# Role-Based Navigation Visibility Configuration

This document outlines the role-based navigation visibility configuration for all Filament resources in the Vilnius Utilities Billing System.

## Overview

The Filament admin panel implements role-based navigation visibility to ensure users only see resources appropriate to their role. This is implemented through the `shouldRegisterNavigation()` method in each resource class.

## Role Hierarchy

The system supports four user roles with different access levels:

1. **SUPERADMIN**: Full system access across all organizations
2. **ADMIN**: Property owner with access to system configuration
3. **MANAGER**: Day-to-day operations within tenant scope (legacy role, similar to ADMIN)
4. **TENANT**: Apartment resident with limited access to their own data

## Navigation Groups

Resources are organized into the following navigation groups:

- **Operations**: Day-to-day operational resources (properties, buildings, meters, meter readings, invoices)
- **Configuration**: System configuration resources (tariffs, providers)
- **System Management**: System-level resources (users, subscriptions, organizations, FAQs)
- **Localization**: Localization resources (languages, translations)

## Resource Visibility Matrix

| Resource | Superadmin | Admin | Manager | Tenant | Navigation Group | Requirements |
|----------|------------|-------|---------|--------|------------------|--------------|
| **Operational Resources** |
| PropertyResource | ✅ | ✅ | ✅ | ❌ | Operations | 9.2 |
| BuildingResource | ✅ | ✅ | ✅ | ❌ | Operations | 9.2 |
| MeterResource | ✅ | ✅ | ✅ | ❌ | Operations | 9.2 |
| MeterReadingResource | ✅ | ✅ | ✅ | ✅ | Operations | 9.1, 9.2 |
| InvoiceResource | ✅ | ✅ | ✅ | ✅ | Operations | 9.1, 9.2 |
| **Configuration Resources** |
| TariffResource | ✅ | ✅ | ❌ | ❌ | Configuration | 9.3 |
| ProviderResource | ✅ | ✅ | ❌ | ❌ | Configuration | 9.3 |
| **System Management Resources** |
| UserResource | ✅ | ✅ | ✅ | ❌ | System | 9.3 |
| SubscriptionResource | ✅ | ✅ | ❌ | ❌ | System Management | 9.3 |
| OrganizationResource | ✅ | ❌ | ❌ | ❌ | System Management | 9.3 |
| FaqResource | ✅ | ✅ | ❌ | ❌ | System Management | 9.3 |
| **Localization Resources** |
| LanguageResource | ✅ | ❌ | ❌ | ❌ | Localization | 9.3 |
| TranslationResource | ✅ | ❌ | ❌ | ❌ | Localization | 9.3 |

## Implementation Details

### Operational Resources (Requirements 9.1, 9.2)

**Visible to: Superadmin, Admin, Manager, and (some) Tenant**

These resources handle day-to-day operations:

```php
// PropertyResource, BuildingResource, MeterResource
public static function shouldRegisterNavigation(): bool
{
    $user = auth()->user();
    return $user instanceof User && $user->role !== UserRole::TENANT;
}

// MeterReadingResource, InvoiceResource (tenant-accessible)
public static function shouldRegisterNavigation(): bool
{
    return auth()->check();
}
```

**Resources:**
- `PropertyResource`: Property management
- `BuildingResource`: Building management
- `MeterResource`: Meter management
- `MeterReadingResource`: Meter reading management (tenant-accessible)
- `InvoiceResource`: Invoice management (tenant-accessible)

### Configuration Resources (Requirement 9.3)

**Visible to: Superadmin, Admin only**

These resources manage system configuration:

```php
public static function shouldRegisterNavigation(): bool
{
    $user = auth()->user();
    return $user instanceof User && in_array($user->role, [
        UserRole::SUPERADMIN,
        UserRole::ADMIN,
    ], true);
}
```

**Resources:**
- `TariffResource`: Tariff configuration
- `ProviderResource`: Provider management

### System Management Resources (Requirement 9.3)

**Visible to: Varies by resource**

These resources manage system-level functionality:

```php
// UserResource (Superadmin, Admin, Manager)
public static function shouldRegisterNavigation(): bool
{
    $user = auth()->user();
    return $user instanceof User && in_array($user->role, [
        UserRole::SUPERADMIN,
        UserRole::ADMIN,
        UserRole::MANAGER,
    ], true);
}

// SubscriptionResource, FaqResource (Superadmin, Admin)
public static function shouldRegisterNavigation(): bool
{
    $user = auth()->user();
    return $user instanceof User && in_array($user->role, [
        UserRole::SUPERADMIN,
        UserRole::ADMIN,
    ], true);
}

// OrganizationResource (Superadmin only)
public static function shouldRegisterNavigation(): bool
{
    $user = auth()->user();
    return $user instanceof User && $user->role === UserRole::SUPERADMIN;
}
```

**Resources:**
- `UserResource`: User management (Superadmin, Admin, Manager)
- `SubscriptionResource`: Subscription management (Superadmin, Admin)
- `OrganizationResource`: Organization management (Superadmin only)
- `FaqResource`: FAQ management (Superadmin, Admin)

### Localization Resources (Requirement 9.3)

**Visible to: Superadmin only**

These resources manage localization:

```php
public static function shouldRegisterNavigation(): bool
{
    $user = auth()->user();
    return $user instanceof User && $user->role === UserRole::SUPERADMIN;
}
```

**Resources:**
- `LanguageResource`: Language configuration
- `TranslationResource`: Translation management

## Authorization Integration

Navigation visibility works in conjunction with Filament's authorization system:

1. **Navigation Visibility** (`shouldRegisterNavigation()`): Controls whether a resource appears in the navigation menu
2. **Policy Authorization** (`canViewAny()`, `canCreate()`, etc.): Controls whether a user can perform specific actions on a resource

Both layers work together to provide comprehensive access control:

```php
// Navigation visibility
public static function shouldRegisterNavigation(): bool
{
    $user = auth()->user();
    return $user instanceof User && $user->role !== UserRole::TENANT;
}

// Policy authorization
public static function canViewAny(): bool
{
    return auth()->check() && auth()->user()->can('viewAny', Property::class);
}
```

## Tenant Scope Integration

Resources that support tenant scoping automatically filter data based on the authenticated user's `tenant_id`:

- **Superadmin**: Sees all data across all tenants (bypasses tenant scope)
- **Admin/Manager**: Sees only data within their tenant scope
- **Tenant**: Sees only data for their assigned property

This is implemented through:
1. `BelongsToTenant` trait on models
2. `TenantScope` global scope
3. `scopeToUserTenant()` method in resources

## Testing

Role-based navigation visibility is tested through:

1. **Unit Tests**: Verify `shouldRegisterNavigation()` returns correct values for each role
2. **Feature Tests**: Verify navigation items appear/disappear based on authenticated user role
3. **Property Tests**: Verify tenant scope isolation across all resources

## Requirements Mapping

- **Requirement 9.1**: Tenant users restricted to tenant-specific resources (MeterReadingResource, InvoiceResource)
- **Requirement 9.2**: Manager users access operational resources (Properties, Buildings, Meters, MeterReadings, Invoices)
- **Requirement 9.3**: Admin users access all resources including system configuration (Tariffs, Providers, Users, Subscriptions)
- **Requirement 9.5**: Policy classes integrated for authorization (all resources implement `canViewAny()`, `canCreate()`, etc.)

## Maintenance

When adding new resources:

1. Determine the appropriate navigation group
2. Implement `shouldRegisterNavigation()` based on role requirements
3. Integrate with existing policy classes
4. Update this documentation
5. Add tests for navigation visibility

## Related Documentation

- [Filament Admin Panel Requirements](.kiro/specs/4-filament-admin-panel/requirements.md)
- [Filament Admin Panel Design](.kiro/specs/4-filament-admin-panel/design.md)
- [User Role Enum](app/Enums/UserRole.php)
- [Tenant Scope Documentation](../architecture/MULTI_TENANT_ARCHITECTURE.md)
