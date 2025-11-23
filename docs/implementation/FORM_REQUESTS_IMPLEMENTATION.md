# Form Requests Implementation

This document describes the Form Request validation classes created for the Vilnius Utilities Billing System.

## Created Form Requests

### 1. StoreMeterReadingRequest

**Location:** `app/Http/Requests/StoreMeterReadingRequest.php`

**Purpose:** Validates meter reading submissions with monotonicity and temporal validation.

**Validation Rules:**
- `meter_id`: Required, must exist in meters table
- `reading_date`: Required, must be a valid date, cannot be in the future
- `value`: Required, numeric, minimum 0
- `zone`: Optional, string, max 50 characters
- `entered_by`: Required, must exist in users table

**Custom Validation:**
- **Monotonicity Validation** (Property 1, Requirement 1.2):
  - Ensures new reading value is not lower than the previous reading
  - Compares within the same zone for multi-zone meters
  - Provides clear error message with previous reading value

- **Zone Support Validation** (Property 4, Requirement 1.5):
  - Validates that zone is only provided for meters that support zones
  - Requires zone for meters with `supports_zones = true`
  - Rejects zone for meters with `supports_zones = false`

**Error Messages:**
- Custom messages for all validation failures
- Clear indication of why reading was rejected

---

### 2. UpdateMeterReadingRequest

**Location:** `app/Http/Requests/UpdateMeterReadingRequest.php`

**Purpose:** Validates meter reading corrections with audit trail requirements.

**Validation Rules:**
- `value`: Required, numeric, minimum 0
- `change_reason`: Required, string, minimum 10 characters, maximum 500 characters
- `reading_date`: Optional, must be a valid date, cannot be in the future
- `zone`: Optional, string, max 50 characters

**Custom Validation:**
- **Monotonicity Validation** (Property 1, Requirement 1.2):
  - Ensures updated reading maintains monotonicity with both previous and next readings
  - Prevents creating gaps or inversions in the reading sequence
  - Validates within the same zone for multi-zone meters

**Audit Trail:**
- Requires a detailed change reason (minimum 10 characters)
- Ensures transparency and accountability for all corrections

---

### 3. StoreTariffRequest

**Location:** `app/Http/Requests/StoreTariffRequest.php`

**Purpose:** Validates tariff configurations with complex time-of-use zone validation.

**Validation Rules:**
- `provider_id`: Required, must exist in providers table
- `name`: Required, string, max 255 characters
- `configuration`: Required, array
- `configuration.type`: Required, must be 'flat' or 'time_of_use'
- `configuration.currency`: Required, must be 'EUR'
- `configuration.rate`: Required for flat tariffs, numeric, minimum 0
- `configuration.zones`: Required for time-of-use tariffs, array, minimum 1 zone
- `configuration.zones.*.id`: Required, string
- `configuration.zones.*.start`: Required, HH:MM format (24-hour)
- `configuration.zones.*.end`: Required, HH:MM format (24-hour)
- `configuration.zones.*.rate`: Required, numeric, minimum 0
- `configuration.weekend_logic`: Optional, must be one of: apply_night_rate, apply_day_rate, apply_weekend_rate
- `configuration.fixed_fee`: Optional, numeric, minimum 0
- `active_from`: Required, date
- `active_until`: Optional, date, must be after active_from

**Custom Validation:**
- **Time-of-Use Zone Validation** (Property 6, Requirement 2.2):
  - **Overlap Detection**: Ensures no time zones overlap
    - Handles overnight ranges (e.g., 23:00 to 07:00)
    - Splits overnight ranges into two segments for validation
    - Compares all zone pairs for overlaps
  
  - **24-Hour Coverage**: Ensures all 24 hours are covered
    - Creates a minute-by-minute coverage array
    - Identifies gaps in coverage
    - Reports the first uncovered time if gaps exist

**Algorithm Details:**
- Converts time strings to minutes since midnight for easier comparison
- Handles edge cases like midnight crossings
- Provides clear error messages indicating which zones overlap or where gaps exist

---

### 4. FinalizeInvoiceRequest

**Location:** `app/Http/Requests/FinalizeInvoiceRequest.php`

**Purpose:** Validates that an invoice can be finalized and made immutable.

**Validation Rules:**
- No input fields required (validation is based on invoice state)

**Custom Validation:**
- **Invoice Finalization Validation** (Property 11, Requirements 5.1-5.5):
  - Checks if invoice is already finalized (prevents double finalization)
  - Ensures invoice has at least one item
  - Validates total amount is greater than zero
  - Verifies all items have valid data (description, unit_price, quantity)
  - Confirms billing period is valid (start before end)

**Immutability Enforcement:**
- Once finalized, invoice cannot be modified
- Snapshotted tariff rates remain unchanged
- Provides clear error messages for each validation failure

---

## Requirements Coverage

### Requirement 1.2: Meter Reading Monotonicity
✓ Implemented in `StoreMeterReadingRequest::validateMonotonicity()`
✓ Implemented in `UpdateMeterReadingRequest::validateMonotonicity()`

### Requirement 1.3: Temporal Validity
✓ Implemented via `reading_date` validation rule: `before_or_equal:today`

### Requirement 1.4: Audit Trail
✓ Implemented via `entered_by` field requirement in `StoreMeterReadingRequest`
✓ Implemented via `change_reason` requirement in `UpdateMeterReadingRequest`

### Requirement 1.5: Multi-Zone Support
✓ Implemented in `StoreMeterReadingRequest::validateZoneSupport()`

### Requirement 2.2: Time-of-Use Zone Validation
✓ Implemented in `StoreTariffRequest::validateTimeOfUseZones()`
✓ Overlap detection algorithm
✓ 24-hour coverage validation

### Requirements 5.1-5.5: Invoice Immutability
✓ Implemented in `FinalizeInvoiceRequest::validateInvoiceCanBeFinalized()`

---

## Properties Validated

### Property 1: Meter reading monotonicity
Validated by: `StoreMeterReadingRequest`, `UpdateMeterReadingRequest`

### Property 4: Multi-zone meter reading acceptance
Validated by: `StoreMeterReadingRequest`

### Property 6: Time-of-use zone validation
Validated by: `StoreTariffRequest`

### Property 11: Invoice immutability after finalization
Validated by: `FinalizeInvoiceRequest`

---

## Usage Examples

### Creating a Meter Reading
```php
use App\Http\Requests\StoreMeterReadingRequest;

public function store(StoreMeterReadingRequest $request)
{
    $validated = $request->validated();
    
    MeterReading::create([
        'meter_id' => $validated['meter_id'],
        'reading_date' => $validated['reading_date'],
        'value' => $validated['value'],
        'zone' => $validated['zone'] ?? null,
        'entered_by' => $validated['entered_by'],
    ]);
}
```

### Updating a Meter Reading
```php
use App\Http\Requests\UpdateMeterReadingRequest;

public function update(UpdateMeterReadingRequest $request, MeterReading $reading)
{
    $validated = $request->validated();
    
    // Create audit record
    MeterReadingAudit::create([
        'meter_reading_id' => $reading->id,
        'changed_by_user_id' => auth()->id(),
        'old_value' => $reading->value,
        'new_value' => $validated['value'],
        'change_reason' => $validated['change_reason'],
    ]);
    
    $reading->update(['value' => $validated['value']]);
}
```

### Creating a Tariff
```php
use App\Http\Requests\StoreTariffRequest;

public function store(StoreTariffRequest $request)
{
    $validated = $request->validated();
    
    Tariff::create($validated);
}
```

### Finalizing an Invoice
```php
use App\Http\Requests\FinalizeInvoiceRequest;

public function finalize(FinalizeInvoiceRequest $request, Invoice $invoice)
{
    $invoice->finalize();
    
    return response()->json([
        'message' => 'Invoice finalized successfully',
        'invoice' => $invoice,
    ]);
}
```

---

## Testing

Unit tests are provided in `tests/Unit/FormRequestValidationTest.php` using Pest PHP.

To run the tests:
```bash
php artisan test --filter=FormRequestValidationTest
```

---

## Additional Files Created

### Factories
- `database/factories/MeterFactory.php`: Factory for creating test meters
- `database/factories/ProviderFactory.php`: Factory for creating test providers

These factories support the unit tests and provide realistic Lithuanian data for testing.

---

## Notes

- All Form Requests use Laravel's built-in validation system
- Custom validation is implemented using the `withValidator()` method
- Error messages are clear and user-friendly
- All validation logic is thoroughly documented with property and requirement references
- The implementation follows Laravel best practices and conventions
