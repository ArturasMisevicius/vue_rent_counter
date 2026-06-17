# Action Class Conventions

> **AI agent usage:** Use this before adding or changing a business workflow. Check existing actions in `app/Filament/Actions` and `app/Actions/Billing` first.

Updated on 2026-06-15.

## Rule

One business workflow should have one authoritative action path. Filament, Livewire, controllers, jobs, commands, and tests should call that path instead of reimplementing the workflow.

## Naming

Use verb plus noun:

- `ConfirmInvoicePayment`
- `SendInvoiceToTenant`
- `CorrectMeterReading`
- `GenerateDraftInvoicesForBillingPeriod`
- `AcceptTenantInvitation`

Avoid vague names like `ProcessData`, `HandleInvoice`, or `DoBilling`.

## Shape

An important action should define:

- input contract through a typed method signature, Form Request validation, or a DTO;
- actor argument for authorization;
- business validation and invariant guards;
- transaction boundary for writes;
- audit record for sensitive changes;
- events, notifications, or jobs after the state change;
- result model or result object;
- focused tests for success, permission failure, business failure, and organization isolation.

Example:

```php
final readonly class ConfirmInvoicePayment
{
    public function handle(InvoicePayment $payment, User $actor): InvoicePayment
    {
        // authorize
        // validate status and currency
        // transact payment and invoice state
        // audit
        // notify after commit path
        // return fresh model/result
    }
}
```

## Placement

Use the current project placement:

- Filament/admin UI workflow actions: `app/Filament/Actions`;
- tenant portal actions: `app/Filament/Actions/Tenant*`;
- current billing payment actions: `app/Actions/Billing`;
- pure support/read models: `app/Filament/Support` or established service namespaces.

Do not add a new base action namespace until an ADR approves the migration plan.

## Actor Handling

Prefer explicit actors:

```php
$action->handle($invoice, $actor, $data);
```

Avoid hidden globals inside reusable actions:

```php
$actor = auth()->user();
```

Some older Filament-bound actions still resolve `auth()` as a fallback. New reusable actions should not extend that pattern.

## Transactions And Side Effects

Keep the core mutation in the transaction. Send notifications, dispatch jobs, or call external services after the transaction unless the operation is explicitly designed for outbox/replay.

## Tests

Every new critical action needs:

- happy path;
- policy/permission denial;
- organization isolation;
- business invariant failure;
- event/audit/notification assertion when applicable.
