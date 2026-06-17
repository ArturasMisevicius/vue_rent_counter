# ADR 0004: Design Side Effects For A Transactional Outbox

## Status

Accepted

## Date

2026-06-15

## Context

Tenanto sends emails, reminders, notifications, exports, and audit records from financial and tenant workflows. Some workflows already dispatch jobs or notifications after state changes. A generalized outbox is not yet implemented for every event.

## Decision

Design new side effects as outbox-ready: stable event names, scoped payloads, idempotent handlers, and retry-safe jobs. Core state changes stay in actions; side effects are dispatched through events/jobs/notifications after the state change or through an explicit outbox when introduced.

## Alternatives Considered

### Direct Side Effects In UI

- Pros: easy to write.
- Cons: hard to retry, test, or audit.
- Rejected.

### Full Outbox Migration Immediately

- Pros: strongest consistency pattern.
- Cons: broad migration risk.
- Deferred until a dedicated implementation slice.

## Consequences

- New side effects need idempotency thinking.
- Critical events should use stable mutation keys.
- Existing direct notification code can be refactored gradually.
