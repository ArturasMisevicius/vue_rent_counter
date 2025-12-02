# MeterReadingUpdateController API Reference

## Overview

Single-action controller for meter reading corrections with full audit trail support and automatic draft invoice recalculation. This controller is separated from the main `MeterReadingController` to emphasize the importance of corrections and maintain single responsibility.

**Namespace**: `App\Http\Controllers`  
**Requirements**: 1.1, 1.2, 1.3, 1.4, 8.1, 8.2, 8.3  
**Status**: ✅ Production Ready

---

## Endpoint

### PUT `/manager/meter-readings/{meterReading}/correct`

Updates a meter reading with audit trail and automatic draft invoice recalculation.

**Method**: `PUT`  
**Route Name**: `manager.meter-readings.correct`  
**Middleware**: `auth`, `role:manager`  
**Authorization**: `MeterReadingPolicy::update()`

---

## Request

### Route Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `meterReading` | `MeterReading` | The meter reading to update (route model binding) |

### Request Body

| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `value` | `numeric` | Yes | `min:0` | New meter reading value |
| `change_reason` | `string` | Yes | `min:10`, `max:500` | Reason for correction (audit trail) |
| `reading_date` | `date` | No | `before_or_equal:today` | Reading date (optional, defaults to existing) |
| `zone` | `string` | No | `max:50` | Tariff zone (optional, defaults to existing) |

### Validation Rules

**Monotonicity Validation** (Property 1):
- New value must be ≥ previous reading value (same zone)
- New value must be ≤ next reading value (same zone)
- Validates against adjacent readings using `MeterReadingService`

**Temporal Validation** (Requirement 1.3):
- Reading date cannot be in the future
- Reading date must be valid date format

**Change Reason Validation** (Requirement 8.2):
- Minimum 10 characters (configurable via `config/billing.php`)
- Maximum 500 characters (configurable via `config/billing.php`)
- Required for audit trail

### Example Request

```http
PUT /manager/meter-readings/123/correct HTTP/1.1
Content-Type: application/json
Authorization: Bearer {token}

{
  "value": 1150.00,
  "change_reason": "Correcting data entry error - meter was misread during initial recording",
  "reading_date": "2025-11-26",
  "zone": "day"
}
```

---

## Response

### Success Response (200 OK)

```http
HTTP/1.1 302 Found
Location: /manager/meter-readings
Set-Cookie: laravel_session=...

{
  "message": "Meter reading updated successfully"
}
```

**Redirect**: Back to previous page with success flash message

**Flash Message Key**: `success`  
**Flash Message Value**: `notifications.meter_reading.updated` (localized)

### Side Effects

1. **Audit Trail Creation** (Requirement 8.1, 8.2):
   - `MeterReadingAudit` record created via `MeterReadingObserver::updating()`
   - Captures: `old_value`, `new_value`, `change_reason`, `changed_by_user_id`, `ip_address`, `user_agent`

2. **Draft Invoice Recalculation** (Requirement 8.3):
   - Triggered via `MeterReadingObserver::updated()`
   - Finds all invoice items referencing this reading
   - Filters for draft invoices only
   - Recalculates consumption and totals
   - Updates `meter_reading_snapshot` with new values

3. **Finalized Invoice Protection**:
   - Finalized invoices are NOT recalculated
   - Immutability enforced via `Invoice::scopeDraft()`

---

## Error Responses

### 401 Unauthorized

```json
{
  "message": "Unauthenticated."
}
```

**Cause**: User not authenticated  
**Action**: Redirect to login page

### 403 Forbidden

```json
{
  "message": "This action is unauthorized."
}
```

**Cause**: User lacks permission to update this meter reading  
**Policy**: `MeterReadingPolicy::update()` returned `false`

### 404 Not Found

```json
{
  "message": "Meter reading not found."
}
```

**Cause**: Meter reading ID does not exist or is outside user's tenant scope

### 422 Unprocessable Entity

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "value": [
      "The value must be at least 1000.00 (previous reading value)."
    ],
    "change_reason": [
      "The change reason must be at least 10 characters."
    ]
  }
}
```

**Causes**:
- Monotonicity violation (value < previous or value > next)
- Temporal violation (reading_date in future)
- Change reason too short/long
- Invalid data types

---

## Authorization

### Policy Check

**Policy**: `App\Policies\MeterReadingPolicy`  
**Method**: `update(User $user, MeterReading $meterReading)`

**Authorization Rules**:
- **SUPERADMIN**: Can update any meter reading
- **ADMIN**: Can update any meter reading within their tenant
- **MANAGER**: Can update meter readings within their tenant
- **TENANT**: Cannot update meter readings

### Tenant Isolation

All queries are automatically scoped by `tenant_id` via:
- `TenantScope` global scope
- Route model binding with tenant filtering
- Policy checks validate tenant ownership

---

## Usage Examples

### Example 1: Correcting End Reading

```php
// Manager discovers incorrect end reading
$response = $this->put(route('manager.meter-readings.correct', $reading), [
    'value' => 1150.00,
    'change_reason' => 'Correcting data entry error - meter was misread',
]);

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
$response = $this->put(route('manager.meter-readings.correct', $reading), [
    'value' => 950.00,
    'change_reason' => 'Correcting initial reading - meter was at 950 not 1000',
]);

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
$response = $this->put(route('manager.meter-readings.correct', $reading), [
    'value' => 1150.00,
    'change_reason' => 'Late correction after invoice finalization',
]);

// System:
// 1. Creates audit record ✅
// 2. Finds affected invoices ✅
// 3. Filters out finalized invoices ✅
// 4. No recalculation occurs ✅
```

### Example 4: Validation Error (Monotonicity)

```php
// Previous reading: 1000.00
// Current reading: 1100.00
// Next reading: 1200.00

// Attempt to set value below previous
$response = $this->put(route('manager.meter-readings.correct', $reading), [
    'value' => 950.00, // Invalid: < previous (1000.00)
    'change_reason' => 'Attempting to correct reading',
]);

// Response: 422 Unprocessable Entity
// Error: "The value must be at least 1000.00 (previous reading value)."
```

---

## Integration Points

### Related Components

**Models**:
- `MeterReading` - Source model with `change_reason` attribute
- `MeterReadingAudit` - Audit trail storage
- `Invoice` - Target model with `scopeDraft()`
- `InvoiceItem` - Contains `meter_reading_snapshot` JSON

**Services**:
- `MeterReadingService` - Provides adjacent reading lookup for validation
- `BillingService` - Creates initial snapshot structure

**Observers**:
- `MeterReadingObserver::updating()` - Creates audit trail
- `MeterReadingObserver::updated()` - Triggers recalculation

**Policies**:
- `MeterReadingPolicy::update()` - Authorization check

**Form Requests**:
- `UpdateMeterReadingRequest` - Validation and monotonicity checks

### Event Flow

```
HTTP PUT /manager/meter-readings/{id}/correct
    ↓
MeterReadingUpdateController::__invoke()
    ↓
UpdateMeterReadingRequest::validate()
    → Validates value, change_reason, reading_date, zone
    → Checks monotonicity against previous/next readings
    ↓
MeterReadingPolicy::update() authorization
    ↓
Set $meterReading->change_reason (temporary attribute)
    ↓
$meterReading->update([...])
    ↓
MeterReadingObserver::updating()
    → Creates MeterReadingAudit record
    → Captures old_value, new_value, change_reason, user_id
    ↓
MeterReadingObserver::updated()
    → Checks if value changed (wasChanged('value'))
    → Calls recalculateAffectedDraftInvoices()
        ↓
        → Finds InvoiceItems via JSON snapshot
        → Filters for draft invoices only
        → Calls recalculateInvoice() for each
            ↓
            → Fetches current meter readings
            → Recalculates consumption
            → Updates item quantity, total, snapshot
            → Updates invoice total_amount
    ↓
Redirect back with success message
```

---

## Configuration

### Validation Configuration

**File**: `config/billing.php`

```php
'validation' => [
    'change_reason_min_length' => env('METER_READING_CHANGE_REASON_MIN', 10),
    'change_reason_max_length' => env('METER_READING_CHANGE_REASON_MAX', 500),
],
```

### Localization

**File**: `lang/en/notifications.php`

```php
'meter_reading' => [
    'updated' => 'Meter reading updated successfully',
],
```

**File**: `lang/en/meter_readings.php`

```php
'validation' => [
    'value' => [
        'required' => 'The meter reading value is required.',
        'numeric' => 'The meter reading value must be a number.',
        'min' => 'The meter reading value must be at least 0.',
    ],
    'change_reason' => [
        'required' => 'A reason for the change is required.',
        'min' => 'The change reason must be at least :min characters.',
        'max' => 'The change reason must not exceed :max characters.',
    ],
    'custom' => [
        'monotonicity_lower' => 'The value must be at least :previous (previous reading value).',
        'monotonicity_higher' => 'The value must not exceed :next (next reading value).',
    ],
],
```

---

## Performance Considerations

### Query Optimization

**Efficient Queries**:
- Route model binding with tenant scope (1 query)
- Adjacent reading lookup (2 queries max)
- Affected invoice items (2 queries max via JSON contains)
- Draft invoices (1 query with whereIn + scope)
- Current readings (N queries, N = affected items)

**Total Queries**: ~6 + N (where N = affected invoice items)

### Caching Opportunities

```php
// Future enhancement: Cache adjacent readings
$adjacentReadings = Cache::remember(
    "meter_{$meter_id}_adjacent_{$reading_id}",
    3600,
    fn() => $service->getAdjacentReadings($reading)
);
```

### Scalability

- **Small scale** (< 10 affected invoices): Instant
- **Medium scale** (10-50 affected invoices): < 1 second
- **Large scale** (> 50 affected invoices): Consider queue

---

## Security Considerations

### Audit Trail

- All changes logged with user ID
- Change reason required (min 10 characters)
- Immutable audit records
- IP address and user agent captured

### Invoice Protection

- Finalized invoices never recalculated
- Status check via `Invoice::scopeDraft()`
- Immutability enforced at model level

### Tenant Isolation

- All queries scoped by `tenant_id`
- Cross-tenant access prevented
- Enforced by `TenantScope` global scope

### Authorization

- Policy check before update
- Manager can only update within their tenant
- Tenant users cannot update readings

---

## Testing

### Test File

`tests/Feature/Http/Controllers/MeterReadingUpdateControllerTest.php`

### Test Coverage

- ✅ Successful meter reading correction
- ✅ Audit trail creation
- ✅ Draft invoice recalculation
- ✅ Finalized invoice protection
- ✅ Monotonicity validation
- ✅ Temporal validation
- ✅ Change reason validation
- ✅ Authorization checks
- ✅ Tenant isolation

### Running Tests

```bash
# Full suite
php artisan test --filter=MeterReadingUpdateControllerTest

# Individual test
php artisan test --filter="manager can correct meter reading"

# With coverage
XDEBUG_MODE=coverage php artisan test --filter=MeterReadingUpdateControllerTest --coverage
```

---

## Monitoring & Debugging

### Logging

```php
// Add logging for debugging (not in production code)
Log::info('Meter reading correction', [
    'reading_id' => $meterReading->id,
    'old_value' => $meterReading->getOriginal('value'),
    'new_value' => $validated['value'],
    'change_reason' => $validated['change_reason'],
    'user_id' => auth()->id(),
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

## Related Documentation

- **Implementation**: [docs/implementation/METER_READING_CONTROLLERS_IMPLEMENTATION.md](../implementation/METER_READING_CONTROLLERS_IMPLEMENTATION.md)
- **Observer API**: [docs/api/METER_READING_OBSERVER_API.md](METER_READING_OBSERVER_API.md)
- **Test Coverage**: [docs/testing/METER_READING_OBSERVER_TEST_COVERAGE.md](../testing/METER_READING_OBSERVER_TEST_COVERAGE.md)
- **Draft Invoice Recalculation**: [docs/implementation/DRAFT_INVOICE_RECALCULATION_IMPLEMENTATION.md](../implementation/DRAFT_INVOICE_RECALCULATION_IMPLEMENTATION.md)
- **Billing Service**: [docs/implementation/BILLING_SERVICE_V2_IMPLEMENTATION.md](../implementation/BILLING_SERVICE_V2_IMPLEMENTATION.md)

---

## Changelog

### 2025-11-26 - Initial Implementation
- ✅ Created single-action controller for meter reading corrections
- ✅ Integrated with UpdateMeterReadingRequest validation
- ✅ Automatic audit trail creation via observer
- ✅ Automatic draft invoice recalculation
- ✅ Finalized invoice protection
- ✅ Comprehensive documentation

---

## Status

✅ **PRODUCTION READY**

All functionality implemented, tested, and documented. Ready for production deployment.

---

**Last Updated**: November 26, 2025  
**Maintained By**: Development Team  
**Version**: 1.0.0
