# Adding A Feature

> **AI agent usage:** Use this guide before implementing a new Tenanto feature. Verify current code and the affected module contract before editing.

Updated on 2026-06-15.

## Workflow

1. Identify the owning module in `docs/modules`.
2. Add or update a feature flag if rollout risk requires it.
3. Add migrations/models/enums only after checking existing schema and patterns.
4. Add validation through `app/Http/Requests`.
5. Add or reuse an action for the business workflow.
6. Add policy/permission checks.
7. Add audit, events, notifications, jobs, or outbox behavior for side effects.
8. Wire Filament, Livewire, controller, command, or API entrypoints to the action.
9. Add scenario tests and architecture tests when boundaries change.
10. Update module docs, operations docs, translations, and the permission matrix when the contract changes.
11. Run focused tests and `php artisan architecture:check`.

## PR Questions

- What module owns this?
- What action owns the workflow?
- What policy/permission protects it?
- What organization or tenant scope is enforced?
- What invariants apply?
- What side effects are emitted?
- What audit records are written?
- What tests prove the scenario?
- What docs changed?
- What rollback or migration risk remains?
