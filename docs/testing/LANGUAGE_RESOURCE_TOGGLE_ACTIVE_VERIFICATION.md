# Language Resource Toggle Active Status - Verification

## Overview

This document verifies the implementation of the toggle active status functionality in the LanguageResource.

## Implementation Status

✅ **COMPLETE** - The toggle active status functionality is fully implemented in `app/Filament/Resources/LanguageResource.php`.

## Features Implemented

### 1. Individual Toggle Action

**Location**: Lines 195-211 in LanguageResource.php

**Features**:
- Dynamic label: Shows "Deactivate" for active languages, "Activate" for inactive languages
- Dynamic icon: Uses `heroicon-o-x-circle` for deactivate, `heroicon-o-check-circle` for activate
- Dynamic color: Uses `danger` for deactivate, `success` for activate
- Confirmation required before action
- Protection: Cannot deactivate the default language
- Action: Toggles the `is_active` status

**Code**:
```php
Tables\Actions\Action::make('toggle_active')
    ->label(fn (Language $record): string => $record->is_active
            ? __('locales.actions.deactivate')
            : __('locales.actions.activate')
    )
    ->icon(fn (Language $record): string => $record->is_active
            ? 'heroicon-o-x-circle'
            : 'heroicon-o-check-circle'
    )
    ->color(fn (Language $record): string => $record->is_active ? 'danger' : 'success'
    )
    ->requiresConfirmation()
    ->action(fn (Language $record) => $record->update(['is_active' => ! $record->is_active])
    )
    ->visible(fn (Language $record): bool =>
        // Don't allow deactivating the default language
        ! $record->is_default || ! $record->is_active
    )
```

### 2. Bulk Activate Action

**Location**: Lines 220-229 in LanguageResource.php

**Features**:
- Activates multiple selected languages at once
- Icon: `heroicon-o-check-circle`
- Color: `success`
- Confirmation required
- Deselects records after completion

**Code**:
```php
Tables\Actions\BulkAction::make('activate')
    ->label(__('locales.actions.bulk_activate'))
    ->icon('heroicon-o-check-circle')
    ->color('success')
    ->requiresConfirmation()
    ->action(fn (Collection $records) => $records->each->update(['is_active' => true])
    )
    ->deselectRecordsAfterCompletion()
```

### 3. Bulk Deactivate Action

**Location**: Lines 231-245 in LanguageResource.php

**Features**:
- Deactivates multiple selected languages at once
- Icon: `heroicon-o-x-circle`
- Color: `danger`
- Confirmation required
- Protection: Prevents deactivating the default language
- Deselects records after completion

**Code**:
```php
Tables\Actions\BulkAction::make('deactivate')
    ->label(__('locales.actions.bulk_deactivate'))
    ->icon('heroicon-o-x-circle')
    ->color('danger')
    ->requiresConfirmation()
    ->action(function (Collection $records) {
        // Prevent deactivating default language
        $defaultLanguage = $records->firstWhere('is_default', true);
        if ($defaultLanguage) {
            throw new \Exception(__('locales.errors.cannot_deactivate_default'));
        }

        $records->each->update(['is_active' => false]);
    })
    ->deselectRecordsAfterCompletion()
```

## Namespace Consolidation

✅ All actions use the consolidated namespace pattern:
- `Tables\Actions\Action` for individual actions
- `Tables\Actions\BulkAction` for bulk actions
- `Tables\Actions\BulkActionGroup` for grouping bulk actions

The resource imports `use Filament\Tables;` at the top, allowing the use of the `Tables\` prefix throughout.

## Security Features

### 1. Default Language Protection

The implementation prevents deactivating the default language through multiple safeguards:

**Individual Action**:
- The toggle action is hidden for active default languages
- Uses `->visible()` callback to check if the language is both default and active

**Bulk Action**:
- Throws an exception if attempting to deactivate a default language
- Checks the collection for any default languages before processing

### 2. Authorization

- Only superadmins can access the LanguageResource
- Controlled by `shouldRegisterNavigation()` method
- Additional authorization handled by LanguagePolicy

## User Experience

### Visual Feedback

1. **Active Languages**:
   - Toggle button shows "Deactivate" with red danger color
   - Icon: X-circle (heroicon-o-x-circle)
   - Indicates the action will deactivate the language

2. **Inactive Languages**:
   - Toggle button shows "Activate" with green success color
   - Icon: Check-circle (heroicon-o-check-circle)
   - Indicates the action will activate the language

3. **Default Language**:
   - Toggle button is hidden when the language is both default and active
   - Prevents accidental deactivation of the system's default language

### Confirmation Dialogs

All toggle actions require confirmation before execution:
- Individual toggle: Confirmation modal appears
- Bulk activate: Confirmation modal appears
- Bulk deactivate: Confirmation modal appears

## Testing

### Test Suite Created

A comprehensive test suite was created at `tests/Feature/Filament/LanguageResourceToggleActiveTest.php` with 16 test cases:

1. ✅ Namespace consolidation verification
2. ✅ Toggle active to inactive
3. ✅ Toggle inactive to active
4. ✅ Default language protection
5. ✅ Bulk activate namespace verification
6. ✅ Bulk activate functionality
7. ✅ Bulk deactivate namespace verification
8. ✅ Bulk deactivate functionality
9. ✅ Bulk deactivate default language protection
10. ✅ Dynamic label for active language
11. ✅ Dynamic label for inactive language
12. ✅ Dynamic icon for active language
13. ✅ Dynamic icon for inactive language
14. ✅ Dynamic color for active language
15. ✅ Dynamic color for inactive language
16. ✅ Authorization (superadmin only)

### Manual Testing Checklist

To manually verify the toggle active status functionality:

- [ ] Navigate to `/admin/languages` as superadmin
- [ ] Verify toggle button appears for non-default languages
- [ ] Click toggle on an active language
  - [ ] Confirm the confirmation dialog appears
  - [ ] Confirm the language is deactivated
  - [ ] Verify the button changes to "Activate" with green color
- [ ] Click toggle on an inactive language
  - [ ] Confirm the confirmation dialog appears
  - [ ] Confirm the language is activated
  - [ ] Verify the button changes to "Deactivate" with red color
- [ ] Verify toggle button is hidden for active default language
- [ ] Select multiple non-default languages
  - [ ] Click "Activate" bulk action
  - [ ] Confirm all selected languages are activated
- [ ] Select multiple non-default languages
  - [ ] Click "Deactivate" bulk action
  - [ ] Confirm all selected languages are deactivated
- [ ] Attempt to bulk deactivate including default language
  - [ ] Verify error message appears
  - [ ] Confirm default language remains active

## Translation Keys

The following translation keys are used:

- `locales.actions.deactivate` - Label for deactivate action
- `locales.actions.activate` - Label for activate action
- `locales.actions.bulk_activate` - Label for bulk activate action
- `locales.actions.bulk_deactivate` - Label for bulk deactivate action
- `locales.errors.cannot_deactivate_default` - Error message when attempting to deactivate default language

## Related Files

- **Resource**: `app/Filament/Resources/LanguageResource.php`
- **Model**: `app/Models/Language.php`
- **Policy**: `app/Policies/LanguagePolicy.php`
- **Test Suite**: `tests/Feature/Filament/LanguageResourceToggleActiveTest.php`
- **Translations**: `lang/*/locales.php`

## Conclusion

The toggle active status functionality is fully implemented and follows Filament 4 best practices with consolidated namespaces. The implementation includes:

✅ Individual toggle action with dynamic UI
✅ Bulk activate action
✅ Bulk deactivate action
✅ Default language protection
✅ Confirmation dialogs
✅ Proper authorization
✅ Namespace consolidation
✅ Comprehensive test coverage

The feature is ready for production use and provides a user-friendly interface for managing language activation status.
