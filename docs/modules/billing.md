# Billing Module Contract

> **AI agent usage:** Read this before changing invoice generation, invoice review, billing periods, reading-driven invoicing, invoice PDFs, or billing reports. Verify live code and tests before editing behavior.

Updated on 2026-06-15.

## Purpose

Billing owns billing periods, draft/final invoices, invoice review, invoice calculation, invoice visibility, reminders, and billing reports.

## Owns

- Models: `BillingPeriod`, `BillingGenerationLog`, `BillingGenerationLogItem`, `Invoice`, `InvoiceItem`, `InvoiceReminderLog`, `InvoiceEmailLog`.
- Actions: `GenerateDraftInvoicesForBillingPeriod`, `OpenReadingInvoiceCycleAction`, `ApproveInvoice`, `SendInvoiceToTenant`, invoice draft/finalize actions.
- Services/support: `BillingService`, `InvoiceService`, `UniversalBillingCalculator`, billing review presenters/builders.
- Policies: `InvoicePolicy`, `BillingPeriodPolicy`, `BillingGenerationLogPolicy`.

## Public Actions

| Action | Purpose | Callers |
| --- | --- | --- |
| `GenerateDraftInvoicesForBillingPeriod` | Create/update monthly draft invoices | command, Filament billing periods |
| `OpenReadingInvoiceCycleAction` | Open reading-request invoice cycle | command, Filament |
| `ApproveInvoice` | Approve a review-ready invoice | billing review pages |
| `SendInvoiceToTenant` | Queue/send finalized invoice communication | billing review and invoice UI |
| `FinalizeInvoiceAction` | Finalize draft invoices | invoice resource/tests |
| `RecordInvoicePaymentAction` | Delegate invoice payment recording to `CreateManualPayment` and `ConfirmInvoicePayment` | invoice resource/tests |

## Events And Side Effects

- invoice generation logs;
- invoice email jobs;
- reading reminders;
- invoice audit/activity logs;
- tenant notifications for invoice-ready and reminders.

## Permissions

- `invoices.view`;
- `invoices.generate`;
- `invoices.recalculate`;
- `invoices.approve`;
- `invoices.send`;
- `invoices.cancel`;
- `invoices.void`.

## Invariants

- finalized invoices are not arbitrary editable drafts;
- tenant-visible invoices must be scoped to tenant and organization;
- readings only affect invoice totals after approval/correction;
- duplicate active invoices for the same assignment/period are skipped or rejected;
- invoice PDF/download access goes through authorized routes/actions.

## Dependencies

Billing may depend on tenants, properties, meters, tariffs, documents, notifications, and payments read state.

## Must Not

- directly post accounting entries;
- send email from Filament table callbacks;
- use unscoped invoice lookup for tenant or organization data;
- mutate payment state outside payment actions.

## Tests And Scenarios

Primary tests:

- `tests/Feature/Billing`;
- `tests/Feature/Tenant/TenantInvoiceHistoryTest.php`;
- `tests/Feature/Tenant/InvoicePdfLocalizationTest.php`;
- architecture guardrails under `tests/Feature/Architecture`.
