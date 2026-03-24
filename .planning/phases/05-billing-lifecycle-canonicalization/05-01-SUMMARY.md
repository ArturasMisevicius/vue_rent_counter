# 05-01 Summary

- Plan: `05-billing-lifecycle-canonicalization/05-01-PLAN.md`
- Wave: `1`
- Status: Completed
- Branch: `main`

## Task 1 — Due-date-first overdue policy

- Status: Done
- PROBLEM
  - Overdue logic diverged between invoice views, dashboards, and the outstanding balances report because some surfaces treated `billing_period_end` as the primary aging date even when an explicit `due_date` existed.
- SOLUTION
  - Canonicalized overdue behavior in `app/Models/Invoice.php` with shared overdue helpers and updated `app/Filament/Support/Admin/Reports/OutstandingBalancesReportBuilder.php` to consume the same due-date-first policy.
  - Added `tests/Feature/Billing/InvoiceOverduePolicyTest.php` and kept `tests/Feature/Billing/ReportsTest.php` aligned with the shared contract.
- QUERY DELTA
  - Outstanding-balance reporting now derives invoice status and overdue days from one canonical invoice policy instead of ad hoc report-local rules.
- REUSABLE SNIPPET
  - `Invoice::isOverdue()`, `Invoice::overdueDays()`, and `Invoice::effectiveStatus()` are now the reusable billing-aging contract.
- BLADE USAGE
  - No Blade changes.
- FILAMENT INTEGRATION
  - Dashboard and report surfaces now read the same overdue semantics as invoice detail pages.
- TESTS
  - `php artisan test tests/Feature/Billing/InvoiceOverduePolicyTest.php tests/Feature/Billing/ReportsTest.php --compact`
- CAVEATS
  - The fallback to `billing_period_end` is preserved only when `due_date` is absent.

## Task 2 — Preview and finalization parity

- Status: Done
- PROBLEM
  - Billing preview and invoice generation could drift on due-date defaults and selected-candidate outcomes, creating different results for the same billing period.
- SOLUTION
  - Added `tests/Feature/Billing/BillingPreviewFinalizationParityTest.php`.
  - Locked preview and generation to the same prepared candidate payload contract in `app/Services/Billing/BillingService.php`, which already centralizes candidate selection and due-date defaults for the bulk billing path.
- QUERY DELTA
  - No additional query paths were introduced; this task removed behavioral drift in orchestration defaults.
- REUSABLE SNIPPET
  - `BillingService::preparedBulkInvoicePayloads()` is now the reusable parity seam for preview and generation outcomes.
- BLADE USAGE
  - No Blade changes.
- FILAMENT INTEGRATION
  - Admin bulk invoice preview and finalization now reflect the same candidate set and due-date behavior.
- TESTS
  - `php artisan test tests/Feature/Billing/BillingPreviewFinalizationParityTest.php tests/Feature/Admin/BulkInvoiceGenerationTest.php tests/Feature/Billing/BillingModuleTest.php --compact`
- CAVEATS
  - This task tightened orchestration parity rather than replacing the existing billing service seam.

## Task 3 — Canonical rounding and allocation

- Status: Done
- PROBLEM
  - Shared-service billing could lose or misplace remainder cents because rounding and proportional allocation were not explicit or deterministic.
- SOLUTION
  - Added `tests/Unit/Support/Billing/MoneyAllocationPolicyTest.php`.
  - Extended `app/Services/Billing/UniversalBillingCalculator.php`, `app/Services/Billing/SharedServiceCostDistributorService.php`, and `app/Services/Billing/BillingService.php` with a deterministic largest-remainder allocation policy.
- QUERY DELTA
  - No query changes; this task standardized in-memory money math.
- REUSABLE SNIPPET
  - `UniversalBillingCalculator::allocate()` is now the reusable remainder-safe allocation primitive.
- BLADE USAGE
  - No Blade changes.
- FILAMENT INTEGRATION
  - Generated invoices now keep shared-service totals and itemized allocations consistent across preview and finalized records.
- TESTS
  - `php artisan test tests/Unit/Support/Billing/MoneyAllocationPolicyTest.php tests/Unit/Services/BillingServiceTest.php --compact`
- CAVEATS
  - Allocation determinism depends on the stable peer ordering added in the billing service context builder.

## Task 4 — Eligibility and candidate selection parity

- Status: Done
- PROBLEM
  - Meter-reading eligibility and invoice-candidate selection were not guaranteed to reject the same invalid reading conditions across tenant, admin, and billing-generation paths.
- SOLUTION
  - Added `tests/Feature/Billing/BillingEligibilityConsistencyTest.php`.
  - Updated `app/Services/Billing/BillingService.php` so non-billable measurement contexts skip invoice candidates with an explicit `ineligible_meter_readings` reason.
  - Preserved parity with the shared validation updates already applied to admin and tenant meter-reading flows.
- QUERY DELTA
  - No additional queries were added; this task standardized candidate filtering using existing eager-loaded billing inputs.
- REUSABLE SNIPPET
  - The `billable` flag emitted by the billing line-item payload is now the reusable candidate-eligibility contract.
- BLADE USAGE
  - No Blade changes.
- FILAMENT INTEGRATION
  - Preview and generation now skip the same ineligible assignments instead of rendering candidates the finalization path cannot actually bill.
- TESTS
  - `php artisan test tests/Feature/Billing/BillingEligibilityConsistencyTest.php tests/Feature/Admin/MeterReadingValidationRulesTest.php tests/Feature/Tenant/TenantSubmitReadingTest.php --compact`
- CAVEATS
  - The billing service still contains dense orchestration logic, but the eligibility rule is now covered by a direct regression contract.

## Task 5 — Invoice explainability contract

- Status: Done
- PROBLEM
  - Tenant history, staff invoice detail, and invoice PDF rendering did not reliably expose the same itemized breakdown and payment evidence for the same invoice.
- SOLUTION
  - Reused `app/Services/Billing/InvoicePresentationService.php` as the canonical invoice breakdown source.
  - Fixed `app/Livewire/Tenant/InvoiceHistory.php` so tenant history passes stable per-invoice presentation data to `resources/views/components/shared/invoice-summary.blade.php`.
  - Updated `app/Filament/Support/Tenant/Portal/TenantInvoiceIndexQuery.php` to eager load payments for the tenant invoice surface.
  - Updated `app/Filament/Resources/Invoices/Schemas/InvoiceInfolist.php` so staff invoice detail renders the same explainable breakdown contract instead of a totals-only summary.
  - Kept `app/Services/Billing/InvoicePdfService.php` on the same presentation service so PDF rows and summary amounts match the UI contract.
  - Verified the shared contract with `tests/Feature/Tenant/InvoiceExplainabilityContractTest.php`.
- QUERY DELTA
  - Tenant invoice history now eagerly loads payment rows once per paginated invoice query instead of leaving the explainability surface to ad hoc relation loading.
- REUSABLE SNIPPET
  - `InvoicePresentationService::present()` is now the canonical invoice explainability contract for tenant, admin, and PDF surfaces.
- BLADE USAGE
  - The shared `invoice-summary` component now consumes prepared presentation data instead of deriving the invoice contract independently.
- FILAMENT INTEGRATION
  - The Filament invoice detail infolist now renders the same explainable invoice summary used by tenant history and PDF generation.
- TESTS
  - `php artisan test tests/Feature/Tenant/InvoiceExplainabilityContractTest.php tests/Feature/Tenant/TenantInvoiceHistoryTest.php tests/Feature/Admin/InvoicesResourceTest.php --compact`
- CAVEATS
  - The history page now renders richer invoice content per row, so future pagination or mobile refinements should preserve the shared presentation contract instead of re-implementing it.

## Completion self-check

- [x] Overdue behavior is due-date-first everywhere it is exposed
- [x] Preview and finalization produce aligned billing outcomes
- [x] Shared-service allocations use one deterministic money policy
- [x] Meter-reading eligibility feeds one billing-candidate contract
- [x] Tenant, staff, and PDF invoice surfaces now share one explainable invoice presentation
- [x] Full Phase 5 verification bundle passed
