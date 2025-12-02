# Language Resource Toggle Active API Reference

## Overview

This document provides API reference for the toggle active status functionality in the LanguageResource.

## Table Actions

### Individual Toggle Action

**Action Name**: `toggle_active`

**Type**: `Tables\Actions\Action`

**Purpose**: Toggles the `is_active` status of a single language record

#### Configuration

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
    ->color(fn (Language $record): string => $record->is_active ? 'danger' : 'success')
    ->requiresConfirmation()
    ->action(fn (Language $record) => $record->update(['is_active' => ! $record->is_active]))
    ->visible(fn (Language $record): bool => ! $record->is_default || ! $record->is_active)
```

#### Properties

| Property | Type | Description |
|----------|------|-------------|
| `label` | Closure | Dynamic label based on current state |
| `icon` | Closure | Dynamic icon based on current state |
| `color` | Closure | Dynamic color based on current state |
| `requiresConfirmation` | boolean | Requires user confirmation |
| `action` | Closure | Toggles the `is_active` field |
| `visible` | Closure | Controls action visibility |

#### Label Logic

```php
fn (Language $record): string => $record->is_active
    ? __('locales.actions.deactivate')  // For active languages
    : __('locales.actions.activate')     // For inactive languages
```

**Translation Keys**:
- `locales.actions.deactivate` - "Deactivate"
- `locales.actions.activate` - "Activate"

#### Icon Logic

```php
fn (Language $record): string => $record->is_active
    ? 'heroicon-o-x-circle'      // For active languages (deactivate)
    : 'heroicon-o-check-circle'  // For inactive languages (activate)
```

**Icons**:
- `heroicon-o-x-circle` - X circle outline (deactivate)
- `heroicon-o-check-circle` - Check circle outline (activate)

#### Color Logic

```php
fn (Language $record): string => $record->is_active
    ? 'danger'   // Red for deactivate
    : 'success'  // Green for activate
```

**Colors**:
- `danger` - Red (for deactivate action)
- `success` - Green (for activate action)

#### Visibility Logic

```php
fn (Language $record): bool => ! $record->is_default || ! $record->is_active
```

**Visibility Rules**:
- Hidden when: `is_default = true` AND `is_active = true`
- Visible when: `is_default = false` OR `is_active = false`

**Rationale**: Prevents deactivating the default language

#### Action Logic

```php
fn (Language $record) => $record->update(['is_active' => ! $record->is_active])
```

**Behavior**:
- Toggles the `is_active` boolean field
- `true` → `false` (deactivate)
- `false` → `true` (activate)

## Bulk Actions

### Bulk Activate Action

**Action Name**: `activate`

**Type**: `Tables\Actions\BulkAction`

**Purpose**: Activates multiple selected language records

#### Configuration

```php
Tables\Actions\BulkAction::make('activate')
    ->label(__('locales.actions.bulk_activate'))
    ->icon('heroicon-o-check-circle')
    ->color('success')
    ->requiresConfirmation()
    ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))
    ->deselectRecordsAfterCompletion()
```

#### Properties

| Property | Type | Value | Description |
|----------|------|-------|-------------|
| `label` | string | Translation key | "Activate" |
| `icon` | string | `heroicon-o-check-circle` | Check circle icon |
| `color` | string | `success` | Green color |
| `requiresConfirmation` | boolean | `true` | Requires confirmation |
| `deselectRecordsAfterCompletion` | boolean | `true` | Clears selection after action |

#### Action Logic

```php
fn (Collection $records) => $records->each->update(['is_active' => true])
```

**Behavior**:
- Iterates through all selected records
- Sets `is_active = true` for each record
- No restrictions on which languages can be activated

### Bulk Deactivate Action

**Action Name**: `deactivate`

**Type**: `Tables\Actions\BulkAction`

**Purpose**: Deactivates multiple selected language records

#### Configuration

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

#### Properties

| Property | Type | Value | Description |
|----------|------|-------|-------------|
| `label` | string | Translation key | "Deactivate" |
| `icon` | string | `heroicon-o-x-circle` | X circle icon |
| `color` | string | `danger` | Red color |
| `requiresConfirmation` | boolean | `true` | Requires confirmation |
| `deselectRecordsAfterCompletion` | boolean | `true` | Clears selection after action |

#### Action Logic

```php
function (Collection $records) {
    // Protection: Check for default language
    $defaultLanguage = $records->firstWhere('is_default', true);
    if ($defaultLanguage) {
        throw new \Exception(__('locales.errors.cannot_deactivate_default'));
    }

    // Deactivate all selected records
    $records->each->update(['is_active' => false]);
}
```

**Behavior**:
1. Checks if any selected record is the default language
2. If default language found, throws exception
3. Otherwise, sets `is_active = false` for all selected records

**Exception**:
- Type: `\Exception`
- Message: Translation key `locales.errors.cannot_deactivate_default`
- Trigger: When attempting to deactivate default language

## Translation Keys

### Action Labels

```php
// Individual toggle
'locales.actions.activate'      // "Activate"
'locales.actions.deactivate'    // "Deactivate"

// Bulk actions
'locales.actions.bulk_activate'    // "Activate Selected"
'locales.actions.bulk_deactivate'  // "Deactivate Selected"
```

### Error Messages

```php
'locales.errors.cannot_deactivate_default'  // "Cannot deactivate the default language"
```

## Database Schema

### Language Table

**Table**: `languages`

**Relevant Columns**:

| Column | Type | Description |
|--------|------|-------------|
| `is_active` | boolean | Whether the language is active |
| `is_default` | boolean | Whether this is the default language |

**Indexes**:
- `is_active` - For filtering active/inactive languages
- `is_default` - For identifying the default language

## Authorization

### Resource Access

**Method**: `shouldRegisterNavigation()`

```php
public static function shouldRegisterNavigation(): bool
{
    $user = auth()->user();
    return $user instanceof User && $user->role === UserRole::SUPERADMIN;
}
```

**Access Control**:
- Only users with `UserRole::SUPERADMIN` can access the LanguageResource
- This includes all toggle actions (individual and bulk)

### Policy Integration

**Policy**: `App\Policies\LanguagePolicy`

Additional authorization checks are performed by the LanguagePolicy for:
- View operations
- Create operations
- Update operations
- Delete operations

## Security Considerations

### Default Language Protection

**Individual Toggle**:
- Action is hidden for active default languages
- Prevents accidental deactivation through UI

**Bulk Deactivate**:
- Throws exception if default language is in selection
- Prevents deactivation even if multiple languages selected

### Confirmation Dialogs

All toggle actions require user confirmation:
- Prevents accidental state changes
- Gives user opportunity to review action
- Standard Filament confirmation modal

### Authorization

- Only superadmins can access toggle functionality
- Enforced at resource level via `shouldRegisterNavigation()`
- Additional checks via LanguagePolicy

## Usage Examples

### Programmatic Toggle

```php
// Get a language
$language = Language::find(1);

// Toggle active status
$language->update(['is_active' => !$language->is_active]);
```

### Bulk Activate

```php
// Get multiple languages
$languages = Language::whereIn('id', [1, 2, 3])->get();

// Activate all
$languages->each->update(['is_active' => true]);
```

### Bulk Deactivate (with protection)

```php
// Get multiple languages
$languages = Language::whereIn('id', [1, 2, 3])->get();

// Check for default language
$defaultLanguage = $languages->firstWhere('is_default', true);
if ($defaultLanguage) {
    throw new \Exception('Cannot deactivate default language');
}

// Deactivate all
$languages->each->update(['is_active' => false]);
```

## Testing

### Test Suite

**Location**: `tests/Feature/Filament/LanguageResourceToggleActiveTest.php`

**Test Cases**: 16 tests covering:
- Namespace consolidation
- Individual toggle functionality
- Bulk action functionality
- Default language protection
- UI element verification (labels, icons, colors)
- Authorization

### Manual Testing

See `docs/testing/LANGUAGE_RESOURCE_TOGGLE_ACTIVE_VERIFICATION.md` for manual testing checklist.

## Related Documentation

- **Verification**: `docs/testing/LANGUAGE_RESOURCE_TOGGLE_ACTIVE_VERIFICATION.md`
- **Quick Reference**: `docs/testing/LANGUAGE_RESOURCE_TOGGLE_ACTIVE_QUICK_REFERENCE.md`
- **Summary**: `docs/testing/LANGUAGE_RESOURCE_TOGGLE_ACTIVE_SUMMARY.md`
- **Resource API**: `docs/filament/LANGUAGE_RESOURCE_API.md`

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | 2025-11-28 | Initial implementation with namespace consolidation |

## Support

For issues or questions regarding the toggle active functionality:
1. Check the verification document for common scenarios
2. Review the test suite for expected behavior
3. Consult the LanguageResource implementation
4. Check LanguagePolicy for authorization rules
