# Test Generation Guide

## Overview

This guide provides comprehensive instructions for generating tests using the `gsferro/generate-tests-easy` package integrated into the Vilnius Utilities Billing Platform.

## Quick Start

### 1. Generate All Tests

```bash
# Run the comprehensive test generation script
php scripts/generate-all-tests.php

# Dry run to preview what will be generated
php scripts/generate-all-tests.php --dry-run

# Verbose output for detailed information
php scripts/generate-all-tests.php --verbose
```

### 2. Generate Specific Component Tests

```bash
# Generate tests for a specific model
php artisan generate:test App\\Models\\Property --type=model

# Generate tests for a specific controller
php artisan generate:test App\\Http\\Controllers\\Manager\\PropertyController --type=controller

# Generate tests for a specific service
php artisan generate:test App\\Services\\BillingService --type=service
```

### 3. Review and Enhance

After generation:
1. Review generated tests in `tests/` directory
2. Add custom test scenarios
3. Enhance assertions
4. Add edge cases

### 4. Run Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage --min=80
```

## Test Categories

### Model Tests

Generated model tests include:

- **Creation Tests**: Verify model can be created
- **Fillable Attributes**: Check fillable properties
- **Tenant Isolation**: Verify BelongsToTenant trait
- **Tenant Scope**: Test TenantScope application
- **Soft Deletes**: Test soft delete functionality
- **Relationships**: Verify model relationships
- **Casts**: Check attribute casting
- **Table Name**: Verify correct table name

**Example:**

```bash
php artisan generate:test App\\Models\\Property --type=model
```

Generated file: `tests/Unit/Models/PropertyTest.php`

### Controller Tests

Generated controller tests include:

- **Index Action**: List resources
- **Show Action**: Display single resource
- **Store Action**: Create new resource
- **Update Action**: Update existing resource
- **Destroy Action**: Delete resource
- **Tenant Isolation**: Verify cross-tenant access prevention
- **Authentication**: Require authentication
- **Authorization**: Enforce policies
- **Validation**: Test input validation

**Example:**

```bash
php artisan generate:test App\\Http\\Controllers\\Manager\\PropertyController --type=controller
```

Generated file: `tests/Feature/Controllers/Manager/PropertyControllerTest.php`

### Service Tests

Generated service tests include:

- **Instantiation**: Verify service can be created
- **Tenant Context**: Respect tenant boundaries
- **Valid Input**: Handle correct data
- **Invalid Input**: Throw exceptions for bad data
- **Data Structure**: Return expected format
- **Calculations**: Perform accurate computations
- **Edge Cases**: Handle boundary conditions
- **Database Interaction**: Correct data persistence
- **Caching**: Utilize cache when appropriate
- **Logging**: Log important operations

**Example:**

```bash
php artisan generate:test App\\Services\\BillingService --type=service
```

Generated file: `tests/Unit/Services/BillingServiceTest.php`

### Filament Resource Tests

Generated Filament tests include:

- **Resource Access**: Verify resource is accessible
- **Form Rendering**: Test form displays correctly
- **Table Rendering**: Test table displays correctly
- **Create Action**: Test resource creation
- **Edit Action**: Test resource editing
- **Delete Action**: Test resource deletion
- **Bulk Actions**: Test bulk operations
- **Filters**: Test table filters
- **Search**: Test search functionality
- **Tenant Isolation**: Verify tenant scoping

**Example:**

```bash
php artisan generate:test App\\Filament\\Resources\\PropertyResource --type=filament
```

Generated file: `tests/Feature/Filament/PropertyResourceTest.php`

### Policy Tests

Generated policy tests include:

- **View Any**: Test index permission
- **View**: Test show permission
- **Create**: Test create permission
- **Update**: Test update permission
- **Delete**: Test delete permission
- **Restore**: Test restore permission
- **Force Delete**: Test force delete permission
- **Role-Based Access**: Test different user roles
- **Tenant Isolation**: Verify tenant boundaries

**Example:**

```bash
php artisan generate:test App\\Policies\\PropertyPolicy --type=policy
```

Generated file: `tests/Unit/Policies/PropertyPolicyTest.php`

## Customization

### Custom Test Templates

Edit templates in `tests/stubs/`:

- `controller.test.stub` - Controller test template
- `model.test.stub` - Model test template
- `service.test.stub` - Service test template
- `filament.test.stub` - Filament resource test template
- `policy.test.stub` - Policy test template
- `middleware.test.stub` - Middleware test template
- `observer.test.stub` - Observer test template
- `value-object.test.stub` - Value object test template

### Configuration

Edit `config/generate-tests-easy.php` to customize:

- Test paths and namespaces
- Template locations
- Multi-tenancy settings
- Test framework (Pest/PHPUnit)
- Authentication helpers
- Assertion generation
- Mocking behavior
- Coverage requirements
- Naming conventions
- File generation behavior

## Best Practices

### 1. Incremental Generation

Generate tests incrementally by component type:

```bash
# Step 1: Models
php artisan generate:test --type=model --all

# Step 2: Controllers
php artisan generate:test --type=controller --all

# Step 3: Services
php artisan generate:test --type=service --all

# Step 4: Filament Resources
php artisan generate:test --type=filament --all
```

### 2. Review Before Commit

Always review generated tests:

```bash
# Check generated tests
git diff tests/

# Review specific test file
cat tests/Feature/Controllers/PropertyControllerTest.php
```

### 3. Enhance Generated Tests

Add project-specific scenarios:

```php
/** @test */
public function it_calculates_gyvatukas_correctly(): void
{
    // Arrange
    $property = Property::factory()->create([
        'heated_area' => 100,
    ]);
    
    $tariff = Tariff::factory()->create([
        'type' => TariffType::GYVATUKAS,
        'rate' => 0.50,
    ]);

    // Act
    $result = $this->billingService->calculateGyvatukas($property, $tariff);

    // Assert
    $this->assertEquals(50.00, $result);
}
```

### 4. Maintain Test Quality

Follow quality guidelines:

```bash
# Format tests
./vendor/bin/pint tests/

# Analyze tests
./vendor/bin/phpstan analyse tests/

# Run tests
php artisan test

# Check coverage
php artisan test --coverage --min=80
```

### 5. Document Custom Patterns

Document any custom test patterns in this guide.

## Multi-Tenancy Testing

All generated tests include multi-tenancy support:

### Tenant Context Setup

```php
protected function setUp(): void
{
    parent::setUp();
    $this->actingAsAdmin(); // Sets up tenant context
}
```

### Tenant Isolation Tests

```php
/** @test */
public function it_enforces_tenant_isolation(): void
{
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    TenantContext::set($tenant1);
    $property1 = Property::factory()->create();

    TenantContext::set($tenant2);
    $property2 = Property::factory()->create();

    TenantContext::set($tenant1);
    $this->assertCount(1, Property::all());
    $this->assertTrue(Property::all()->contains($property1));
    $this->assertFalse(Property::all()->contains($property2));
}
```

## Authorization Testing

Generated tests include authorization checks:

```php
/** @test */
public function it_enforces_authorization(): void
{
    $this->actingAsTenant();
    $property = Property::factory()->create();

    $response = $this->delete(route('properties.destroy', $property));

    $response->assertForbidden();
}
```

## Validation Testing

Generated tests include validation checks:

```php
/** @test */
public function it_validates_required_fields(): void
{
    $response = $this->post(route('properties.store'), []);

    $response->assertSessionHasErrors(['name', 'address']);
}
```

## Performance Testing

Add performance tests for critical operations:

```php
/** @test */
public function it_calculates_billing_efficiently(): void
{
    // Arrange
    $properties = Property::factory()->count(100)->create();
    
    // Act
    $startTime = microtime(true);
    foreach ($properties as $property) {
        $this->billingService->calculateBilling($property);
    }
    $endTime = microtime(true);
    
    // Assert
    $executionTime = $endTime - $startTime;
    $this->assertLessThan(5.0, $executionTime, 'Billing calculation took too long');
}
```

## Integration with CI/CD

### GitHub Actions

Add to `.github/workflows/tests.yml`:

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
        
      - name: Generate Missing Tests
        run: php scripts/generate-all-tests.php --missing-only
        
      - name: Run Tests
        run: php artisan test --parallel
        
      - name: Check Coverage
        run: php artisan test --coverage --min=80
```

### Pre-commit Hook

Add to `.git/hooks/pre-commit`:

```bash
#!/bin/bash

# Generate tests for modified files
php artisan generate:test --modified

# Run tests
php artisan test --filter=Generated

# Check if tests pass
if [ $? -ne 0 ]; then
    echo "Tests failed. Commit aborted."
    exit 1
fi
```

## Troubleshooting

### Issue: Tests Not Generating

**Solution:**

```bash
# Clear cache
php artisan cache:clear
php artisan config:clear

# Regenerate autoload
composer dump-autoload

# Try again
php artisan generate:test App\\Models\\Property --type=model --verbose
```

### Issue: Namespace Conflicts

**Solution:**

Edit `config/generate-tests-easy.php`:

```php
'namespaces' => [
    'feature' => 'Tests\\Feature',
    'unit' => 'Tests\\Unit',
],
```

### Issue: Template Errors

**Solution:**

Check template syntax in `tests/stubs/` and ensure placeholders are correct.

### Issue: Multi-Tenancy Not Working

**Solution:**

Verify configuration in `config/generate-tests-easy.php`:

```php
'multi_tenancy' => [
    'enabled' => true,
    'tenant_trait' => 'App\\Traits\\BelongsToTenant',
    'tenant_context' => 'App\\Services\\TenantContext',
],
```

## Maintenance

### Weekly Tasks

```bash
# Generate tests for new code
php scripts/generate-all-tests.php --missing-only

# Run all tests
php artisan test

# Check coverage
php artisan test --coverage
```

### Monthly Tasks

```bash
# Review and enhance generated tests
# Update templates if needed
# Update configuration
# Document new patterns
```

### Quarterly Tasks

```bash
# Full test suite review
# Update package
composer update gsferro/generate-tests-easy

# Regenerate all tests with new templates
php scripts/generate-all-tests.php --force
```

## Resources

- [Package Documentation](https://github.com/gsferro/generate-tests-easy)
- [Laravel Testing Docs](https://laravel.com/docs/testing)
- [Pest Documentation](https://pestphp.com)
- [Project Quality Guide](../quality.md)
- [Project Testing Guide](README.md)

## Support

For issues or questions:
1. Check this guide
2. Review package documentation
3. Check project testing guidelines
4. Consult team lead
5. Open issue in project repository
