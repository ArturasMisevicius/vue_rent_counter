# Language Resource Toggle Active - Quick Reference

## Quick Summary

✅ **Status**: COMPLETE - Toggle active status functionality is fully implemented

## Features at a Glance

| Feature | Status | Location |
|---------|--------|----------|
| Individual Toggle | ✅ | Lines 195-211 |
| Bulk Activate | ✅ | Lines 220-229 |
| Bulk Deactivate | ✅ | Lines 231-245 |
| Default Language Protection | ✅ | Built-in |
| Namespace Consolidation | ✅ | Uses `Tables\Actions\` |

## Quick Actions

### Toggle Individual Language

```php
// Button appears in table row actions
// Label: "Activate" (green) or "Deactivate" (red)
// Icon: Check-circle or X-circle
// Requires: Confirmation
// Protection: Cannot deactivate default language
```

### Bulk Activate

```php
// Select multiple languages → Bulk Actions → Activate
// Icon: heroicon-o-check-circle
// Color: success (green)
// Requires: Confirmation
```

### Bulk Deactivate

```php
// Select multiple languages → Bulk Actions → Deactivate
// Icon: heroicon-o-x-circle
// Color: danger (red)
// Requires: Confirmation
// Protection: Prevents deactivating default language
```

## Visual Indicators

| State | Button Label | Icon | Color |
|-------|-------------|------|-------|
| Active Language | "Deactivate" | X-circle | Red (danger) |
| Inactive Language | "Activate" | Check-circle | Green (success) |
| Active Default | Hidden | - | - |

## Protection Rules

1. ✅ Cannot deactivate default language via individual toggle
2. ✅ Cannot deactivate default language via bulk action
3. ✅ Toggle button hidden for active default languages
4. ✅ Exception thrown if bulk deactivate includes default language

## Authorization

- ✅ Only superadmins can access LanguageResource
- ✅ Controlled by `shouldRegisterNavigation()` method
- ✅ Additional checks in LanguagePolicy

## Manual Test (30 seconds)

1. Go to `/admin/languages` as superadmin
2. Click toggle on any non-default language
3. Confirm it changes state
4. Verify button label/color changes
5. Try to toggle default language (should be hidden)

## Code Location

**File**: `app/Filament/Resources/LanguageResource.php`

**Key Lines**:
- Individual Toggle: 195-211
- Bulk Activate: 220-229
- Bulk Deactivate: 231-245

## Namespace Pattern

```php
use Filament\Tables;

// Individual action
Tables\Actions\Action::make('toggle_active')

// Bulk actions
Tables\Actions\BulkAction::make('activate')
Tables\Actions\BulkAction::make('deactivate')
```

## Translation Keys

```php
'locales.actions.activate'
'locales.actions.deactivate'
'locales.actions.bulk_activate'
'locales.actions.bulk_deactivate'
'locales.errors.cannot_deactivate_default'
```

## Related Documentation

- Full Verification: [docs/testing/LANGUAGE_RESOURCE_TOGGLE_ACTIVE_VERIFICATION.md](LANGUAGE_RESOURCE_TOGGLE_ACTIVE_VERIFICATION.md)
- Test Suite: `tests/Feature/Filament/LanguageResourceToggleActiveTest.php`
- API Documentation: [docs/filament/LANGUAGE_RESOURCE_API.md](../filament/LANGUAGE_RESOURCE_API.md)
