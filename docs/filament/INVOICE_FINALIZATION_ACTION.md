# Invoice Finalization Action

## Overview

The invoice finalization action in Filament's `ViewInvoice` page allows authorized users (admins and managers) to finalize draft invoices, making them immutable. This implements **Task 4.3** from the `filament-admin-panel` spec.

## Architecture

### Component Responsibilities

```
ViewInvoice (Filament Page)
    ↓ delegates validation & business logic
InvoiceService
    ↓ validates & updates
Invoice Model
    ↓ enforces immutability
Model Observer (booted method)
```

**Separation of Concerns:**
- **ViewInvoice**: UI layer, action configuration, user feedback
- **InvoiceService**: Business logic, validation orchestration, transaction management
- **Invoice Model**: Data persistence, status management, immutability enforcement
- **InvoicePolicy**: Authorization rules, permission checks

## Features

### 1. Finalization Action

**Location:** `app/Filament/Resources/InvoiceResource/Pages/ViewInvoice.php`

**Visibility Rules:**
- Only visible for invoices with `status = DRAFT`
- Requires `finalize` permission via `InvoicePolicy::finalize()`
- Hidden for `FINALIZED` or `PAID` invoices

**User Experience:**
- Displays confirmation modal with warning message
- Shows success notification on completion
- Shows danger notification with specific error on validation failure
- Automatically refreshes UI to reflect updated status

### 2. Validation Rules

Enforced by `InvoiceService::validateCanFinalize()`:

| Rule | Error Key | Message |
|------|-----------|---------|
| Must have at least one item | `invoice` | "Cannot finalize invoice: invoice has no items" |
| Total amount > 0 | `total_amount` | "Cannot finalize invoice: total amount must be greater than zero" |
| All items valid | `items` | "Cannot finalize invoice: all items must have valid description, unit price, and quantity" |
| Valid billing period | `billing_period` | "Cannot finalize invoice: billing period start must be before billing period end" |

### 3. Authorization

**Policy Method:** `InvoicePolicy::finalize()`

**Permission Matrix:**

| Role | Can Finalize | Conditions |
|------|--------------|------------|
| Superadmin | ✅ Yes | Any invoice |
| Admin | ✅ Yes | Only invoices within their `tenant_id` |
| Manager | ✅ Yes | Only invoices within their `tenant_id` |
| Tenant | ❌ No | Cannot finalize invoices |

### 4. Immutability Enforcement

Once finalized, invoices become immutable via `Invoice::booted()` observer:

**Allowed Changes:**
- Status transitions (e.g., `FINALIZED` → `PAID`)

**Blocked Changes:**
- `total_amount`
- `billing_period_start` / `billing_period_end`
- `tenant_id` / `tenant_renter_id`
- Any other field modifications

**Exception:** Throws `InvoiceAlreadyFinalizedException` if non-status fields are modified

## Usage Examples

### Basic Finalization Flow

```php
// User clicks "Finalize Invoice" button in Filament UI
// ↓
// ViewInvoice::makeFinalizeAction() executes
// ↓
app(InvoiceService::class)->finalize($invoice);
// ↓
// InvoiceService validates:
//   - Has items? ✓
//   - Total > 0? ✓
//   - Valid billing period? ✓
// ↓
// Invoice::finalize() updates:
//   - status = FINALIZED
//   - finalized_at = now()
// ↓
// UI refreshes, shows success notification
```

### Validation Failure Example

```php
// Invoice has no items
app(InvoiceService::class)->finalize($invoice);
// ↓
// Throws ValidationException with errors:
// ['invoice' => 'Cannot finalize invoice: invoice has no items']
// ↓
// ViewInvoice catches exception
// ↓
// Displays danger notification with error message
// ↓
// Re-throws exception to prevent action completion
```

### Authorization Check Example

```php
// Tenant user attempts to finalize invoice
$user->role = UserRole::TENANT;
$user->can('finalize', $invoice); // false
// ↓
// Action is hidden in UI (not visible)
// ↓
// If accessed directly, returns 403 Forbidden
```

## Testing

### Property-Based Tests

**Test:** `FilamentInvoiceFinalizationImmutabilityPropertyTest`
- **Iterations:** 100+
- **Validates:** Finalized invoices cannot be modified (except status)
- **Coverage:** All invoice fields, various user roles

### Feature Tests

**Test:** `FilamentPanelIntegrationTest::test_invoice_finalization_action()`
- Validates finalization action visibility
- Tests successful finalization flow
- Tests validation error handling
- Tests authorization enforcement

### Unit Tests

**Test:** `InvoiceServiceTest`
- Tests `finalize()` method
- Tests `validateCanFinalize()` rules
- Tests `canFinalize()` helper
- Tests transaction rollback on failure

## API Reference

### ViewInvoice::makeFinalizeAction()

```php
private function makeFinalizeAction(): Actions\Action
```

**Returns:** Configured Filament action instance

**Action Configuration:**
- **Label:** "Finalize Invoice"
- **Icon:** `heroicon-o-lock-closed`
- **Color:** `warning`
- **Confirmation:** Required
- **Visibility:** `$record->isDraft() && auth()->user()->can('finalize', $record)`

**Action Callback:**
1. Calls `InvoiceService::finalize($record)`
2. On success: Shows success notification, refreshes UI
3. On failure: Shows danger notification with error, re-throws exception

### InvoiceService::finalize()

```php
public function finalize(Invoice $invoice): void
```

**Parameters:**
- `$invoice` - The invoice to finalize

**Throws:**
- `ValidationException` - If invoice cannot be finalized
- `InvoiceAlreadyFinalizedException` - If invoice is already finalized

**Side Effects:**
- Sets `status = FINALIZED`
- Sets `finalized_at = now()`
- Saves invoice in database transaction

### InvoicePolicy::finalize()

```php
public function finalize(User $user, Invoice $invoice): bool
```

**Parameters:**
- `$user` - The authenticated user
- `$invoice` - The invoice to check

**Returns:** `true` if user can finalize, `false` otherwise

**Logic:**
- Returns `false` if invoice is not draft
- Returns `true` for superadmin (any invoice)
- Returns `true` for admin/manager (tenant-scoped)
- Returns `false` for tenant users

## Data Flow

### Finalization Sequence

```
1. User clicks "Finalize Invoice" button
   ↓
2. Filament displays confirmation modal
   ↓
3. User confirms action
   ↓
4. ViewInvoice::makeFinalizeAction() callback executes
   ↓
5. InvoiceService::finalize() called
   ↓
6. InvoiceService::validateCanFinalize() checks rules
   ↓
7. DB::transaction() begins
   ↓
8. Invoice::finalize() updates status and timestamp
   ↓
9. Invoice::save() persists changes
   ↓
10. Transaction commits
   ↓
11. ViewInvoice refreshes UI data
   ↓
12. Success notification displayed
```

### Error Handling Flow

```
1. Validation fails in InvoiceService
   ↓
2. ValidationException thrown with error messages
   ↓
3. ViewInvoice catches exception
   ↓
4. Extracts error message from exception
   ↓
5. Displays danger notification
   ↓
6. Re-throws exception
   ↓
7. Filament prevents action completion
   ↓
8. UI remains in current state
```

## Security Considerations

### Tenant Isolation

- All finalization checks respect `tenant_id` scope
- Admins/managers cannot finalize invoices from other tenants
- `InvoicePolicy::finalize()` enforces tenant boundaries
- `HierarchicalScope` automatically filters queries

### Audit Trail

- `finalized_at` timestamp records when finalization occurred
- Status changes are tracked in invoice history
- Authorization failures are logged via `AdminPanelProvider`
- Model observer prevents unauthorized modifications

### Immutability Guarantees

- Once finalized, invoice data cannot be changed (except status)
- Tariff snapshots in `InvoiceItem` remain unchanged
- Meter reading snapshots remain unchanged
- Prevents retroactive billing adjustments

## Related Documentation

- **Spec:** `.kiro/specs/filament-admin-panel/requirements.md` (Requirement 4.5)
- **Spec:** `.kiro/specs/filament-admin-panel/tasks.md` (Task 4.3)
- **Service:** `app/Services/InvoiceService.php`
- **Model:** `app/Models/Invoice.php`
- **Policy:** `app/Policies/InvoicePolicy.php`
- **Tests:** `tests/Feature/FilamentInvoiceFinalizationImmutabilityPropertyTest.php`
- **Integration:** `docs/integration/FILAMENT_INTEGRATION_VERIFICATION.md`

## Changelog

### 2025-11-23: Initial Implementation
- ✅ Created `ViewInvoice::makeFinalizeAction()`
- ✅ Integrated `InvoiceService` for validation
- ✅ Added confirmation modal with warning
- ✅ Implemented success/error notifications
- ✅ Added UI refresh after finalization
- ✅ Enforced authorization via `InvoicePolicy`
- ✅ Added comprehensive DocBlocks
- ✅ Created property-based tests
