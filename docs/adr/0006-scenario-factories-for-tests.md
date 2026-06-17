# ADR 0006: Use Scenario Factories For Tests

## Status

Accepted

## Date

2026-06-15

## Context

Financial and tenant workflows require realistic combinations of organization, subscription, property, tenant, assignment, meter, invoice, payment, and document state. Direct inserts make tests easy to write but can bypass domain rules.

## Decision

Tests should use factories plus scenario builders for repeated business states. Feature-specific helpers are allowed when they keep setup realistic and readable.

## Alternatives Considered

### Manual Row Creation Everywhere

- Pros: explicit per test.
- Cons: duplicates setup and can create impossible states.
- Rejected for critical workflows.

### Huge Global Fixture

- Pros: convenient.
- Cons: opaque coupling and slow tests.
- Rejected.

## Consequences

- New modules should document test scenarios.
- Critical action tests should build state through factories/actions.
- Existing helpers in `tests/Pest.php` remain preferred for common organization and tenant setup.
