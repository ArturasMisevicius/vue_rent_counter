# Generate Tests Easy Integration Guide

## Overview

This document outlines the complete integration of `gsferro/generate-tests-easy` package for automated test generation in the Vilnius Utilities Billing Platform.

## Package Information

- **Package**: gsferro/generate-tests-easy
- **Version**: ^1.0
- **Purpose**: Automated test generation for Laravel applications
- **Documentation**: https://github.com/gsferro/generate-tests-easy

## Installation

The package has been installed via Composer:

```bash
composer require gsferro/generate-tests-easy --dev
```

## Configuration

### Publishing Configuration

```bash
php artisan vendor:publish --provider="Gsferro\GenerateTestsEasy\GenerateTestsEasyServiceProvider"
```

This publishes:
- Configuration file: `config/generate-tests-easy.php`
- Stub templates for customization

### Configuration Options

The package configuration allows customization of:

1. **Test Generation Paths**
   - Feature tests directory
   - Unit tests directory
   - Custom test namespaces

2. **Test Templates**
   - Controller test templates
   - Model test templates
   - Service test templates
   - Repository test templates

3. **Naming Conventions**
   - Test class naming patterns
   - Test method naming patterns
   - File naming conventions

4. **Coverage Options**
   - Methods to include/exclude
   - Automatic assertion generation
   - Mock generation

## Usage

### Basic Commands

#### Generate Tests for Controllers

```bash
# Generate tests for a specific controller
php artisan generate:test App\\Http\\Controllers\\PropertyController

# Generate tests for all controllers
php artisan generate:test --type=controller --all

# Generate tests for Filament resources
php artisan generate:test App\\Filament\\Resources\\PropertyResource
```

### Using Test Generation Service

```php
use App\Services\Testing\TestGenerationService;

$service = app(TestGenerationService::class);

// Generate tests for a model
$result = $service->generateTests(App\Models\Property::class, 'model');

// Generate tests for a controller
$result = $service->generateTests(App\Http\Controllers\PropertyController::class, 'controller');

// Generate tests for a Filament resource
$result = $service->generateTests(App\Filament\Resources\PropertyResource::class, 'filament');
```

#### Generate Tests for Models

```bash
# Generate tests for a specific model
php artisan generate:test App\\Models\\Property

# Generate tests for all models
php artisan generate:test --type=model --all
```

#### Generate Tests for Services

```bash
# Generate tests for billing service
php artisan generate:test App\\Services\\BillingService

# Generate tests for all services
php artisan generate:test --type=service --all
```

## Integration with Project Structure

### Test Organization

Tests will be generated following the project structure:

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
│       ├── TariffResolverTest.php
│       └── GyvatukasCalculatorTest.php
├── Unit/
│   ├── Models/
│   │   ├── PropertyTest.php
│   │   ├── BuildingTest.php
│   │   └── MeterTest.php
│   ├── Services/
│   │   └── SubscriptionServiceTest.php
│   └── ValueObjects/
│       ├── InvoiceItemDataTest.php
│       └── BillingPeriodTest.php
└── Performance/
    └── BillingPerformanceTest.php
```

### Multi-Tenancy Integration

Generated tests will automatically include:

1. **Tenant Context Setup**
   ```php
   use Tests\TestCase;
   use App\Services\TenantContext;
   
   class PropertyControllerTest extends TestCase
   {
       protected function setUp(): void
       {
           parent::setUp();
           $this->actingAsAdmin(); // From TestCase helper
       }
   }
   ```

2. **Tenant Isolation Assertions**
   - Verify tenant_id scoping
   - Test cross-tenant access prevention
   - Validate BelongsToTenant trait behavior

3. **Policy Integration**
   - Test authorization for each action
   - Verify role-based access control
   - Test superadmin/admin/manager/tenant permissions

## Customization

### Custom Test Templates

Create custom templates in `tests/stubs/`:

```php
// tests/stubs/controller.test.stub
<?php

namespace {{ namespace }};

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class {{ class }} extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    /** @test */
    public function it_can_list_{{ resource }}()
    {
        // Generated test code
    }
}
```

### Configuration File

Edit `config/generate-tests-easy.php`:

```php
<?php

return [
    'paths' => [
        'feature' => 'tests/Feature',
        'unit' => 'tests/Unit',
    ],
    
    'namespaces' => [
        'feature' => 'Tests\\Feature',
        'unit' => 'Tests\\Unit',
    ],
    
    'templates' => [
        'controller' => 'tests/stubs/controller.test.stub',
        'model' => 'tests/stubs/model.test.stub',
        'service' => 'tests/stubs/service.test.stub',
    ],
    
    'multi_tenancy' => [
        'enabled' => true,
        'tenant_trait' => 'App\\Traits\\BelongsToTenant',
        'tenant_context' => 'App\\Services\\TenantContext',
    ],
    
    'pest' => [
        'enabled' => true,
        'version' => 3,
    ],
];
```

## Generation Workflow

### Step 1: Analyze Codebase

```bash
# Scan all controllers
php artisan generate:test --scan --type=controller

# Scan all models
php artisan generate:test --scan --type=model

# Scan all services
php artisan generate:test --scan --type=service
```

### Step 2: Generate Tests

```bash
# Generate all tests
php artisan generate:test --all

# Generate with coverage report
php artisan generate:test --all --coverage
```

### Step 3: Review and Customize

1. Review generated tests in `tests/` directories
2. Add custom assertions
3. Enhance test scenarios
4. Add edge cases

### Step 4: Run Tests

```bash
# Run all generated tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

## Best Practices

### 1. Incremental Generation

Generate tests incrementally by component:

```bash
# Start with models
php artisan generate:test --type=model --all

# Then controllers
php artisan generate:test --type=controller --all

# Finally services
php artisan generate:test --type=service --all
```

### 2. Review Before Commit

Always review generated tests before committing:

1. Check test logic
2. Verify assertions
3. Add missing scenarios
4. Ensure tenant isolation

### 3. Enhance Generated Tests

Add project-specific scenarios:

```php
/** @test */
public function it_enforces_tenant_isolation()
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

### 4. Maintain Test Quality

Follow quality guidelines from `quality.md`:

- Run `./vendor/bin/pint --test` on generated tests
- Run `./vendor/bin/phpstan analyse` on test files
- Ensure tests follow project conventions
- Add property-based tests where appropriate

## Integration with Existing Tests

### Merge Strategy

1. **Preserve Existing Tests**
   - Keep all manually written tests
   - Don't overwrite custom test logic
   - Use `--no-overwrite` flag

2. **Complement Existing Coverage**
   - Generate tests for uncovered code
   - Fill gaps in test coverage
   - Add missing scenarios

3. **Enhance Test Suites**
   - Add generated tests to existing suites
   - Maintain test organization
   - Follow naming conventions

### Example Merge

```bash
# Generate only for classes without tests
php artisan generate:test --missing-only

# Generate with custom prefix
php artisan generate:test --prefix=Generated
```

## Continuous Integration

### CI/CD Integration

Add to `.github/workflows/tests.yml`:

```yaml
- name: Generate Missing Tests
  run: php artisan generate:test --missing-only --ci

- name: Run All Tests
  run: php artisan test --parallel
```

### Pre-commit Hook

Add to `.git/hooks/pre-commit`:

```bash
#!/bin/bash

# Generate tests for modified files
php artisan generate:test --modified

# Run tests
php artisan test --filter=Generated
```

## Troubleshooting

### Common Issues

1. **Namespace Conflicts**
   - Solution: Configure custom namespaces in config

2. **Template Errors**
   - Solution: Customize templates in `tests/stubs/`

3. **Multi-Tenancy Issues**
   - Solution: Ensure TenantContext is properly configured

4. **Pest Compatibility**
   - Solution: Enable Pest mode in configuration

### Debug Mode

```bash
# Run with verbose output
php artisan generate:test --verbose --debug

# Dry run (preview without generating)
php artisan generate:test --dry-run
```

## Maintenance

### Regular Updates

1. **Weekly**: Generate tests for new code
2. **Monthly**: Review and enhance generated tests
3. **Quarterly**: Update templates and configuration

### Quality Checks

```bash
# Check test coverage
php artisan test --coverage --min=80

# Run quality gates
./vendor/bin/pint --test
./vendor/bin/phpstan analyse tests/
```

## Resources

- Package Repository: https://github.com/gsferro/generate-tests-easy
- Laravel Testing Docs: https://laravel.com/docs/testing
- Pest Documentation: https://pestphp.com
- Project Quality Guide: `docs/quality.md`
- Project Testing Guide: `docs/testing/README.md`

## Next Steps

1. Configure package settings
2. Generate initial test suite
3. Review and enhance tests
4. Integrate with CI/CD
5. Document custom patterns
6. Train team on usage

## Support

For issues or questions:
- Check package documentation
- Review project testing guidelines
- Consult team lead
- Open issue in project repository
