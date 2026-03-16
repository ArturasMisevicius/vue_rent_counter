## 1. Inventory and Mapping
- [ ] 1.1 Inventory all current references to `layouts.app`, `layouts.tenant`, and `layouts.superadmin`.
- [ ] 1.2 Inventory all usages of `<x-backoffice.*>`, `<x-manager.*>`, `<x-tenant.*>`, and `<x-button>`.
- [ ] 1.3 Map role-driven presentation differences that must move behind navigation data, policies, props, or slots.

## 2. Canonical Layout
- [ ] 2.1 Extend `resources/views/layouts/app.blade.php` so it can serve tenant and backoffice pages without separate non-Filament layout files.
- [ ] 2.2 Verify that no active non-Filament view extends `layouts.tenant` or `layouts.superadmin`.
- [ ] 2.3 Remove `resources/views/layouts/tenant.blade.php` and `resources/views/layouts/superadmin.blade.php` after verification.

## 3. Shared Components
- [ ] 3.1 Normalize a canonical shared component set under `resources/views/components/ui`.
- [ ] 3.2 Migrate role-scoped usages from `components/backoffice`, `components/manager`, and `components/tenant` to shared `x-ui.*` components.
- [ ] 3.3 Consolidate the legacy anonymous `<x-button>` into the canonical `<x-ui.button>` component and remove the duplicate implementation.
- [ ] 3.4 Remove role-scoped component directories after zero-reference verification.

## 4. Policy-Driven Presentation
- [ ] 4.1 Keep role-specific navigation and action visibility behind policies, route-aware navigation data, props, and slots.
- [ ] 4.2 Remove raw role-specific presentation branching where a shared component contract can express the difference.

## 5. Tests and Verification
- [ ] 5.1 Add or update Pest tests covering shared layout rendering for superadmin, admin, manager, and tenant pages.
- [ ] 5.2 Add component rendering tests for the new shared primitives that replace the role-scoped ones.
- [ ] 5.3 Add regression checks that fail on references to `layouts.tenant`, `layouts.superadmin`, `components/backoffice`, `components/manager`, `components/tenant`, or the legacy `<x-button>` implementation.
- [ ] 5.4 Produce a legacy-removal report listing deleted files, migrated references, and any follow-up work.
- [ ] 5.5 Run targeted verification commands for grep-based checks and relevant Pest suites.
