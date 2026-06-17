# Payments Module Contract

> **AI agent usage:** Read this before changing invoice payment proof, confirmation, rejection, voiding, reconciliation, or payment reminders.

Updated on 2026-06-15.

## Purpose

Payments owns invoice payment records, tenant payment proofs, manual payments, confirmation/rejection/voiding, invoice balance recalculation, and payment reminder behavior.

## Owns

- Models: `InvoicePayment`, payment proof `Attachment` records.
- Actions: `SubmitTenantPaymentProof`, `CreateManualPayment`, `ConfirmInvoicePayment`, `RejectInvoicePayment`, `VoidInvoicePayment`, `RecalculateInvoicePaymentStatus`, `MarkOverdueInvoices`, `SendPaymentReminders`.
- Policy: `InvoicePaymentPolicy`.
- Enums: `PaymentStatus`, `PaymentMethod`, `InvoicePaymentStatus`.

## Public Actions

| Action | Purpose | Callers |
| --- | --- | --- |
| `SubmitTenantPaymentProof` | Tenant submits proof/reference for an invoice | tenant invoice portal |
| `CreateManualPayment` | Admin records a manual payment | payment resource, invoice record-payment action |
| `ConfirmInvoicePayment` | Confirm a pending payment and recalculate invoice status | payment resource, manual payment action, invoice record-payment action, tests |
| `RejectInvoicePayment` | Reject pending payment proof | payment resource |
| `VoidInvoicePayment` | Void pending or confirmed payment | payment resource |
| `SendPaymentReminders` | Queue overdue reminders when no pending review exists | command |

## Events And Side Effects

- audit mutations: `payment.proof_submitted`, `payment.confirmed`, `payment.rejected`, `payment.voided`;
- tenant/admin notifications for submitted, confirmed, rejected, overdue, and reminders;
- invoice payment status recalculation.

## Permissions

- `payments.view`;
- `payments.create`;
- `payments.confirm`;
- `payments.reject`;
- `payments.void`;
- `payments.upload_proof`;
- tenant portal proof upload permission for own invoices.

## Invariants

- confirmed amount must be positive;
- payment currency must match invoice currency;
- pending payments do not change paid balance;
- rejection requires a reason;
- voiding requires a reason and recalculates balance;
- tenants can only submit proof for their own organization invoice.

## Dependencies

Payments depends on billing invoices, attachments, audit, and notifications.

## Must Not

- confirm payments in Filament callbacks by setting status directly;
- update invoice balances without `RecalculateInvoicePaymentStatus`;
- expose internal payment notes to tenants;
- store sensitive proof files on a public disk.

## Tests And Scenarios

Primary tests:

- `tests/Feature/Billing/PaymentTrackingAndReconciliationTest.php`;
- tenant invoice presentation/download tests;
- architecture boundary tests for policies and action wiring.
