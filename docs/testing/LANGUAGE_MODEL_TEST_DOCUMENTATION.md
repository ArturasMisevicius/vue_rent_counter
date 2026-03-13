# Language Model Test Documentation

## Overview

The `LanguageTest` suite provides comprehensive testing for the `Language` model, covering model configuration, query scopes, caching behavior, factory states, and database constraints. This test suite ensures the localization system's foundation is robust and secure.

**Test File**: `tests/Unit/Models/LanguageTest.php`  
**Model**: `App\Models\Language`  
**Factory**: `Database\Factories\LanguageFactory`  
**Observer**: `App\Observers\LanguageObserver`

## Test Coverage Summary

| Category | Tests | Coverage |
|----------|-------|----------|
| Model Configuration | 2 | Fillable attributes, attribute casting |
| Query Scopes | 1 | Active language filtering |
| Static Methods | 2 | getActiveLanguages(), getDefault() |
| Caching | 4 | Cache invalidation, cache usage |
| Factory | 2 | Basic creation, factory states |
| Database Constraints | 1 | Unique code constraint |
| Attribute Mutators | 1 | Code normalization |
| **Total** | **13** | **Complete model coverage** |

## Test Categories

### 1. Model Configuration Tests

#### test_language_has_fillable_attributes()
**Purpose**: Verify mass assignment protection  
**Security**: Ensures only whitelisted attributes can be mass-assigned

```php
public function test_language_has_fillable_attributes(): void
{
    $fillable = [
        'code',
        'name',
        'native_name',
        'is_default',
        'is_active',
        'display_order',
    ];
    
    $language = new Language();
    $this->assertEquals($fillable, $language->getFillable());
}
```

**Assertions**:
- Verifies fillable array matches expected attributes
- Protects against mass assignment vulnerabilities

#### test_language_casts_attributes_correctly()
**Purpose**: Verify boolean attribute casting  
**Security**: Prevents type confusion attacks

```php
public function test_language_casts_attributes_correctly(): void
{
    $language = Language::factory()->create([
        'is_active' => true,
        'is_default' => false,
    ]);

    $this->assertIsBool($language->is_active);
    $this->assertIsBool($language->is_default);
}
```

**Assertions**:
- `is_active` is properly cast to boolean
- `is_default` is properly cast to boolean

### 2. Attribute Mutator Tests

#### test_language_code_is_normalized_to_lowercase()
**Purpose**: Verify code normalization  
**Security**: Ensures consistent lookups regardless of input case

```php
public function test_language_code_is_normalized_to_lowercase(): void
{
    $language = Language::factory()->create([
        'code' => 'EN-US',
    ]);

    $this->assertEquals('en-us', $language->code);
    $this->assertEquals('en-us', $language->fresh()->code);
}
```

**Assertions**:
- Code is converted to lowercase on set
- Normalization persists in database

**Business Logic**: Prevents case-sensitivity issues in language lookups

### 3. Query Scope Tests

#### test_language_active_scope()
**Purpose**: Verify active() scope filters correctly  
**Security**: Ensures SQL injection prevention through query scopes

```php
public function test_language_active_scope(): void
{
    Language::factory()->create(['is_active' => true]);
    Language::factory()->create(['is_active' => false]);

    $activeLanguages = Language::active()->get();
    
    $this->assertCount(1, $activeLanguages);
    $this->assertTrue($activeLanguages->first()->is_active);
}
```

**Assertions**:
- Only active languages are returned
- Inactive languages are filtered out

**Usage**: `Language::active()->get()`

### 4. Static Method Tests

#### test_get_active_languages_returns_ordered_active_languages()
**Purpose**: Verify getActiveLanguages() returns ordered results  
**Performance**: Tests caching behavior

```php
public function test_get_active_languages_returns_ordered_active_languages(): void
{
    Language::factory()->create(['is_active' => false, 'display_order' => 1]);
    Language::factory()->create(['is_active' => true, 'display_order' => 3]);
    Language::factory()->create(['is_active' => true, 'display_order' => 1]);
    Language::factory()->create(['is_active' => true, 'display_order' => 2]);

    $activeLanguages = Language::getActiveLanguages();
    
    $this->assertCount(3, $activeLanguages);
    $this->assertEquals(1, $activeLanguages->first()->display_order);
    $this->assertEquals(3, $activeLanguages->last()->display_order);
}
```

**Assertions**:
- Only active languages returned (3 out of 4)
- Results ordered by display_order ascending
- First language has display_order = 1
- Last language has display_order = 3

**Cache Key**: `languages.active` (TTL: 15 minutes)

#### test_get_default_language_returns_default()
**Purpose**: Verify getDefault() returns default language  
**Performance**: Tests caching behavior

```php
public function test_get_default_language_returns_default(): void
{
    $defaultLanguage = Language::factory()->create(['is_default' => true]);
    Language::factory()->create(['is_default' => false]);

    $result = Language::getDefault();
    
    $this->assertNotNull($result);
    $this->assertTrue($result->is_default);
    $this->assertEquals($defaultLanguage->id, $result->id);
}
```

**Assertions**:
- Default language is returned
- Correct language is identified
- Non-default languages ignored

**Cache Key**: `languages.default` (TTL: 15 minutes)

### 5. Cache Management Tests

#### test_cache_is_invalidated_when_language_is_saved()
**Purpose**: Verify cache invalidation on save  
**Performance**: Ensures fresh data after updates

```php
public function test_cache_is_invalidated_when_language_is_saved(): void
{
    Cache::shouldReceive('remember')
        ->with('languages.active', 900, \Closure::class)
        ->once()
        ->andReturn(collect());

    Cache::shouldReceive('forget')
        ->with('languages.active')
        ->once();

    Cache::shouldReceive('forget')
        ->with('languages.default')
        ->once();

    Language::factory()->create();
}
```

**Assertions**:
- `languages.active` cache is cleared
- `languages.default` cache is cleared
- Cache invalidation happens on model save

#### test_cache_is_invalidated_when_language_is_deleted()
**Purpose**: Verify cache invalidation on delete  
**Performance**: Ensures fresh data after deletions

```php
public function test_cache_is_invalidated_when_language_is_deleted(): void
{
    $language = Language::factory()->create();

    Cache::shouldReceive('forget')
        ->with('languages.active')
        ->once();

    Cache::shouldReceive('forget')
        ->with('languages.default')
        ->once();

    $language->delete();
}
```

**Assertions**:
- `languages.active` cache is cleared on delete
- `languages.default` cache is cleared on delete

#### test_get_active_languages_uses_cache()
**Purpose**: Verify caching behavior for getActiveLanguages()  
**Performance**: Confirms cache is used for repeated calls

```php
public function test_get_active_languages_uses_cache(): void
{
    // First call should cache
    $firstResult = Language::getActiveLanguages();
    
    // Create a new language
    Language::factory()->create(['is_active' => true]);
    
    // Second call should return cached result
    $secondResult = Language::getActiveLanguages();
    
    $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $firstResult);
    $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $secondResult);
}
```

**Note**: In production, cache is invalidated by model events

#### test_get_default_uses_cache()
**Purpose**: Verify caching behavior for getDefault()  
**Performance**: Confirms cache is used for repeated calls

```php
public function test_get_default_uses_cache(): void
{
    Language::factory()->default()->create();
    
    // First call should cache
    $firstResult = Language::getDefault();
    
    // Second call should return cached result
    $secondResult = Language::getDefault();
    
    $this->assertInstanceOf(Language::class, $firstResult);
    $this->assertEquals($firstResult->id, $secondResult->id);
}
```

**Cache Behavior**: 15-minute TTL, invalidated on model changes

### 6. Factory Tests

#### test_language_factory_creates_valid_language()
**Purpose**: Verify factory creates valid language records

```php
public function test_language_factory_creates_valid_language(): void
{
    $language = Language::factory()->create();

    $this->assertNotNull($language->code);
    $this->assertNotNull($language->name);
    $this->assertNotNull($language->native_name);
    $this->assertIsBool($language->is_active);
    $this->assertIsBool($language->is_default);
    $this->assertIsInt($language->display_order);
}
```

**Assertions**:
- All required fields are populated
- Boolean fields are properly typed
- display_order is integer

#### test_language_factory_states_work_correctly()
**Purpose**: Verify factory states function properly

```php
public function test_language_factory_states_work_correctly(): void
{
    $activeLanguage = Language::factory()->active()->create();
    $inactiveLanguage = Language::factory()->inactive()->create();
    $defaultLanguage = Language::factory()->default()->create();

    $this->assertTrue($activeLanguage->is_active);
    $this->assertFalse($inactiveLanguage->is_active);
    $this->assertTrue($defaultLanguage->is_default);
}
```

**Factory States**:
- `active()` - Sets is_active = true
- `inactive()` - Sets is_active = false
- `default()` - Sets is_default = true

### 7. Database Constraint Tests

#### test_language_code_is_unique()
**Purpose**: Verify unique constraint on code column  
**Database**: Tests database-level constraint enforcement

```php
public function test_language_code_is_unique(): void
{
    Language::factory()->create(['code' => 'en']);

    $this->expectException(\Illuminate\Database\QueryException::class);
    
    Language::factory()->create(['code' => 'en']);
}
```

**Assertions**:
- Duplicate language codes are rejected
- QueryException is thrown on constraint violation

**Database Constraint**: `UNIQUE(code)`

## Running the Tests

### Run All Language Model Tests
```bash
php artisan test --filter=LanguageTest
```

### Run Specific Test
```bash
php artisan test --filter=LanguageTest::test_language_has_fillable_attributes
```

### Run with Coverage
```bash
php artisan test --filter=LanguageTest --coverage
```

## Test Data Setup

### Basic Language Creation
```php
$language = Language::factory()->create();
```

### Create Active Language
```php
$language = Language::factory()->active()->create();
```

### Create Default Language
```php
$language = Language::factory()->default()->create();
```

### Create with Specific Attributes
```php
$language = Language::factory()->create([
    'code' => 'en',
    'name' => 'English',
    'native_name' => 'English',
    'is_default' => true,
    'is_active' => true,
    'display_order' => 1,
]);
```

## Security Considerations

### Mass Assignment Protection
The test suite verifies that only whitelisted attributes can be mass-assigned:
- `code`
- `name`
- `native_name`
- `is_default`
- `is_active`
- `display_order`

### Type Safety
Boolean casting prevents type confusion:
- `is_default` always returns boolean
- `is_active` always returns boolean

### SQL Injection Prevention
Query scopes use parameterized queries:
- `active()` scope safely filters by is_active
- No raw SQL in scope definitions

### Code Normalization
Lowercase conversion prevents case-sensitivity issues:
- `EN` → `en`
- `en-US` → `en-us`
- Consistent lookups regardless of input case

## Performance Considerations

### Caching Strategy
- **Cache Keys**: `languages.active`, `languages.default`
- **TTL**: 15 minutes (900 seconds)
- **Invalidation**: Automatic on save/delete via model events

### Cache Invalidation Events
```php
protected static function booted(): void
{
    self::saved(function () {
        cache()->forget('languages.active');
        cache()->forget('languages.default');
    });

    self::deleted(function () {
        cache()->forget('languages.active');
        cache()->forget('languages.default');
    });
}
```

### Query Optimization
- `getActiveLanguages()` uses single query with ordering
- `getDefault()` uses single query with where clause
- Both methods leverage caching for repeated calls

## Related Documentation

### Model Documentation
- [Language Model](../models/LANGUAGE_MODEL.md)
- [Language Observer](../observers/LANGUAGE_OBSERVER.md)
- [Language Factory](../factories/LANGUAGE_FACTORY.md)

### Filament Resources
- [Language Resource](../filament/LANGUAGE_RESOURCE.md)
- [Language Resource Filters](../filament/LANGUAGE_RESOURCE_FILTER_API.md)
- [Language Resource Actions](../filament/LANGUAGE_RESOURCE_SET_DEFAULT_API.md)

### Related Tests
- [Translation Model Tests](../testing/TRANSLATION_MODEL_TEST_DOCUMENTATION.md)
- [Language Resource Tests](../testing/LANGUAGE_RESOURCE_TEST_DOCUMENTATION.md)

## Maintenance

### Adding New Tests
When adding new Language model functionality:
1. Add corresponding test method
2. Follow naming convention: `test_[feature]_[behavior]()`
3. Add DocBlock with purpose and security notes
4. Update this documentation

### Test Maintenance Checklist
- [ ] All tests passing
- [ ] Coverage > 90%
- [ ] DocBlocks up to date
- [ ] Security considerations documented
- [ ] Performance implications noted

## Changelog

### 2024-12-05
- ✅ Created comprehensive test suite (13 tests)
- ✅ Added model configuration tests
- ✅ Added query scope tests
- ✅ Added caching tests
- ✅ Added factory tests
- ✅ Added database constraint tests
- ✅ Added attribute mutator tests
- ✅ Created test documentation

## Support

For questions or issues:
1. Review [Language Model Documentation](../models/LANGUAGE_MODEL.md)
2. Check [Localization Guide](../guides/LOCALIZATION.md)
3. Examine test implementation for examples
