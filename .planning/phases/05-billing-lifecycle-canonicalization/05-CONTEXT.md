# Phase 5: Billing Lifecycle Canonicalization - Context

**Gathered:** 2026-03-19
**Status:** Ready for planning

<domain>
## Phase Boundary

Phase 5 makes invoice aging, billing preview, billing finalization, money semantics, meter-reading eligibility, and invoice explainability internally consistent. It is the billing-rules extraction phase, built on the earlier safety, boundary, read, and mutation work.

</domain>

<decisions>
## Implementation Decisions

### Overdue and aging policy
- `due_date` is the canonical overdue boundary whenever it exists.
- Any fallback to `billing_period_end` or other dates must be explicit and test-covered, not implicit.

### Preview and finalization policy
- Preview and finalization should share the same candidate-selection and calculation pipeline.
- Phase 5 should remove duplicate billing branches from large orchestration services instead of layering new conditions on top.

### Money policy
- Rounding and allocation rules should become explicit reusable policy code rather than ad hoc math scattered across services.
- The billing layer should avoid float drift and make currency rounding behavior testable in isolation.

### Invoice explainability
- Tenant- and staff-facing invoice views should present the same understandable bill breakdown for the same invoice.
- Downloaded artifacts should be stable representations of the same canonical invoice data, not alternate calculations.

</decisions>

<canonical_refs>
## Canonical References

- `.planning/ROADMAP.md`
- `.planning/REQUIREMENTS.md`
- `.planning/codebase/CONCERNS.md`
- `app/Services/Billing/BillingService.php`
- `app/Services/Billing/InvoiceService.php`
- `app/Services/Billing/UniversalBillingCalculator.php`
- `app/Services/Billing/SharedServiceCostDistributorService.php`
- `app/Filament/Support/Admin/Invoices/BulkInvoicePreviewBuilder.php`
- `app/Filament/Support/Admin/Reports/OutstandingBalancesReportBuilder.php`
- `app/Services/Billing/InvoicePdfService.php`
- `tests/Unit/Services/BillingServiceTest.php`
- `tests/Feature/Billing/BillingModuleTest.php`
- `tests/Feature/Billing/ReportsTest.php`
- `tests/Feature/Admin/BulkInvoiceGenerationTest.php`
- `tests/Feature/Tenant/TenantInvoiceHistoryTest.php`

</canonical_refs>

<code_context>
## Existing Code Insights

- The concerns audit already identifies one concrete overdue bug in `OutstandingBalancesReportBuilder`, making that the cleanest starting point for canonical due-date behavior.
- Billing orchestration is concentrated in a few large services, so Phase 5 should extract smaller collaborators instead of extending those classes further.
- The repository already has useful billing and invoice feature coverage; Phase 5 should extend those tests into explicit parity and money-policy contracts.

</code_context>

---

*Phase: 05-billing-lifecycle-canonicalization*
*Context gathered: 2026-03-19*
