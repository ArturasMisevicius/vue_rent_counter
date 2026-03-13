## 1. Route and Access Foundation
- [x] 1.1 Create a role-to-route access matrix for all web routes (guest, superadmin, admin, manager, tenant).
- [x] 1.2 Add or update feature tests that assert `allowed`, `forbidden`, or `redirect-to-login` behavior for each route by role.
- [x] 1.3 Remove ambiguous role allowances (for example, manager routes allowing admin without explicit intent) and align to matrix.

## 2. Unified Backoffice Layout (Superadmin/Admin/Manager)
- [x] 2.1 Build/normalize one shared custom backoffice Blade layout using Tailwind CSS and Livewire-compatible structure.
- [x] 2.2 Migrate `superadmin`, `admin`, and `manager` page templates to that shared layout.
- [x] 2.3 Consolidate role-aware navigation into reusable custom components/partials (no Filament UI dependencies).

## 3. Tenant Layout Isolation
- [x] 3.1 Keep tenant pages on dedicated tenant layout/template.
- [x] 3.2 Verify tenant navigation and page rendering remain isolated from backoffice navigation.

## 4. Remove Filament Web Interface Surface
- [x] 4.1 Disable Filament panel provider bootstrapping for web panels.
- [x] 4.2 Remove or replace browser-facing Filament route aliases with custom route targets.
- [x] 4.3 Ensure no browser navigation renders `resources/views/vendor/filament*` templates.

## 5. Validation and Quality Gates
- [x] 5.1 Run `php artisan route:list` checks to confirm role route map and absence of Filament panel routes.
- [x] 5.2 Run targeted feature tests for role route access and dashboard/page rendering.
- [ ] 5.3 Run full test suite and formatting checks; fix regressions before completion. (Formatting completed via `vendor/bin/pint --dirty`; targeted suites pass. Full `php artisan test --compact` was re-run and produced a large set of existing failures across the suite, so this item remains blocked until those regressions are addressed.)
