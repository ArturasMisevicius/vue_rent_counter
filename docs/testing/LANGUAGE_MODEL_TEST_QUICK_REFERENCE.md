# Language Model Test Quick Reference

## Test Execution

```bash
# Run all Language model tests
php artisan test --filter=LanguageTest

# Run specific test
php artisan test --filter=LanguageTest::test_language_has_fillable_attributes

# Run with coverage
php artisan test --filter=LanguageTest --coverage

# Run in parallel
php artisan test --filter=LanguageTest --parallel
```

## Test Categories

| Category | Test Count | Key Tests |
|----------|------------|-----------|
| Model Configuration | 2 | Fillable, Casts |
| Query Scopes | 1 | Active scope |
| Static Methods | 2 | getActiveLanguages, getDefault |
| Caching | 4 | Invalidation, Usage |
| Factory | 2 | Creation, States |
| Constraints | 1 | Unique code |
| Mutators | 1 | Code normalization |

## Quick Test Examples

### Test Fillable Attributes
```php
$language = new Language();
$this->assertEquals([
    'code', 'name', 'native_name', 
    'is_default', 'is_active', 'display_order'
], $language->getFillable());
```

### Test Active Scope
```php
Language::factory()->create(['is_active' => true]);
Language::factory()->create(['is_active' => false]);

$active = Language::active()->get();
$this->assertCount(1, $active);
```

### Test Code Normalization
```php
$language = Language::factory()->create(['code' => 'EN-US']);
$this->assertEquals('en-us', $language->code);
```

### Test Cache Invalidation
```php
Cache::shouldReceive('forget')
    ->with('languages.active')
    ->once();

Language::factory()->create();
```

## Factory Usage

```php
# Basic creation
$language = Language::factory()->create();

# Active language
$language = Language::factory()->active()->create();

# Inactive language
$language = Language::factory()->inactive()->create();

# Default language
$language = Language::factory()->default()->create();

# Custom attributes
$language = Language::factory()->create([
    'code' => 'en',
    'name' => 'English',
    'is_default' => true,
]);
```

## Cache Keys

| Key | TTL | Purpose |
|-----|-----|---------|
| `languages.active` | 15 min | Active languages list |
| `languages.default` | 15 min | Default language |

## Security Checks

✅ Mass assignment protection  
✅ Boolean type casting  
✅ SQL injection prevention  
✅ Code normalization  

## Performance Optimizations

✅ Query result caching (15 min TTL)  
✅ Automatic cache invalidation  
✅ Single query for active languages  
✅ Ordered results by display_order  

## Common Assertions

```php
# Type assertions
$this->assertIsBool($language->is_active);
$this->assertIsInt($language->display_order);

# Collection assertions
$this->assertInstanceOf(Collection::class, $languages);
$this->assertCount(3, $languages);

# Value assertions
$this->assertEquals('en', $language->code);
$this->assertTrue($language->is_default);
$this->assertNotNull($language->name);
```

## Related Documentation

- [Full Test Documentation](LANGUAGE_MODEL_TEST_DOCUMENTATION.md)
- [Language Model](../models/LANGUAGE_MODEL.md)
- [Language Observer](../observers/LANGUAGE_OBSERVER.md)
- [Language Factory](../factories/LANGUAGE_FACTORY.md)
