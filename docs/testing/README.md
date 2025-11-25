# Testing Documentation

## Overview

This directory contains testing guides, verification scripts documentation, and testing best practices for the Vilnius Utilities Billing Platform.

## Quick Links

### Verification Scripts

- **[Batch 3 Verification Guide](BATCH_3_VERIFICATION_GUIDE.md)** - Complete guide for verifying Batch 3 Filament resources
- **[Model Verification Guide](MODEL_VERIFICATION_GUIDE.md)** - Complete guide for verifying Eloquent model configuration
- **[Verification Quick Reference](VERIFICATION_QUICK_REFERENCE.md)** - Quick command reference for verification scripts

### Testing Guides

- **[Testing Guide](../guides/TESTING_GUIDE.md)** - Comprehensive testing approach and conventions
- **[Factory and Seeding Guide](FACTORY_AND_SEEDING_GUIDE.md)** - Database seeding and factory usage

### Test Coverage Reports

#### Filament Resources
- **[Building Resource Tests](BUILDING_RESOURCE_TEST_SUMMARY.md)** - BuildingResource test coverage
- **[Properties Relation Manager Tests](PROPERTIES_RELATION_MANAGER_TESTING_SUMMARY.md)** - PropertiesRelationManager test coverage
- **[FAQ Resource Namespace Tests](FAQ_RESOURCE_NAMESPACE_TESTING.md)** - FaqResource namespace consolidation test coverage
- **[FAQ Namespace Test Implementation](FAQ_NAMESPACE_TEST_IMPLEMENTATION.md)** - FaqResource namespace test implementation details

#### Billing & Invoicing
- **[Invoice Finalization Tests](INVOICE_FINALIZATION_TEST_SUMMARY.md)** - Invoice finalization test coverage
- **[Billing Service Tests](BILLING_SERVICE_V3_TEST_COVERAGE.md)** - BillingService v3 test coverage
- **[Gyvatukas Calculator Tests](GYVATUKAS_CALCULATOR_TEST_COVERAGE.md)** - GyvatukasCalculator test coverage
- **[Meter Reading Observer Tests](METER_READING_OBSERVER_TEST_COVERAGE.md)** - MeterReadingObserver draft invoice recalculation tests

#### Controllers
- **[Meter Reading Update Controller Tests](../api/METER_READING_UPDATE_CONTROLLER_API.md#testing)** - Meter reading correction controller tests

#### View Layer
- **[Navigation Composer Tests](NAVIGATION_COMPOSER_TESTING_COMPLETE.md)** - NavigationComposer test coverage

### Testing Recommendations

- **[Testing Recommendations](TESTING_RECOMMENDATIONS.md)** - Best practices and patterns

---

## Verification Scripts

### Available Scripts

| Script | Purpose | Documentation |
|--------|---------|---------------|
| `verify-batch3-resources.php` | Verify Batch 3 Filament resources (User, Subscription, Organization, OrganizationActivityLog) | [Guide](BATCH_3_VERIFICATION_GUIDE.md) |
| `verify-batch4-resources.php` | Verify Batch 4 Filament resources (Faq, Language, Translation) | [Guide](BATCH_4_VERIFICATION_GUIDE.md) |
| `verify-models.php` | Verify Eloquent model casts and relationships (11 core models) | [Guide](MODEL_VERIFICATION_GUIDE.md) |

### Quick Commands

```bash
# Verify Batch 3 resources
php verify-batch3-resources.php

# Verify Batch 4 resources
php verify-batch4-resources.php

# Verify Eloquent models
php verify-models.php

# Run all verifications
php verify-batch3-resources.php && \
php verify-batch4-resources.php && \
php verify-models.php && \
echo "✓ All verifications passed"

# With composer
composer verify:batch3

# In CI/CD
php verify-batch3-resources.php && \
php verify-batch4-resources.php && \
php verify-models.php || exit 1
```

---

## Test Suites

### Feature Tests

```bash
# Run all feature tests
php artisan test --testsuite=Feature

# Run specific feature tests
php artisan test --filter=Filament
php artisan test --filter=Building
php artisan test --filter=Invoice
```

### Unit Tests

```bash
# Run all unit tests
php artisan test --testsuite=Unit

# Run specific unit tests
php artisan test --filter=NavigationComposer
php artisan test --filter=BillingService
```

### Property Tests

```bash
# Run all property tests
php artisan test --filter=Property

# Run specific property tests
php artisan test --filter=MultiTenancyProperty
php artisan test --filter=InvoiceProperty
```

### Performance Tests

```bash
# Run performance tests
php artisan test --testsuite=Performance

# Run specific performance tests
php artisan test --filter=BuildingResourcePerformance
```

### Security Tests

```bash
# Run security tests
php artisan test --testsuite=Security

# Run specific security tests
php artisan test --filter=Authorization
```

---

## Test Coverage

### Current Coverage

| Component | Tests | Assertions | Coverage |
|-----------|-------|------------|----------|
| BuildingResource | 37 | 150+ | 100% |
| NavigationComposer | 15 | 71 | 100% |
| Invoice Finalization | 25+ | 100+ | 95% |
| Properties Relation Manager | 20+ | 80+ | 90% |
| Middleware | 11 | 16 | 100% |
| BillingService v3 | 15 | 45 | 95% |
| GyvatukasCalculator | 43 | 109 | 100% |
| MeterReadingObserver | 6 | 15 | 100% |

### Coverage Goals

- **Unit Tests**: 90%+ coverage
- **Feature Tests**: 85%+ coverage
- **Integration Tests**: 80%+ coverage
- **Property Tests**: Key invariants covered

---

## Testing Best Practices

### 1. Test Organization

```php
// Group related tests
describe('UserResource', function () {
    describe('authorization', function () {
        test('admin can view users', function () {
            // ...
        });
    });
    
    describe('form validation', function () {
        test('email is required', function () {
            // ...
        });
    });
});
```

### 2. Test Helpers

```php
// Use TestCase helpers
$this->actingAsAdmin();
$this->actingAsManager();
$this->actingAsTenant();

// Create test data
$property = $this->createTestProperty();
$reading = $this->createTestMeterReading();
```

### 3. Assertions

```php
// Use descriptive assertions
expect($user->role)->toBe(UserRole::Admin);
expect($invoice->status)->toBe(InvoiceStatus::Finalized);

// Use custom matchers
expect($response)->toBeSuccessful();
expect($query)->toHaveCount(5);
```

### 4. Test Data

```php
// Use factories
$user = User::factory()->create();
$building = Building::factory()->create();

// Use seeders for complex scenarios
$this->seed(TestDatabaseSeeder::class);
```

---

## CI/CD Integration

### GitHub Actions

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - name: Install Dependencies
        run: composer install
      - name: Run Tests
        run: php artisan test
      - name: Verify Resources
        run: php verify-batch3-resources.php
```

### GitLab CI

```yaml
test:
  script:
    - composer install
    - php artisan test
    - php verify-batch3-resources.php
```

---

## Troubleshooting

### Common Issues

#### Tests Failing After Migration

```bash
# Refresh database
php artisan migrate:fresh --seed

# Clear caches
php artisan optimize:clear

# Run tests
php artisan test
```

#### Verification Script Errors

```bash
# Refresh autoload
composer dump-autoload

# Clear caches
php artisan optimize:clear

# Run verification
php verify-batch3-resources.php
```

#### Database Connection Errors

```bash
# Check database configuration
php artisan db:show

# Verify .env settings
cat .env | grep DB_

# Test connection
php artisan tinker
>>> DB::connection()->getPdo();
```

---

## Related Documentation

### Testing

- [Testing Guide](../guides/TESTING_GUIDE.md) - Comprehensive testing guide
- [Verification Guide](BATCH_3_VERIFICATION_GUIDE.md) - Verification script guide
- [Quick Reference](VERIFICATION_QUICK_REFERENCE.md) - Quick command reference

### API

- [Verification Scripts API](../api/VERIFICATION_SCRIPTS_API.md) - API reference

### Architecture

- [Verification Scripts Architecture](../architecture/VERIFICATION_SCRIPTS_ARCHITECTURE.md) - Architecture

### Upgrades

- [Batch 3 Verification Summary](../upgrades/BATCH_3_VERIFICATION_SUMMARY.md) - Implementation summary
- [Verification Implementation Complete](../upgrades/VERIFICATION_IMPLEMENTATION_COMPLETE.md) - Completion report

---

## Contributing

### Adding New Tests

1. Create test file in appropriate directory
2. Follow naming conventions (`*Test.php`)
3. Use descriptive test names
4. Add documentation comments
5. Update coverage reports

### Adding New Verification Scripts

1. Create script in project root
2. Follow existing patterns
3. Add comprehensive documentation
4. Update this README
5. Add CI/CD examples

---

**Last Updated**: November 24, 2025  
**Maintained By**: Development Team
