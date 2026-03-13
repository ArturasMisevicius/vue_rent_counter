# Change: Refactor Unified UI System and Canonical Filament/Livewire Surface

## Why
The current UI surface is split across multiple Blade layouts, role-specific component stacks, and partially overlapping Filament panels. This causes inconsistent design language, duplicated navigation logic, and mixed interaction patterns across roles. The requested direction is one universal design system and one canonical template/component architecture that all roles share, with Filament and Livewire as the only interactive delivery layers and no API-driven UI paths.

## What Changes
- Establish one canonical layout architecture shared across superadmin, admin, manager, and tenant experiences.
- Consolidate reusable UI primitives (cards, headers, tables, forms, badges, alerts, empty states, actions) into a single shared component system.
- Centralize role-aware navigation composition and route/link permission checks.
- Migrate any remaining web UI interactions off API endpoints into Filament or Livewire flows.
- Remove legacy or duplicate templates/components/layouts that are superseded by the canonical system.
- Align Filament panel and custom Blade/Livewire surfaces under a unified design language and token system.
- Update tests, localization, and docs/agent instructions to reflect the new canonical UI architecture.

## Impact
- **BREAKING**: Legacy layouts and duplicate components will be removed or consolidated; any routes/views relying on them must be migrated to the canonical path.
- Affected specs (new capabilities introduced by this change):
  - `ui-system`
  - `role-access-control`
  - `interaction-surface`
- Related in-progress changes to reconcile:
  - `refactor-unify-role-layout-disable-filament-web`
  - `migrate-core-pages-to-livewire-modules`
  - `remove-api-surface-use-livewire-filament` (already marked complete)

This change supersedes conflicting assumptions in the above proposals by explicitly keeping Filament and Livewire as the canonical interaction layers while unifying the design system across all roles.
