# Invoice Finalization API

## Endpoint Overview

The invoice finalization functionality is exposed through Filament's action system rather than traditional REST endpoints. This document describes the internal API contracts and integration points.

## Service Layer API

### InvoiceService::finalize()

**Purpose:** Finalize a draft invoice, making it immutable

**Signature:**
```php
public function finalize(Invoice $invoice): void
```

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `$invoice` | `Invoice` | Yes | The invoice model instance to finalize |

**Returns:** `void` (modifies invoice in-place)

**Throws:**
| Exception | Condition | HTTP Equivalent |
|-----------|-----------|-----------------|
| `ValidationException` | Invoice fails validation rules | 422 Unprocessable Entity |
| `InvoiceAlreadyFinalizedException` | Invoice is already finalized | 409 Conflict |

**Validation Rules:**

```php
[
    'invoice' => 'Invoice must have at least one item',
    'total_amount' => 'Total amount must be greater than zero',
    'items' => 'All items must have valid description, unit_price >= 0, quantity >= 0',
    'billing_period' => 'Billing period start must be before end'
]
```

**Side Effects:**
- Sets `invoice.status = FINALIZED`
- Sets `invoice.finalized_at = now()`
- Persists changes in database transaction
- Triggers model observers

**Example Usage:**

```php
use App\Services\InvoiceService;
use App\Models\Invoice;
use Illuminate\Validation\ValidationException;

$invoice = Invoice::find(123);
$service = app(InvoiceService::class);

try {
    $service->finalize($invoice);
    // Success: invoice is now finalized
} catch (ValidationException $e) {
    // Handle validation errors
    $errors = $e->errors();
    // ['invoice' => 'Cannot finalize invoice: invoice has no items']
}
```

### InvoiceService::canFinalize()

**Purpose:** Check if an invoice can be finalized without throwing exceptions

**Signature:**
```php
public function canFinalize(Invoice $invoice): bool
```

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `$invoice` | `Invoice` | Yes | The invoice to check |

**Returns:** `bool` - `true` if invoice can be finalized, `false` otherwise

**Example Usage:**

```php
$invoice = Invoice::find(123);
$service = app(InvoiceService::class);

if ($service->canFinalize($invoice)) {
    // Show finalize button
} else {
    // Hide finalize button or show error message
}
```

## Model API

### Invoice::finalize()

**Purpose:** Update invoice status and timestamp

**Signature:**
```php
public function finalize(): void
```

**Parameters:** None

**Returns:** `void`

**Side Effects:**
- Sets `$this->status = InvoiceStatus::FINALIZED`
- Sets `$this->finalized_at = now()`
- Calls `$this->save()`

**Example Usage:**

```php
$invoice = Invoice::find(123);
$invoice->finalize();
// Invoice is now finalized
```

### Invoice::isDraft()

**Purpose:** Check if invoice is in draft status

**Signature:**
```php
public function isDraft(): bool
```

**Returns:** `bool` - `true` if status is DRAFT

### Invoice::isFinalized()

**Purpose:** Check if invoice is finalized

**Signature:**
```php
public function isFinalized(): bool
```

**Returns:** `bool` - `true` if status is FINALIZED

### Invoice::isPaid()

**Purpose:** Check if invoice is paid

**Signature:**
```php
public function isPaid(): bool
```

**Returns:** `bool` - `true` if status is PAID

## Authorization API

### InvoicePolicy::finalize()

**Purpose:** Determine if user can finalize an invoice

**Signature:**
```php
public function finalize(User $user, Invoice $invoice): bool
```

**Parameters:**
| Name | Type | Required | Description |
|------|------|----------|-------------|
| `$user` | `User` | Yes | The authenticated user |
| `$invoice` | `Invoice` | Yes | The invoice to check |

**Returns:** `bool` - `true` if user can finalize

**Authorization Matrix:**

| User Role | Can Finalize | Conditions |
|-----------|--------------|------------|
| `SUPERADMIN` | ✅ Yes | Any invoice |
| `ADMIN` | ✅ Yes | `invoice.tenant_id === user.tenant_id` |
| `MANAGER` | ✅ Yes | `invoice.tenant_id === user.tenant_id` |
| `TENANT` | ❌ No | Never |

**Example Usage:**

```php
$user = auth()->user();
$invoice = Invoice::find(123);

if ($user->can('finalize', $invoice)) {
    // User is authorized to finalize
} else {
    // Return 403 Forbidden
}
```

## Filament Action API

### ViewInvoice::makeFinalizeAction()

**Purpose:** Create Filament action for invoice finalization

**Signature:**
```php
private function makeFinalizeAction(): Actions\Action
```

**Returns:** `Actions\Action` - Configured Filament action

**Action Configuration:**

```php
[
    'name' => 'finalize',
    'label' => 'Finalize Invoice',
    'icon' => 'heroicon-o-lock-closed',
    'color' => 'warning',
    'requiresConfirmation' => true,
    'modalHeading' => 'Finalize Invoice',
    'modalDescription' => 'Are you sure you want to finalize this invoice? Once finalized, the invoice cannot be modified.',
    'modalSubmitActionLabel' => 'Yes, finalize it',
    'visible' => fn($record) => $record->isDraft() && auth()->user()->can('finalize', $record)
]
```

**Action Callback Flow:**

```php
1. Call InvoiceService::finalize($record)
2. On success:
   - Show success notification
   - Refresh UI data (status, finalized_at)
3. On ValidationException:
   - Extract error message
   - Show danger notification
   - Re-throw exception
```

## Request/Response Formats

### Success Response (Filament Notification)

```json
{
  "type": "success",
  "title": "Invoice finalized",
  "body": "The invoice has been successfully finalized."
}
```

### Error Response (Filament Notification)

```json
{
  "type": "danger",
  "title": "Cannot finalize invoice",
  "body": "Cannot finalize invoice: invoice has no items"
}
```

### Validation Error Structure

```php
[
    'invoice' => [
        'Cannot finalize invoice: invoice has no items'
    ],
    'total_amount' => [
        'Cannot finalize invoice: total amount must be greater than zero'
    ],
    'items' => [
        'Cannot finalize invoice: all items must have valid description, unit price, and quantity'
    ],
    'billing_period' => [
        'Cannot finalize invoice: billing period start must be before billing period end'
    ]
]
```

## Integration Examples

### Livewire Component Integration

```php
use App\Services\InvoiceService;
use Livewire\Component;

class InvoiceFinalization extends Component
{
    public Invoice $invoice;
    
    public function finalize()
    {
        $this->authorize('finalize', $this->invoice);
        
        try {
            app(InvoiceService::class)->finalize($this->invoice);
            
            session()->flash('success', 'Invoice finalized successfully');
            return redirect()->route('invoices.show', $this->invoice);
        } catch (ValidationException $e) {
            session()->flash('error', $e->getMessage());
        }
    }
}
```

### Controller Integration

```php
use App\Services\InvoiceService;
use App\Http\Controllers\Controller;

class InvoiceController extends Controller
{
    public function finalize(Invoice $invoice, InvoiceService $service)
    {
        $this->authorize('finalize', $invoice);
        
        try {
            $service->finalize($invoice);
            
            return response()->json([
                'message' => 'Invoice finalized successfully',
                'invoice' => $invoice->fresh()
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }
}
```

### API Route Example

```php
// routes/api.php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/invoices/{invoice}/finalize', [InvoiceController::class, 'finalize'])
        ->name('api.invoices.finalize');
});
```

## Error Codes

| Code | Exception | Description | HTTP Status |
|------|-----------|-------------|-------------|
| `INVOICE_NO_ITEMS` | `ValidationException` | Invoice has no items | 422 |
| `INVOICE_ZERO_AMOUNT` | `ValidationException` | Total amount is zero or negative | 422 |
| `INVOICE_INVALID_ITEMS` | `ValidationException` | Items have invalid data | 422 |
| `INVOICE_INVALID_PERIOD` | `ValidationException` | Billing period is invalid | 422 |
| `INVOICE_ALREADY_FINALIZED` | `InvoiceAlreadyFinalizedException` | Invoice is already finalized | 409 |
| `INVOICE_UNAUTHORIZED` | `AuthorizationException` | User lacks permission | 403 |

## Testing API

### Property-Based Test Example

```php
use Tests\TestCase;
use App\Models\Invoice;
use App\Services\InvoiceService;

class InvoiceFinalizationTest extends TestCase
{
    public function test_finalize_validates_invoice_has_items()
    {
        $invoice = Invoice::factory()->create();
        // No items added
        
        $service = app(InvoiceService::class);
        
        $this->expectException(ValidationException::class);
        $service->finalize($invoice);
    }
    
    public function test_finalize_sets_status_and_timestamp()
    {
        $invoice = Invoice::factory()
            ->has(InvoiceItem::factory()->count(3))
            ->create(['status' => InvoiceStatus::DRAFT]);
        
        $service = app(InvoiceService::class);
        $service->finalize($invoice);
        
        $this->assertEquals(InvoiceStatus::FINALIZED, $invoice->status);
        $this->assertNotNull($invoice->finalized_at);
    }
}
```

## Performance Considerations

### Database Queries

**Finalization Operation:**
```sql
-- 1. Load invoice with items
SELECT * FROM invoices WHERE id = ? LIMIT 1;
SELECT * FROM invoice_items WHERE invoice_id = ?;

-- 2. Update invoice
BEGIN TRANSACTION;
UPDATE invoices 
SET status = 'finalized', finalized_at = NOW() 
WHERE id = ?;
COMMIT;
```

**Query Count:** 3 queries (1 select invoice, 1 select items, 1 update)

### Optimization Tips

1. **Eager Load Items:** Use `$invoice->load('items')` before validation
2. **Transaction Scope:** Keep transaction minimal (only update operation)
3. **Cache Checks:** Cache `canFinalize()` results for UI rendering
4. **Batch Operations:** Use queue jobs for bulk finalization

## Related Documentation

- **Service:** `app/Services/InvoiceService.php`
- **Model:** `app/Models/Invoice.php`
- **Policy:** `app/Policies/InvoicePolicy.php`
- **Page:** `app/Filament/Resources/InvoiceResource/Pages/ViewInvoice.php`
- **Usage Guide:** `docs/filament/INVOICE_FINALIZATION_ACTION.md`
- **Integration:** `docs/integration/FILAMENT_INTEGRATION_VERIFICATION.md`
