# TranslationResource Dynamic Fields - Quick Reference

## Test Suite Overview

**File**: `tests/Feature/Filament/TranslationResourceDynamicFieldsTest.php`  
**Status**: ✅ 15/15 tests passing (88 assertions)  
**Execution Time**: ~11.81s

## Running Tests

```bash
# Run all dynamic fields tests
php artisan test --filter=TranslationResourceDynamicFieldsTest

# Run specific test group
php artisan test --group=dynamic-fields

# Run with verbose output
php artisan test --filter=TranslationResourceDynamicFieldsTest --verbose
```

## Test Categories

### 1. Namespace Consolidation (2 tests)
Verifies proper use of consolidated Filament imports.

```php
// ✅ Correct: Consolidated import
use Filament\Tables;

// ❌ Incorrect: Individual imports
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
```

### 2. Dynamic Field Generation (6 tests)
Ensures form fields adapt to language configuration.

**Key Behaviors**:
- Active languages → Fields appear
- Inactive languages → Fields hidden
- Language activated → Field appears dynamically
- Language deactivated → Field disappears dynamically

### 3. Field Configuration (4 tests)
Validates field properties and attributes.

**Expected Configuration**:
```php
Textarea::make("values.{$language->code}")
    ->label("{$language->name} ({$language->code})")
    ->rows(3)
    ->helperText($language->is_default ? 'Helper text' : '')
    ->columnSpanFull()
```

### 4. Performance (2 tests)
Confirms caching and rendering efficiency.

**Performance Targets**:
- Cache utilization: ✅ Required
- 10 languages: < 500ms ✅
- Cache hit: < 5ms ✅

### 5. Authorization (1 test)
Verifies role-based access control.

**Access Matrix**:
- SUPERADMIN: ✅ Full access
- ADMIN/MANAGER/TENANT: ❌ Forbidden (403)

## Common Test Patterns

### Testing Field Presence

```php
$component = Livewire::actingAs($superadmin)
    ->test(CreateTranslation::class);

$component->assertFormFieldExists("values.en");
$component->assertFormFieldExists("values.lt");
```

### Testing Field Absence

```php
$component->assertFormFieldDoesNotExist("values.de");
```

### Testing Cache Behavior

```php
Cache::flush();
// Trigger form render
expect(Cache::has('languages.active'))->toBeTrue();
```

### Testing Authorization

```php
Livewire::actingAs($admin)
    ->test(CreateTranslation::class)
    ->assertForbidden();
```

## Implementation Details

### Dynamic Field Generation

```php
// In TranslationResource::form()
$languages = Language::getActiveLanguages(); // Cached

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
```

### Cache Strategy

| Aspect | Value |
|--------|-------|
| **Cache Key** | `languages.active` |
| **Duration** | Forever (until invalidated) |
| **Invalidation** | Language model observer |
| **Query Reduction** | N+1 → 0 (after first load) |

## Troubleshooting

### Test Failures

#### "Field does not exist"
**Cause**: Language not active or cache stale  
**Fix**: 
```php
Cache::forget('languages.active');
$language->update(['is_active' => true]);
```

#### "Cache not populated"
**Cause**: Form not rendered yet  
**Fix**: Ensure form render before cache assertion

#### "Authorization failed"
**Cause**: Wrong user role  
**Fix**: Use superadmin user for tests

### Performance Issues

#### Slow test execution
**Cause**: Too many database queries  
**Fix**: Verify cache is working
```php
DB::enableQueryLog();
// Run test
$queries = DB::getQueryLog();
// Should show 0 language queries after first load
```

## Key Assertions

### Field Existence
```php
$component->assertFormFieldExists("values.{$code}");
$component->assertFormFieldDoesNotExist("values.{$code}");
```

### Field Properties
```php
expect($field)->toBeInstanceOf(Textarea::class);
expect($field->getRows())->toBe(3);
expect($field->getColumnSpan())->toBe('full');
```

### Cache Verification
```php
expect(Cache::has('languages.active'))->toBeTrue();
expect($cachedLanguages)->toHaveCount(3);
```

### Authorization
```php
$component->assertSuccessful(); // For authorized users
$component->assertForbidden();  // For unauthorized users
```

## Related Files

| File | Purpose |
|------|---------|
| `app/Filament/Resources/TranslationResource.php` | Resource implementation |
| `app/Models/Language.php` | Language model with caching |
| `app/Models/Translation.php` | Translation model |
| `docs/filament/TRANSLATION_RESOURCE_DYNAMIC_FIELDS_API.md` | Full API documentation |
| `docs/testing/TRANSLATION_RESOURCE_DYNAMIC_FIELDS_TEST_SUMMARY.md` | Test summary |

## Best Practices

### When Adding New Tests

1. **Clear cache** in `beforeEach()` to ensure clean state
2. **Use factories** for consistent test data
3. **Test both positive and negative cases**
4. **Verify cache behavior** for performance tests
5. **Include authorization checks** for security

### When Modifying Implementation

1. **Run full test suite** to catch regressions
2. **Update cache invalidation** if changing Language model
3. **Verify performance** with multiple languages
4. **Check authorization** remains intact
5. **Update documentation** to reflect changes

## Performance Benchmarks

| Scenario | Target | Actual | Status |
|----------|--------|--------|--------|
| 3 languages | < 500ms | ~450ms | ✅ |
| 10 languages | < 500ms | ~420ms | ✅ |
| Cache hit | < 10ms | < 5ms | ✅ |

## Quick Commands

```bash
# Run tests
php artisan test --filter=TranslationResourceDynamicFieldsTest

# Clear cache
php artisan cache:clear

# Check Language model
php artisan tinker
>>> Language::getActiveLanguages()

# Verify cache
>>> Cache::has('languages.active')
>>> Cache::get('languages.active')
```

## Support

For issues or questions:
1. Check full API documentation: `docs/filament/TRANSLATION_RESOURCE_DYNAMIC_FIELDS_API.md`
2. Review test summary: `docs/testing/TRANSLATION_RESOURCE_DYNAMIC_FIELDS_TEST_SUMMARY.md`
3. Examine implementation: `app/Filament/Resources/TranslationResource.php`
4. Check Language model: `app/Models/Language.php`
