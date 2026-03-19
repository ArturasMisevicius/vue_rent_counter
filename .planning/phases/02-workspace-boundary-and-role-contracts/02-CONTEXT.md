# Phase 2: Workspace Boundary and Role Contracts - Context

**Gathered:** 2026-03-19
**Status:** Ready for planning

<domain>
## Phase Boundary

Phase 2 makes workspace resolution and role authority explicit before protected data is read or mutated. The goal is to replace scattered query-by-query boundary checks with one reusable contract that Filament resources, Livewire components, policies, and tenant routes all inherit. This phase does not yet unify navigation, read-model composition, billing semantics, or queue behavior; it establishes the boundary contract those later phases depend on.

</domain>

<decisions>
## Implementation Decisions

### Workspace context policy
- Every protected request should resolve one shared workspace context before protected data access begins.
- Prefer reusable support classes and middleware over repeated `organization_id`, `property_id`, or role checks embedded directly in resources, pages, and components.
- Existing workspace-aware Eloquent scopes remain the preferred query boundary, but they should be driven from a single resolved context instead of per-call improvisation.

### Elevated and workspace role authority
- `SUPERADMIN` platform authority should be reconciled to one canonical contract even if transitional compatibility is temporarily needed for the legacy `is_super_admin` boolean.
- Non-superadmin roles must derive authority from organization or property assignment, never from panel access alone.
- `ADMIN` and `MANAGER` should continue to share the same organization workspace for ordinary operations, but privileged finance and billing-management controls must remain admin-only.

### Tenant boundary policy
- Tenant self-service remains tied to the tenant's current property assignment and current organization context.
- Cross-organization, unassigned, or malformed tenant relationships must fail closed rather than attempting soft recovery inside UI classes.
- Tenant routes, invoice reads, downloads, and meter-reading writes should all resolve through the same tenant-safe query and authorization rules.

### Regression proof
- Phase 2 should add focused executable proof for workspace resolution, role authority differences, and tenant boundary enforcement.
- Tests should explicitly cover mismatched superadmin flags, admin versus manager billing privileges, and tenant attempts to access out-of-scope records.

### Claude's Discretion
- Exact class names and the final placement of the shared workspace context or resolver may be chosen during implementation as long as the boundary contract is explicit, reusable, and test-proven.

</decisions>

<specifics>
## Specific Ideas

- Introduce one immutable workspace-context object that can represent platform, organization, and tenant scopes without UI classes reconstructing that state.
- Expand policy and feature coverage around legacy superadmin authority drift before changing the authority contract.
- Keep tenant scope builders close to the existing `app/Filament/Support/Tenant/Portal/*` and Eloquent scope patterns instead of inventing a second tenant query layer.

</specifics>

<canonical_refs>
## Canonical References

### Phase scope and milestone rules
- `.planning/ROADMAP.md` — Defines the fixed Phase 2 boundary, requirements mapping, and success criteria for Workspace Boundary and Role Contracts.
- `.planning/REQUIREMENTS.md` — Defines the linked requirement set for this phase, especially `SEC-01` through `SEC-04`.
- `.planning/PROJECT.md` — Captures the project-wide non-negotiables around tenant safety, billing correctness, and aggressive cleanup without a rewrite.
- `.planning/STATE.md` — Records the current project position and confirms Phase 2 is the next active planning target.

### Codebase reference docs
- `.planning/codebase/ARCHITECTURE.md` — Summarizes the single-panel architecture, existing workspace scopes, policies, middleware, and Livewire boundaries.
- `.planning/codebase/CONCERNS.md` — Captures the current scattered workspace-boundary risk and the dual-source `SUPERADMIN` authority problem.
- `.planning/codebase/TESTING.md` — Summarizes current regression assets that can be extended for Phase 2.

### Boundary and authority implementation surface
- `app/Models/User.php` — Central authority helpers, role checks, and the current `isSuperadmin()` compatibility behavior.
- `app/Filament/Support/Admin/OrganizationContext.php` — Existing organization context helper that should inform the shared workspace contract.
- `app/Providers/Filament/AppPanelProvider.php` — The unified panel bootstrap that currently mixes role-aware composition, auth middleware, and navigation behavior.
- `app/Http/Middleware/EnsureAccountIsAccessible.php` — Existing account-access middleware that can help anchor shared boundary resolution.
- `app/Http/Middleware/EnsureUserIsTenant.php` — Existing tenant gate that Phase 2 should keep aligned with the new workspace contract.
- `app/Policies/*.php` — Current resource-level authorization surface that must inherit a clearer role and workspace authority contract.

### Tenant and workspace read or write surfaces
- `app/Http/Controllers/TenantPortalRouteController.php` — Tenant route alias and destination boundary surface.
- `app/Filament/Support/Tenant/Portal/TenantInvoiceIndexQuery.php` — Existing tenant invoice read builder.
- `app/Filament/Support/Tenant/Portal/TenantHomePresenter.php` — Existing tenant-scoped summary builder.
- `app/Filament/Actions/Tenant/Readings/SubmitTenantReadingAction.php` — Existing tenant write surface that should remain property-scoped.
- `app/Filament/Resources/Invoices/InvoiceResource.php` — Current mixed-role invoice surface with high boundary sensitivity.
- `app/Filament/Resources/Properties/PropertyResource.php` — Existing organization workspace resource with scoped access requirements.
- `app/Filament/Resources/Tenants/TenantResource.php` — Existing tenant-assignment and relationship-sensitive surface.

### Existing regression assets
- `tests/Feature/Security/TenantIsolationTest.php`
- `tests/Feature/Security/TenantPortalIsolationTest.php`
- `tests/Feature/Tenant/TenantAccessIsolationTest.php`
- `tests/Feature/Admin/ManagerPolicyParityTest.php`
- `tests/Feature/Auth/AccessIsolationTest.php`

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- The repository already encodes workspace-aware Eloquent scopes such as `forSuperadminControlPlane()`, `forOrganizationWorkspace()`, and `forTenantWorkspace()` across key models.
- `app/Filament/Support/Admin/OrganizationContext.php` already centralizes part of the organization-side context problem and can act as a bridge into a broader workspace contract.
- Existing isolation tests already prove several happy-path and negative-path cases; Phase 2 can extend them instead of rebuilding an entirely new security suite from scratch.

### Established Patterns
- The application prefers support classes and scoped builders under `app/Filament/Support/*` instead of controller-local orchestration.
- Authorization is already layered across middleware, policies, Filament resource accessors, and targeted Gate calls; the weakness is inconsistency, not absence.
- Tenant self-service already has focused support builders under `app/Filament/Support/Tenant/Portal/*`; Phase 2 should reuse that shape for boundary hardening.

### Integration Points
- A shared workspace-context contract will need to integrate with middleware, Filament panel bootstrapping, policies, and tenant route resolution.
- Role-authority normalization will connect through `User`, policy helpers, and admin-versus-manager settings or billing surfaces.
- Tenant property scoping will connect through the tenant support query layer, tenant actions, and the existing tenant route controller.

</code_context>

<deferred>
## Deferred Ideas

- Canonical entry-path and navigation cleanup belongs to Phase 3.
- Mutation-pipeline and queue-backed write standardization belongs to Phase 4.
- Billing semantics and invoice explainability changes belong to Phase 5.

</deferred>

---

*Phase: 02-workspace-boundary-and-role-contracts*
*Context gathered: 2026-03-19*
