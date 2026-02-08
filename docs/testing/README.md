# Testing Documentation

## Overview

This directory contains comprehensive testing documentation for the Vilnius Utilities Billing Platform, including automated test generation using `gsferro/generate-tests-easy`.

## Documentation Structure

### Core Documentation

1. **[TESTCASE_API_REFERENCE.md](TESTCASE_API_REFERENCE.md)** ⭐ NEW
   - Complete API reference for TestCase helper methods
   - Authentication, data creation, and tenant context helpers
   - Usage patterns and best practices
   - Architecture and optimization details

2. **[TESTCASE_HELPERS_GUIDE.md](TESTCASE_HELPERS_GUIDE.md)**
   - User-friendly guide to TestCase helpers
   - Common usage examples
   - Migration guide from old patterns
   - Troubleshooting tips

3. **[TESTCASE_REFACTORING_SUMMARY.md](TESTCASE_REFACTORING_SUMMARY.md)**
   - Implementation details and improvements
   - Before/after comparisons
   - Performance considerations
   - Test coverage information

4. **[GENERATE_TESTS_EASY_INTEGRATION.md](GENERATE_TESTS_EASY_INTEGRATION.md)**
   - Complete integration guide for the test generation package
   - Installation and configuration instructions
   - Usage examples and best practices
   - Troubleshooting and maintenance

5. **[TEST_GENERATION_GUIDE.md](TEST_GENERATION_GUIDE.md)**
   - Comprehensive guide for generating tests
   - Test categories and patterns
   - Customization options
   - CI/CD integration

6. **[QUICK_REFERENCE.md](QUICK_REFERENCE.md)**
   - Quick command reference
   - Common test patterns
   - Helper functions
   - File locations

## Quick Start

### 1. Install Package

The package is already installed. If you need to reinstall:

```bash
composer require gsferro/generate-tests-easy --dev
```

### 2. Configure

Configuration file is located at `config/generate-tests-easy.php`. Review and adjust settings as needed.

### 3. Generate Tests

```bash
# Dry run to preview
php scripts/generate-all-tests.php --dry-run

# Generate all tests
php scripts/generate-all-tests.php --verbose

# Generate specific component
php artisan generate:test App\\Models\\Property --type=model
```

### 4. Run Tests

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage --min=80

# Run specific suite
php artisan test --testsuite=Feature
```

## Test Organization

### Directory Structure

```
tests/
├── Feature/
│   ├── Controllers/
│   │   ├── Manager/
│   │   ├── Superadmin/
│   │   └── Tenant/
│   ├── Filament/
│   │   ├── Resources/
│   │   └── Pages/
│   ├── Services/
│   └── Http/
├── Unit/
│   ├── Models/
│   ├── Services/
│   ├── Policies/
│   ├── ValueObjects/
│   └── Helpers/
├── Performance/
│   ├── BillingPerformanceTest.php
│   └── QueryOptimizationTest.php
├── Security/
│   ├── AuthorizationTest.php
│   └── TenantIsolationTest.php
└── stubs/
    ├── controller.test.stub
    ├── model.test.stub
    ├── service.test.stub
    ├── filament.test.stub
    ├── policy.test.stub
    ├── middleware.test.stub
    ├── observer.test.stub
    └── value-object.test.stub
```

### Test Categories

#### Feature Tests

Test complete features and user workflows:

- **Controller Tests**: HTTP request/response cycles
- **Filament Tests**: Admin panel functionality
- **Service Integration Tests**: Service interactions
- **API Tests**: API endpoints and responses

#### Unit Tests

Test individual components in isolation:

- **Model Tests**: Model behavior and relationships
- **Service Tests**: Service logic and calculations
- **Policy Tests**: Authorization rules
- **Value Object Tests**: Value object behavior
- **Helper Tests**: Utility functions

#### Performance Tests

Test system performance:

- **Query Optimization**: N+1 query detection
- **Billing Performance**: Calculation efficiency
- **Response Time**: API response times
- **Memory Usage**: Memory consumption

#### Security Tests

Test security measures:

- **Authorization**: Policy enforcement
- **Tenant Isolation**: Cross-tenant access prevention
- **Input Validation**: XSS and injection prevention
- **Authentication**: Login and session security

## Test Helpers

The `Tests\TestCase` class provides comprehensive helper methods for testing. See [TESTCASE_API_REFERENCE.md](TESTCASE_API_REFERENCE.md) for complete documentation.

### Authentication Helpers

Located in `tests/TestCase.php`:

```php
// Authenticate as different user types with tenant context
$admin = $this->actingAsAdmin(1);           // Admin for tenant 1
$manager = $this->actingAsManager(2);       // Manager for tenant 2
$tenant = $this->actingAsTenant(1);         // Tenant user for tenant 1
$superadmin = $this->actingAsSuperadmin();  // Superadmin (no tenant context)
```

### Data Creation Helpers

```php
// Create test data with automatic tenant context
$property = $this->createTestProperty(1);
$building = $this->createTestBuilding(1);
$meter = $this->createTestMeter($property->id, MeterType::ELECTRICITY);
$reading = $this->createTestMeterReading($meter->id, 100.0);
$invoice = $this->createTestInvoice($property->id);
```

### Tenant Context Helpers

```php
// Execute callback within specific tenant context
$result = $this->withinTenant(2, function () {
    return Property::count();
});

// Ensure organization exists
$organization = $this->ensureTenantExists(5);
```

### Assertion Helpers

```php
// Verify tenant context
$this->assertTenantContext(1);      // Assert context is tenant 1
$this->assertNoTenantContext();     // Assert no context is set
```

**📖 For detailed documentation, see [TESTCASE_API_REFERENCE.md](TESTCASE_API_REFERENCE.md)**

## Test Patterns

### Multi-Tenancy Testing

All tests must verify tenant isolation:

```php
/** @test */
public function it_enforces_tenant_isolation(): void
{
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    TenantContext::set($tenant1);
    $item1 = Model::factory()->create();

    TenantContext::set($tenant2);
    $item2 = Model::factory()->create();

    TenantContext::set($tenant1);
    $this->assertCount(1, Model::all());
}
```

### Authorization Testing

Verify policy enforcement:

```php
/** @test */
public function it_enforces_authorization(): void
{
    $this->actingAsTenant();
    $item = Model::factory()->create();

    $response = $this->delete(route('items.destroy', $item));

    $response->assertForbidden();
}
```

### Validation Testing

Test input validation:

```php
/** @test */
public function it_validates_required_fields(): void
{
    $response = $this->post(route('items.store'), []);

    $response->assertSessionHasErrors(['name', 'description']);
}
```

## Quality Standards

### Code Quality

All tests must pass quality checks:

```bash
# Format tests
./vendor/bin/pint tests/

# Static analysis
./vendor/bin/phpstan analyse tests/

# Run tests
php artisan test
```

### Coverage Requirements

- **Minimum Coverage**: 80%
- **Critical Components**: 90%+
- **New Features**: 100%

Check coverage:

```bash
php artisan test --coverage --min=80
```

### Test Naming

Follow naming conventions:

- Test classes: `{ClassName}Test`
- Test methods: `test_{what_it_does}` or `it_{does_something}`
- Use descriptive names that explain the test purpose

## Continuous Integration

### GitHub Actions

Tests run automatically on:
- Push to main branch
- Pull requests
- Scheduled daily runs

Configuration: `.github/workflows/tests.yml`

### Pre-commit Hooks

Set up pre-commit hooks:

```bash
# Create hook
cat > .git/hooks/pre-commit << 'EOF'
#!/bin/bash
php artisan test --filter=Modified
EOF

# Make executable
chmod +x .git/hooks/pre-commit
```

## Maintenance

### Weekly Tasks

```bash
# Generate tests for new code
php scripts/generate-all-tests.php --missing-only

# Run full test suite
php artisan test

# Check coverage
php artisan test --coverage
```

### Monthly Tasks

- Review and enhance generated tests
- Update test templates
- Update documentation
- Review test coverage reports

### Quarterly Tasks

- Full test suite review
- Update test generation package
- Regenerate tests with new templates
- Update testing guidelines

## Troubleshooting

### Common Issues

1. **Tests Not Generating**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   composer dump-autoload
   ```

2. **Namespace Conflicts**
   - Check `config/generate-tests-easy.php`
   - Verify namespace configuration

3. **Template Errors**
   - Review templates in `tests/stubs/`
   - Check placeholder syntax

4. **Multi-Tenancy Issues**
   - Verify TenantContext configuration
   - Check BelongsToTenant trait

### Debug Mode

Run with verbose output:

```bash
php artisan generate:test App\\Models\\Property --type=model --verbose --debug
```

## Resources

### Internal Documentation

- [Integration Guide](GENERATE_TESTS_EASY_INTEGRATION.md)
- [Generation Guide](TEST_GENERATION_GUIDE.md)
- [Quick Reference](QUICK_REFERENCE.md)
- [Quality Guidelines](../quality.md)

### External Resources

- [Laravel Testing Docs](https://laravel.com/docs/testing)
- [Pest Documentation](https://pestphp.com)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Package Repository](https://github.com/gsferro/generate-tests-easy)

## Support

For issues or questions:

1. Check this documentation
2. Review package documentation
3. Check project guidelines
4. Consult team lead
5. Open issue in project repository

## Contributing

When adding new test patterns:

1. Document the pattern in this guide
2. Add example to Quick Reference
3. Update test templates if needed
4. Update configuration if required
5. Submit PR with documentation updates

## Version History

- **v1.0.0** (2024-01-29): Initial integration
  - Package installation
  - Configuration setup
  - Custom templates
  - Documentation creation
  - Generation scripts

## License

This documentation is part of the Vilnius Utilities Billing Platform project.
