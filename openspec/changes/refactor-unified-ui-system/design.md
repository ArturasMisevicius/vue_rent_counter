## Context
The repository currently serves role experiences through a mix of custom Blade layouts, role-specific Blade components, and Filament panels. There is an existing design system stylesheet (`resources/css/design-system.css`) and a Filament theme (`resources/css/filament/theme.css`), but multiple layout shells (`resources/views/layouts/app.blade.php`, `resources/views/layouts/superadmin.blade.php`, `resources/views/layouts/tenant.blade.php`) and role-scoped components under `resources/views/components/{backoffice,manager,tenant}` produce divergent UI patterns. Navigation logic is partially centralized in `App\View\Composers\NavigationComposer` but tenant navigation remains bespoke. Some controllers still return JSON responses even though API routes are no longer loaded.

## Goals / Non-Goals
- Goals:
  - One canonical layout and shared component system for all roles.
  - Shared design tokens applied to both Filament and custom Blade/Livewire surfaces.
  - Centralized role-aware navigation and direct-route authorization checks.
  - No API-driven UI interactions; only Filament/Livewire for interactivity.
  - Preserve tenant isolation and role-based data boundaries.
- Non-Goals:
  - Introducing new frameworks or dependencies.
  - Replacing Filament or Livewire with another UI stack.
  - Changing core billing domain logic beyond what is required for UI migration.

## Decisions
- Canonical layout will be derived from `resources/views/layouts/app.blade.php`, extended to support tenant navigation so all roles share one layout shell.
- Shared components will live in `resources/views/components/ui` (or an existing shared namespace) and role-specific component directories will be consolidated or removed once migrated.
- Navigation will be composed via a centralized service or view composer (extending `NavigationComposer`) to provide role-aware, permission-aware link sets used by the unified layout.
- Filament theme continues to import `resources/css/design-system.css` so the same tokens drive both Filament and custom Blade surfaces.
- Any remaining JSON-returning controller methods will be migrated to Livewire or Filament flows and the JSON endpoints removed.

## Risks / Trade-offs
- Consolidating layouts can introduce regressions in role-specific views; mitigated by feature tests for each role dashboard and navigation.
- Removing legacy components may impact pages still referencing them; mitigated by staged migration and explicit search/replace of legacy component usage.
- Filament panel navigation may diverge from custom navigation; mitigated by aligning tokenized styling and documenting which surfaces use Filament navigation versus shared Blade navigation.

## Migration Plan
1. Inventory existing layouts, role-specific components, and navigation definitions.
2. Define the canonical layout shell and update role dashboards to use it.
3. Consolidate shared UI components and update Blade views to use them.
4. Align Filament theme and custom UI tokens.
5. Migrate any remaining API-style endpoints to Livewire/Filament actions.
6. Remove legacy layouts/components and update translations.
7. Add tests for role access, navigation visibility, and rendering parity.

## Open Questions
- None. Conflicts with prior proposals will be reconciled by superseding them in this change once approved.
