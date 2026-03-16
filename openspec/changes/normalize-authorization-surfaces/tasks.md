## 1. Audit and Policy Inventory
- [ ] 1.1 Inventory existing policy coverage for `Organization`, `Tenant`, `Property`, `Building`, `Meter`, `MeterReading`, `Invoice`, `Subscription`, `User`, `Tariff`, `Provider`, `Language`, and `Translation`.
- [ ] 1.2 Identify any missing policies, missing abilities, or inconsistent ability names across controllers, Blade, Livewire, and Filament.
- [ ] 1.3 Inventory raw role-based conditionals in Blade, Livewire, and Filament that should be replaced with policy-driven checks.

## 2. Policy Normalization
- [ ] 2.1 Define or complete policy methods for the target resources, including `viewAny`, `view`, `create`, `update`, `delete`, `restore`, and `forceDelete` where applicable.
- [ ] 2.2 Normalize superadmin as the global full-control role for CRUD, exports, impersonation, audit access, and system configuration access.
- [ ] 2.3 Preserve scoped authorization for admin, manager, and tenant according to tenant and property boundaries.
- [ ] 2.4 Ensure policy registration is complete and canonical for all covered models.

## 3. UI Surface Normalization
- [ ] 3.1 Replace raw role-string UI checks in Blade with `@can`, `@cannot`, `@canany`, or authorization-aware view data.
- [ ] 3.2 Replace bespoke role checks in Livewire components with explicit policy authorization and authorization-aware component state.
- [ ] 3.3 Normalize Filament resources/pages/widgets to use policy-backed authorization hooks for navigation visibility and resource actions.

## 4. Superadmin Deep-Control Visibility
- [ ] 4.1 Define the superadmin-only metadata contract for internal IDs, audit fields, timestamps, relationship diagnostics, and workflow state.
- [ ] 4.2 Ensure deep metadata is hidden from admin, manager, and tenant surfaces unless explicitly authorized.
- [ ] 4.3 Ensure superadmin-only actions are consistently visible and executable across Blade, Livewire, and Filament.

## 5. Tests and Verification
- [ ] 5.1 Add or update Pest tests for policy coverage across the target resources.
- [ ] 5.2 Add feature tests for role-based access to sensitive actions and direct routes.
- [ ] 5.3 Add Blade/Livewire/Filament-focused regression tests for authorization-driven visibility.
- [ ] 5.4 Add superadmin coverage tests for full-control actions and deep metadata visibility.
- [ ] 5.5 Produce an authorization normalization report listing replaced raw role checks, completed policies, and any remaining follow-up work.
