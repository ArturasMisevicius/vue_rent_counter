# Events And Outbox

> **AI agent usage:** Use this when adding asynchronous side effects, notifications, webhooks, exports, reminders, ledger posting, or external integrations.

Updated on 2026-06-15.

## Rule

State changes happen in actions. Side effects happen through events, jobs, notifications, or an outbox-style replay mechanism.

## Event Naming

Use past-tense domain event names:

- `InvoiceApproved`
- `InvoiceSent`
- `PaymentConfirmed`
- `KycDocumentRejected`
- `TenantMoveOutCompleted`

For audit metadata, use stable mutation keys:

```text
payment.confirmed
billing_review.invoice.sent_to_tenant
tenant_move_out.completed
```

## Handler Requirements

Every queued handler or job should document or encode:

- queue name where relevant;
- attempts, backoff, and timeout for critical work;
- idempotency key or duplicate prevention;
- related organization and record IDs;
- failure behavior;
- replay safety.

## Outbox Direction

Tenanto does not yet have a fully generalized transactional outbox for every workflow. New high-risk side effects should still be designed as if they may be replayed later: payloads should be stable, idempotent, and scoped to organization/record IDs.

## Forbidden Shortcuts

- Do not send email directly from Filament resource callbacks.
- Do not dispatch external webhooks directly from controllers or table actions.
- Do not post accounting entries from UI code.
- Do not hide critical side effects in model observers.

## Tests

Side-effect tests should assert the action wrote the state first, then queued or sent the side effect through the expected channel. Use `Notification::fake()`, `Queue::fake()`, or event fakes as appropriate.
