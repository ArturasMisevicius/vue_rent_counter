# ADR 0008: Domain Validation And Invariants

## Status

Accepted

## Date

2026-06-15

## Context

Billing and property management rely on invariants such as positive payments, matching currency, tenant ownership, private file visibility, valid reading transitions, and immutable finalized invoice behavior.

## Decision

Domain invariants belong in actions, policies, Form Requests, enums, model scopes/helpers, and dedicated support services. UI validation may improve UX, but it does not replace backend validation.

## Alternatives Considered

### Frontend/Filament-Only Validation

- Pros: quick feedback.
- Cons: bypassable and inconsistent across surfaces.
- Rejected.

### Database Constraints Only

- Pros: strong last line of defense.
- Cons: not expressive enough for every business rule.
- Rejected as the only strategy.

## Consequences

- Financial changes must define invariants.
- Statuses should use enums where the model lifecycle is meaningful.
- Tests should assert invalid transitions and impossible states are rejected.
