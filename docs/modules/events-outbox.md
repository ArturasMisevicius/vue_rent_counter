# Events And Outbox Module Contract

> **AI agent usage:** Read this before changing cross-module side effects, jobs, notifications, reminders, or future transactional outbox work.

Updated on 2026-06-15.

## Purpose

Events/outbox owns the convention for replay-safe side effects. It keeps emails, notifications, webhooks, reminders, exports, and downstream integrations out of UI callbacks.

## Owns

- Notification dispatch patterns.
- Jobs for invoice email/reminders and exports.
- Audit mutation keys.
- Future outbox tables and replay workers when implemented.

## Public Actions

| Action | Purpose | Callers |
| --- | --- | --- |
| `DispatchDomainNotification` | Central domain notification dispatch | notification actions |
| reminder actions | Queue recurring reminders | commands/schedule |
| export queue actions | Queue async exports | Filament platform UI |

## Events And Side Effects

Side effects must be:

- idempotent;
- scoped to organization and record IDs;
- safe to retry;
- covered by queue/notification tests when critical.

## Permissions

Side effects inherit permission from the action that caused the state transition. Replay/ops commands require operator access in deployment, not tenant/admin runtime access.

## Invariants

- no direct external webhook dispatch from UI callbacks;
- no hidden financial side effects in observers;
- failure handling must preserve the primary domain state;
- handlers must not trust stale or cross-organization payloads.

## Dependencies

Events/outbox can depend on modules through stable IDs and action interfaces. Modules should not depend on handler internals.

## Tests And Scenarios

- `Queue::fake()` for queued jobs;
- `Notification::fake()` for notifications;
- command tests for reminder/maintenance runs;
- future outbox replay tests when the outbox table exists.
