# Test Generation Usage Guide

## Quick Start

### 1. Generate Tests for a Model

```bash
php scripts/generate-all-tests.php --type=model --class="App\Models\Property"
```

This will create `tests/Unit/PropertyTest.php` with:
- Factory creation tests
- Relationship tests
- Scope tests (TenantScope, HierarchicalScope)
- Attribute casting tests
- Soft delete tests

### 2. Generate Tests for a Controller

```bash
php scripts/generate-all-tests.php --type=controller --class="App\Http\Controllers\PropertyController"
```

This will create `tests/Feature/PropertyControllerTest.php` with:
- CRUD operation tests
- Tenant isolation tests
- Authorization tests
- Validation tests
- Cross-tenant access prevention tests

### 3. Generate Tests for a Filament Resource

```bash
php scripts/generate-all-tests.php --type=filament --class="App\Filament\Resources\PropertyResource"
```

This will create `tests/Feature/Filament/PropertyResourceTest.php` with:
- List page tests
- Create page tests
- Edit page tests
- Table operation tests (search, sort, filter)
- Form operation tests
- Bulk action tests
- Navigation visibility tests

## Advanced Usage

### Using Test Helper Traits

#### InteractsWithTenancy

```php
use Tests\Concerns\InteractsWithTenancy;

uses(InteractsWithTenancy::class);

it('enforces tenant isolation', function () {
    $this->assertTenantIsolation(Property::class);
});

it('prevents cross-tenant access', function () {
    $record = $this->createTenantRecord(Property::class);
    
    $this->assertCrossTenantAccessPrevented(Property::class, $record);
});
```

#### InteractsWithFilamentResources

```php
use Tests\Concerns\InteractsWithFilamentResources;

uses(InteractsWithFilamentResources::class);

it('shows tenant isolation in table', function () {
    $this->assertTenantIsolationInTable(PropertyResource::class);
});

it('automatically sets tenant_id on create', function () {
    $data = Property::factory()->make()->toArray();
    
    $this->assertTenantIdAutoSet(PropertyResource::class, $data);
});
```

### Customizing Generated Tests

#### 1. Modify Test Stubs

Edit the stub files in `tests/stubs/` to customize the generated test structure:

```php
// tests/stubs/model.test.stub

// Add custom test cases
it('has custom business logic', function () {
    ${{ resourceName }} = {{ modelName }}::factory()->create();
    
    // Your custom assertions
});
```

#### 2. Extend Test Generation Service

```php
namespace App\Services\Testing;

class CustomTestGenerationService extends TestGenerationService
{
    protected function addModelRequirements(array &$requirements, array $analysis): void
    {
        parent::addModelRequirements($requirements, $analysis);
        
        // Add custom requirements
        $requirements['tests'][] = 'custom_business_logic';
    }
}
```

#### 3. Add Custom Test Helpers

```php
namespace Tests\Concerns;

trait CustomTestHelpers
{
    protected function assertBillingCalculation(Invoice $invoice): void
    {
        // Custom billing assertions
    }
    
    protected function assertGyvatukasCalculation(Property $property): void
    {
        // Custom gyvatukas assertions
    }
}
```

## Test Organization

### Directory Structure

```
tests/
├── Feature/
│   ├── Controllers/
│   │   ├── PropertyControllerTest.php
│   │   ├── BuildingControllerTest.php
│   │   └── MeterControllerTest.php
│   ├── Filament/
│   │   ├── PropertyResourceTest.php
│   │   ├── BuildingResourceTest.php
│   │   └── MeterResourceTest.php
│   └── Services/
│       ├── BillingServiceTest.php
│       └── TariffResolverTest.php
├── Unit/
│   ├── Models/
│   │   ├── PropertyTest.php
│   │   ├── BuildingTest.php
│   │   └── MeterTest.php
│   └── ValueObjects/
│       ├── InvoiceItemDataTest.php
│       └── BillingPeriodTest.php
├── Performance/
│   └── BillingPerformanceTest.php
├── Security/
│   └── TenantIsolationTest.php
└── Concerns/
    ├── InteractsWithTenancy.php
    └── InteractsWithFilamentResources.php
```

### Naming Conventions

- **Test Files**: `{ClassName}Test.php`
- **Test Methods**: `test_{action}_{scenario}` or `it {describes behavior}`
- **Factory Methods**: `create{ModelName}`, `make{ModelName}`
- **Helper Methods**: `assert{Behavior}`, `setup{Context}`

## Running Generated Tests

### Run All Tests

```bash
php artisan test
```

### Run Specific Test Suite

```bash
# Feature tests
php artisan test --testsuite=Feature

# Unit tests
php artisan test --testsuite=Unit

# Performance tests
php artisan test --testsuite=Performance

# Security tests
php artisan test --testsuite=Security
```

### Run Tests for Specific Component

```bash
# Run all Property tests
php artisan test --filter=Property

# Run specific test
php artisan test --filter=PropertyTest::test_enforces_tenant_isolation
```

### Run Tests in Parallel

```bash
php artisan test --parallel --processes=4
```

### Generate Coverage Report

```bash
php artisan test --coverage --min=80
```

## Best Practices

### 1. Review Generated Tests

Always review generated tests before committing:

```bash
# Generate tests
php scripts/generate-all-tests.php --type=model --class="App\Models\Property"

# Review the generated file
cat tests/Unit/PropertyTest.php

# Run the tests
php artisan test --filter=PropertyTest

# Enhance with custom scenarios
vim tests/Unit/PropertyTest.php
```

### 2. Add Project-Specific Scenarios

```php
// After generation, add custom tests

it('calculates billing correctly for multi-zone tariffs', function () {
    $property = Property::factory()->create();
    $meter = Meter::factory()->for($property)->create();
    
    // Custom billing logic tests
});

it('handles gyvatukas calculation for summer period', function () {
    $property = Property::factory()->create();
    
    // Custom gyvatukas tests
});
```

### 3. Maintain Test Quality

```bash
# Run code style checks on tests
./vendor/bin/pint tests/

# Run static analysis on tests
./vendor/bin/phpstan analyse tests/

# Check test coverage
php artisan test --coverage
```

### 4. Keep Tests Fast

```php
// Use transactions for faster tests
uses(RefreshDatabase::class);

// Avoid unnecessary database operations
it('validates input without database', function () {
    $validator = Validator::make([], ['name' => 'required']);
    
    expect($validator->fails())->toBeTrue();
});

// Use in-memory SQLite for faster tests
// phpunit.xml: <env name="DB_DATABASE" value=":memory:"/>
```

### 5. Test Tenant Isolation Consistently

```php
// Always test tenant isolation for multi-tenant models
it('enforces tenant isolation', function () {
    $this->assertTenantIsolation(Property::class);
});

// Always test cross-tenant access prevention
it('prevents cross-tenant access', function () {
    $record = $this->createTenantRecord(Property::class);
    
    $this->assertCrossTenantAccessPrevented(Property::class, $record);
});
```

## Troubleshooting

### Issue: Generated Tests Fail

**Solution**: Check that:
1. Factories exist for all models
2. Database migrations are up to date
3. Test database is properly configured
4. Required relationships are defined

```bash
# Run migrations
php artisan migrate --env=testing

# Verify factories
php artisan tinker
>>> App\Models\Property::factory()->make()

# Check test configuration
cat phpunit.xml
```

### Issue: Tenant Isolation Tests Fail

**Solution**: Verify:
1. Model uses `BelongsToTenant` trait
2. `TenantScope` is registered
3. `TenantContext` is properly set in tests

```php
// Verify trait usage
$traits = class_uses_recursive(Property::class);
expect($traits)->toContain(App\Traits\BelongsToTenant::class);

// Verify scope registration
$scopes = Property::getGlobalScopes();
expect($scopes)->toHaveKey(App\Scopes\TenantScope::class);
```

### Issue: Filament Tests Fail

**Solution**: Check:
1. Filament resources are properly registered
2. Livewire is configured correctly
3. User has appropriate permissions

```bash
# Verify Filament installation
php artisan filament:check

# Check resource registration
php artisan route:list | grep filament
```

## Integration with CI/CD

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v2
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3
          
      - name: Install Dependencies
        run: composer install
        
      - name: Run Tests
        run: php artisan test --parallel
        
      - name: Generate Coverage
        run: php artisan test --coverage --min=80
```

### GitLab CI Example

```yaml
test:
  image: php:8.3
  script:
    - composer install
    - php artisan test --parallel
    - php artisan test --coverage --min=80
  coverage: '/^\s*Lines:\s*\d+.\d+\%/'
```

## Next Steps

1. Generate tests for all core models
2. Generate tests for critical services
3. Generate tests for Filament resources
4. Review and enhance generated tests
5. Integrate with CI/CD pipeline
6. Establish coverage baselines
7. Document custom test patterns

## Resources

- [Pest Documentation](https://pestphp.com)
- [Filament Testing Documentation](https://filamentphp.com/docs/testing)
- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [Project Testing Guide](README.md)
- [Test Generation Architecture](TEST_GENERATION_ARCHITECTURE.md)
