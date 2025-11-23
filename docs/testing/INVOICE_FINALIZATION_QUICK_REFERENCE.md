# Invoice Finalization Testing - Quick Reference

## Test Commands

```bash
# Run all invoice finalization tests
php artisan test --filter=InvoiceFinalization

# Run specific test file
php artisan test tests/Feature/Filament/InvoiceFinalizationActionTest.php

# Run single test
php artisan test --filter="admin_can_finalize_valid_draft_invoice"

# Run with coverage
php artisan test --filter=InvoiceFinalization --coverage

# Run in parallel
php artisan test --filter=InvoiceFinalization --parallel
```

## Test Data Setup

```php
// Valid draft invoice with items
$invoice = Invoice::factory()->create([
    'tenant_id' => 1,
    'status' => InvoiceStatus::DRAFT,
    'total_amount' => 100.00,
    'billing_period_start' => now()->subMonth(),
    'billing_period_end' => now(),
]);

$invoice->items()->create([
    'description' => 'Test item',
    'quantity' => 1,
    'unit_price' => 100.00,
    'total' => 100.00,  // Note: 'total', not 'total_price'
]);

// User roles
$admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
$manager = User::factory()->create(['role' => UserRole::MANAGER, 'tenant_id' => 1]);
$tenant = User::factory()->create(['role' => UserRole::TENANT, 'tenant_id' => 1]);
$superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN, 'tenant_id' => null]);
```

## Common Assertions

```php
// Invoice state
$this->assertTrue($invoice->isFinalized());
$this->assertNotNull($invoice->finalized_at);
$this->assertEquals(InvoiceStatus::FINALIZED, $invoice->status);

// Livewire component
Livewire::test(InvoiceResource\Pages\ViewInvoice::class, ['record' => $invoice->id])
    ->callAction('finalize')
    ->assertHasNoActionErrors()
    ->assertNotified();

// Action visibility
$actions = $component->instance()->getCachedHeaderActions();
$finalizeAction = collect($actions)->first(fn ($action) => $action->getName() === 'finalize');
$this->assertTrue($finalizeAction->isVisible());

// Rate limiting
$this->assertTrue(RateLimiter::tooManyAttempts('invoice-finalize:'.$user->id, 10));

// Audit logging
Log::shouldHaveReceived('info')
    ->with('Invoice finalization attempt', \Mockery::on(function ($context) {
        return isset($context['user_id']) && isset($context['invoice_id']);
    }));
```

## Test Checklist

### Before Committing
- [ ] All tests pass locally
- [ ] No hardcoded test data (use factories)
- [ ] Rate limiter cleared in setUp()
- [ ] Proper test isolation (RefreshDatabase)
- [ ] Descriptive test names
- [ ] AAA pattern (Arrange, Act, Assert)

### When Adding Features
- [ ] Add authorization test
- [ ] Add validation test
- [ ] Add audit logging verification
- [ ] Update documentation
- [ ] Check for regression risks

### When Fixing Bugs
- [ ] Add regression test first
- [ ] Verify test fails before fix
- [ ] Verify test passes after fix
- [ ] Check related tests still pass

## Troubleshooting

### Rate Limiter Not Clearing
```php
protected function setUp(): void
{
    parent::setUp();
    RateLimiter::clear('invoice-finalize:*');
}
```

### Authorization Exception Not Caught
```php
$this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
Livewire::test(...)->callAction('finalize');
```

### Audit Log Assertions Failing
```php
Log::spy();  // Before action
Log::shouldHaveReceived('info')->with('Invoice finalization attempt', \Mockery::on(...));
```

## Coverage Goals

- **Authorization:** 100% ✅
- **Validation:** 100% ✅
- **Security:** 100% ✅
- **UI Behavior:** 100% ✅
- **Edge Cases:** 100% ✅

## Files

- **Tests:** `tests/Feature/Filament/InvoiceFinalization*.php`
- **Docs:** `tests/Feature/Filament/README_INVOICE_FINALIZATION_TESTS.md`
- **Implementation:** `app/Filament/Resources/InvoiceResource/Pages/ViewInvoice.php`
- **Service:** `app/Services/InvoiceService.php`
- **Policy:** `app/Policies/InvoicePolicy.php`
