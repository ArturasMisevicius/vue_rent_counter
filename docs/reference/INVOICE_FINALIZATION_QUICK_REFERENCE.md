# Invoice Finalization Quick Reference

## At a Glance

**Controller**: `FinalizeInvoiceController`  
**Route**: `POST /manager/invoices/{invoice}/finalize`  
**Route Name**: `manager.invoices.finalize`  
**Authorization**: `InvoicePolicy::finalize()`  
**Service**: `BillingService::finalizeInvoice()`

## Quick Start

### Blade Template
```blade
@can('finalize', $invoice)
    <form method="POST" action="{{ route('manager.invoices.finalize', $invoice) }}">
        @csrf
        <button type="submit">Finalize</button>
    </form>
@endcan
```

### Livewire
```php
public function finalize()
{
    return redirect()->route('manager.invoices.finalize', $this->invoice);
}
```

### JavaScript
```javascript
fetch(`/manager/invoices/${invoiceId}/finalize`, {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': csrfToken }
});
```

## Authorization Matrix

| Role | Can Finalize | Conditions |
|------|--------------|------------|
| Superadmin | ✅ Yes | Any invoice |
| Admin | ✅ Yes | Same tenant only |
| Manager | ✅ Yes | Same tenant only |
| Tenant | ❌ No | Read-only |

## Validation Rules

| Rule | Check | Error Message |
|------|-------|---------------|
| Invoice exists | Route model binding | 404 Not Found |
| Status is DRAFT | `status === DRAFT` | "Invoice is already finalized" |
| Has items | `items()->count() > 0` | "Invoice has no items" |
| Total > 0 | `total_amount > 0` | "Invalid total amount" |
| Valid period | `start < end` | "Invalid billing period" |
| Valid items | All items valid | "Invalid invoice items" |

## Response Codes

| Code | Meaning | Scenario |
|------|---------|----------|
| 302 | Redirect (success) | Invoice finalized successfully |
| 302 | Redirect (error) | Validation or business logic error |
| 403 | Forbidden | Authorization failed |
| 404 | Not Found | Invoice doesn't exist |
| 422 | Unprocessable | Validation failed |

## Error Messages

### Success
```
"Invoice finalized and locked successfully"
```

### Errors
```
"Invoice is already finalized"
"Invoice finalization failed. Please try again."
```

## State Transitions

```
DRAFT → finalize() → FINALIZED (immutable)
```

## Common Issues

### Issue: "Invoice is already finalized"
**Cause**: Invoice status is already FINALIZED  
**Solution**: Check invoice status before attempting finalization

### Issue: "Invoice finalization failed"
**Cause**: Generic error (database, network, etc.)  
**Solution**: Check logs, retry operation

### Issue: 403 Forbidden
**Cause**: User lacks permission  
**Solution**: Verify user role and tenant_id match

### Issue: Validation errors
**Cause**: Invoice doesn't meet finalization criteria  
**Solution**: Ensure invoice has items, valid total, valid period

## Testing Checklist

- [ ] Manager can finalize draft invoice
- [ ] Admin can finalize draft invoice
- [ ] Tenant cannot finalize invoice
- [ ] Cannot finalize already finalized invoice
- [ ] Cannot finalize invoice without items
- [ ] Finalized invoice has timestamp
- [ ] Finalization validates billing period

## Performance Benchmarks

| Metric | Target | Typical |
|--------|--------|---------|
| Response Time | <100ms | ~60ms |
| Authorization | <5ms | ~2ms |
| Service Call | <50ms | ~30ms |
| Database Update | <20ms | ~10ms |

## Translation Keys

```php
// Success
'notifications.invoice.finalized_locked'

// Errors
'invoices.errors.already_finalized'
'invoices.errors.finalization_failed'

// Actions
'invoices.actions.finalize'
'invoices.actions.finalizing'

// Confirmations
'invoices.confirm.finalize'
```

## Related Commands

```bash
# Run tests
php artisan test --filter=FinalizeInvoiceControllerTest

# Check routes
php artisan route:list --name=finalize

# Check policies
php artisan tinker
>>> Gate::inspect('finalize', $invoice)
```

## Code Snippets

### Check if can finalize
```php
if (auth()->user()->can('finalize', $invoice)) {
    // Show finalize button
}
```

### Programmatic finalization
```php
$billingService = app(BillingService::class);
$billingService->finalizeInvoice($invoice);
```

### Test finalization
```php
$this->actingAs($manager)
    ->post(route('manager.invoices.finalize', $invoice))
    ->assertRedirect()
    ->assertSessionHas('success');
```

## Documentation Links

- [Full API Reference](../api/FINALIZE_INVOICE_CONTROLLER_API.md)
- [Usage Guide](../controllers/FINALIZE_INVOICE_CONTROLLER_USAGE.md)
- [Architecture](../architecture/INVOICE_FINALIZATION_FLOW.md)
- [Implementation Details](../controllers/FINALIZE_INVOICE_CONTROLLER_REFACTORING_COMPLETE.md)
