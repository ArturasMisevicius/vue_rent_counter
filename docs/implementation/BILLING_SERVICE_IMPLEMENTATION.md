# BillingService Implementation

## Overview

The `BillingService` class orchestrates invoice generation with tariff snapshotting and gyvatukas calculations for the Vilnius Utilities Billing System. It implements requirements 3.1, 3.2, 3.3, 5.1, 5.2, and 5.5 from the specification.

## Implementation Date

November 25, 2025

## Location

- **Service**: `app/Services/BillingService.php`
- **Tests**: `tests/Unit/Services/BillingServiceTest.php`

## Key Features

### 1. Invoice Generation (`generateInvoice`)

Generates a draft invoice for a tenant for a specific billing period:

1. **Collects meter readings** for the period
2. **Resolves applicable tariffs** and snapshots rates
3. **Calculates consumption** per utility type
4. **Applies GyvatukasCalculator** for heating/hot water
5. **Creates Invoice with InvoiceItems**
6. **Returns draft invoice**

**Method Signature:**
```php
public function generateInvoice(Tenant $tenant, Carbon $periodStart, Carbon $periodEnd): Invoice
```

**Features:**
- Automatic tariff resolution and snapshotting
- Multi-zone meter support (day/night electricity)
- Water billing with supply + sewage + fixed fee
- Gyvatukas calculation integration
- Comprehensive error handling and logging
- Transaction-based execution for data integrity

### 2. Water Bill Calculation

Implements Requirements 3.1, 3.2, 3.3:

- **Supply rate**: €0.97/m³ (configurable)
- **Sewage rate**: €1.23/m³ (configurable)
- **Fixed fee**: €0.85/month (configurable)
- **Property type-specific tariffs**: Framework in place for future enhancement

**Formula:**
```
Total = (consumption × supply_rate) + (consumption × sewage_rate) + fixed_fee
```

### 3. Tariff Snapshotting

Implements Requirements 5.1, 5.2:

Each invoice item includes a complete snapshot of:
- Meter reading IDs and values
- Reading dates
- Tariff ID and name
- **Complete tariff configuration** (rates, zones, rules)

This ensures invoices remain accurate even if tariffs change after generation.

### 4. Invoice Finalization (`finalizeInvoice`)

Implements Requirement 5.5:

```php
public function finalizeInvoice(Invoice $invoice): Invoice
```

- Sets `status` to `FINALIZED`
- Sets `finalized_at` timestamp
- Makes invoice immutable (enforced by Invoice model)
- Throws `InvoiceAlreadyFinalizedException` if already finalized

### 5. Multi-Zone Meter Support

Handles meters with multiple tariff zones (e.g., day/night electricity):

- Automatically detects zones from meter readings
- Creates separate invoice items for each zone
- Snapshots zone-specific rates and consumption

### 6. Gyvatukas Integration

Integrates with `GyvatukasCalculator` for hot water circulation fees:

- Automatically calculates for properties with buildings
- Handles seasonal variations (summer/winter)
- Gracefully handles calculation failures
- Creates dedicated invoice item for gyvatukas

## Error Handling

### Exceptions Thrown

1. **`BillingException`**
   - Tenant has no associated property
   - Property has no meters
   - No provider found for meter type

2. **`MissingMeterReadingException`**
   - Required meter readings not available for period
   - Re-thrown if only one meter or no readings found

3. **`InvoiceAlreadyFinalizedException`**
   - Attempting to finalize an already finalized invoice

### Graceful Degradation

- Missing gyvatukas calculation: Logs warning, continues
- Missing readings for one meter (when multiple exist): Logs warning, continues with others

## Configuration

Uses `config/billing.php`:

```php
'water_tariffs' => [
    'default_supply_rate' => 0.97,  // EUR per m³
    'default_sewage_rate' => 1.23,  // EUR per m³
    'default_fixed_fee' => 0.85,    // EUR per month
],

'invoice' => [
    'default_due_days' => 14,  // Days until invoice is due
],
```

## Dependencies

- **`TariffResolver`**: Resolves and calculates tariff costs
- **`GyvatukasCalculator`**: Calculates hot water circulation fees
- **`MeterReadingService`**: Retrieves meter readings
- **`BaseService`**: Provides transaction management and logging

## Test Coverage

Comprehensive test suite with 10 tests covering:

1. ✅ Electricity consumption billing
2. ✅ Water consumption with supply, sewage, and fixed fee
3. ✅ Multi-zone electricity meters (day/night)
4. ✅ Exception when tenant has no property
5. ✅ Exception when property has no meters
6. ✅ Exception when meter readings are missing
7. ✅ Invoice finalization
8. ✅ Exception when finalizing already finalized invoice
9. ✅ Tariff configuration snapshotting
10. ✅ Correct total amount calculation

**Test Results**: All 10 tests passing with 36 assertions

## Usage Example

```php
use App\Services\BillingService;
use Carbon\Carbon;

$billingService = app(BillingService::class);

// Generate invoice for current month
$invoice = $billingService->generateInvoice(
    $tenant,
    Carbon::now()->startOfMonth(),
    Carbon::now()->endOfMonth()
);

// Review and finalize
$finalizedInvoice = $billingService->finalizeInvoice($invoice);
```

## Invoice Item Structure

Each invoice item includes:

```php
[
    'invoice_id' => 1,
    'description' => 'Electricity (day)',
    'quantity' => 100.00,
    'unit' => 'kWh',
    'unit_price' => 0.18,
    'total' => 18.00,
    'meter_reading_snapshot' => [
        'meter_id' => 1,
        'meter_serial' => 'LT-1234-5678',
        'start_reading_id' => 10,
        'start_value' => 1000.00,
        'start_date' => '2025-11-01',
        'end_reading_id' => 11,
        'end_value' => 1100.00,
        'end_date' => '2025-11-30',
        'zone' => 'day',
        'tariff_id' => 5,
        'tariff_name' => 'Ignitis Day/Night',
        'tariff_configuration' => [
            'type' => 'time_of_use',
            'zones' => [...],
        ],
    ],
]
```

## Logging

Comprehensive logging at key points:

- Invoice generation start/completion
- Missing meter readings (warning level)
- Gyvatukas calculation failures (error level)
- Invoice finalization

All logs include:
- Service class name
- Tenant ID (from TenantContext)
- User ID and role (if authenticated)
- Relevant entity IDs

## Future Enhancements

1. **Property Type-Specific Tariffs**: Currently uses default rates; framework in place for house vs. apartment differentiation
2. **Bulk Invoice Generation**: Generate invoices for multiple tenants in batch
3. **Invoice Recalculation**: Recalculate draft invoices when meter readings are corrected
4. **Payment Integration**: Track payments and update invoice status
5. **Late Payment Fees**: Automatic calculation based on overdue invoices

## Requirements Validation

| Requirement | Status | Implementation |
|-------------|--------|----------------|
| 3.1 | ✅ | Water supply and sewage rates applied |
| 3.2 | ✅ | Fixed meter subscription fee added |
| 3.3 | ✅ | Framework for property type-specific tariffs |
| 5.1 | ✅ | Tariff rates snapshotted in invoice items |
| 5.2 | ✅ | Meter readings snapshotted in invoice items |
| 5.5 | ✅ | Invoice finalization makes invoice immutable |

## Related Documentation

- [Tariff Resolver Implementation](TARIFF_RESOLVER_IMPLEMENTATION.md)
- [Gyvatukas Calculator Implementation](GYVATUKAS_CALCULATOR_IMPLEMENTATION.md)
- [Service Layer Architecture](../architecture/SERVICE_LAYER_ARCHITECTURE.md)
- [Billing Configuration](../../config/billing.php)
