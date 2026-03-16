# Change: Refactor Shared Layout and Component System

## Why
The non-Filament UI still carries legacy layout files and role-scoped Blade component trees even though the active page layer is already converging on `resources/views/layouts/app.blade.php`. This creates duplicated markup, inconsistent styling, and role-specific drift that should instead be handled through shared components plus authorization-aware rendering.

This proposal intentionally narrows the first phase of the larger UI refactor to one reviewable slice: one canonical non-Filament layout and one canonical shared component library.

## What Changes
- Keep `resources/views/layouts/app.blade.php` as the only non-Filament layout shell.
- Remove `resources/views/layouts/tenant.blade.php` and `resources/views/layouts/superadmin.blade.php` after migration verification.
- Consolidate role-scoped Blade component usage from `components/{backoffice,manager,tenant}` into `components/ui`.
- Express role-specific differences through policies, shared navigation data, props, and slots instead of separate component folders.
- Consolidate the legacy anonymous `<x-button>` implementation into the canonical `<x-ui.button>` component.
- Add focused Pest coverage and a legacy-removal report for the deleted layouts/components.

## Impact
- **BREAKING**: superseded layout files, role-scoped component directories, and the legacy anonymous button component will be removed once references are migrated.
- Affected specs:
  - `shared-ui-shell`
- Related pending changes:
  - `refactor-unified-ui-system`
  - `update-role-dashboards-billing`

## Scope Notes
- This change does **not** replace Filament panel internals with a literal shared Blade layout file.
- This change does **not** migrate controller workflows to Livewire; it only prepares the shared non-Filament shell and component system that those later changes can rely on.
