# BillingService Quick Reference

**Version**: 2.0.0  
**Last Updated**: 2024-11-25

## Quick Start

### Basic Invoice Generation

```php
use App\Services\BillingService;
use Carbon\Carbon;

$billingService = app(BillingService::class);
$tenant = Tenant::find(1);

$invoice = $billingService->generateInvoice(
    $tenant,
    Carbon::parse('2024-11-01'),
    Carbon::parse('2024-11-30')
);
```

### Invoice Finalization

```php
$billingService->finalizeInvoice($invoice);
```

## Common Patterns

### Batch Processing

```php
$tenants = Tenant::with('property.meters.readings')->get();

foreach ($tenants as $tenant) {
    try {
        $invoice = $billingService->generateInvoice($tenant, $start, $end);
    } catch (BillingException $e) {
        Log::error("Billing failed: {$e->getMessage()}");
    }
}
```

### Error Handling

```php
try {
    $invoice = $billingService->generateInvoice($tenant, $start, $end);
} catch (BillingException $e) {
    // No property, no meters, no provider
} catch (MissingMeterReadingException $e) {
    // Missing readings
} catch (InvoiceAlreadyFinalizedException $e) {
    // Already finalized
}
```

## Key Features

### Automatic Tariff Snapshotting

All tariff rates are automatically snapshotted in invoice items:

```php
'meter_reading_snapshot' => [
    'tariff_id' => 5,
    'tariff_name' => 'Ignitis Day/Night',
    'tariff_configuration' => [...],
]
```

### Multi-Zone Meter Support

Automatically handles day/night electricity meters:

```php
// Generates separate items for each zone
[
    ['description' => 'Electricity (day)', ...],
    ['description' => 'Electricity (night)', ...],
]
```

### Water Billing

Automatically calculates supply + sewage + fixed fee:

```php
// Supply: 10 m³ × €0.97 = €9.70
// Sewage: 10 m³ × €1.23 = €12.30
// Fixed: €0.85
// Total: €22.85
```

### Gyvatukas Integration

Automatically adds gyvatukas items if building exists:

```php
[
    'description' => 'Gyvatukas (Hot Water Circulation)',
    'quantity' => 1.00,
    'unit' => 'month',
    'unit_price' => 150.50,
    'total' => 150.50,
]
```

## Performance

- **Queries**: Constant 3 queries regardless of meter count
- **Execution**: ~50-250ms depending on meter count
- **Memory**: ~3-15MB depending on meter count

## Configuration

### Water Tariffs

```php
// config/billing.php
'water_tariffs' => [
    'default_supply_rate' => 0.97,  // €/m³
    'default_sewage_rate' => 1.23,  // €/m³
    'default_fixed_fee' => 0.85,    // €/month
],
```

### Invoice Settings

```php
// config/billing.php
'invoice' => [
    'default_due_days' => 14,  // Days after period end
],
```

## Exceptions

| Exception | Cause | Solution |
|-----------|-------|----------|
| `BillingException` | No property/meters/provider | Check tenant setup |
| `MissingMeterReadingException` | Missing readings | Enter readings |
| `InvoiceAlreadyFinalizedException` | Already finalized | Cannot modify |

## Testing

```bash
# Run BillingService tests
php artisan test --filter=BillingServiceRefactoredTest

# Run specific test
php artisan test --filter=test_generates_invoice_with_water_meters
```

## Logging

All operations are logged with structured context:

```php
[info] Starting invoice generation
    tenant_id: 1
    period_start: 2024-11-01
    period_end: 2024-11-30

[info] Invoice created
    invoice_id: 123

[warning] Missing meter reading
    meter_id: 5
    meter_type: electricity

[info] Invoice generation completed
    invoice_id: 123
    total_amount: 125.50
    items_count: 4
```

## Related Documentation

- [Implementation Guide](BILLING_SERVICE_V2_IMPLEMENTATION.md)
- [API Reference](../api/BILLING_SERVICE_API.md)
- [Service Layer Architecture](../architecture/SERVICE_LAYER_ARCHITECTURE.md)

---

**Status**: Production Ready ✅
