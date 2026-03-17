---
name: tenanto-billing-reporting
description: Use for Tenanto billing, tariff, meter reading, invoice, statement, or reporting changes in the current Filament-first codebase.
allowed-tools: Read, Write, Edit, Glob, Grep, Bash
---

# Tenanto Billing Reporting

## Use This Skill When

- Implementing or changing meter reading, tariff, billing, invoice, payment, or subscription logic.
- Updating reporting or export flows, including PDF or spreadsheet style outputs.
- Refactoring financial calculations and related read models.

## Domain Focus

- Accuracy and determinism of calculations
- Reproducible invoice generation and finalization behavior
- Consistent handling of periods, statuses, and edge cases
- Auditable report and export outputs

## Project Anchors

- Billing models: `app/Models/Invoice.php`, `app/Models/BillingRecord.php`, `app/Models/InvoiceItem.php`, `app/Models/InvoicePayment.php`, `app/Models/Tariff.php`, `app/Models/Meter.php`, `app/Models/MeterReading.php`
- Billing actions: `app/Filament/Actions/Admin/Invoices/*`, `app/Filament/Actions/Admin/MeterReadings/*`, `app/Filament/Actions/Tenant/Readings/*`, `app/Filament/Actions/Tenant/Invoices/*`
- Billing and reporting support: `app/Filament/Support/Admin/Invoices/*`, `app/Filament/Support/Admin/Reports/*`, `app/Filament/Support/Admin/ReadingValidation/*`
- Pages and widgets: `app/Filament/Pages/GenerateBulkInvoices.php`, `app/Filament/Pages/Reports.php`, `app/Filament/Widgets/Admin/*`
- Views and resources: invoice, meter, tariff, report, and tenant billing Blade or Filament resources
- Audit trail: `app/Models/InvoiceGenerationAudit.php` and the `invoice_generation_audits` table

## Implementation Checklist

1. Define the business rule change with explicit input and output behavior.
2. Confirm period boundaries, statuses, and rounding behavior.
3. Apply the change in the model scope, Filament action, or support/query layer first, then wire the UI surface.
4. Preserve invoice-generation idempotency for tenant plus period draft runs.
5. Keep generation audits complete: actor, period, totals, metadata.
6. Add regression tests for happy path plus edge and failure paths.
7. Validate report or export impact if PDF, CSV, or spreadsheet flows are touched.
8. Prefer aggregate helpers like `withCount()`, `withExists()`, and explicit `select()` lists when building reporting surfaces.

## Test Scenarios To Cover

- Correct totals under normal conditions
- Boundary periods and missing or late readings
- Status transitions such as draft, finalized, paid, overdue, or void
- Multi-tenant safety for billing and report access
- Export or report generation does not regress format-critical fields

## Completion Checklist

- [ ] Calculation behavior documented in tests.
- [ ] Edge cases covered.
- [ ] Invoice, report, and export paths validated.
- [ ] No tenant boundary regression introduced.
