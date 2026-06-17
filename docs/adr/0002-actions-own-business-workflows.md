# ADR 0002: Actions Own Business Workflows

## Status

Accepted

## Date

2026-06-15

## Context

Billing, payment, reading, tenant access, document, KYC, and support workflows are called from Filament, Livewire, commands, jobs, and tests. If each surface mutates models directly, rules diverge and tests can create impossible states.

## Decision

Business workflows must be owned by named action classes. UI, commands, jobs, API endpoints, and tests should call those actions.

Current placement is:

- `app/Filament/Actions` for established Filament and tenant portal workflows;
- `app/Actions/Billing` for current billing-payment lifecycle actions;
- no new broad base action namespace without a migration ADR.

## Alternatives Considered

### Model Methods

- Pros: discoverable from the model.
- Cons: encourages large models and hidden cross-aggregate workflows.
- Rejected for workflows.

### UI Callbacks

- Pros: fast for one screen.
- Cons: duplicates behavior across surfaces.
- Rejected for business workflows.

## Consequences

- New workflows need action tests.
- Existing direct mutations should be refactored gradually.
- Actions must handle authorization, validation, transaction boundaries, audit, and side effects.
