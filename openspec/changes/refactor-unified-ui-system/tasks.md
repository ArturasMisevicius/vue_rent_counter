## 1. Inventory and Mapping
- [ ] 1.1 Enumerate all layout shells and role-specific Blade component paths; map each view to its target canonical component or layout.
- [ ] 1.2 Identify remaining JSON-returning controller actions and any client-side API callers in the UI surface.
- [ ] 1.3 Document the navigation link sources (NavigationComposer, Filament panel navigation, tenant nav) and build a unified map.

## 2. Canonical Layout and Shared Components
- [ ] 2.1 Extend `resources/views/layouts/app.blade.php` into the single canonical shell for all roles, including tenant navigation support.
- [ ] 2.2 Consolidate shared components into a canonical shared namespace (`resources/views/components/ui` or existing shared set) and migrate usages from `components/{backoffice,manager,tenant}`.
- [ ] 2.3 Remove superseded layout files and duplicate components after migration.

## 3. Role-Aware Navigation and Access Control
- [ ] 3.1 Extend `App\View\Composers\NavigationComposer` (or introduce a dedicated navigation builder service) to provide permission-aware, route-aware navigation sets for all roles.
- [ ] 3.2 Ensure direct-route access checks remain enforced via middleware/policies for all role routes.
- [ ] 3.3 Normalize link rendering to use the centralized navigation data.

## 4. Filament/Livewire Surface Alignment
- [ ] 4.1 Align Filament panel styling with the design system tokens (`resources/css/design-system.css` + `resources/css/filament/theme.css`).
- [ ] 4.2 Migrate role-specific dashboards and operational pages to Filament or Livewire as the canonical interactive surfaces (removing duplicated controller+Blade implementations).

## 5. API Remnant Removal
- [ ] 5.1 Replace JSON-returning controller methods with Livewire/Filament actions and remove unused API methods.
- [ ] 5.2 Verify no `/api/*` routes or client API calls remain in the repository.

## 6. Localization and Documentation
- [ ] 6.1 Update `/lang` entries for all touched UI strings and shared components.
- [ ] 6.2 Update repository guidance docs and agent instructions to reflect the unified design system and canonical UI architecture.

## 7. Tests and Verification
- [ ] 7.1 Add/update feature tests for role-based access, navigation visibility, and unified layout rendering.
- [ ] 7.2 Add/update Livewire/Filament tests for migrated flows.
- [ ] 7.3 Run `vendor/bin/pint --dirty --format agent` and targeted `php artisan test --compact` suites.
