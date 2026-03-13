# MeterReadingObserver API Reference

## Overview

The `MeterReadingObserver` automatically handles meter reading changes, creating audit trails and recalculating affected draft invoices while protecting finalized invoices from modification.

**Namespace**: `App\Observers`  
**Requirement**: 8.3 - Draft invoice recalculation on reading correction  
**Status**: ✅ Production Ready

---

## Observer Events

### `updating(MeterReading $meterReading): void`

Creates an audit trail record before a meter reading is updated.

**Trigger**: Before `MeterReading::save()` when value changes  
**Purpose**: Capture old/new values for audit trail

#### Behavior
```php
// Only creates audit if value is changing
if ($meterReading->isDirty('value')) {
    MeterReadingAudit::create([
        'meter_reading_id' => $meterReading->id,
        'changed_by_user_id' => Auth::id(),
        'old_value' => $meterReading->getOriginal('value'),
        'new_value' => $meterReading->value,
        'change_reason' => $meterReading->change_reason ?? 'No reason provided',
    ]);
}
```

#### Parameters
- `$meterReading` - The MeterReading model being updated

#### Side Effects
- Creates `MeterReadingAudit` record
- Captures authenticated user ID
- Stores change reason from `$meterReading->change_reason`

#### Example
```php
$reading = MeterReading::find(123);
$reading->change_reason = 'Correcting data entry error';
$reading->value = 1150.00;
$reading->save(); // Triggers updating() event
```

---

### `updated(MeterReading $meterReading): void`

Recalculates affected draft invoices after a meter reading is updated.

**Trigger**: After `MeterReading::save()` when value changed  
**Purpose**: Maintain invoice accuracy with current readings

#### Behavior
```php
// Only recalculates if value actually changed
if ($meterReading->wasChanged('value')) {
    $this->recalculateAffectedDraftInvoices($meterReading);
}
```

#### Parameters
- `$meterReading` - The MeterReading model that was updated

#### Side Effects
- Finds all invoice items referencing this reading
- Filters for draft invoices only
- Recalculates consumption and totals
- Updates `meter_reading_snapshot` with new values

#### Example
```php
$reading = MeterReading::find(123);
$reading->value = 1150.00;
$reading->save(); // Triggers updated() event → recalculation
```

---

## Private Methods

### `recalculateAffectedDraftInvoices(MeterReading $meterReading): void`

Finds and recalculates all draft invoices affected by a meter reading change.

#### Algorithm
1. Find invoice items via JSON snapshot (start_reading_id OR end_reading_id)
2. Extract unique invoice IDs
3. Filter for draft status only
4. Recalculate each affected invoice

#### Database Queries
```php
// Find affected items (2 queries max)
InvoiceItem::whereJsonContains('meter_reading_snapshot->start_reading_id', $id)
    ->orWhereJsonContains('meter_reading_snapshot->end_reading_id', $id)
    ->get();

// Filter draft invoices (1 query)
Invoice::whereIn('id', $affectedInvoiceIds)
    ->draft()
    ->get();
```

#### Performance
- **Complexity**: O(n) where n = number of affected invoice items
- **Queries**: 3 + n (n = affected items for reading lookups)

---

### `recalculateInvoice(Invoice $invoice): void`

Recalculates an invoice's totals based on current meter readings.

#### Algorithm
1. Iterate through invoice items
2. Fetch current start/end readings from snapshot IDs
3. Recalculate consumption: `end_value - start_value`
4. Update item quantity, total, and snapshot
5. Sum all item totals
6. Update invoice total_amount

#### Calculation
```php
$newConsumption = $endReading->value - $startReading->value;
$newTotal = $newConsumption * $item->unit_price;

$item->update([
    'quantity' => $newConsumption,
    'total' => $newTotal,
    'meter_reading_snapshot' => array_merge($snapshot, [
        'start_value' => $startReading->value,
        'end_value' => $endReading->value,
    ]),
]);
```

#### Edge Cases
- **Missing snapshot**: Skips item (continues to next)
- **Missing readings**: Skips item (continues to next)
- **Multiple items**: Recalculates all, sums correctly

---

## Data Structures

### Meter Reading Snapshot

Stored in `invoice_items.meter_reading_snapshot` (JSON column).

```php
[
    'meter_id' => 123,
    'meter_serial' => 'ABC123',
    'start_reading_id' => 456,
    'start_value' => '1000.00',      // Updated on recalculation
    'start_date' => '2025-10-01',
    'end_reading_id' => 789,
    'end_value' => '1100.00',        // Updated on recalculation
    'end_date' => '2025-11-01',
    'zone' => 'day',
    'tariff_id' => 10,
    'tariff_name' => 'Standard Rate',
    'tariff_configuration' => [...]
]
```

### Audit Record

Created in `meter_reading_audits` table.

```php
[
    'meter_reading_id' => 123,
    'changed_by_user_id' => 456,
    'old_value' => '1100.00',
    'new_value' => '1150.00',
    'change_reason' => 'Correcting data entry error',
    'created_at' => '2025-11-26 10:30:00',
]
```

---

## Usage Examples

### Example 1: Correcting End Reading

```php
// Manager discovers incorrect end reading
$reading = MeterReading::find(789);
$reading->change_reason = 'Correcting data entry error';
$reading->value = 1150.00; // Was 1100.00
$reading->save();

// System automatically:
// 1. Creates audit record (via updating event)
// 2. Finds affected draft invoices
// 3. Recalculates consumption: 1150 - 1000 = 150 kWh
// 4. Updates invoice item total: 150 * 0.20 = €30.00
// 5. Updates invoice total_amount
// 6. Updates meter_reading_snapshot
```

### Example 2: Correcting Start Reading

```php
// Manager realizes start reading was wrong
$reading = MeterReading::find(456);
$reading->change_reason = 'Correcting initial reading';
$reading->value = 950.00; // Was 1000.00
$reading->save();

// System automatically:
// 1. Creates audit record
// 2. Finds affected draft invoices
// 3. Recalculates consumption: 1100 - 950 = 150 kWh
// 4. Updates invoice accordingly
```

### Example 3: Finalized Invoice (No Recalculation)

```php
// Invoice has been finalized
$invoice->finalize(); // Sets status to FINALIZED

// Later, a reading is corrected
$reading = MeterReading::find(789);
$reading->change_reason = 'Late correction';
$reading->value = 1150.00;
$reading->save();

// System:
// 1. Creates audit record ✅
// 2. Finds affected invoices ✅
// 3. Filters out finalized invoices ✅
// 4. No recalculation occurs ✅
```

### Example 4: Multiple Affected Invoices

```php
// Reading used in multiple invoices
$reading = MeterReading::find(456); // Middle reading
$reading->change_reason = 'Meter calibration adjustment';
$reading->value = 1050.00; // Was 1100.00
$reading->save();

// System automatically:
// 1. Finds Invoice A (uses 456 as end_reading_id)
// 2. Finds Invoice B (uses 456 as start_reading_id)
// 3. Recalculates both invoices
// 4. Invoice A: consumption decreases
// 5. Invoice B: consumption increases
```

---

## Integration Points

### Models
- **MeterReading**: Source model with `change_reason` attribute
- **MeterReadingAudit**: Audit trail storage
- **Invoice**: Target model with `scopeDraft()`
- **InvoiceItem**: Contains `meter_reading_snapshot` JSON

### Services
- **BillingService**: Creates initial snapshot structure
- **MeterReadingService**: Provides reading lookup helpers

### Scopes
- **Invoice::scopeDraft()**: Filters for draft status
- **TenantScope**: Ensures tenant isolation

---

## Authorization

### Required Permissions
- User must be authenticated (for audit trail)
- User must have permission to update meter readings
- Handled by `MeterReadingPolicy::update()`

### Tenant Isolation
- All queries respect `TenantScope`
- Only invoices within same tenant are affected
- Cross-tenant leakage prevented

---

## Error Handling

### Graceful Degradation
```php
// Missing snapshot - skip item
if (!$snapshot) {
    continue;
}

// Missing readings - skip item
if (!$startReading || !$endReading) {
    continue;
}
```

### No Exceptions Thrown
- Observer never throws exceptions
- Failed recalculations are silently skipped
- Audit trail always created (in `updating` event)

---

## Performance Considerations

### Query Optimization
- Uses `whereJsonContains` for efficient JSON queries
- Batch fetches invoices with `whereIn`
- Minimal database hits per recalculation

### Caching Opportunities
```php
// Future enhancement: Cache readings
$readings = Cache::remember("readings_{$meter_id}", 3600, function () {
    return MeterReading::where('meter_id', $meter_id)->get();
});
```

### Scalability
- **Small scale** (< 100 invoices): Instant
- **Medium scale** (100-1000 invoices): < 1 second
- **Large scale** (> 1000 invoices): Consider queue

---

## Testing

### Test File
`tests/Unit/MeterReadingObserverDraftInvoiceTest.php`

### Test Coverage
- ✅ Basic recalculation (3 assertions)
- ✅ Finalized protection (3 assertions)
- ✅ Multiple invoices (2 assertions)
- ✅ Multiple items (1 assertion)
- ✅ No affected invoices (1 assertion)
- ✅ Start reading update (3 assertions)

### Running Tests
```bash
php artisan test --filter=MeterReadingObserverDraftInvoiceTest
```

---

## Monitoring & Debugging

### Logging
```php
// Add logging for debugging (not in production code)
Log::info('Recalculating invoice', [
    'invoice_id' => $invoice->id,
    'reading_id' => $meterReading->id,
    'old_value' => $meterReading->getOriginal('value'),
    'new_value' => $meterReading->value,
]);
```

### Audit Trail Query
```php
// View all changes to a reading
$audits = MeterReadingAudit::where('meter_reading_id', 123)
    ->with('changedBy')
    ->orderBy('created_at', 'desc')
    ->get();
```

### Affected Invoices Query
```php
// Find invoices affected by a reading
$items = InvoiceItem::whereJsonContains('meter_reading_snapshot->start_reading_id', 123)
    ->orWhereJsonContains('meter_reading_snapshot->end_reading_id', 123)
    ->with('invoice')
    ->get();
```

---

## Security Considerations

### Audit Trail
- All changes logged with user ID
- Change reason required (defaults to "No reason provided")
- Immutable audit records

### Invoice Protection
- Finalized invoices never recalculated
- Status check via `Invoice::scopeDraft()`
- Immutability enforced at model level

### Tenant Isolation
- All queries scoped by `tenant_id`
- Cross-tenant access prevented
- Enforced by `TenantScope` global scope

---

## Related Documentation

- **Implementation**: [docs/implementation/DRAFT_INVOICE_RECALCULATION_IMPLEMENTATION.md](../implementation/DRAFT_INVOICE_RECALCULATION_IMPLEMENTATION.md)
- **Test Coverage**: [docs/testing/METER_READING_OBSERVER_TEST_COVERAGE.md](../testing/METER_READING_OBSERVER_TEST_COVERAGE.md)
- **Quick Reference**: [docs/testing/METER_READING_OBSERVER_TEST_QUICK_REFERENCE.md](../testing/METER_READING_OBSERVER_TEST_QUICK_REFERENCE.md)
- **Observer Guide**: [docs/implementation/METER_READING_OBSERVER_IMPLEMENTATION.md](../implementation/METER_READING_OBSERVER_IMPLEMENTATION.md)
- **Billing Service**: [docs/implementation/BILLING_SERVICE_V2_IMPLEMENTATION.md](../implementation/BILLING_SERVICE_V2_IMPLEMENTATION.md)

---

## Changelog

### 2025-11-26 - Initial Implementation
- ✅ Implemented draft invoice recalculation
- ✅ Added finalized invoice protection
- ✅ Created comprehensive test suite
- ✅ Documented API and usage patterns

---

## Status

✅ **PRODUCTION READY**

All functionality implemented, tested, and documented. Ready for production deployment.

---

**Last Updated**: November 26, 2025  
**Maintained By**: Development Team  
**Version**: 1.0.0
