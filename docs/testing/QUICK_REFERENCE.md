# Test Generation Quick Reference

## Common Commands

### Generate All Tests

```bash
# Generate all tests (dry run first)
php scripts/generate-all-tests.php --dry-run

# Generate all tests (actual)
php scripts/generate-all-tests.php --verbose

# Generate only missing tests
php artisan generate:test --missing-only
```

### Generate by Type

```bash
# Models
php artisan generate:test --type=model --all

# Controllers
php artisan generate:test --type=controller --all

# Services
php artisan generate:test --type=service --all

# Filament Resources
php artisan generate:test --type=filament --all

# Policies
php artisan generate:test --type=policy --all
```

### Generate Specific Class

```bash
# Model
php artisan generate:test App\\Models\\Property --type=model

# Controller
php artisan generate:test App\\Http\\Controllers\\Manager\\PropertyController --type=controller

# Service
php artisan generate:test App\\Services\\BillingService --type=service

# Filament Resource
php artisan generate:test App\\Filament\\Resources\\PropertyResource --type=filament
```

## Run Tests

```bash
# All tests
php artisan test

# Specific suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Specific test file
php artisan test --filter=PropertyTest

# With coverage
php artisan test --coverage --min=80

# Parallel execution
php artisan test --parallel
```

## Quality Checks

```bash
# Format code
./vendor/bin/pint tests/

# Static analysis
./vendor/bin/phpstan analyse tests/

# All quality checks
./vendor/bin/pint --test && ./vendor/bin/phpstan analyse tests/ && php artisan test
```

## Test Helpers

### Authentication

```php
// Authenticate with tenant context (see TESTCASE_API_REFERENCE.md for details)
$admin = $this->actingAsAdmin(1);           // Admin for tenant 1
$manager = $this->actingAsManager(2);       // Manager for tenant 2
$tenant = $this->actingAsTenant(1);         // Tenant user for tenant 1
$superadmin = $this->actingAsSuperadmin();  // Superadmin (no tenant context)

// With custom attributes
$admin = $this->actingAsAdmin(1, ['name' => 'John Admin']);
```

### Data Creation

```php
// Create test data with automatic tenant context
$property = $this->createTestProperty(1);
$building = $this->createTestBuilding(1);
$meter = $this->createTestMeter($property->id, MeterType::ELECTRICITY);
$reading = $this->createTestMeterReading($meter->id, 100.0);
$invoice = $this->createTestInvoice($property->id);

// Flexible property creation
$property = $this->createTestProperty(1, ['area_sqm' => 75.0]);
$property = $this->createTestProperty(['tenant_id' => 1, 'area_sqm' => 75.0]);
```

### Tenant Context

```php
// Execute within specific tenant context
$result = $this->withinTenant(2, function () {
    return Property::count();
});

// Ensure organization exists
$organization = $this->ensureTenantExists(5);

// Assertions
$this->assertTenantContext(1);      // Assert context is tenant 1
$this->assertNoTenantContext();     // Assert no context is set
```

**📖 Complete API Reference**: [TESTCASE_API_REFERENCE.md](TESTCASE_API_REFERENCE.md)

### Factory Usage

```php
// Create model
$property = Property::factory()->create();

// Create multiple
$properties = Property::factory()->count(5)->create();

// Make without saving
$property = Property::factory()->make();

// With specific attributes
$property = Property::factory()->create([
    'name' => 'Test Property',
    'address' => '123 Test St',
]);
```

### Assertions

```php
// Database assertions
$this->assertDatabaseHas('properties', ['name' => 'Test']);
$this->assertDatabaseMissing('properties', ['name' => 'Test']);
$this->assertSoftDeleted('properties', ['id' => 1]);

// Response assertions
$response->assertOk();
$response->assertRedirect();
$response->assertForbidden();
$response->assertSessionHasErrors(['name']);

// Model assertions
$this->assertInstanceOf(Property::class, $property);
$this->assertEquals('Test', $property->name);
$this->assertTrue($property->exists);
```

## Common Test Patterns

### Tenant Isolation Test

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
    $this->assertTrue(Model::all()->contains($item1));
    $this->assertFalse(Model::all()->contains($item2));
}
```

### Authorization Test

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

### Validation Test

```php
/** @test */
public function it_validates_required_fields(): void
{
    $response = $this->post(route('items.store'), []);

    $response->assertSessionHasErrors(['name', 'description']);
}
```

### Service Test

```php
/** @test */
public function it_performs_calculation_correctly(): void
{
    $input = ['value' => 100];
    $expected = 150;

    $result = $this->service->calculate($input);

    $this->assertEquals($expected, $result);
}
```

## File Locations

```
tests/
├── Feature/
│   ├── Controllers/      # Controller tests
│   ├── Filament/         # Filament resource tests
│   └── Services/         # Service integration tests
├── Unit/
│   ├── Models/           # Model tests
│   ├── Services/         # Service unit tests
│   ├── Policies/         # Policy tests
│   └── ValueObjects/     # Value object tests
├── Performance/          # Performance tests
├── Security/             # Security tests
└── stubs/                # Test templates
    ├── controller.test.stub
    ├── model.test.stub
    ├── service.test.stub
    └── filament.test.stub
```

## Configuration

```
config/generate-tests-easy.php  # Package configuration
tests/stubs/                    # Custom templates
scripts/generate-all-tests.php  # Generation script
```

## Troubleshooting

### Clear Cache

```bash
php artisan cache:clear
php artisan config:clear
composer dump-autoload
```

### Verbose Output

```bash
php artisan generate:test App\\Models\\Property --type=model --verbose --debug
```

### Dry Run

```bash
php scripts/generate-all-tests.php --dry-run --verbose
```

## Resources

- [Full Integration Guide](GENERATE_TESTS_EASY_INTEGRATION.md)
- [Test Generation Guide](TEST_GENERATION_GUIDE.md)
- [Project Testing Guide](README.md)
- [Quality Guidelines](../quality.md)
