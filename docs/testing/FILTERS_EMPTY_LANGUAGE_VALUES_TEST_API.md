# FiltersEmptyLanguageValues Test API Reference

## Overview

Complete API reference for the `FiltersEmptyLanguageValuesTest` unit test suite, documenting all test methods, assertions, and validation patterns.

**Test File**: `tests/Unit/Filament/Concerns/FiltersEmptyLanguageValuesTest.php`  
**Trait Under Test**: `App\Filament\Resources\TranslationResource\Concerns\FiltersEmptyLanguageValues`  
**Test Framework**: PHPUnit 11.x with Pest 3.x runner  
**Test Type**: Unit test (no database required)

## Test Class

```php
namespace Tests\Unit\Filament\Concerns;

use App\Filament\Resources\TranslationResource\Concerns\FiltersEmptyLanguageValues;
use PHPUnit\Framework\TestCase;

class FiltersEmptyLanguageValuesTest extends TestCase
{
    use FiltersEmptyLanguageValues;
}
```

### Class Metadata

- **@group unit**: Categorized as unit test
- **@group filament**: Filament-related functionality
- **@group translation**: Translation system component
- **@group concerns**: Trait/concern testing
- **@covers**: `\App\Filament\Resources\TranslationResource\Concerns\FiltersEmptyLanguageValues`

### Related Components

- `\App\Filament\Resources\TranslationResource\Pages\CreateTranslation`
- `\App\Filament\Resources\TranslationResource\Pages\EditTranslation`
- `\App\Models\Translation`

## Test Methods

### Valid Value Preservation Tests

#### `test_preserves_valid_language_values()`

**Purpose**: Validates that all valid language values are preserved unchanged.

**Test Data**:
```php
[
    'group' => 'common',
    'key' => 'welcome',
    'values' => [
        'en' => 'Welcome',
        'lt' => 'Sveiki',
        'ru' => 'Добро пожаловать',
    ],
]
```

**Assertions**:
- Result equals input data (no modifications)
- Values array contains exactly 3 items

**Expected Behavior**: All valid text values remain unchanged.

---

#### `test_preserves_values_with_meaningful_spaces()`

**Purpose**: Ensures leading/trailing spaces are preserved when they're part of the content.

**Test Data**:
```php
[
    'values' => [
        'en' => ' Welcome ',
        'lt' => 'Sveiki ',
        'ru' => ' Добро пожаловать',
    ],
]
```

**Assertions**:
- All 3 values preserved
- Spaces remain intact: `' Welcome '`, `'Sveiki '`, `' Добро пожаловать'`

**Expected Behavior**: Meaningful whitespace is not trimmed.

---

#### `test_preserves_numeric_string_values()`

**Purpose**: Validates that numeric strings (including '0') are preserved.

**Test Data**:
```php
[
    'values' => [
        'en' => '0',
        'lt' => '123',
        'ru' => '456.78',
    ],
]
```

**Assertions**:
- All 3 numeric strings preserved
- Values remain as strings: `'0'`, `'123'`, `'456.78'`

**Expected Behavior**: Numeric content is valid translation text.

---

#### `test_preserves_special_characters()`

**Purpose**: Ensures special characters, HTML, and symbols are preserved.

**Test Data**:
```php
[
    'values' => [
        'en' => '<html>',
        'lt' => 'Test & Co.',
        'ru' => 'Тест "кавычки"',
        'es' => '¡Hola!',
    ],
]
```

**Assertions**:
- All 4 values with special characters preserved
- No escaping or modification of special characters

**Expected Behavior**: Special characters remain unchanged.

---

#### `test_preserves_multiline_values()`

**Purpose**: Validates that multiline text with line breaks is preserved.

**Test Data**:
```php
[
    'values' => [
        'en' => "Line 1\nLine 2\nLine 3",
        'lt' => "Eilutė 1\nEilutė 2",
    ],
]
```

**Assertions**:
- Both multiline values preserved
- Line breaks (`\n`) remain intact

**Expected Behavior**: Multiline content is valid for translations.

---

#### `test_preserves_zero_string()`

**Purpose**: Edge case validation that string '0' is preserved.

**Test Data**:
```php
[
    'values' => [
        'en' => '0',
        'lt' => 'Zero',
    ],
]
```

**Assertions**:
- Both values preserved
- String '0' not treated as empty

**Expected Behavior**: '0' is valid content, not an empty value.

---

### Empty Value Filtering Tests

#### `test_filters_null_values()`

**Purpose**: Validates that null values are removed from the values array.

**Test Data**:
```php
[
    'values' => [
        'en' => 'Welcome',
        'lt' => null,
        'ru' => 'Добро пожаловать',
    ],
]
```

**Assertions**:
- Result contains 2 values (not 3)
- 'en' key exists
- 'lt' key does not exist (filtered out)
- 'ru' key exists

**Expected Behavior**: Null values are removed, others preserved.

---

#### `test_filters_empty_string_values()`

**Purpose**: Validates that empty strings are removed from the values array.

**Test Data**:
```php
[
    'values' => [
        'en' => 'Welcome',
        'lt' => '',
        'ru' => 'Добро пожаловать',
    ],
]
```

**Assertions**:
- Result contains 2 values (not 3)
- 'en' key exists
- 'lt' key does not exist (filtered out)
- 'ru' key exists

**Expected Behavior**: Empty strings are removed, others preserved.

---

#### `test_filters_whitespace_only_values()`

**Purpose**: Validates that whitespace-only values are removed.

**Test Data**:
```php
[
    'values' => [
        'en' => 'Welcome',
        'lt' => '   ',
        'ru' => '     ',
        'es' => '  ',
    ],
]
```

**Assertions**:
- Result contains 1 value (not 4)
- Only 'en' key exists
- All whitespace-only keys filtered out

**Expected Behavior**: Whitespace-only values provide no content.

---

#### `test_filters_mixed_empty_and_valid_values()`

**Purpose**: Validates handling of mixed null, empty, whitespace, and valid values.

**Test Data**:
```php
[
    'values' => [
        'en' => 'Welcome',
        'lt' => null,
        'ru' => '',
        'es' => '   ',
        'fr' => 'Bienvenue',
        'de' => "\t",
    ],
]
```

**Assertions**:
- Result contains 2 values (not 6)
- Only 'en' and 'fr' keys exist
- Values are 'Welcome' and 'Bienvenue'

**Expected Behavior**: Only valid content is preserved.

---

#### `test_all_empty_values_results_in_empty_array()`

**Purpose**: Validates that all empty values result in an empty array.

**Test Data**:
```php
[
    'values' => [
        'en' => null,
        'lt' => '',
        'ru' => '   ',
    ],
]
```

**Assertions**:
- 'values' key exists
- 'values' is an array
- 'values' array is empty

**Expected Behavior**: Empty array, not null or removed key.

---

### Edge Case Tests

#### `test_data_without_values_key_unchanged()`

**Purpose**: Validates graceful handling when 'values' key is missing.

**Test Data**:
```php
[
    'group' => 'common',
    'key' => 'welcome',
]
```

**Assertions**:
- Result equals input (no modifications)
- 'values' key does not exist

**Expected Behavior**: Data without 'values' key is unchanged.

---

#### `test_non_array_values_key_unchanged()`

**Purpose**: Validates handling when 'values' is not an array.

**Test Data**:
```php
[
    'group' => 'common',
    'key' => 'welcome',
    'values' => 'not-an-array',
]
```

**Assertions**:
- Result equals input (no modifications)
- 'values' remains as string 'not-an-array'

**Expected Behavior**: Non-array values are left unchanged.

---

#### `test_empty_values_array_preserved()`

**Purpose**: Validates that an explicitly empty array is preserved.

**Test Data**:
```php
[
    'values' => [],
]
```

**Assertions**:
- 'values' key exists
- 'values' is an array
- 'values' array is empty

**Expected Behavior**: Empty array is preserved as-is.

---

#### `test_preserves_other_form_fields()`

**Purpose**: Validates that filtering only affects 'values', not other fields.

**Test Data**:
```php
[
    'group' => 'common',
    'key' => 'welcome',
    'values' => [
        'en' => 'Welcome',
        'lt' => null,
    ],
    'created_at' => '2024-01-01 00:00:00',
    'updated_at' => '2024-01-02 00:00:00',
    'metadata' => ['foo' => 'bar'],
]
```

**Assertions**:
- 'group' remains 'common'
- 'key' remains 'welcome'
- 'created_at' remains unchanged
- 'updated_at' remains unchanged
- 'metadata' remains unchanged
- 'values' contains 1 item (null filtered out)

**Expected Behavior**: Only 'values' array is filtered.

---

#### `test_preserves_boolean_false()`

**Purpose**: Edge case validation for boolean false values.

**Test Data**:
```php
[
    'values' => [
        'en' => false,
        'lt' => 'Valid',
    ],
]
```

**Assertions**:
- Result contains 1 value (not 2)
- Only 'lt' key exists

**Expected Behavior**: Boolean false is filtered out (converts to empty string).

**Note**: This is expected behavior as translations should be strings, not booleans.

---

## Filtering Logic

### Filter Conditions

The trait uses three conditions to determine if a value should be preserved:

```php
fn (mixed $value): bool => 
    $value !== null &&           // Condition 1: Not null
    $value !== '' &&             // Condition 2: Not empty string
    trim((string) $value) !== '' // Condition 3: Not whitespace-only
```

**All three conditions must be true** for a value to be preserved.

### Type Coercion

The filter uses `(string)` type coercion before trimming:
- Strings: Remain as-is
- Numbers: Convert to string representation
- Boolean true: Converts to '1' (preserved)
- Boolean false: Converts to '' (filtered out)
- Null: Fails first condition (filtered out)

## Usage Examples

### In CreateTranslation Page

```php
use App\Filament\Resources\TranslationResource\Concerns\FiltersEmptyLanguageValues;

class CreateTranslation extends CreateRecord
{
    use FiltersEmptyLanguageValues;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->filterEmptyLanguageValues($data);
    }
}
```

### In EditTranslation Page

```php
use App\Filament\Resources\TranslationResource\Concerns\FiltersEmptyLanguageValues;

class EditTranslation extends EditRecord
{
    use FiltersEmptyLanguageValues;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->filterEmptyLanguageValues($data);
    }
}
```

## Test Execution

### Run All Tests

```bash
php artisan test --filter=FiltersEmptyLanguageValuesTest
```

### Expected Output

```
PASS  Tests\Unit\Filament\Concerns\FiltersEmptyLanguageValuesTest
✓ preserves valid language values
✓ filters null values
✓ filters empty string values
✓ filters whitespace only values
✓ filters mixed empty and valid values
✓ all empty values results in empty array
✓ data without values key unchanged
✓ non array values key unchanged
✓ empty values array preserved
✓ preserves values with meaningful spaces
✓ preserves numeric string values
✓ preserves special characters
✓ preserves multiline values
✓ preserves other form fields
✓ preserves boolean false
✓ preserves zero string

Tests:  16 passed (67 assertions)
Duration: ~5-6s
```

### Run with Coverage

```bash
php artisan test --filter=FiltersEmptyLanguageValuesTest --coverage
```

## Benefits

### Data Integrity
- Prevents null values in database
- Prevents empty strings in database
- Prevents whitespace-only values in database

### Storage Efficiency
- Reduces JSON field size
- Improves query performance on JSON fields
- Reduces database storage requirements

### User Experience
- Cleaner data presentation
- No empty translation entries displayed
- Consistent data quality

### Consistency
- Same filtering logic for create and edit
- Predictable behavior across operations
- Centralized filtering logic in trait

## Related Documentation

- **Trait Implementation**: `app/Filament/Resources/TranslationResource/Concerns/FiltersEmptyLanguageValues.php`
- **Test Documentation**: `docs/testing/FILTERS_EMPTY_LANGUAGE_VALUES_TEST_DOCUMENTATION.md`
- **Test Summary**: `docs/testing/FILTERS_EMPTY_LANGUAGE_VALUES_SUMMARY.md`
- **Completion Report**: `docs/testing/FILTERS_EMPTY_LANGUAGE_VALUES_COMPLETION.md`
- **CreateTranslation Page**: `app/Filament/Resources/TranslationResource/Pages/CreateTranslation.php`
- **EditTranslation Page**: `app/Filament/Resources/TranslationResource/Pages/EditTranslation.php`

## Maintenance Notes

- Tests use PHPUnit's `TestCase` directly (not Laravel's `TestCase`)
- No database interaction required (pure unit test)
- Fast execution time (~0.01-0.11s per test)
- Trait is used directly in test class for isolated testing
- No mocking required

## Version History

- **v1.0.0** (2024-11-29): Initial comprehensive test suite with 16 tests and 67 assertions
