# Invoice Finalization Flow Architecture

## Overview

This document describes the complete architecture and data flow for invoice finalization in the Vilnius Utilities Billing Platform.

**Last Updated**: 2025-11-25  
**Status**: Production Ready

## System Components

### 1. Controller Layer
**Component**: `FinalizeInvoiceController`  
**Responsibility**: HTTP request handling and response formatting  
**Pattern**: Single-action invokable controller

### 2. Request Validation Layer
**Component**: `FinalizeInvoiceRequest`  
**Responsibility**: Input validation and state verification  
**Pattern**: Form Request with custom validator

### 3. Authorization Layer
**Component**: `InvoicePolicy::finalize()`  
**Responsibility**: Role-based access control and tenant isolation  
**Pattern**: Laravel Policy

### 4. Business Logic Layer
**Component**: `BillingService::finalizeInvoice()`  
**Responsibility**: State transition and business rules enforcement  
**Pattern**: Service class with transaction management

### 5. Data Layer
**Component**: `Invoice` Model  
**Responsibility**: Data persistence and relationships  
**Pattern**: Eloquent Model with observers

## Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         User Action                              │
│                  (Click "Finalize Invoice")                      │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                    HTTP POST Request                             │
│         POST /manager/invoices/{invoice}/finalize                │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│              FinalizeInvoiceController::__invoke()               │
│                                                                   │
│  1. Route Model Binding (loads Invoice)                          │
│  2. Request Validation (FinalizeInvoiceRequest)                  │
│  3. Authorization Check (InvoicePolicy::finalize)                │
│  4. Service Call (BillingService::finalizeInvoice)               │
│  5. Response Formatting (redirect with flash message)            │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                  FinalizeInvoiceRequest                          │
│                                                                   │
│  Validates:                                                       │
│  ✓ Invoice exists                                                │
│  ✓ Invoice is in DRAFT status                                    │
│  ✓ Invoice has items (count > 0)                                 │
│  ✓ Invoice total > 0                                             │
│  ✓ All items have valid data                                     │
│  ✓ Billing period is valid (start < end)                         │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                InvoicePolicy::finalize()                         │
│                                                                   │
│  Checks:                                                          │
│  ✓ Invoice is DRAFT (not already finalized)                      │
│  ✓ User role (SUPERADMIN, ADMIN, or MANAGER)                     │
│  ✓ Tenant match (invoice.tenant_id === user.tenant_id)           │
│  ✓ Cross-tenant prevention                                       │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│              BillingService::finalizeInvoice()                   │
│                                                                   │
│  1. Verify invoice is DRAFT                                      │
│  2. Set status = FINALIZED                                       │
│  3. Set finalized_at = now()                                     │
│  4. Save invoice                                                 │
│  5. Log finalization event                                       │
│  6. Return finalized invoice                                     │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Database Update                             │
│                                                                   │
│  UPDATE invoices SET                                             │
│    status = 'finalized',                                         │
│    finalized_at = '2025-11-25 10:30:00',                         │
│    updated_at = '2025-11-25 10:30:00'                            │
│  WHERE id = ?                                                    │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Success Response                              │
│                                                                   │
│  HTTP 302 Redirect                                               │
│  Location: (back to previous page)                               │
│  Flash: success = "Invoice finalized and locked successfully"    │
└─────────────────────────────────────────────────────────────────┘
```

## Sequence Diagram

```
User          Controller       Request         Policy          Service         Database
 │                │               │               │               │               │
 │─POST finalize─>│               │               │               │               │
 │                │               │               │               │               │
 │                │─validate─────>│               │               │               │
 │                │               │               │               │               │
 │                │               │─check state──>│               │               │
 │                │               │<──valid───────│               │               │
 │                │<──validated───│               │               │               │
 │                │               │               │               │               │
 │                │─authorize────────────────────>│               │               │
 │                │               │               │               │               │
 │                │               │               │─check role───>│               │
 │                │               │               │─check tenant─>│               │
 │                │<──authorized──────────────────│               │               │
 │                │               │               │               │               │
 │                │─finalize─────────────────────────────────────>│               │
 │                │               │               │               │               │
 │                │               │               │               │─verify state─>│
 │                │               │               │               │<──draft───────│
 │                │               │               │               │               │
 │                │               │               │               │─update status>│
 │                │               │               │               │─set timestamp>│
 │                │               │               │               │<──saved───────│
 │                │               │               │               │               │
 │                │<──finalized───────────────────────────────────│               │
 │                │               │               │               │               │
 │<─redirect─────│               │               │               │               │
 │  (success)     │               │               │               │               │
```

## Error Handling Flow

### Already Finalized Error

```
User → Controller → Request → Policy → Service
                                         │
                                         ├─ Check: invoice.status === FINALIZED
                                         │
                                         └─ Throw: InvoiceAlreadyFinalizedException
                                                   │
Controller ←─────────────────────────────────────┘
    │
    └─ Catch exception
    └─ Flash error message
    └─ Redirect back
```

### Authorization Failure

```
User → Controller → Request → Policy
                                 │
                                 ├─ Check: user.role
                                 ├─ Check: tenant_id match
                                 │
                                 └─ Return: false
                                           │
Controller ←─────────────────────────────┘
    │
    └─ Throw: AuthorizationException
    └─ Laravel handles 403 response
```

### Validation Failure

```
User → Controller → Request
                      │
                      ├─ Check: invoice.status
                      ├─ Check: invoice.items.count
                      ├─ Check: invoice.total_amount
                      │
                      └─ Add validation errors
                                │
Controller ←──────────────────┘
    │
    └─ Redirect back with errors
```

## State Transitions

### Invoice Status State Machine

```
┌─────────┐
│  DRAFT  │ ◄─── Initial state (created by BillingService)
└────┬────┘
     │
     │ finalize()
     │ ✓ Validation passes
     │ ✓ Authorization passes
     │ ✓ Business rules satisfied
     │
     ▼
┌────────────┐
│ FINALIZED  │ ◄─── Terminal state (immutable)
└────────────┘
     │
     │ (no transitions allowed)
     │
     ▼
   [END]
```

### Immutability Enforcement

Once an invoice reaches `FINALIZED` status:
- ✗ Cannot change status back to DRAFT
- ✗ Cannot modify invoice items
- ✗ Cannot change total amount
- ✗ Cannot change billing period
- ✗ Cannot delete invoice (soft or hard)
- ✓ Can view invoice details
- ✓ Can download PDF
- ✓ Can mark as PAID (status transition only)

## Security Architecture

### Defense in Depth

```
Layer 1: Route Middleware
├─ auth: Requires authenticated user
└─ tenant: Validates tenant context

Layer 2: Request Validation
├─ Invoice exists
├─ Invoice is DRAFT
├─ Invoice has valid data
└─ Billing period is valid

Layer 3: Policy Authorization
├─ User role check (SUPERADMIN, ADMIN, MANAGER)
├─ Tenant isolation (tenant_id match)
└─ Cross-tenant prevention

Layer 4: Business Logic
├─ Double-check invoice status
├─ Transaction management
└─ Exception handling

Layer 5: Database Constraints
├─ Foreign key constraints
├─ NOT NULL constraints
└─ Check constraints (if applicable)
```

### Tenant Isolation

```
Request → Middleware (TenantContext)
            │
            ├─ Set tenant_id in context
            │
            ▼
         Policy Check
            │
            ├─ Verify: invoice.tenant_id === user.tenant_id
            │
            ▼
         Service Layer
            │
            ├─ All queries scoped by tenant_id (TenantScope)
            │
            ▼
         Database
            │
            └─ Only returns records matching tenant_id
```

## Performance Considerations

### Query Optimization

**Route Model Binding**:
```sql
-- Single query to load invoice
SELECT * FROM invoices WHERE id = ? LIMIT 1
```

**Eager Loading** (if needed):
```php
Route::bind('invoice', function ($value) {
    return Invoice::with(['items', 'tenant'])->findOrFail($value);
});
```

### Caching Strategy

**No caching required** for finalization:
- Single-use operation
- State change operation
- Must always be fresh data

### Transaction Management

```php
DB::transaction(function () use ($invoice) {
    $invoice->status = InvoiceStatus::FINALIZED;
    $invoice->finalized_at = now();
    $invoice->save();
    
    // Additional operations if needed
});
```

## Testing Strategy

### Unit Tests
- Request validation logic
- Policy authorization logic
- Service finalization logic

### Feature Tests
- End-to-end finalization flow
- Authorization scenarios (different roles)
- Error handling scenarios
- Validation scenarios

### Integration Tests
- Database state verification
- Transaction rollback scenarios
- Concurrent finalization attempts

## Monitoring and Observability

### Key Metrics

1. **Success Rate**: Percentage of successful finalizations
2. **Response Time**: Time from request to response
3. **Authorization Failures**: Count of 403 responses
4. **Validation Failures**: Count of validation errors
5. **Exception Rate**: Count of unexpected exceptions

### Logging Points

1. **Service Layer**: BillingService logs finalization events
2. **Exception Handler**: Logs unexpected errors
3. **Policy Layer**: Can log authorization failures (if needed)

### Alerting Thresholds

- Success rate < 95%: Warning
- Response time > 1s: Warning
- Exception rate > 1%: Critical
- Authorization failures spike: Security alert

## Related Documentation

- [FinalizeInvoiceController API](../api/FINALIZE_INVOICE_CONTROLLER_API.md)
- [FinalizeInvoiceController Usage Guide](../controllers/FINALIZE_INVOICE_CONTROLLER_USAGE.md)
- [BillingService Architecture](./BILLING_SERVICE_ARCHITECTURE.md)
- [Multi-Tenancy Architecture](./MULTI_TENANCY_ARCHITECTURE.md)
- [Authorization Architecture](./AUTHORIZATION_ARCHITECTURE.md)
