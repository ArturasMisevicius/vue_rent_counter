# Filament Admin Panel Tests

This directory contains comprehensive test suites for the Filament v4 admin panel implementation, covering resources, pages, widgets, and property-based invariants.

## Test Organization

### Resource Tests
Tests for Filament resource CRUD operations, table configurations, and form validations:

- `BuildingResourceTest.php` - Building resource functionality
- `InvoiceFinalizationActionTest.php` - Invoice finalization action
- `PropertiesRelationManagerTest.php` - Properties relation manager
- `SubscriptionResourceTest.php` - Subscription resource

### Property-Based Tests
Property tests verify invariants across different data distributions:

- `FilamentInvoiceStatusFilteringPropertyTest.php` - Invoice status filtering invariants
- `FilamentInvoiceFinalizationPropertyTest.php` - Invoice finalization immutability
- `FilamentMeterReadingMonotonicityPropertyTest.php` - Meter reading validation

### Integration Tests
Tests for cross-component functionality:

- `AdminDashboardTest.php` - Dashboard widget integration
- `AdminResourceAccessTest.php` - Resource access control
- `PlatformAnalyticsPageTest.php` - Analytics page functionality

## Running Tests

### Run All Filament Tests
```bash
php artisan test tests/Feature/Filament
```

### Run Specific Test Suite
```bash
# Invoice tests
php artisan test --filter=Invoice

# Property tests
php artisan test --filter=Property

# Resource tests
php artisan test --filter=Resource
```

### Run Individual Test File
```bash
php artisan test tests/Feature/Filament/FilamentInvoiceStatusFilteringPropertyTest.php
```

## Test Patterns

### Property-Based Testing
Property tests use randomized data to verify invariants:

```php
// Create random number of invoices per status
foreach (InvoiceStatus::cases() as $status) {
    $count = rand(2, 5);
    for ($i = 0; $i < $count; $i++) {
        Invoice::factory()->create(['status' => $status]);
    }
}

// Verify invariant holds for all data distributions
$this->assertTrue($records->every(fn($r) => $r->status === $filteredStatus));
```

### Tenant Scope Testing
All tests verify multi-tenancy isolation:

```php
// Create data in multiple tenants
$tenant1Invoices = Invoice::factory()->count(5)->create(['tenant_id' => 1]);
$tenant2Invoices = Invoice::factory()->count(5)->create(['tenant_id' => 2]);

// Verify tenant isolation
$this->actingAs($tenant1User);
$records = Livewire::test(InvoiceResource\Pages\ListInvoices::class)
    ->instance()->getTableRecords();

$this->assertTrue($records->every(fn($r) => $r->tenant_id === 1));
```

### Livewire Component Testing
Tests use Livewire testing utilities for Filament components:

```php
// Test table filtering
Livewire::test(InvoiceResource\Pages\ListInvoices::class)
    ->filterTable('status', 'draft')
    ->assertCanSeeTableRecords($draftInvoices)
    ->assertCanNotSeeTableRecords($finalizedInvoices);

// Test form submission
Livewire::test(InvoiceResource\Pages\CreateInvoice::class)
    ->fillForm(['tenant_renter_id' => $tenant->id])
    ->call('create')
    ->assertHasNoFormErrors();
```

## Performance Optimization

### Tenant Reuse Pattern
Reuse tenant records to avoid factory cascade overhead:

```php
// ❌ Slow: Creates new tenant for each invoice
for ($i = 0; $i < 10; $i++) {
    Invoice::factory()->create(['tenant_id' => 1]);
}

// ✅ Fast: Reuse single tenant
$tenant = Tenant::factory()->create(['tenant_id' => 1]);
for ($i = 0; $i < 10; $i++) {
    Invoice::factory()->create([
        'tenant_id' => 1,
        'tenant_renter_id' => $tenant->id,
    ]);
}
```

### Eager Loading
Prevent N+1 queries in test assertions:

```php
// Load relationships upfront
$invoices = Invoice::with(['tenant.property', 'items'])->get();

// Assertions don't trigger additional queries
foreach ($invoices as $invoice) {
    $this->assertNotNull($invoice->tenant->property->address);
}
```

## Documentation

Each test suite has corresponding documentation:

- `docs/testing/invoice-status-filtering-tests.md` - Invoice filtering tests
- `docs/testing/INVOICE_FINALIZATION_TEST_SUMMARY.md` - Finalization tests
- `docs/testing/BUILDING_RESOURCE_TEST_SUMMARY.md` - Building resource tests

## Quality Standards

### Test Requirements
- ✅ Use `RefreshDatabase` trait for isolation
- ✅ Include PHPDoc with test purpose and strategy
- ✅ Verify tenant scope isolation
- ✅ Test both success and failure cases
- ✅ Use descriptive assertion messages
- ✅ Follow property-based testing for invariants

### Code Quality
- ✅ Pass Pint style checks
- ✅ Pass PHPStan static analysis
- ✅ Use strict types (`declare(strict_types=1)`)
- ✅ Use PHP 8.3+ features (enums, attributes)

## Troubleshooting

### Tests Timing Out
**Symptom**: Tests exceed 60-second timeout

**Solutions**:
1. Implement tenant reuse pattern
2. Reduce randomized data ranges
3. Use `createQuietly()` for non-observed models
4. Check for N+1 queries in test setup

### Tenant Scope Failures
**Symptom**: Tests see data from other tenants

**Solutions**:
1. Verify `actingAs()` is called before Livewire tests
2. Check TenantScope is applied to model
3. Ensure tenant_id is set correctly in factory
4. Verify middleware is applied in test environment

### Livewire Component Errors
**Symptom**: Component not found or method errors

**Solutions**:
1. Verify component class path is correct
2. Check component is registered in Filament
3. Ensure user has proper authorization
4. Verify component dependencies are loaded

## Related Documentation

- [Filament v4 Documentation](https://filamentphp.com/docs/4.x)
- [Livewire Testing](https://livewire.laravel.com/docs/testing)
- [Pest PHP](https://pestphp.com/docs)
- [Laravel Testing](https://laravel.com/docs/12.x/testing)

## Contributing

When adding new Filament tests:

1. Follow existing test patterns and naming conventions
2. Add comprehensive PHPDoc documentation
3. Include property-based tests for invariants
4. Verify tenant scope isolation
5. Update this README with new test categories
6. Create corresponding documentation in `docs/testing/`
