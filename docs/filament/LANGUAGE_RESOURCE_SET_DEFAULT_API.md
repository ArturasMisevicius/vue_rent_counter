# Language Resource Set Default API Reference

## Overview

This document provides API reference for the "Set as Default" functionality in the LanguageResource.

## Table Action

### Set Default Action

**Action Name**: `set_default`

**Type**: `Actions\Action`

**Purpose**: Sets a language as the system default language

#### Configuration

```php
Actions\Action::make('set_default')
    ->label(__('locales.actions.set_default'))
    ->icon('heroicon-o-star')
    ->color('warning')
    ->requiresConfirmation()
    ->modalHeading(__('locales.modals.set_default.heading'))
    ->modalDescription(__('locales.modals.set_default.description'))
    ->action(function (Language $record) {
        // Unset all other defaults
        Language::where('is_default', true)
            ->where('id', '!=', $record->id)
            ->update(['is_default' => false]);

        // Set this language as default and ensure it's active
        $record->update([
            'is_default' => true,
            'is_active' => true,
        ]);
    })
    ->visible(fn (?Language $record): bool =>
        // Only show for non-default languages
        $record && ! $record->is_default
    )
    ->successNotificationTitle(__('locales.notifications.default_set'))
```

#### Properties

| Property | Type | Description |
|----------|------|-------------|
| `label` | string | Translation key for "Set as Default" |
| `icon` | string | Star icon (heroicon-o-star) |
| `color` | string | Warning color (yellow) |
| `requiresConfirmation` | boolean | Requires user confirmation |
| `modalHeading` | string | Confirmation modal heading |
| `modalDescription` | string | Confirmation modal description |
| `action` | Closure | Sets language as default |
| `visible` | Closure | Controls action visibility |
| `successNotificationTitle` | string | Success notification message |

#### Action Logic

```php
function (Language $record) {
    // Step 1: Unset all other defaults
    Language::where('is_default', true)
        ->where('id', '!=', $record->id)
        ->update(['is_default' => false]);

    // Step 2: Set this language as default and activate it
    $record->update([
        'is_default' => true,
        'is_active' => true,
    ]);
}
```

**Behavior**:
1. Finds all languages currently marked as default (excluding the target language)
2. Sets their `is_default` field to `false`
3. Sets the target language's `is_default` to `true`
4. Ensures the target language is active (`is_active = true`)

**Database Impact**:
- Updates 1-2 records (previous default + new default)
- Maintains database integrity (exactly 1 default language)

#### Visibility Logic

```php
fn (?Language $record): bool =>
    // Only show for non-default languages
    $record && ! $record->is_default
```

**Visibility Rules**:
- Hidden when: `is_default = true`
- Visible when: `is_default = false`

**Rationale**: Prevents redundant action on already-default language

## Translation Keys

### Action Labels

```php
'locales.actions.set_default'  // "Set as Default"
```

### Modal Content

```php
'locales.modals.set_default.heading'      // "Set as Default Language"
'locales.modals.set_default.description'  // "Are you sure you want to set this language as the default?"
```

### Notifications

```php
'locales.notifications.default_set'  // "Default language updated successfully"
```

## Database Schema

### Language Table

**Table**: `languages`

**Relevant Columns**:

| Column | Type | Description |
|--------|------|-------------|
| `is_default` | boolean | Whether this is the default language |
| `is_active` | boolean | Whether the language is active |

**Indexes**:
- `is_default` - For identifying the default language
- `is_active` - For filtering active/inactive languages

**Constraints**:
- Only one language should have `is_default = true` at any time (enforced by application logic)

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
- This includes the set default action

### Policy Integration

**Policy**: `App\Policies\LanguagePolicy`

Additional authorization checks are performed by the LanguagePolicy for:
- View operations
- Create operations
- Update operations
- Delete operations

## Security Considerations

### Single Default Enforcement

**Database Integrity**:
- Action explicitly unsets all other defaults before setting new default
- Prevents multiple default languages
- Maintains system stability

### Inactive Language Handling

**Auto-Activation**:
- Setting an inactive language as default automatically activates it
- Prevents system from having an inactive default language
- Ensures default language is always usable

### Confirmation Dialogs

All set default actions require user confirmation:
- Prevents accidental changes
- Gives user opportunity to review action
- Standard Filament confirmation modal

### Authorization

- Only superadmins can access set default functionality
- Enforced at resource level via `shouldRegisterNavigation()`
- Additional checks via LanguagePolicy

## Usage Examples

### Programmatic Set Default

```php
// Get a language
$language = Language::find(2);

// Unset all other defaults
Language::where('is_default', true)
    ->where('id', '!=', $language->id)
    ->update(['is_default' => false]);

// Set as default and activate
$language->update([
    'is_default' => true,
    'is_active' => true,
]);
```

### Via Filament Action

```php
// In LanguageResource table
Actions\Action::make('set_default')
    ->action(function (Language $record) {
        Language::where('is_default', true)
            ->where('id', '!=', $record->id)
            ->update(['is_default' => false]);
        
        $record->update([
            'is_default' => true,
            'is_active' => true,
        ]);
    })
```

## Testing

### Test Suite

**Location**: `tests/Feature/Filament/LanguageResourceSetDefaultTest.php`

**Test Cases**: 14 tests covering:
- Namespace consolidation
- Set default functionality
- Default language uniqueness
- Action visibility
- UI element verification (labels, icons, colors)
- Authorization
- Edge cases (inactive languages)
- Performance

### Manual Testing

1. Navigate to `/admin/languages` as superadmin
2. Verify "Set as Default" button appears for non-default languages
3. Click "Set as Default" on a non-default language
4. Confirm the action in the modal
5. Verify the language is now marked as default
6. Verify the previous default is no longer default
7. Verify the button is hidden for the new default language

## Related Documentation

- **Test Documentation**: `docs/testing/LANGUAGE_RESOURCE_SET_DEFAULT_TEST_DOCUMENTATION.md`
- **Quick Reference**: `docs/testing/LANGUAGE_RESOURCE_SET_DEFAULT_QUICK_REFERENCE.md`
- **Summary**: `docs/testing/LANGUAGE_RESOURCE_SET_DEFAULT_SUMMARY.md`
- **Resource API**: `docs/filament/LANGUAGE_RESOURCE_API.md`

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | 2025-11-28 | Initial implementation with namespace consolidation |

## Support

For issues or questions regarding the set default functionality:
1. Check the test suite for expected behavior
2. Review the LanguageResource implementation
3. Consult the LanguagePolicy for authorization rules
4. Verify translation keys are properly defined
