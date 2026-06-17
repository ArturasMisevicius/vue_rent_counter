# ADR 0003: UI Layer Must Be Thin

## Status

Accepted

## Date

2026-06-15

## Context

Tenanto uses Filament, Livewire, Blade, console commands, and routes. These surfaces are effective for input and presentation, but dangerous as owners of payment, invoice, document, KYC, or tenant-access rules.

## Decision

UI code collects input, calls actions/query presenters, handles responses, and displays messages. It must not own business workflows.

## Alternatives Considered

### Rich Resource/Page Classes

- Pros: fewer files for small CRUD.
- Cons: business rules become hard to reuse and audit.
- Rejected for critical workflows.

### Separate Backend Application

- Pros: physical separation.
- Cons: unnecessary complexity before module contracts mature.
- Rejected for now.

## Consequences

- Filament resources may have schemas and action wiring, not workflow logic.
- Livewire components must delegate to actions/presenters.
- Controllers and route closures must stay thin.
- Architecture tests scan UI surfaces for direct side-effect shortcuts.
