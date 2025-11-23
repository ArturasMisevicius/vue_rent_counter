# Superadmin Resources

This document outlines all Filament resources that are restricted to superadmin access only.

## Superadmin-Only Resources

The following resources are accessible only to users with the `SUPERADMIN` role:

### 1. FAQ Resource (`FaqResource`)
- **Model**: `App\Models\Faq`
- **Navigation Group**: System
- **Navigation Sort**: 10
- **Purpose**: Manage frequently asked questions displayed on the public landing page
- **Features**:
  - Question and answer management
  - Category organization
  - Display order control
  - Publish/draft status
  - Rich text editor for answers

### 2. Organization Resource (`OrganizationResource`)
- **Model**: `App\Models\Organization`
- **Navigation Group**: System Management
- **Navigation Sort**: 1
- **Purpose**: Manage organizations and their subscriptions
- **Features**:
  - Organization details (name, slug, email, phone, domain)
  - Subscription plans (basic, professional, enterprise)
  - Property and user limits
  - Regional settings (timezone, locale, currency)
  - Suspension management
  - Trial period tracking

### 3. Subscription Resource (`SubscriptionResource`)
- **Model**: `App\Models\Subscription`
- **Navigation Group**: System Management
- **Navigation Sort**: 2
- **Purpose**: Manage subscription lifecycle and limits
- **Features**:
  - Plan type management
  - Subscription period tracking
  - Property and tenant limits
  - Status management (active, expired, suspended, cancelled)
  - Renewal actions
  - Expiry warnings

### 4. Organization Activity Log Resource (`OrganizationActivityLogResource`)
- **Model**: `App\Models\OrganizationActivityLog`
- **Navigation Group**: System Management
- **Navigation Sort**: 3
- **Purpose**: Audit trail for organization-level activities
- **Features**:
  - Read-only access (no create/edit)
  - Activity tracking by organization and user
  - Action type filtering (create, update, delete, view)
  - IP address logging
  - Date range filtering

### 5. Language Resource (`LanguageResource`)
- **Model**: `App\Models\Language`
- **Navigation Group**: System
- **Purpose**: Manage available languages in the system
- **Features**:
  - Language code and name management
  - Active/inactive status
  - System-wide language configuration

### 6. Translation Resource (`TranslationResource`)
- **Model**: `App\Models\Translation`
- **Navigation Group**: System
- **Purpose**: Manage translation strings across languages
- **Features**:
  - Translation key management
  - Multi-language support
  - Group organization
  - Translation status tracking

## Authorization Pattern

All superadmin resources follow this authorization pattern:

```php
public static function shouldRegisterNavigation(): bool
{
    return auth()->user()?->isSuperadmin() ?? false;
}

public static function canViewAny(): bool
{
    return auth()->user()?->isSuperadmin() ?? false;
}

public static function canCreate(): bool
{
    return auth()->user()?->isSuperadmin() ?? false;
}

public static function canEdit($record): bool
{
    return auth()->user()?->isSuperadmin() ?? false;
}

public static function canDelete($record): bool
{
    return auth()->user()?->isSuperadmin() ?? false;
}

public static function canView($record): bool
{
    return auth()->user()?->isSuperadmin() ?? false;
}
```

## Admin-Level Resources

For comparison, these resources are accessible to ADMIN role users:

- **UserResource**: User management within organization
- **TariffResource**: Tariff configuration
- **ProviderResource**: Utility provider management
- **BuildingResource**: Building management
- **PropertyResource**: Property management
- **MeterResource**: Meter management
- **MeterReadingResource**: Meter reading management
- **InvoiceResource**: Invoice management

## Security Notes

1. All superadmin resources use `withoutGlobalScopes()` in their Eloquent queries to access data across all tenants
2. Navigation visibility is controlled by `shouldRegisterNavigation()` method
3. All CRUD operations are protected by authorization methods
4. Activity logs are read-only to preserve audit integrity
5. Superadmin role is checked using the `isSuperadmin()` helper method on the User model

## Testing

When testing superadmin resources:

```php
// Create a superadmin user
$superadmin = User::factory()->create([
    'role' => UserRole::SUPERADMIN,
]);

// Act as superadmin
$this->actingAs($superadmin);

// Test resource access
$this->get(FaqResource::getUrl('index'))->assertSuccessful();
```

## Related Documentation

- [Hierarchical User Management Spec](../../.kiro/specs/hierarchical-user-management/)
- [Filament Admin Panel Spec](../../.kiro/specs/filament-admin-panel/)
- [Authorization Fix Summary](../../AUTHORIZATION_FIX_SUMMARY.md)
