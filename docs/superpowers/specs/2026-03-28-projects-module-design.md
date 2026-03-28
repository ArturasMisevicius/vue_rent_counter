# Projects Module Design

## Goal

Extend the existing lightweight Projects surface into a full organization-scoped operational module that supports lifecycle management, budgeting, scheduling, approvals, team assignment, cost passthrough, and superadmin diagnostics.

## Key Repo-Constrained Assumptions

- The current codebase still uses integer primary keys across the collaboration domain. This design upgrades the Projects module in place instead of attempting a repo-wide ULID migration.
- Tenant isolation continues to follow the repo's existing workspace-context and policy patterns, with additional project-specific guards and organization consistency validation.
- Organization-scoped project configuration is stored in `organization_settings` because there is no org-level `system_configs` model yet.

## Data Model

### `projects`

Upgrade the existing `projects` table to support:

- scope columns: `organization_id`, `building_id`, `property_id`
- identity: `name`, `description`, `reference_number`
- lifecycle: `status`, `priority`, `type`, `manager_id`
- budget: `budget_amount`, `actual_cost`, `cost_passed_to_tenant`
- schedule: `estimated_start_date`, `actual_start_date`, `estimated_end_date`, `actual_end_date`
- workflow: `completion_percentage`, `requires_approval`, `approved_at`, `approved_by`
- terminal metadata: `completed_at`, `cancelled_at`, `cancellation_reason`
- contractor metadata: `external_contractor`, `contractor_contact`, `contractor_reference`
- internal fields: `notes`, `metadata`, `deleted_at`

### Supporting tables

Add:

- `project_users` pivot for project team membership
- `cost_records` for direct materials / contractor / expense costs

Extend:

- `organization_settings` with project numbering and alert settings
- `invoice_items` with nullable `project_id`, passthrough state metadata
- `tasks` with hold/cancel metadata needed for project lifecycle rollups
- `time_entries` with `organization_id` and project-aware cost snapshot data

## Domain Model

### Enums

Add:

- `ProjectStatus`
- `ProjectPriority`
- `ProjectType`
- `ProjectTeamRole`
- `ProjectCostRecordType`

These use the repo's translated enum pattern and provide badge colors / options for Filament.

### Project lifecycle

- `draft -> planned -> in_progress -> completed`
- hold and cancellation flows per the requested transition rules
- emergency projects may be created directly in `in_progress`
- completed and cancelled are terminal

All transitions run through model/service validation, never inline Filament closures.

### Cost model

`actual_cost` is derived from:

- summed project-linked `time_entries` cost snapshots
- summed `cost_records.amount`

Manual editing of `actual_cost` is removed.

### Completion model

Completion mode is organization-configurable:

- `manual`
- `automatic_from_tasks`

Automatic mode recalculates on task status changes.

## Services and Side Effects

### `ProjectService`

Centralize:

- project creation
- status transitions
- approval
- cost passthrough invoice item generation
- actual cost recalculation
- completion recalculation

### Observers

`ProjectObserver` validates organization/building/property consistency, handles reference generation, enforces status side effects, and blocks invalid soft deletes.

Additional observers will keep project totals in sync from:

- `TimeEntry`
- `CostRecord`
- `Task`

### Audit logging

Use the existing `AuditLogger` plus explicit metadata payloads for:

- creation
- approval
- manager assignment
- status transitions
- cost passthrough generation
- organization reassignment
- deletion blocks

## Filament Surface

### Shared resource strategy

Keep the existing `ProjectResource` path and upgrade it into a dual-scope resource:

- superadmin sees cross-org control-plane data
- org users see current-organization projects only

This matches existing resources like `PropertyResource`.

### List view

Add:

- org-aware columns
- status / priority badges
- budget variance and schedule variance presentation
- progress column
- attention filters and preset
- bulk actions for status, manager, tags, and CSV export

### Detail view

Add:

- identity section
- schedule health section
- budget health section
- team section
- tasks summary section
- recent audit activity
- cost passthrough preview section

### Header actions

Add dedicated actions for:

- change status
- assign manager
- approve
- generate cost passthrough
- view organization
- view audit log

## Permissions

Replace the current superadmin-only `ProjectPolicy` with mixed-scope rules:

- superadmin full visibility
- org admins / owners full org access
- project managers limited update access
- project team members can view when explicitly attached

Delete remains superadmin-only and blocked by time entries, completed tasks, or committed passthrough items.

## Scheduled jobs / commands

Add commands for:

- stalled on-hold alerts
- overdue project alerts
- unapproved project reminders / superadmin escalations

## Testing

Cover:

- reference generation and uniqueness
- organization/building/property consistency
- emergency project flow
- lifecycle transitions and terminal guards
- approval flow
- completion and cancellation side effects
- cost recalculation
- passthrough invoice item generation
- organization reassignment job dispatch
- delete blocking
- cross-tenant authorization

## Implementation Notes

- Follow existing integer-key schema conventions in this repository.
- Add soft deletes to project-owned records introduced by this module.
- Prefer extracted schema/table/action classes over large resource closures.
