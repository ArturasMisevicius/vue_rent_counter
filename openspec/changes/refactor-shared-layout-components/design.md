## Context
The repository already has a strong candidate for the canonical non-Filament shell in `resources/views/layouts/app.blade.php`, and active non-Filament views already extend it. However, legacy layout files still exist at `resources/views/layouts/tenant.blade.php` and `resources/views/layouts/superadmin.blade.php`, and role-scoped component trees remain under `resources/views/components/{backoffice,manager,tenant}`. The duplicate button implementation in `resources/views/components/button.blade.php` further shows that the shared design system is not yet the single source of truth.

## Goals / Non-Goals
- Goals:
  - One canonical non-Filament layout shell.
  - One shared component system for non-Filament UI primitives.
  - Role-aware presentation driven by policies, shared navigation data, props, and slots.
  - Safe deletion of dead legacy layout/component files after verification.
- Non-Goals:
  - Replacing Filament’s internal panel chrome with a literal shared Blade layout file.
  - Migrating controller workflows to Livewire in this phase.
  - Changing billing or tenant-isolation business logic.

## Decisions
- `resources/views/layouts/app.blade.php` is the only canonical non-Filament layout shell.
- Filament continues to use its own panel providers and theme, but should share visual tokens with the custom UI rather than separate Blade layouts.
- Shared non-Filament primitives live under `resources/views/components/ui` and become the only supported namespace for cards, alerts, buttons, page shells, stat cards, quick actions, and structural wrappers.
- Role-specific differences are expressed through:
  - centralized navigation/view data,
  - policy and authorization checks,
  - component props and slots,
  - small presentation flags where needed.
- Deletion of legacy files is gated by zero-reference verification and targeted Pest coverage.

## Risks / Trade-offs
- Some role pages may rely on subtle spacing or markup differences currently hidden inside role-specific components.
  - Mitigation: migrate component-by-component, starting with wrappers and repeated primitives.
- Tenant pages may need extra layout regions not currently exposed by `layouts.app`.
  - Mitigation: extend `layouts.app` with slots/sections rather than reintroducing a separate layout file.
- Existing tests currently reference role-scoped components.
  - Mitigation: replace them with tests for the shared component contracts and add regression checks for forbidden legacy references.

## Migration Plan
1. Inventory current layout and component references.
2. Expand `layouts.app` to cover tenant and backoffice shell needs.
3. Introduce or normalize `x-ui.*` wrapper primitives.
4. Migrate role-scoped component usages to shared components.
5. Consolidate the button implementation into `x-ui.button`.
6. Remove dead layout/component files only after grep verification and targeted tests pass.
7. Publish a legacy-removal report that documents deletions and any remaining follow-up work.
