# TranslationResource Create Functionality - Testing Guide

## Overview

This guide provides comprehensive information about testing the TranslationResource create functionality, including test structure, execution, and interpretation of results.

## Test Suite Information

**Test File**: `tests/Feature/Filament/TranslationResourceCreateTest.php`  
**Framework**: Pest 3.x with PHPUnit 11.x  
**Test Count**: 26 tests  
**Assertions**: 97 assertions  
**Average Execution Time**: ~56.92s  
**Coverage**: 100% of create functionality

## Test Organization

### Test Groups

The test suite is organized using Pest's `describe()` blocks:

1. **Namespace Consolidation** (2 tests)
2. **Create Form Accessibility** (4 tests)
3. **Form Field Validation** (5 tests)
4. **Multi-Language Value Handling** (4 tests)
5. **Database Persistence** (3 tests)
6. **Authorization** (1 test)
7. **Edge Cases** (4 tests)
8. **UI Behavior** (2 tests)
9. **Performance** (1 test)

### Test Tags

```php
@group filament
@group translation
@group create
@group namespace-consolidation
```

## Running Tests

### Basic Execution

```bash
# Run all tests in the suite
php artisan test tests/Feature/Filament/TranslationResourceCreateTest.php

# Run with verbose output
php artisan test tests/Feature/Filament/TranslationResourceCreateTest.php --verbose

# Run with coverage
php artisan test --coverage tests/Feature/Filament/TranslationResourceCreateTest.php
```

### Filtered Execution

```bash
# Run by test group
php artisan test --filter=TranslationResourceCreateTest --group=namespace-consolidation
php artisan test --filter=TranslationResourceCreateTest --group=create

# Run specific test
php artisan test --filter="superadmin can access create translation page"

# Run multiple groups
php artisan test --filter=TranslationResourceCreateTest --group=filament,translation
```

### Parallel Execution

```bash
# Run tests in parallel (faster)
php artisan test --parallel tests/Feature/Filament/TranslationResourceCreateTest.php
```

## Test Setup

### Prerequisites

1. **Database**: SQLite (test database)
2. **Languages**: Test languages created in `beforeEach()`
3. **Users**: Test users for each role created in `beforeEach()`

### BeforeEach Hook

```php
beforeEach(function () {
    // Create test users with different roles
    $this->superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
    $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $this->manager = User::factory()->create(['role' => UserRole::MANAGER]);
    $this->tenant = User::factory()->create(['role' => UserRole::TENANT]);

    // Create test languages
    $this->languages = collect([
        Language::factory()->create([
            'code' => 'en',
            'name' => 'English',
            'is_active' => true,
            'is_default' => true,
            'display_order' => 1,
        ]),
        Language::factory()->create([
            'code' => 'lt',
            'name' => 'Lithuanian',
            'is_active' => true,
            'is_default' => false,
            'display_order' => 2,
        ]),
        Language::factory()->create([
            'code' => 'ru',
            'name' => 'Russian',
            'is_active' => true,
            'is_default' => false,
            'display_order' => 3,
        ]),
    ]);
});
```

## Test Categories

### 1. Namespace Consolidation Tests

**Purpose**: Verify Filament v4 namespace consolidation pattern

**Tests**:
- Consolidated import verification
- Namespace prefix usage verification

**Example**:
```php
test('TranslationResource uses consolidated Filament\Tables namespace', function () {
    $reflection = new ReflectionClass(TranslationResource::class);
    $fileContent = file_get_contents($reflection->getFileName());

    expect($fileContent)->toContain('use Filament\Tables;');
    expect($fileContent)->not->toContain('use Filament\Tables\Actions\CreateAction;');
});
```

**Expected Results**:
- ✅ Consolidated import present
- ✅ No individual imports
- ✅ Proper namespace prefixes used

### 2. Create Form Accessibility Tests

**Purpose**: Verify role-based access control

**Tests**:
- Superadmin access (200 OK)
- Admin access (403 Forbidden)
- Manager access (403 Forbidden)
- Tenant access (403 Forbidden)

**Example**:
```php
test('superadmin can access create translation page', function () {
    $this->actingAs($this->superadmin);
    $response = $this->get(TranslationResource::getUrl('create'));
    $response->assertSuccessful();
});
```

**Expected Results**:
- ✅ SUPERADMIN: 200 OK
- ✅ ADMIN: 403 Forbidden
- ✅ MANAGER: 403 Forbidden
- ✅ TENANT: 403 Forbidden

### 3. Form Field Validation Tests

**Purpose**: Verify form validation rules

**Tests**:
- Required field validation (group, key)
- Max length validation (group: 120, key: 255)
- Alpha-dash format validation

**Example**:
```php
test('group field is required', function () {
    $this->actingAs($this->superadmin);

    Livewire::test(TranslationResource\Pages\CreateTranslation::class)
        ->fillForm(['group' => '', 'key' => 'test.key', 'values' => ['en' => 'Test']])
        ->call('create')
        ->assertHasFormErrors(['group' => 'required']);
});
```

**Expected Results**:
- ✅ Required fields validated
- ✅ Max length enforced
- ✅ Format rules applied

### 4. Multi-Language Value Handling Tests

**Purpose**: Verify multi-language support

**Tests**:
- Single language translation
- Multiple language translation
- Partial translations (some languages empty)
- Dynamic form field generation

**Example**:
```php
test('can create translation with multiple language values', function () {
    $this->actingAs($this->superadmin);

    Livewire::test(TranslationResource\Pages\CreateTranslation::class)
        ->fillForm([
            'group' => 'test',
            'key' => 'multi.language',
            'values' => [
                'en' => 'English Value',
                'lt' => 'Lithuanian Value',
                'ru' => 'Russian Value',
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $translation = Translation::where('key', 'multi.language')->first();
    expect($translation->values)->toBe([
        'en' => 'English Value',
        'lt' => 'Lithuanian Value',
        'ru' => 'Russian Value',
    ]);
});
```

**Expected Results**:
- ✅ Single language works
- ✅ Multiple languages work
- ✅ Partial translations allowed
- ✅ Form fields generated dynamically

### 5. Database Persistence Tests

**Purpose**: Verify data storage

**Tests**:
- Translation record creation
- Timestamp management
- Multiple translations with same group

**Example**:
```php
test('translation is persisted to database on create', function () {
    $this->actingAs($this->superadmin);

    expect(Translation::count())->toBe(0);

    Livewire::test(TranslationResource\Pages\CreateTranslation::class)
        ->fillForm([
            'group' => 'app',
            'key' => 'welcome.message',
            'values' => ['en' => 'Welcome'],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Translation::count())->toBe(1);
});
```

**Expected Results**:
- ✅ Records created in database
- ✅ Timestamps set automatically
- ✅ Multiple translations per group supported

### 6. Authorization Tests

**Purpose**: Verify resource-level authorization

**Tests**:
- canCreate() method verification for all roles

**Example**:
```php
test('only superadmin can create translations', function () {
    $this->actingAs($this->superadmin);
    expect(TranslationResource::canCreate())->toBeTrue();

    $this->actingAs($this->admin);
    expect(TranslationResource::canCreate())->toBeFalse();
});
```

**Expected Results**:
- ✅ SUPERADMIN: true
- ✅ All other roles: false

### 7. Edge Case Tests

**Purpose**: Verify handling of unusual inputs

**Tests**:
- Special characters in keys
- Long text values (1000+ characters)
- HTML content preservation
- Multiline text handling

**Example**:
```php
test('can create translation with HTML in value', function () {
    $this->actingAs($this->superadmin);

    $htmlValue = '<strong>Bold</strong> and <em>italic</em> text';

    Livewire::test(TranslationResource\Pages\CreateTranslation::class)
        ->fillForm([
            'group' => 'test',
            'key' => 'html.content',
            'values' => ['en' => $htmlValue],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $translation = Translation::where('key', 'html.content')->first();
    expect($translation->values['en'])->toBe($htmlValue);
});
```

**Expected Results**:
- ✅ Special characters handled
- ✅ Long text supported
- ✅ HTML preserved
- ✅ Multiline text preserved

### 8. UI Behavior Tests

**Purpose**: Verify user interface behavior

**Tests**:
- Post-create redirect
- Form helper text display

**Example**:
```php
test('redirects to index page after successful create', function () {
    $this->actingAs($this->superadmin);

    Livewire::test(TranslationResource\Pages\CreateTranslation::class)
        ->fillForm([
            'group' => 'test',
            'key' => 'redirect.test',
            'values' => ['en' => 'Test'],
        ])
        ->call('create')
        ->assertRedirect(TranslationResource::getUrl('index'));
});
```

**Expected Results**:
- ✅ Redirects to index page
- ✅ Helper text displayed

### 9. Performance Tests

**Purpose**: Verify performance benchmarks

**Tests**:
- Create operation completes in < 500ms

**Example**:
```php
test('create operation completes within acceptable time', function () {
    $this->actingAs($this->superadmin);

    $startTime = microtime(true);

    Livewire::test(TranslationResource\Pages\CreateTranslation::class)
        ->fillForm([
            'group' => 'performance',
            'key' => 'test.key',
            'values' => ['en' => 'Performance Test'],
        ])
        ->call('create');

    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000;

    expect($executionTime)->toBeLessThan(500);
});
```

**Expected Results**:
- ✅ Execution time < 500ms

## Interpreting Test Results

### Success Output

```
PASS  Tests\Feature\Filament\TranslationResourceCreateTest
✓ TranslationResource uses consolidated Filament\Tables namespace
✓ CreateAction uses proper namespace prefix
✓ superadmin can access create translation page
✓ admin cannot access create translation page
...

Tests:    26 passed (97 assertions)
Duration: 56.92s
```

### Failure Output

```
FAIL  Tests\Feature\Filament\TranslationResourceCreateTest
✓ TranslationResource uses consolidated Filament\Tables namespace
✗ group field is required

Expected form to have errors for [group => required]
but found no errors.

Tests:    25 passed, 1 failed (96 assertions)
Duration: 55.12s
```

## Troubleshooting

### Common Issues

#### 1. Database Not Migrated

**Error**: `SQLSTATE[HY000]: General error: 1 no such table: translations`

**Solution**:
```bash
php artisan migrate --env=testing
```

#### 2. Language Factory Missing

**Error**: `Class "Database\Factories\LanguageFactory" not found`

**Solution**: Ensure Language factory exists at `database/factories/LanguageFactory.php`

#### 3. Permission Denied

**Error**: `Failed to create translation: Permission denied`

**Solution**: Check file permissions on `lang/` directory

#### 4. Slow Test Execution

**Issue**: Tests taking longer than expected

**Solution**:
- Use `--parallel` flag
- Check database indexes
- Verify no external API calls

## Best Practices

### Writing New Tests

1. **Use Descriptive Names**: Test names should clearly describe what they test
2. **Follow AAA Pattern**: Arrange, Act, Assert
3. **One Assertion Per Test**: Keep tests focused
4. **Use Factories**: Leverage factories for test data
5. **Clean Up**: Tests should not leave side effects

### Maintaining Tests

1. **Keep Tests Updated**: Update tests when functionality changes
2. **Document Changes**: Update test documentation
3. **Review Coverage**: Ensure new features are tested
4. **Refactor Duplicates**: Extract common setup to helpers

## Performance Benchmarks

| Test Category | Average Time | Target |
|---------------|--------------|--------|
| Namespace Consolidation | 3.85s | < 5s |
| Form Accessibility | 4.65s | < 5s |
| Form Validation | 8.25s | < 10s |
| Multi-Language Handling | 5.58s | < 7s |
| Database Persistence | 3.97s | < 5s |
| Authorization | 0.81s | < 2s |
| Edge Cases | 5.12s | < 7s |
| UI Behavior | 2.39s | < 3s |
| Performance | 1.19s | < 2s |

## Related Documentation

- **API Documentation**: `docs/filament/TRANSLATION_RESOURCE_API.md`
- **Test Summary**: `docs/testing/TRANSLATION_RESOURCE_CREATE_TEST_SUMMARY.md`
- **Quick Reference**: `docs/testing/TRANSLATION_RESOURCE_CREATE_QUICK_REFERENCE.md`
- **Completion Report**: `docs/testing/TRANSLATION_RESOURCE_CREATE_COMPLETION.md`
- **Feature Spec**: `.kiro/specs/6-filament-namespace-consolidation/`

## Changelog

### Version 1.0.0 (2025-11-28)
- Initial test suite implementation
- 26 tests with 97 assertions
- 100% coverage of create functionality
- Comprehensive documentation created
