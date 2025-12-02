# TranslationResource Dynamic Fields API Documentation

## Overview

The TranslationResource implements a dynamic form field generation system that automatically adapts to active language configurations. This document describes the architecture, implementation patterns, and usage guidelines for this system.

## Architecture

### Component Hierarchy

```
TranslationResource
├── Form Schema (Dynamic)
│   ├── Key Section (Static)
│   │   ├── Group Field
│   │   └── Key Field
│   └── Values Section (Dynamic)
│       └── Language Fields (Generated per active language)
├── Table Schema
└── Pages
    ├── ListTranslations
    ├── CreateTranslation
    └── EditTranslation
```

### Data Flow

```mermaid
graph TD
    A[Form Render Request] --> B[Language::getActiveLanguages]
    B --> C{Cache Hit?}
    C -->|Yes| D[Return Cached Languages]
    C -->|No| E[Query Active Languages]
    E --> F[Cache Result]
    F --> D
    D --> G[Map Languages to Fields]
    G --> H[Generate Textarea Components]
    H --> I[Render Form]
```

## Dynamic Field Generation

### Implementation

```php
public static function form(Schema $schema): Schema
{
    // PERFORMANCE: Use cached active languages to avoid N+1 query
    $languages = Language::getActiveLanguages();

    return $schema->schema([
        // Static key section
        Forms\Components\Section::make(__('translations.sections.key'))
            ->schema([
                Forms\Components\TextInput::make('group')
                    ->required()
                    ->maxLength(120)
                    ->alphaDash(),
                Forms\Components\TextInput::make('key')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]),

        // Dynamic values section
        Forms\Components\Section::make(__('translations.sections.values'))
            ->schema(
                $languages->map(function (Language $language) {
                    return Forms\Components\Textarea::make("values.{$language->code}")
                        ->label(__('translations.table.language_label', [
                            'language' => $language->name,
                            'code' => $language->code,
                        ]))
                        ->rows(3)
                        ->helperText($language->is_default 
                            ? __('translations.helper_text.default_language') 
                            : ''
                        )
                        ->columnSpanFull();
                })->all()
            )
            ->collapsible()
            ->persistCollapsed(),
    ]);
}
```

### Field Configuration

Each dynamically generated field has the following properties:

| Property | Value | Purpose |
|----------|-------|---------|
| **Name** | `values.{language_code}` | Maps to Translation model's JSON column |
| **Type** | `Textarea` | Supports multi-line translation strings |
| **Label** | `{Language Name} ({CODE})` | Clear identification of language |
| **Rows** | `3` | Adequate space for typical translations |
| **Helper Text** | Conditional | Shows guidance for default language only |
| **Column Span** | `full` | Maximizes available space |

### Helper Text Logic

```php
->helperText($language->is_default 
    ? __('translations.helper_text.default_language') 
    : ''
)
```

**Behavior**:
- **Default Language**: Displays helper text explaining its role as fallback
- **Other Languages**: No helper text to reduce visual clutter

## Language Model Integration

### Required Model Methods

The TranslationResource depends on the following Language model methods:

#### `Language::getActiveLanguages()`

```php
/**
 * Get all active languages with caching.
 *
 * @return \Illuminate\Support\Collection<Language>
 */
public static function getActiveLanguages(): Collection
{
    return Cache::rememberForever('languages.active', function () {
        return static::where('is_active', true)
            ->orderBy('display_order')
            ->get();
    });
}
```

**Cache Invalidation**: Automatically handled by Language model observers on:
- Language creation
- Language update (especially `is_active` changes)
- Language deletion

### Database Schema Requirements

The Language model must have:

```php
Schema::table('languages', function (Blueprint $table) {
    $table->string('code', 10)->unique();
    $table->string('name', 100);
    $table->boolean('is_active')->default(true);
    $table->boolean('is_default')->default(false);
    $table->integer('display_order')->default(0);
});
```

## Performance Characteristics

### Caching Strategy

| Aspect | Implementation | Benefit |
|--------|---------------|---------|
| **Cache Key** | `languages.active` | Simple, predictable |
| **Cache Duration** | Forever (until invalidated) | Maximum performance |
| **Invalidation** | Model observer events | Automatic consistency |
| **Query Reduction** | N+1 → 0 (after first load) | Significant performance gain |

### Performance Benchmarks

Based on test suite measurements:

| Scenario | Performance | Status |
|----------|------------|--------|
| **3 Languages** | ~450ms | ✅ Excellent |
| **10 Languages** | ~420ms | ✅ Excellent |
| **Cache Hit** | <5ms | ✅ Optimal |

**Note**: Performance remains consistent regardless of language count due to caching.

## Authorization

### Access Control

All TranslationResource operations require **SUPERADMIN** role:

```php
public static function shouldRegisterNavigation(): bool
{
    $user = auth()->user();
    return $user instanceof User && $user->role === UserRole::SUPERADMIN;
}

public static function canViewAny(): bool
{
    $user = auth()->user();
    return $user instanceof User && $user->role === UserRole::SUPERADMIN;
}

public static function canCreate(): bool
{
    $user = auth()->user();
    return $user instanceof User && $user->role === UserRole::SUPERADMIN;
}

public static function canEdit(Model $record): bool
{
    $user = auth()->user();
    return $user instanceof User && $user->role === UserRole::SUPERADMIN;
}

public static function canDelete(Model $record): bool
{
    $user = auth()->user();
    return $user instanceof User && $user->role === UserRole::SUPERADMIN;
}
```

### Authorization Matrix

| Role | View | Create | Edit | Delete |
|------|------|--------|------|--------|
| **SUPERADMIN** | ✅ | ✅ | ✅ | ✅ |
| **ADMIN** | ❌ | ❌ | ❌ | ❌ |
| **MANAGER** | ❌ | ❌ | ❌ | ❌ |
| **TENANT** | ❌ | ❌ | ❌ | ❌ |

## Usage Examples

### Adding a New Language

When a new language is added and activated:

1. **Admin Action**: Create language via LanguageResource
2. **Cache Invalidation**: Language model observer clears cache
3. **Automatic Update**: Next form load includes new language field
4. **No Code Changes**: System adapts automatically

```php
// Example: Adding German language
Language::create([
    'code' => 'de',
    'name' => 'German',
    'is_active' => true,
    'display_order' => 4,
]);

// Cache is automatically invalidated
// Next TranslationResource form render includes German field
```

### Deactivating a Language

When a language is deactivated:

1. **Admin Action**: Set `is_active = false` via LanguageResource
2. **Cache Invalidation**: Language model observer clears cache
3. **Automatic Update**: Next form load excludes deactivated language
4. **Data Preservation**: Existing translation values remain in database

```php
// Example: Deactivating Russian
$russian = Language::where('code', 'ru')->first();
$russian->update(['is_active' => false]);

// Cache is automatically invalidated
// Next TranslationResource form render excludes Russian field
// Existing Russian translations remain in database
```

## Edge Cases and Considerations

### No Active Languages

**Scenario**: All languages are deactivated

**Behavior**:
- Form renders with only Key section
- Values section is empty
- No validation errors
- System remains functional

**Recommendation**: Ensure at least one language is always active

### Default Language Changes

**Scenario**: Default language is changed from English to Lithuanian

**Behavior**:
- Helper text moves from English field to Lithuanian field
- No data migration required
- Existing translations unaffected

### Large Language Sets

**Scenario**: 20+ active languages

**Considerations**:
- Form remains performant due to caching
- UI may become lengthy (use collapsible sections)
- Consider pagination or search for very large sets

## Testing

### Test Coverage

The test suite (`TranslationResourceDynamicFieldsTest.php`) covers:

1. **Namespace Consolidation** (2 tests)
   - Consolidated import verification
   - Individual import absence verification

2. **Dynamic Field Generation** (6 tests)
   - Create form field presence
   - Edit form field presence
   - Inactive language exclusion
   - Language activation detection
   - Language deactivation detection
   - Field label formatting

3. **Field Configuration** (4 tests)
   - Component type verification
   - Attribute verification
   - Helper text presence (default language)
   - Helper text absence (non-default languages)

4. **Performance** (2 tests)
   - Cache utilization verification
   - Multi-language rendering performance

5. **Authorization** (1 test)
   - Role-based access control

### Running Tests

```bash
# Run all dynamic fields tests
php artisan test --filter=TranslationResourceDynamicFieldsTest

# Run specific test group
php artisan test --group=dynamic-fields

# Run with coverage
php artisan test --filter=TranslationResourceDynamicFieldsTest --coverage
```

## Troubleshooting

### Fields Not Updating After Language Changes

**Symptom**: New/deactivated languages not reflected in form

**Diagnosis**:
```php
// Check cache status
Cache::has('languages.active'); // Should return true

// Check cached data
Cache::get('languages.active'); // Should match database
```

**Solution**:
```php
// Manual cache clear
Cache::forget('languages.active');

// Or clear all cache
php artisan cache:clear
```

### Helper Text Not Displaying

**Symptom**: Default language field missing helper text

**Diagnosis**:
```php
// Check default language
$default = Language::where('is_default', true)->first();
// Should return exactly one language
```

**Solution**:
- Ensure exactly one language has `is_default = true`
- Check translation key exists: `translations.helper_text.default_language`

### Performance Degradation

**Symptom**: Form loads slowly with many languages

**Diagnosis**:
```php
// Check cache hit rate
Cache::has('languages.active'); // Should be true after first load

// Check query count
DB::enableQueryLog();
// Render form
DB::getQueryLog(); // Should show 0 queries for languages
```

**Solution**:
- Verify cache is enabled in `config/cache.php`
- Check Language model observer is registered
- Consider implementing lazy loading for very large language sets

## Related Documentation

- **Test Suite**: [docs/testing/TRANSLATION_RESOURCE_DYNAMIC_FIELDS_TEST_SUMMARY.md](../testing/TRANSLATION_RESOURCE_DYNAMIC_FIELDS_TEST_SUMMARY.md)
- **Language Resource**: [docs/filament/LANGUAGE_RESOURCE_API.md](LANGUAGE_RESOURCE_API.md)
- **Translation Model**: `app/Models/Translation.php`
- **Language Model**: `app/Models/Language.php`
- **Namespace Consolidation**: `.kiro/specs/6-filament-namespace-consolidation/`

## Changelog

### 2024-11-29
- ✅ Implemented dynamic field generation based on active languages
- ✅ Added comprehensive test suite (15 tests, 88 assertions)
- ✅ Integrated caching for optimal performance
- ✅ Documented API and usage patterns
- ✅ Verified namespace consolidation compliance
