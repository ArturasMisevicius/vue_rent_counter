---
name: tenanto-billing-reporting
description: Tenanto billing, tariffs, meter readings, invoices, and export/report workflow playbook. Use for calculation logic and reporting features.
allowed-tools: Read, Write, Edit, Glob, Grep, Bash
---

# Tenanto Billing Reporting

## Use This Skill When

- Implementing or changing meter reading, tariff, billing, invoice, or subscription logic.
- Updating reporting/export flows (PDF, spreadsheet, scheduled exports).
- Refactoring financial calculations and related domain services.

## Domain Focus

- Accuracy and determinism of calculations.
- Reproducible invoice generation and finalization behavior.
- Consistent handling of periods, statuses, and edge cases.
- Auditable export/report outputs.

## Project Anchors

- Billing/services: `app/Services/Billing*`, `app/Services/Tariff*`, `app/Services/Meter*`, `app/Services/Invoice*`.
- Repositories/actions: `app/Repositories/*`, `app/Actions/GenerateInvoiceAction.php`.
- Enums/value objects: `app/Enums/*`, `app/ValueObjects/*`.
- Reports and exports: `app/Console/Commands/Export*`, `app/Services/ExportService.php`, `app/Services/PdfReportService.php`.
- Views/resources: invoice/report blade and Filament resources.
- Audit trail: `app/Models/InvoiceGenerationAudit.php` and `invoice_generation_audits` table.

## Implementation Checklist

1. Define business rule change with explicit input/output behavior.
2. Confirm period boundaries, statuses, and rounding behavior.
3. Apply change in service/domain layer first, then resource/controller wiring.
4. Preserve invoice-generation idempotency for tenant + period draft runs.
5. Keep generation audits complete (actor, period, totals, metadata).
6. Add regression tests for happy path + edge/failure paths.
7. Validate report/export impact (PDF/XLSX generation paths if touched).

## Test Scenarios To Cover

- Correct totals under normal conditions.
- Boundary periods and missing/late readings.
- Status transitions (draft/finalized/paid/overdue as applicable).
- Multi-tenant safety for billing/report access.
- Export generation does not regress format-critical fields.

## Completion Checklist

- [ ] Calculation behavior documented in tests.
- [ ] Edge cases covered.
- [ ] Invoice/report/export paths validated.
- [ ] No tenant boundary regression introduced.
