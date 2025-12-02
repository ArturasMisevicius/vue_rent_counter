# TranslationResource API Documentation

## Overview

The TranslationResource provides a Filament v4 admin interface for managing translation strings across multiple languages. It supports CRUD operations with superadmin-only access, multi-language value management, and automatic PHP language file publishing.

**Resource Class**: `App\Filament\Resources\TranslationResource`  
**Model**: `App\Models\Translation`  
**Namespace Pattern**: Consolidated (`use Filament\Tables;`)  
**Access Level**: Superadmin only

## Table of Contents

- [Authorization](#authorization)
- [Form Schema](#form-schema)
- [Table Configuration](#table-configuration)
- [Actions](#actions)
- [Validation Rules](#validation-rules)
- [Data Structure](#data-structure)
- [Usage Examples](#usage-examples)
- [Integration Points](#integration-points)

## Authorization

### Access Control Matrix

| Role | View | Create | Edit | Delete | Navigation |
|------|------|--------|------|--------|------------|
| SUPERADMIN | ✅ Yes | ✅ Yes | ✅ Yes | ✅ Yes | ✅ Visible |
| ADMIN | ❌ No | ❌ No | ❌ No | ❌ No | ❌ Hidden |
| MANAGER | ❌ No | ❌ No | ❌ No | ❌ No | ❌ Hidden |
| TENANT | ❌ No | ❌ No | ❌ No | ❌ No | ❌ Hidden |

### Authorization Methods

```php
// Check if user can view any translations
TranslationResource::canViewAny(): bool

// Check if user can create translations
TranslationResource::canCreate(): bool

// Check if user can edit a specific translation
TranslationResource::canEdit(Model $record): bool

// Check if user can delete a specific translation
TranslationResource::canDelete(Model $record): bool

// Check if navigation should be visible
TranslationResource::shouldRegisterNavigation(): bool
```

### HTTP Response Codes

- **200 OK**: Successful access (superadmin only)
- **403 Forbidden**: Unauthorized access (admin, manager, tenant)
- **302 Redirect**: Unauthenticated user redirected to login

## Form Schema

### Create/Edit Form Structure

The form is organized into two main sections:

#### Section 1: Key Information

**Fields:**

1. **Group** (`group`)
   - Type: Text Input
   - Required: Yes
   - Max Length: 120 characters
   - Validation: Alpha-dash (letters, numbers, dashes, underscores)
   - Purpose: Organizes translations into logical groups (e.g., 'app', 'auth', 'validation')

2. **Key** (`key`)
   - Type: Text Input
   - Required: Yes
   - Max Length: 255 characters
   - Purpose: Unique identifier within the group (e.g., 'welcome.message')

#### Section 2: Language Values

**Dynamic Fields:**

For each active language in the system:

- **Field Name**: `values.{language_code}` (e.g., `values.en`, `values.lt`, `values.ru`)
- **Type**: Textarea
- **Required**: No (allows partial translations)
- **Rows**: 3
- **Purpose**: Translation text for specific language

**Example Form Data:**

```php
[
    'group' => 'app',
    'key' => 'welcome.message',
    'values' => [
        'en' => 'Welcome to our application',
        'lt' => 'Sveiki atvykę į mūsų programą',
        'ru' => 'Добро пожаловать в наше приложение',
    ],
]
```

### Form Behavior

- **Collapsible Sections**: Language values section is collapsible and persists state
- **Helper Text**: Contextual help displayed for each field
- **Default Language**: Marked with special helper text
- **Validation**: Real-time validation on form submission

## Table Configuration

### Columns

1. **Group**
   - Display: Badge with primary color
   - Sortable: Yes
   - Searchable: Yes
   - Weight: Medium

2. **Key**
   - Display: Text with copy functionality
   - Sortable: Yes
   - Searchable: Yes
   - Copyable: Yes (with confirmation message)
   - Weight: Medium

3. **Value (Default Language)**
   - Display: Text limited to 50 characters
   - Wrapping: Enabled
   - Tooltip: Shows full text if truncated
   - Placeholder: Dash (—) if empty

4. **Last Updated**
   - Display: Relative time (e.g., "2 hours ago")
   - Sortable: Yes
   - Toggleable: Yes (can be hidden)

### Filters

**Group Filter:**
- Type: Select dropdown
- Options: Dynamically loaded from existing translation groups
- Searchable: Yes
- Native: No (uses Filament's custom select)

### Sorting

- **Default Sort**: Group (ascending)
- **Persistence**: Sort order persisted in session
- **Available Sorts**: Group, Key, Updated At

### Search

- **Searchable Fields**: Group, Key
- **Persistence**: Search query persisted in session
- **Behavior**: Case-insensitive partial matching

## Actions

### Row Actions

#### Edit Action

```php
Tables\Actions\EditAction::make()
    ->iconButton()
```

- **Icon**: Edit icon button
- **Authorization**: Superadmin only
- **Behavior**: Opens edit page for the translation

#### Delete Action

```php
Tables\Actions\DeleteAction::make()
    ->iconButton()
```

- **Icon**: Delete icon button
- **Authorization**: Superadmin only
- **Confirmation**: Required
- **Behavior**: Soft deletes the translation and triggers republishing

### Bulk Actions

#### Bulk Delete

```php
Tables\Actions\DeleteBulkAction::make()
    ->requiresConfirmation()
    ->modalHeading(__('translations.modals.delete.heading'))
    ->modalDescription(__('translations.modals.delete.description'))
```

- **Authorization**: Superadmin only
- **Confirmation**: Modal with custom heading and description
- **Behavior**: Deletes multiple translations and triggers republishing

### Empty State Actions

#### Create Action

```php
Tables\Actions\CreateAction::make()
    ->label(__('translations.empty.action'))
```

- **Display**: Shown when no translations exist
- **Authorization**: Superadmin only
- **Behavior**: Opens create page

## Validation Rules

### Field Validation

| Field | Rules | Error Messages |
|-------|-------|----------------|
| `group` | required, max:120, alpha_dash | Required, max length exceeded, invalid format |
| `key` | required, max:255 | Required, max length exceeded |
| `values` | array | Must be an array |
| `values.*` | nullable, string | Must be a string |

### Validation Examples

**Valid Data:**

```php
// Valid group names
'app', 'auth', 'validation', 'user-profile', 'admin_panel'

// Valid keys
'welcome.message', 'auth.failed', 'validation.required'

// Valid values
[
    'en' => 'Welcome',
    'lt' => 'Sveiki',
    'ru' => 'Добро пожаловать',
]
```

**Invalid Data:**

```php
// Invalid group (special characters)
'app@admin' // ❌ Contains @

// Invalid group (too long)
str_repeat('a', 121) // ❌ Exceeds 120 characters

// Invalid key (too long)
str_repeat('a', 256) // ❌ Exceeds 255 characters

// Missing required fields
['key' => 'test'] // ❌ Missing group
['group' => 'test'] // ❌ Missing key
```

## Data Structure

### Database Schema

**Table**: `translations`

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | bigint | No | Auto | Primary key |
| `group` | varchar(120) | No | - | Translation group |
| `key` | varchar(255) | No | - | Translation key |
| `values` | json | No | - | Language values |
| `created_at` | timestamp | Yes | NULL | Creation timestamp |
| `updated_at` | timestamp | Yes | NULL | Last update timestamp |

### JSON Values Structure

```json
{
    "en": "English translation text",
    "lt": "Lithuanian translation text",
    "ru": "Russian translation text"
}
```

### Model Casts

```php
protected $casts = [
    'values' => 'array',
];
```

### Model Events

**On Save/Delete:**
- Triggers `TranslationPublisher` service
- Publishes translations to PHP language files
- Updates `lang/{locale}/{group}.php` files

## Usage Examples

### Creating a Translation

```php
use App\Models\Translation;

// Create via model
Translation::create([
    'group' => 'app',
    'key' => 'welcome.message',
    'values' => [
        'en' => 'Welcome to our application',
        'lt' => 'Sveiki atvykę į mūsų programą',
        'ru' => 'Добро пожаловать в наше приложение',
    ],
]);

// Create via Filament form (handled by resource)
// User fills form and submits
// TranslationResource handles validation and creation
```

### Querying Translations

```php
// Get all translations in a group
$appTranslations = Translation::where('group', 'app')->get();

// Get specific translation
$translation = Translation::where('group', 'app')
    ->where('key', 'welcome.message')
    ->first();

// Get translation value for specific language
$englishValue = $translation->values['en'] ?? null;

// Get all groups
$groups = Translation::distinct()->pluck('group');
```

### Using Translations in Views

```php
// After TranslationPublisher runs, use Laravel's trans() helper
{{ __('app.welcome.message') }}

// Or with parameters
{{ __('app.greeting', ['name' => $user->name]) }}
```

### Updating a Translation

```php
$translation = Translation::find($id);
$translation->update([
    'values' => [
        'en' => 'Updated English text',
        'lt' => 'Atnaujintas lietuviškas tekstas',
        'ru' => 'Обновленный русский текст',
    ],
]);
// TranslationPublisher automatically republishes
```

## Integration Points

### Language Model Integration

**Relationship**: TranslationResource dynamically loads active languages

```php
$languages = Language::query()
    ->where('is_active', true)
    ->orderBy('display_order')
    ->get();
```

**Impact**:
- Form fields generated for each active language
- Deactivating a language hides its field in the form
- Language order determines field display order

### TranslationPublisher Service

**Trigger**: Automatic on save/delete via model events

```php
protected static function booted(): void
{
    static::saved(fn () => app(TranslationPublisher::class)->publish());
    static::deleted(fn () => app(TranslationPublisher::class)->publish());
}
```

**Behavior**:
- Reads all translations from database
- Groups by `group` and `key`
- Writes to `lang/{locale}/{group}.php` files
- Makes translations available via Laravel's `__()` helper

### Filament Navigation

**Group**: Localization  
**Sort Order**: 2  
**Icon**: `heroicon-o-rectangle-stack`  
**Label**: Translated via `translations.navigation`

### Session Persistence

The resource persists the following in session:
- Sort order
- Search query
- Filter selections
- Column visibility

## Routes

### Resource Routes

| Method | URI | Name | Action |
|--------|-----|------|--------|
| GET | `/admin/translations` | `filament.admin.resources.translations.index` | List translations |
| GET | `/admin/translations/create` | `filament.admin.resources.translations.create` | Show create form |
| POST | `/admin/translations` | `filament.admin.resources.translations.store` | Store new translation |
| GET | `/admin/translations/{record}/edit` | `filament.admin.resources.translations.edit` | Show edit form |
| PUT/PATCH | `/admin/translations/{record}` | `filament.admin.resources.translations.update` | Update translation |
| DELETE | `/admin/translations/{record}` | `filament.admin.resources.translations.destroy` | Delete translation |

### Route Middleware

- `web`
- `auth`
- Filament's authentication middleware
- Authorization checks via resource methods

## Error Handling

### Common Errors

**403 Forbidden**
```json
{
    "message": "This action is unauthorized."
}
```
- **Cause**: Non-superadmin user attempting access
- **Solution**: Ensure user has SUPERADMIN role

**422 Unprocessable Entity**
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "group": ["The group field is required."],
        "key": ["The key field is required."]
    }
}
```
- **Cause**: Validation failure
- **Solution**: Provide valid data according to validation rules

**500 Internal Server Error**
- **Cause**: TranslationPublisher service failure
- **Solution**: Check file permissions on `lang/` directory

## Performance Considerations

### Query Optimization

- **Distinct Groups**: Cached for filter dropdown
- **Active Languages**: Queried once per form render
- **Pagination**: Default Filament pagination applied

### Caching

- **Session Persistence**: Reduces repeated queries for filters/search
- **Language Cache**: Consider caching active languages list

### Recommendations

1. **Index Database**: Add index on `group` column for faster filtering
2. **Limit Groups**: Keep number of translation groups manageable
3. **Batch Operations**: Use bulk actions for multiple deletions
4. **Monitor Publishing**: TranslationPublisher can be slow with many translations

## Testing

### Test Coverage

- **Test File**: `tests/Feature/Filament/TranslationResourceCreateTest.php`
- **Test Count**: 26 tests
- **Assertions**: 97 assertions
- **Coverage**: 100% of create functionality

### Running Tests

```bash
# Run all translation tests
php artisan test --filter=TranslationResource

# Run create tests only
php artisan test tests/Feature/Filament/TranslationResourceCreateTest.php

# Run with coverage
php artisan test --coverage tests/Feature/Filament/TranslationResourceCreateTest.php
```

## Related Documentation

- **Model**: `app/Models/Translation.php`
- **Resource**: `app/Filament/Resources/TranslationResource.php`
- **Pages**: `app/Filament/Resources/TranslationResource/Pages/`
- **Test Suite**: `tests/Feature/Filament/TranslationResourceCreateTest.php`
- **Test Documentation**: [docs/testing/TRANSLATION_RESOURCE_CREATE_TEST_SUMMARY.md](../testing/TRANSLATION_RESOURCE_CREATE_TEST_SUMMARY.md)
- **Feature Spec**: `.kiro/specs/6-filament-namespace-consolidation/`

## Changelog

### Version 1.0.0 (2025-11-28)
- Initial implementation with Filament v4
- Consolidated namespace imports (`use Filament\Tables;`)
- Superadmin-only access control
- Multi-language value support
- Automatic PHP file publishing
- Comprehensive test coverage (26 tests, 97 assertions)
