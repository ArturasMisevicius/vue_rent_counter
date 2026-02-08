# Invoice Finalization Architecture

## Component Overview

The invoice finalization feature follows a layered architecture with clear separation of concerns:

```
┌─────────────────────────────────────────────────────────────┐
│                     Presentation Layer                       │
│  (Filament ViewInvoice Page - UI Actions & Notifications)   │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ↓
┌─────────────────────────────────────────────────────────────┐
│                    Authorization Layer                       │
│         (InvoicePolicy - Permission Checks)                  │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ↓
┌─────────────────────────────────────────────────────────────┐
│                     Service Layer                            │
│    (InvoiceService - Business Logic & Validation)           │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ↓
┌─────────────────────────────────────────────────────────────┐
│                      Model Layer                             │
│  (Invoice Model - Data Persistence & Immutability)          │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ↓
┌─────────────────────────────────────────────────────────────┐
│                    Database Layer                            │
│         (SQLite/MySQL - Data Storage)                        │
└─────────────────────────────────────────────────────────────┘
```

## Layer Responsibilities

### 1. Presentation Layer

**Component:** `ViewInvoice` (Filament Page)

**Responsibilities:**
- Render invoice view with action buttons
- Configure finalize action (label, icon, modal)
- Handle user interactions (button clicks, confirmations)
- Display notifications (success/error feedback)
- Refresh UI after state changes

**Key Methods:**
- `getHeaderActions()` - Returns array of page actions
- `makeFinalizeAction()` - Configures finalization action

**Dependencies:**
- `InvoiceService` - For business logic
- `Filament\Notifications` - For user feedback
- `InvoicePolicy` - For authorization checks

**Design Patterns:**
- **Builder Pattern:** Fluent action configuration
- **Observer Pattern:** UI updates on model changes
- **Strategy Pattern:** Different actions based on invoice status

### 2. Authorization Layer

**Component:** `InvoicePolicy`

**Responsibilities:**
- Determine user permissions for invoice operations
- Enforce role-based access control
- Respect tenant scope boundaries
- Prevent unauthorized finalization attempts

**Key Methods:**
- `finalize(User $user, Invoice $invoice): bool`

**Authorization Rules:**
```php
Superadmin → Can finalize any invoice
Admin      → Can finalize invoices where invoice.tenant_id === user.tenant_id
Manager    → Can finalize invoices where invoice.tenant_id === user.tenant_id
Tenant     → Cannot finalize invoices
```

**Design Patterns:**
- **Policy Pattern:** Centralized authorization logic
- **Guard Pattern:** Early returns for permission checks

### 3. Service Layer

**Component:** `InvoiceService`

**Responsibilities:**
- Orchestrate finalization business logic
- Validate invoice meets finalization requirements
- Manage database transactions
- Throw appropriate exceptions on failure

**Key Methods:**
- `finalize(Invoice $invoice): void` - Main finalization method
- `validateCanFinalize(Invoice $invoice): void` - Validation logic
- `canFinalize(Invoice $invoice): bool` - Check without exceptions

**Validation Rules:**
1. Invoice must have at least one item
2. Total amount must be greater than zero
3. All items must have valid data (description, unit_price >= 0, quantity >= 0)
4. Billing period start must be before end
5. Invoice must be in DRAFT status

**Design Patterns:**
- **Service Pattern:** Encapsulated business logic
- **Transaction Script:** Database transaction management
- **Validator Pattern:** Separate validation concerns

### 4. Model Layer

**Component:** `Invoice` Model

**Responsibilities:**
- Represent invoice data structure
- Provide status check methods (isDraft, isFinalized, isPaid)
- Execute finalization (update status and timestamp)
- Enforce immutability via model observer

**Key Methods:**
- `finalize(): void` - Update status and timestamp
- `isDraft(): bool` - Check if invoice is draft
- `isFinalized(): bool` - Check if invoice is finalized
- `booted()` - Register model observer for immutability

**Immutability Enforcement:**
```php
static::updating(function ($invoice) {
    if ($invoice->isFinalized() || $invoice->isPaid()) {
        // Allow only status changes
        if (only status is changing) {
            return; // Allow
        }
        throw new InvoiceAlreadyFinalizedException();
    }
});
```

**Design Patterns:**
- **Active Record:** Model encapsulates data and behavior
- **Observer Pattern:** Model events for immutability
- **State Pattern:** Different behavior based on status

### 5. Database Layer

**Schema:**
```sql
invoices
├── id (primary key)
├── tenant_id (foreign key, indexed)
├── tenant_renter_id (foreign key)
├── billing_period_start (date)
├── billing_period_end (date)
├── total_amount (decimal)
├── status (enum: draft, finalized, paid)
├── finalized_at (timestamp, nullable)
├── created_at (timestamp)
└── updated_at (timestamp)

invoice_items
├── id (primary key)
├── invoice_id (foreign key, indexed)
├── description (string)
├── unit_price (decimal)
├── quantity (decimal)
├── total_amount (decimal)
└── ... (tariff snapshot fields)
```

**Indexes:**
- `invoices.tenant_id` - For tenant scope filtering
- `invoices.status` - For status-based queries
- `invoice_items.invoice_id` - For item lookups

## Data Flow

### Successful Finalization Flow

```
1. User clicks "Finalize Invoice" button
   ↓
2. Filament displays confirmation modal
   ↓
3. User confirms action
   ↓
4. ViewInvoice::makeFinalizeAction() callback
   ├─→ Check authorization: InvoicePolicy::finalize()
   │   └─→ Returns true (user has permission)
   ↓
5. InvoiceService::finalize($invoice)
   ├─→ Check if already finalized
   │   └─→ Not finalized, continue
   ├─→ InvoiceService::validateCanFinalize()
   │   ├─→ Check has items ✓
   │   ├─→ Check total > 0 ✓
   │   ├─→ Check items valid ✓
   │   └─→ Check billing period ✓
   ├─→ DB::transaction(function() {
   │   ├─→ Invoice::finalize()
   │   │   ├─→ Set status = FINALIZED
   │   │   ├─→ Set finalized_at = now()
   │   │   └─→ Save to database
   │   └─→ Commit transaction
   │   })
   └─→ Return (no exception)
   ↓
6. ViewInvoice displays success notification
   ↓
7. ViewInvoice refreshes UI data
   ↓
8. User sees updated invoice status
```

### Validation Failure Flow

```
1. User clicks "Finalize Invoice" button
   ↓
2. Filament displays confirmation modal
   ↓
3. User confirms action
   ↓
4. ViewInvoice::makeFinalizeAction() callback
   ├─→ Check authorization: InvoicePolicy::finalize()
   │   └─→ Returns true (user has permission)
   ↓
5. InvoiceService::finalize($invoice)
   ├─→ Check if already finalized
   │   └─→ Not finalized, continue
   ├─→ InvoiceService::validateCanFinalize()
   │   ├─→ Check has items ✗ (no items found)
   │   └─→ Throw ValidationException
   └─→ Exception propagates up
   ↓
6. ViewInvoice catches ValidationException
   ├─→ Extract error message
   ├─→ Display danger notification
   └─→ Re-throw exception
   ↓
7. Filament prevents action completion
   ↓
8. User sees error message
```

### Authorization Failure Flow

```
1. User clicks "Finalize Invoice" button
   ↓
2. Filament checks action visibility
   ├─→ InvoicePolicy::finalize()
   │   └─→ Returns false (user lacks permission)
   └─→ Action is hidden (not rendered)
   ↓
3. User cannot see finalize button
```

## Relationships & Dependencies

### Component Dependencies

```
ViewInvoice
├── depends on → InvoiceService
├── depends on → InvoicePolicy
├── depends on → Filament\Actions
└── depends on → Filament\Notifications

InvoiceService
├── depends on → Invoice (model)
├── depends on → InvoiceItem (model)
├── depends on → DB (transactions)
└── throws → ValidationException, InvoiceAlreadyFinalizedException

InvoicePolicy
├── depends on → User (model)
├── depends on → Invoice (model)
└── depends on → UserRole (enum)

Invoice
├── has many → InvoiceItem
├── belongs to → Tenant
├── has one through → Property
└── uses → BelongsToTenant (trait)
```

### Data Relationships

```
Invoice (1) ──────────── (N) InvoiceItem
   │
   │ belongs to
   ↓
Tenant (1) ──────────── (1) Property
   │
   │ belongs to
   ↓
User (tenant_id scope)
```

## Design Patterns Used

### 1. Service Layer Pattern

**Purpose:** Separate business logic from presentation

**Implementation:**
- `InvoiceService` encapsulates finalization logic
- `ViewInvoice` delegates to service
- Service handles validation, transactions, exceptions

**Benefits:**
- Reusable across different UI contexts (Filament, Livewire, API)
- Testable in isolation
- Single source of truth for business rules

### 2. Policy Pattern

**Purpose:** Centralize authorization logic

**Implementation:**
- `InvoicePolicy::finalize()` defines permission rules
- Filament checks policy before showing action
- Controller/service layer can also check policy

**Benefits:**
- Consistent authorization across application
- Easy to audit and modify permissions
- Prevents authorization logic duplication

### 3. Observer Pattern

**Purpose:** React to model events

**Implementation:**
- `Invoice::booted()` registers updating observer
- Observer prevents modifications to finalized invoices
- Throws exception if non-status fields change

**Benefits:**
- Automatic immutability enforcement
- No need to check in every update path
- Centralized data integrity rules

### 4. Transaction Script Pattern

**Purpose:** Manage database transactions

**Implementation:**
- `InvoiceService::finalize()` wraps update in transaction
- Rollback on validation failure
- Commit on success

**Benefits:**
- Data consistency guaranteed
- Atomic operations
- Easy error recovery

### 5. Builder Pattern

**Purpose:** Fluent action configuration

**Implementation:**
- Filament action uses method chaining
- `->label()->icon()->color()->requiresConfirmation()`
- Readable and maintainable configuration

**Benefits:**
- Clear action definition
- Easy to modify configuration
- Self-documenting code

## Security Considerations

### 1. Authorization

**Threat:** Unauthorized users finalizing invoices

**Mitigation:**
- Policy checks before action visibility
- Policy checks before action execution
- Tenant scope isolation via `HierarchicalScope`

### 2. Data Integrity

**Threat:** Invalid invoices being finalized

**Mitigation:**
- Comprehensive validation in service layer
- Database constraints (foreign keys, not null)
- Transaction rollback on failure

### 3. Immutability

**Threat:** Finalized invoices being modified

**Mitigation:**
- Model observer prevents updates
- Exception thrown on modification attempt
- Only status changes allowed

### 4. Audit Trail

**Threat:** No record of finalization

**Mitigation:**
- `finalized_at` timestamp recorded
- Status changes tracked
- Authorization failures logged

## Performance Considerations

### Database Queries

**Finalization Operation:**
- 1 SELECT (load invoice)
- 1 SELECT (load items for validation)
- 1 UPDATE (finalize invoice)

**Total:** 3 queries per finalization

### Optimization Strategies

1. **Eager Loading:** Load items with invoice to reduce queries
2. **Transaction Scope:** Keep transaction minimal
3. **Index Usage:** Ensure `tenant_id` and `status` are indexed
4. **Caching:** Cache `canFinalize()` results for UI rendering

### Scalability

**Current Design:**
- Synchronous finalization (blocking)
- Suitable for single invoice finalization
- Transaction ensures consistency

**Future Enhancements:**
- Queue jobs for bulk finalization
- Event-driven notifications
- Async validation for large invoices

## Testing Strategy

### Unit Tests

**Target:** `InvoiceService`

**Coverage:**
- `finalize()` success path
- `finalize()` validation failures
- `validateCanFinalize()` rules
- `canFinalize()` helper
- Transaction rollback on error

### Feature Tests

**Target:** Filament integration

**Coverage:**
- Action visibility based on status
- Action visibility based on permissions
- Successful finalization flow
- Validation error handling
- Authorization enforcement

### Property-Based Tests

**Target:** Immutability invariants

**Coverage:**
- Finalized invoices cannot be modified (100+ iterations)
- Status changes are allowed (100+ iterations)
- Various user roles and scenarios (100+ iterations)

## Related Documentation

- **Usage Guide:** [docs/filament/INVOICE_FINALIZATION_ACTION.md](../filament/INVOICE_FINALIZATION_ACTION.md)
- **API Reference:** [docs/api/INVOICE_FINALIZATION_API.md](../api/INVOICE_FINALIZATION_API.md)
- **Integration:** [docs/integration/FILAMENT_INTEGRATION_VERIFICATION.md](../integration/FILAMENT_INTEGRATION_VERIFICATION.md)
- **Spec:** `.kiro/specs/filament-admin-panel/requirements.md` (Requirement 4.5)
- **Spec:** [.kiro/specs/filament-admin-panel/tasks.md](../tasks/tasks.md) (Task 4.3)
