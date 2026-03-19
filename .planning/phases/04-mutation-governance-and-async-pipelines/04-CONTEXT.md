# Phase 4: Mutation Governance and Async Pipelines - Context

**Gathered:** 2026-03-19
**Status:** Ready for planning

<domain>
## Phase Boundary

Phase 4 standardizes how equivalent writes happen, how high-risk changes are audited, and how slow side effects leave interactive request paths. It builds on the stabilized boundaries and read surfaces from Phases 2 and 3, and it explicitly excludes deeper billing-rule simplification, which belongs to Phase 5.

</domain>

<decisions>
## Implementation Decisions

### Mutation pipeline policy
- Equivalent mutations across resources, pages, and Livewire components should route through one validated request plus action path per behavior.
- Phase 4 should prefer extracting shared request or action contracts over leaving duplicated write logic in UI classes.

### Governance and audit policy
- High-risk financial record changes must capture actor, timestamp, workspace, and before-or-after context consistently.
- Audit capture should be attached to the real mutation pipeline rather than added as a secondary UI concern.

### Tenant meter-reading write policy
- Tenant meter submission should remain a first-class self-service flow, but it must share the same validation and domain mutation rules as operator-facing reading workflows.
- Out-of-scope and malformed write attempts should fail closed with explicit, testable outcomes.

### Async side-effect policy
- Notifications, reminders, exports, and comparable slow work should move to queue-backed jobs.
- Interactive request flows should finish quickly and hand off durable side effects to an asynchronous path.

</decisions>

<canonical_refs>
## Canonical References

- `.planning/ROADMAP.md`
- `.planning/REQUIREMENTS.md`
- `.planning/codebase/ARCHITECTURE.md`
- `.planning/codebase/CONCERNS.md`
- `app/Filament/Actions/Admin/*`
- `app/Filament/Actions/Tenant/Readings/SubmitTenantReadingAction.php`
- `app/Filament/Support/Audit/AuditLogger.php`
- `app/Filament/Support/Admin/ReadingValidation/*`
- `app/Filament/Support/Admin/Reports/ReportExportService.php`
- `app/Filament/Actions/Admin/Invoices/SendInvoiceEmailAction.php`
- `app/Filament/Actions/Admin/Invoices/SendInvoiceReminderAction.php`
- `routes/console.php`
- `tests/Feature/Admin/CreateMeterReadingActionTest.php`
- `tests/Feature/Admin/MeterReadingValidationRulesTest.php`
- `tests/Feature/Notifications/NotificationSystemTest.php`

</canonical_refs>

<code_context>
## Existing Code Insights

- The repository already favors actions under `app/Filament/Actions/*`, but equivalent behaviors are not yet guaranteed to share one request-and-action path.
- Audit support already exists, yet the codebase concerns audit still calls out missing consistent governance capture for high-risk financial mutations.
- Queue tooling exists in Laravel and the repo's dev script already runs `queue:listen`, but notification delivery still happens inside interactive actions.

</code_context>

<deferred>
## Deferred Ideas

- Due-date semantics, billing preview parity, and money-policy extraction belong to Phase 5.
- Health probes, backups, and release operations belong to Phase 6.

</deferred>

---

*Phase: 04-mutation-governance-and-async-pipelines*
*Context gathered: 2026-03-19*
