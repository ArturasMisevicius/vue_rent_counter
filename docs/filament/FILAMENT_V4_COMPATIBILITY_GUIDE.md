# Filament V4 Compatibility Guide (Navigation + Form Signatures)

**Context:** Laravel 12 + Filament v4 upgrade introduced stricter typed properties and new `Filament\Schemas\Schema` for forms. Earlier resources used nullable string navigation props and `Filament\Forms\Form`, causing fatals during boot. This guide documents the required patterns and the fixes applied in this repo.

## Required Patterns
- **Navigation typing:** Replace static props with accessors to satisfy `string|BackedEnum|null` and `string|UnitEnum|null`.
  ```php
  public static function getNavigationIcon(): string|BackedEnum|null
  {
      return 'heroicon-o-cpu-chip';
  }

  public static function getNavigationGroup(): string|UnitEnum|null
  {
      return 'Operations';
  }
  ```
- **Form signature:** Base class expects `public static function form(Schema $schema): Schema`. Use `Filament\Schemas\Schema` (not `Filament\Forms\Form`). Tests include a `class_alias` shim in `tests/Pest.php` for backward compat during transition.
- **Tenant scope:** Keep `BelongsToTenant` and relationship filters intact in form queries (e.g., property selectors filtering by `tenant_id` and tenant role).

## Files Updated
- Navigation overrides added to: `BuildingResource`, `InvoiceResource`, `MeterResource`, `MeterReadingResource`, `PropertyResource`, `ProviderResource`, `UserResource`, `FaqResource`, `TranslationResource`, `TariffResource`, `SubscriptionResource`, `OrganizationResource`, `OrganizationActivityLogResource`.
- Form signature alignment: resources now typehint `Filament\Schemas\Schema` (with test shim for legacy references).

## Checklist for New/Existing Resources
- [ ] Use `getNavigationIcon()` / `getNavigationGroup()` instead of typed static props.
- [ ] `form(Schema $schema): Schema` and `table(Table $table): Table` signatures match base class.
- [ ] Ensure tenant-aware queries inside form/table builders (`->where('tenant_id', ...)`, property filters for tenant users).
- [ ] Policies enforced: `canViewAny/canCreate/...` wired to policy methods.
- [ ] Eager load relations in tables where needed to avoid N+1.

## Testing Notes
- Run `php artisan test` (full suite). Critical coverage for resource boot now passes.
- Compatibility shim lives in `tests/Pest.php`; remove once all code fully migrated to Schemas.

## When to Update This Doc
- Adding new Filament resources or relation managers.
- Removing the `Form` alias once all components use `Schema`.
- Changing navigation grouping/icon patterns or adopting enums for navigation metadata.
