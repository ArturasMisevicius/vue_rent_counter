# Filament Guidelines

> **AI agent usage:** Read this before changing Filament resources, pages, actions, schemas, or tables.

Updated on 2026-06-15.

## Rule

Filament is the workspace UI layer. It can collect input, render tables/forms/infolists, call actions, and display notifications. It must not become the home of hidden business workflows.

## Allowed In Resources And Pages

- form schema;
- table schema;
- infolist schema;
- filters;
- authorization hooks;
- action wiring that delegates to action classes;
- navigation and labels;
- simple presentation formatting.

## Not Allowed

- payment confirmation logic in a table callback;
- invoice calculation or tariff selection in a resource;
- document authorization by UI visibility only;
- direct email or webhook dispatch from resource callbacks;
- direct ledger or accounting posting;
- loops mutating multiple financial records without an action.

## Action Callback Pattern

Preferred:

```php
Action::make('confirmPayment')
    ->requiresConfirmation()
    ->action(function (InvoicePayment $record, ConfirmInvoicePayment $action): void {
        $action->handle($record, PaymentResource::currentUser());
    });
```

Avoid:

```php
$record->forceFill(['status' => 'confirmed'])->save();
$record->invoice->update(['paid_amount' => $record->amount]);
```

## Query Rules

Resources should override `getEloquentQuery()` and eager load relationships used by table columns, infolists, and filters. Keep row formatting from causing lazy-loaded query bursts.
