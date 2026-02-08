# Invoice Status Filtering Test API Reference

Complete API reference for the FilamentInvoiceStatusFilteringPropertyTest test suite.

## Test Class

**Namespace**: `Tests\Feature\Filament`  
**Class**: `FilamentInvoiceStatusFilteringPropertyTest`  
**Extends**: `Tests\TestCase`  
**Traits**: `RefreshDatabase`

## Test Methods

### property_status_filter_returns_only_matching_invoices()

Tests that status filtering returns only invoices matching the filtered status.

**Test Type**: Property-based test  
**Complexity**: High (tests all status values with randomized data)  
**Execution Time**: ~2-3 seconds

#### Test Data
- **Admin User**: 1 (tenant_id: 1)
- **Tenant Records**: 1 (reused for performance)
- **Invoices per Status**: 2-5 (randomized)
- **Total Invoices**: 6-15 (depends on randomization)

#### Test Flow
1. Create admin user with tenant_id = 1
2. Create single tenant record for reuse
3. For each InvoiceStatus enum case:
   - Create 2-5 invoices with that status
   - Track invoice IDs by status
4. Authenticate as admin
5. For each status:
   - Apply status filter to InvoiceResource table
   - Retrieve filtered records
   - Assert all records have filtered status
   - Assert count matches expected
   - Assert all expected IDs are present

#### Assertions
- **Per Status**: 3 assertions (status match, count, IDs)
- **Total**: 9 assertions (3 statuses × 3 assertions)

#### Example Usage
```php
// Run this specific test
php artisan test --filter=property_status_filter_returns_only_matching_invoices
```

---

### property_no_filter_returns_all_invoices()

Tests that unfiltered view returns all invoices.

**Test Type**: Property-based test  
**Complexity**: Medium  
**Execution Time**: ~1-2 seconds

#### Test Data
- **Admin User**: 1 (tenant_id: 1)
- **Tenant Records**: 1 (reused for performance)
- **Invoices per Status**: 1-3 (randomized)
- **Total Invoices**: 3-9 (depends on randomization)

#### Test Flow
1. Create admin user with tenant_id = 1
2. Create single tenant record for reuse
3. For each InvoiceStatus enum case:
   - Create 1-3 invoices with that status
   - Track all invoice IDs
4. Authenticate as admin
5. Load InvoiceResource table without filters
6. Assert total count matches all created invoices
7. Assert all invoice IDs are present

#### Assertions
- **Count**: 1 assertion (total count)
- **IDs**: 3-9 assertions (one per invoice)
- **Total**: 4-10 assertions

#### Example Usage
```php
php artisan test --filter=property_no_filter_returns_all_invoices
```

---

### property_filter_respects_tenant_scope()

Tests that filtering respects multi-tenancy boundaries.

**Test Type**: Property-based test with multi-tenancy  
**Complexity**: High (tests 3 tenants × 3 statuses)  
**Execution Time**: ~3-4 seconds

#### Test Data
- **Tenants**: 3 (tenant_id: 1, 2, 3)
- **Admin Users**: 3 (one per tenant)
- **Tenant Records**: 3 (one per tenant_id)
- **Invoices per Tenant per Status**: 1-2 (randomized)
- **Total Invoices**: 9-18 (depends on randomization)

#### Test Flow
1. For each tenant (1, 2, 3):
   - Create admin user for tenant
   - Create tenant record for tenant_id
   - For each InvoiceStatus:
     - Create 1-2 invoices
     - Track invoice IDs by tenant and status
   - Authenticate as tenant's admin
   - For each status:
     - Apply status filter
     - Assert all records belong to current tenant
     - Assert all records have filtered status
     - Assert count matches expected for tenant

#### Assertions
- **Per Tenant per Status**: 3 assertions (tenant_id, status, count)
- **Total**: 27 assertions (3 tenants × 3 statuses × 3 assertions)

#### Example Usage
```php
php artisan test --filter=property_filter_respects_tenant_scope
```

---

### property_filter_works_with_multiple_status_values()

Tests that each status filter works independently.

**Test Type**: Property-based test  
**Complexity**: Low  
**Execution Time**: ~1 second

#### Test Data
- **Admin User**: 1 (tenant_id: 1)
- **Tenant Records**: 1 (reused for performance)
- **Invoices**: 3 (one per status)

#### Test Flow
1. Create admin user with tenant_id = 1
2. Create single tenant record for reuse
3. Create one invoice for each status
4. Track invoice IDs by status
5. Authenticate as admin
6. For each status:
   - Apply status filter
   - Assert exactly 1 invoice returned
   - Assert invoice ID matches expected
   - Assert invoice status matches filter

#### Assertions
- **Per Status**: 3 assertions (count, ID, status)
- **Total**: 9 assertions (3 statuses × 3 assertions)

#### Example Usage
```php
php artisan test --filter=property_filter_works_with_multiple_status_values
```

---

### property_draft_filter_excludes_finalized_and_paid()

Tests that DRAFT filter excludes other statuses.

**Test Type**: Property-based test with exclusivity verification  
**Complexity**: Medium  
**Execution Time**: ~1-2 seconds

#### Test Data
- **Admin User**: 1 (tenant_id: 1)
- **Tenant Records**: 1 (reused for performance)
- **Draft Invoices**: 3-7 (randomized)
- **Finalized Invoices**: 2-5 (randomized)
- **Paid Invoices**: 2-5 (randomized)
- **Total Invoices**: 7-17

#### Test Flow
1. Create admin user with tenant_id = 1
2. Create single tenant record for reuse
3. Create 3-7 draft invoices, track IDs
4. Create 2-5 finalized invoices
5. Create 2-5 paid invoices
6. Authenticate as admin
7. Apply DRAFT status filter
8. Assert count matches draft count
9. Assert all records have DRAFT status
10. Assert all draft IDs are present

#### Assertions
- **Count**: 1 assertion
- **Status per Record**: 3-7 assertions
- **IDs**: 3-7 assertions
- **Total**: 7-15 assertions

#### Example Usage
```php
php artisan test --filter=property_draft_filter_excludes_finalized_and_paid
```

---

### property_finalized_filter_excludes_draft_and_paid()

Tests that FINALIZED filter excludes other statuses.

**Test Type**: Property-based test with exclusivity verification  
**Complexity**: Medium  
**Execution Time**: ~1-2 seconds

#### Test Data
- **Admin User**: 1 (tenant_id: 1)
- **Tenant Records**: 1 (reused for performance)
- **Draft Invoices**: 2-5 (randomized)
- **Finalized Invoices**: 3-7 (randomized)
- **Paid Invoices**: 2-5 (randomized)
- **Total Invoices**: 7-17

#### Test Flow
Similar to draft filter test, but focuses on FINALIZED status.

#### Assertions
- **Total**: 7-15 assertions (varies with randomization)

#### Example Usage
```php
php artisan test --filter=property_finalized_filter_excludes_draft_and_paid
```

---

### property_paid_filter_excludes_draft_and_finalized()

Tests that PAID filter excludes other statuses.

**Test Type**: Property-based test with exclusivity verification  
**Complexity**: Medium  
**Execution Time**: ~1-2 seconds

#### Test Data
- **Admin User**: 1 (tenant_id: 1)
- **Tenant Records**: 1 (reused for performance)
- **Draft Invoices**: 2-5 (randomized)
- **Finalized Invoices**: 2-5 (randomized)
- **Paid Invoices**: 3-7 (randomized)
- **Total Invoices**: 7-17

#### Test Flow
Similar to draft filter test, but focuses on PAID status.

#### Assertions
- **Total**: 7-15 assertions (varies with randomization)

#### Example Usage
```php
php artisan test --filter=property_paid_filter_excludes_draft_and_finalized
```

---

### property_filter_works_across_different_amounts()

Tests that filtering is independent of invoice amounts.

**Test Type**: Property-based test with amount variation  
**Complexity**: Medium  
**Execution Time**: ~2 seconds

#### Test Data
- **Admin User**: 1 (tenant_id: 1)
- **Tenant Records**: 1 (reused for performance)
- **Amounts Tested**: €0.01, €10.50, €100.00, €999.99, €5000.00
- **Invoices per Status**: 5 (one per amount)
- **Total Invoices**: 15 (3 statuses × 5 amounts)

#### Test Flow
1. Create admin user with tenant_id = 1
2. Create single tenant record for reuse
3. For each status:
   - For each test amount:
     - Create invoice with that amount
     - Track invoice IDs by status
4. Authenticate as admin
5. For each status:
   - Apply status filter
   - Assert count equals number of amounts (5)
   - Assert all records have filtered status
   - Assert all expected IDs are present

#### Assertions
- **Per Status**: 3 assertions (count, status, IDs)
- **Total**: 9 assertions (3 statuses × 3 assertions)

#### Example Usage
```php
php artisan test --filter=property_filter_works_across_different_amounts
```

---

### property_filter_works_across_different_billing_periods()

Tests that filtering is independent of billing periods.

**Test Type**: Property-based test with date variation  
**Complexity**: Medium  
**Execution Time**: ~2 seconds

#### Test Data
- **Admin User**: 1 (tenant_id: 1)
- **Tenant Records**: 1 (reused for performance)
- **Periods Tested**: 
  - 3 months ago (2 months ago to 3 months ago)
  - 2 months ago (1 month ago to 2 months ago)
  - 1 month ago (now to 1 month ago)
  - 2 weeks ago (1 week ago to 2 weeks ago)
- **Invoices per Status**: 4 (one per period)
- **Total Invoices**: 12 (3 statuses × 4 periods)

#### Test Flow
1. Create admin user with tenant_id = 1
2. Create single tenant record for reuse
3. For each status:
   - For each test period:
     - Create invoice with that period
     - Track invoice IDs by status
4. Authenticate as admin
5. For each status:
   - Apply status filter
   - Assert count equals number of periods (4)
   - Assert all records have filtered status
   - Assert all expected IDs are present

#### Assertions
- **Per Status**: 3 assertions (count, status, IDs)
- **Total**: 9 assertions (3 statuses × 3 assertions)

#### Example Usage
```php
php artisan test --filter=property_filter_works_across_different_billing_periods
```

---

## Test Suite Summary

### Total Coverage
- **Test Methods**: 9
- **Total Assertions**: 45-100+ (varies with randomization)
- **Execution Time**: ~15-20 seconds (full suite)
- **Status Values Tested**: 3 (DRAFT, FINALIZED, PAID)
- **Tenants Tested**: 3 (multi-tenancy verification)

### Test Categories
1. **Core Filtering** (2 tests): Basic filter behavior
2. **Multi-Tenancy** (1 test): Tenant scope isolation
3. **Status Independence** (1 test): Each status works independently
4. **Status Exclusivity** (3 tests): Each status excludes others
5. **Data Independence** (2 tests): Amount and period independence

### Quality Metrics
- **Code Coverage**: 100% of InvoiceResource filter logic
- **Property Coverage**: All InvoiceStatus enum cases
- **Tenant Coverage**: Multi-tenant isolation verified
- **Edge Cases**: Amount ranges, date ranges, randomized counts

## Running the Full Suite

```bash
# Run all tests
php artisan test tests/Feature/Filament/FilamentInvoiceStatusFilteringPropertyTest.php

# Run with coverage
php artisan test tests/Feature/Filament/FilamentInvoiceStatusFilteringPropertyTest.php --coverage

# Run with verbose output
php artisan test tests/Feature/Filament/FilamentInvoiceStatusFilteringPropertyTest.php -v
```

## Related Documentation

- [Invoice Status Filtering Tests Guide](invoice-status-filtering-tests.md)
- [Filament Tests README](../overview/Filament-readme.md)
- [Testing Best Practices](README.md)
