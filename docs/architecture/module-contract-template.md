# Module Contract Template

> **AI agent usage:** Copy this structure when creating `docs/modules/<module>.md`. Replace placeholders with verified current-state facts.

Updated on 2026-06-15.

```markdown
# <Module> Module Contract

> **AI agent usage:** Read this before changing <module> workflows. Verify live code, routes, policies, schema, translations, and tests before editing behavior.

Updated on YYYY-MM-DD.

## Purpose

What business capability the module owns.

## Owns

- Models:
- Actions:
- Policies:
- Enums:
- Jobs/listeners:
- Docs/runbooks:

## Public Actions

| Action | Purpose | Callers |
| --- | --- | --- |
| `ActionName` | | |

## DTOs And Results

Input and output contracts used by actions.

## Events And Side Effects

| Event or mutation key | Trigger | Handlers/side effects |
| --- | --- | --- |
| | | |

## Permissions

Permission keys and policy abilities.

## Invariants

Rules that must always hold.

## Dependencies

Modules this module may depend on.

## Must Not

Forbidden dependencies or side effects.

## UI Surfaces

Filament, Livewire, route, command, or API entrypoints.

## Tests And Scenarios

Primary test files and scenario builders.

## Common Failures

Known risks and how to verify fixes.

## Audit

Audit events or logs required for sensitive mutations.
```
