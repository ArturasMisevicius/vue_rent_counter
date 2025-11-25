# Filament Documentation Index

## Overview

This directory contains comprehensive documentation for all Filament 4.x resources, pages, widgets, and components in the Vilnius Utilities Billing Platform.

## Resources

### Building Management
- **[BuildingResource](./BUILDING_RESOURCE.md)** - Complete user guide
  - Role-based authorization
  - Form schema and validation
  - Table configuration
  - Tenant scoping
  - Properties relation manager
  - Testing coverage
- **[BuildingResource API](./BUILDING_RESOURCE_API.md)** - API reference
  - Method signatures
  - Authorization matrix
  - Translation keys
  - Usage examples
- **[BuildingResource Summary](./BUILDING_RESOURCE_SUMMARY.md)** - Documentation overview

### Property Management
- PropertyResource - *Documentation pending*
- PropertiesRelationManager - *Documentation pending*

### Meter Management
- MeterResource - *Documentation pending*
- MeterReadingResource - *Documentation pending*

### Billing Management
- InvoiceResource - *Documentation pending*
- TariffResource - *Documentation pending*
- ProviderResource - *Documentation pending*

### User Management
- UserResource - *Documentation pending*
- SubscriptionResource - *Documentation pending*
- OrganizationResource - *Documentation pending*
- OrganizationActivityLogResource - *Documentation pending*

### Content Management
- **[FaqResource API](./FAQ_RESOURCE_API.md)** - API reference
  - Admin/Superadmin access control
  - Rich text editor for answers
  - Category organization
  - Display order management
  - Publication status control
  - Filament 4 namespace consolidation
- LanguageResource - *Documentation pending*
- TranslationResource - *Documentation pending*

## Framework Guides

- **[Filament V4 Compatibility Guide](./FILAMENT_V4_COMPATIBILITY_GUIDE.md)** - Migration from v3 to v4
- **[Validation Localization](./VALIDATION_LOCALIZATION_COMPLETE.md)** - Translation integration
- **[Admin Panel Guide](../admin/ADMIN_PANEL_GUIDE.md)** - General admin panel usage
- **[Admin Panel Testing](../admin/ADMIN_PANEL_TESTING.md)** - Testing strategies

## Common Patterns

### Authorization
All resources delegate authorization to Laravel policies:
```php
public static function canViewAny(): bool
{
    $user = self::getAuthenticatedUser();
    return $user?->can('viewAny', Building::class) ?? false;
}
```

### Tenant Scoping
Resources automatically scope data by tenant_id:
```php
protected function mutateFormDataBeforeCreate(array $data): array
{
    $data['tenant_id'] = auth()->user()->tenant_id;
    return $data;
}
```

### Localization
All UI strings use Laravel's translation system:
```php
->label(__('buildings.labels.name'))
->validationMessages(self::getValidationMessages('name'))
```

### Validation
Resources use the `HasTranslatedValidation` trait:
```php
use HasTranslatedValidation;

protected static string $translationPrefix = 'buildings.validation';
```

## Testing

All resources have comprehensive test coverage:
- Navigation visibility tests
- Authorization tests (view, create, edit, delete)
- Configuration tests
- Form schema tests
- Table configuration tests
- Relation manager tests
- Page registration tests

Run tests:
```bash
php artisan test --filter=BuildingResourceTest
php artisan test --filter=Filament
```

## Related Documentation

- [Multi-Tenant Architecture](../architecture/MULTI_TENANT_ARCHITECTURE.md)
- [Database Schema Guide](../architecture/DATABASE_SCHEMA_AND_MIGRATION_GUIDE.md)
- [Service Layer Guide](../architecture/SERVICE_AND_REPOSITORY_GUIDE.md)
- [API Architecture](../api/API_ARCHITECTURE_GUIDE.md)

## Contributing

When documenting new resources:
1. Create user guide (`{RESOURCE}_RESOURCE.md`)
2. Create API reference (`{RESOURCE}_RESOURCE_API.md`)
3. Add comprehensive inline DocBlocks
4. Document authorization matrix
5. Document form fields and validation
6. Document table columns and filters
7. Add usage examples
8. Update this index
9. Update CHANGELOG.md

## Support

For questions or issues:
1. Check resource-specific documentation
2. Review framework guides
3. Run tests to verify behavior
4. Check logs: `php artisan pail`
5. Verify policies: `php artisan tinker`
