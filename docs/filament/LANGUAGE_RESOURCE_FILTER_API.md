# LanguageResource Filter API Reference

## Overview

This document provides API-level documentation for the LanguageResource filter functionality, including filter configuration, behavior, and integration points.

**Resource**: `app/Filament/Resources/LanguageResource.php`  
**Namespace Pattern**: Filament v4 consolidated namespaces  
**Authorization**: SUPERADMIN only

## Filter Definitions

### Active Status Filter

#### Configuration

```php
Tables\Filters\TernaryFilter::make('is_active')
    ->label(__('locales.labels.active'))
    ->placeholder(__('locales.filters.active_placeholder'))
    ->trueLabel(__('locales.filters.active_only'))
    ->falseLabel(__('locales.filters.inactive_only'))
    ->native(false)
```

#### Properties

| Property | Type | Value | Description |
|----------|------|-------|-------------|
| `name` | string | `'is_active'` | Filter identifier |
| `label` | string | Localized | Display label |
| `placeholder` | string | Localized | Default option text |
| `trueLabel` | string | Localized | Active filter label |
| `falseLabel` | string | Localized | Inactive filter label |
| `native` | boolean | `false` | Use custom UI |

#### Filter States

| State | Value | Query | Result |
|-------|-------|-------|--------|
| **All** (default) | `null` | No filter | All languages |
| **Active Only** | `true` | `WHERE is_active = 1` | Active languages |
| **Inactive Only** | `false` | `WHERE is_active = 0` | Inactive languages |

#### Translation Keys

```php
// lang/en/locales.php
'labels' => [
    'active' => 'Active',
],
'filters' => [
    'active_placeholder' => 'All Languages',
    'active_only' => 'Active Only',
    'inactive_only' => 'Inactive Only',
],
```

---

### Default Status Filter

#### Configuration

```php
Tables\Filters\TernaryFilter::make('is_default')
    ->label(__('locales.labels.default'))
    ->placeholder(__('locales.filters.default_placeholder'))
    ->trueLabel(__('locales.filters.default_only'))
    ->falseLabel(__('locales.filters.non_default_only'))
    ->native(false)
```

#### Properties

| Property | Type | Value | Description |
|----------|------|-------|-------------|
| `name` | string | `'is_default'` | Filter identifier |
| `label` | string | Localized | Display label |
| `placeholder` | string | Localized | Default option text |
| `trueLabel` | string | Localized | Default filter label |
| `falseLabel` | string | Localized | Non-default filter label |
| `native` | boolean | `false` | Use custom UI |

#### Filter States

| State | Value | Query | Result |
|-------|-------|-------|--------|
| **All** (default) | `null` | No filter | All languages |
| **Default Only** | `true` | `WHERE is_default = 1` | Default language |
| **Non-Default Only** | `false` | `WHERE is_default = 0` | Non-default languages |

#### Translation Keys

```php
// lang/en/locales.php
'labels' => [
    'default' => 'Default',
],
'filters' => [
    'default_placeholder' => 'All Languages',
    'default_only' => 'Default Only',
    'non_default_only' => 'Non-Default Only',
],
```

---

## Filter Behavior

### Query Building

Filters are applied to the base query using Eloquent's `where()` method:

```php
// Active filter applied
Language::where('is_active', true)->get();

// Default filter applied
Language::where('is_default', true)->get();

// Both filters applied
Language::where('is_active', true)
    ->where('is_default', true)
    ->get();
```

### Filter Persistence

Filters are persisted in the session:

```php
->persistFiltersInSession()
```

This means:
- Filter state is maintained across page refreshes
- Filter state is maintained when navigating away and back
- Filter state is user-specific (session-based)

### Filter Interaction with Other Features

#### Sorting
Filters work seamlessly with sorting:

```php
Language::where('is_active', true)
    ->orderBy('display_order', 'asc')
    ->get();
```

#### Search
Filters work with search functionality:

```php
Language::where('is_active', true)
    ->where('name', 'like', '%search%')
    ->get();
```

#### Pagination
Filters work with pagination:

```php
Language::where('is_active', true)
    ->paginate(10);
```

---

## Database Schema

### Languages Table

```sql
CREATE TABLE languages (
    id BIGINT UNSIGNED PRIMARY KEY,
    code VARCHAR(5) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    native_name VARCHAR(255),
    is_active BOOLEAN DEFAULT 1,
    is_default BOOLEAN DEFAULT 0,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX idx_is_active (is_active),
    INDEX idx_is_default (is_default),
    INDEX idx_display_order (display_order),
    INDEX idx_is_active_display_order (is_active, display_order)
);
```

### Indexes for Filter Performance

| Index | Columns | Purpose |
|-------|---------|---------|
| `idx_is_active` | `is_active` | Active filter performance |
| `idx_is_default` | `is_default` | Default filter performance |
| `idx_is_active_display_order` | `is_active, display_order` | Combined filter + sort |

---

## Authorization

### Policy Integration

Filters respect the LanguagePolicy:

```php
// app/Policies/LanguagePolicy.php
public function viewAny(User $user): bool
{
    return $user->role === UserRole::SUPERADMIN;
}
```

### Navigation Visibility

```php
// app/Filament/Resources/LanguageResource.php
public static function shouldRegisterNavigation(): bool
{
    $user = auth()->user();
    return $user instanceof User && $user->role === UserRole::SUPERADMIN;
}
```

### Access Control Matrix

| Role | View List | Use Filters | Create | Edit | Delete |
|------|-----------|-------------|--------|------|--------|
| SUPERADMIN | ✅ | ✅ | ✅ | ✅ | ✅ |
| ADMIN | ❌ | ❌ | ❌ | ❌ | ❌ |
| MANAGER | ❌ | ❌ | ❌ | ❌ | ❌ |
| TENANT | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## Performance Characteristics

### Query Performance

| Operation | Dataset Size | Execution Time | Notes |
|-----------|--------------|----------------|-------|
| Active filter | 1,000 records | < 100ms | With index |
| Default filter | 1,000 records | < 100ms | With index |
| Combined filters | 1,000 records | < 150ms | With composite index |

### Optimization Strategies

1. **Database Indexes**: Ensure indexes exist on `is_active` and `is_default`
2. **Query Caching**: Consider caching for frequently accessed filter combinations
3. **Eager Loading**: Not applicable (no relationships in filter queries)
4. **Pagination**: Always use pagination for large result sets

---

## Integration Points

### Language Model

```php
// app/Models/Language.php
class Language extends Model
{
    protected $fillable = [
        'code',
        'name',
        'native_name',
        'is_active',
        'is_default',
        'display_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'display_order' => 'integer',
    ];
}
```

### Language Factory

```php
// database/factories/LanguageFactory.php
public function definition(): array
{
    return [
        'code' => $this->faker->unique()->languageCode(),
        'name' => $this->faker->word(),
        'native_name' => $this->faker->word(),
        'is_active' => $this->faker->boolean(80), // 80% active
        'is_default' => false,
        'display_order' => $this->faker->numberBetween(0, 100),
    ];
}
```

---

## Usage Examples

### Example 1: Filter Active Languages

```php
// In Filament table
// User selects "Active Only" from filter dropdown
// Query executed:
Language::where('is_active', true)
    ->orderBy('display_order', 'asc')
    ->paginate(10);
```

### Example 2: Filter Default Language

```php
// In Filament table
// User selects "Default Only" from filter dropdown
// Query executed:
Language::where('is_default', true)
    ->orderBy('display_order', 'asc')
    ->paginate(10);
```

### Example 3: Combined Filters

```php
// In Filament table
// User selects "Active Only" AND "Default Only"
// Query executed:
Language::where('is_active', true)
    ->where('is_default', true)
    ->orderBy('display_order', 'asc')
    ->paginate(10);
```

### Example 4: Clear Filters

```php
// In Filament table
// User clicks "Clear Filters" or selects "All Languages"
// Query executed:
Language::orderBy('display_order', 'asc')
    ->paginate(10);
```

---

## Error Handling

### No Results

When filters return no results:

```php
// Filament displays empty state
->emptyStateHeading(__('locales.empty.heading'))
->emptyStateDescription(__('locales.empty.description'))
```

### Invalid Filter Values

Filament's TernaryFilter only accepts:
- `null` (all)
- `true` (filter true)
- `false` (filter false)

Invalid values are automatically coerced to `null`.

---

## Testing

### Test Coverage

- ✅ Filter configuration verification
- ✅ Filter functionality (active/inactive)
- ✅ Filter functionality (default/non-default)
- ✅ Combined filter behavior
- ✅ Edge cases (empty, all active, all inactive)
- ✅ Performance benchmarks
- ✅ Authorization checks

### Test File

```bash
tests/Feature/Filament/LanguageResourceFilterTest.php
```

### Running Tests

```bash
# Run all filter tests
php artisan test tests/Feature/Filament/LanguageResourceFilterTest.php

# Run specific test group
php artisan test --filter="Active Status Filter"
```

---

## Namespace Consolidation

### Filament v4 Pattern

```php
// ✅ Correct (consolidated namespace)
use Filament\Tables;

Tables\Filters\TernaryFilter::make('is_active')
```

### Legacy Pattern (Not Used)

```php
// ❌ Old pattern (individual imports)
use Filament\Tables\Filters\TernaryFilter;

TernaryFilter::make('is_active')
```

### Benefits

- Reduced import statements
- Consistent namespace usage
- Easier code reviews
- Better IDE support

---

## Related Documentation

- [LanguageResource Filter Test Documentation](../testing/LANGUAGE_RESOURCE_FILTER_TEST_DOCUMENTATION.md)
- [LanguageResource Navigation Tests](../testing/LANGUAGE_RESOURCE_NAVIGATION_TEST_COMPLETE.md)
- [LanguageResource Performance Optimization](../performance/LANGUAGE_RESOURCE_PERFORMANCE_OPTIMIZATION.md)
- [Filament Namespace Consolidation Spec](../../.kiro/specs/6-filament-namespace-consolidation/tasks.md)

---

## Changelog

### 2025-11-28
- ✅ Initial API documentation created
- ✅ Filter configuration documented
- ✅ Performance characteristics documented
- ✅ Authorization matrix documented
- ✅ Integration points documented

---

**Last Updated**: 2025-11-28  
**API Version**: 1.0.0  
**Status**: ✅ Complete
