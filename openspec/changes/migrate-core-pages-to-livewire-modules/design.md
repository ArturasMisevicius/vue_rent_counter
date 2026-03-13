## Context
Core modules (`profile`, `dashboard`, `settings`) currently combine:
- role controllers returning shared Blade views;
- role switching inside large page templates;
- page-level Livewire classes that prepare similar data.

This creates overlap in ownership and makes role-specific rendering hard to reason about, especially when multiple code paths target the same entry view.

## Goals / Non-Goals
- Goals:
  - Move `profile`, `dashboard`, and `settings` to a single Livewire-first rendering path.
  - Preserve all existing route contracts (URI + route names) and middleware constraints.
  - Preserve role-specific behavior while reducing duplicated role-switching logic.
  - Eliminate duplicate full-page rendering behavior for migrated modules.
  - Add regression coverage for role access and page rendering correctness.
- Non-Goals:
  - Full migration of all remaining modules (providers, users, invoices, etc.) in this change.
  - Rewriting business/domain services not used by these modules.
  - Broad translation refactors outside keys touched by migrated modules.

## Decisions
- Decision: Keep route groups and prefixes unchanged; swap handler targets for migrated pages to Livewire components.
  - Why: Avoid breaking navigation, policies, links, and tests that depend on route contracts.
- Decision: Use module-level Livewire components (`ProfilePage`, `DashboardPage`, `SettingsPage`) as canonical entry points.
  - Why: One source of truth for data preparation and rendering lifecycle.
- Decision: Keep role-aware rendering inside module-specific component/view composition, not role-specific top-level page files.
  - Why: Reduces duplicated templates and aligns with shared page-type file strategy.
- Decision: For write actions in migrated modules, use Livewire actions with explicit authorization and validation parity.
  - Why: Enables full Livewire module behavior without controller wrappers.
- Decision: Decommission controller render methods for migrated modules once routes are fully switched.
  - Why: Prevents dual render paths and future regressions.

## Alternatives Considered
- Keep controllers for page rendering and only embed Livewire children.
  - Rejected: still keeps dual orchestration layers and does not deliver full module migration.
- Migrate all modules in one change.
  - Rejected: high blast radius and hard rollback; module-by-module is safer.

## Risks / Trade-offs
- Risk: Regression in role-specific content and actions.
  - Mitigation: role-matrix feature tests for `profile`, `dashboard`, and `settings`.
- Risk: Breaking middleware or authorization assumptions during route handler swaps.
  - Mitigation: preserve existing route groups and middleware exactly; only handler target changes.
- Risk: Incomplete parity with existing controller validation/messages.
  - Mitigation: assert validation behavior in feature tests before removing legacy paths.

## Migration Plan
1. Add tests that capture existing expected behavior for `profile`, `dashboard`, and `settings`.
2. Migrate `profile` module to full Livewire (read + update flows).
3. Migrate `dashboard` module to full Livewire and remove duplicate entry rendering.
4. Migrate `settings` module to full Livewire (admin scope).
5. Remove deprecated controller rendering paths for migrated modules.
6. Run focused and then broad verification.

## Open Questions
- No blocking questions for proposal creation.
- Assumption: `settings` migration scope applies to the existing admin settings surface only, since manager/tenant settings routes are not currently defined.
