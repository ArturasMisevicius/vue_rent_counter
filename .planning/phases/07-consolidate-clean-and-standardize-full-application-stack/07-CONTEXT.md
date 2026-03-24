# Phase 7: Consolidate, clean, and standardize full application stack - Context

**Gathered:** 2026-03-24
**Status:** Ready for planning

<domain>
## Phase Boundary

Phase 7 continues the architecture cleanup by retiring the remaining concrete web route controllers and moving their behavior into Livewire endpoint components without changing route names, middleware, validation, or tenant and superadmin boundaries.

</domain>

<decisions>
## Implementation Decisions

### Livewire Endpoint Migration
- Replace each remaining concrete route controller with a dedicated Livewire endpoint component in the closest existing namespace (`Security`, `Shell`, `Preferences`, `Superadmin`, `Tenant`).
- Keep route names, URI patterns, default parameters, middleware, and FormRequest or Action usage unchanged.
- Keep `app/Http/Controllers/Controller.php` as the only remaining HTTP controller file for Laravel framework compatibility.

### Claude's Discretion
- Route the new endpoint classes through method actions (`Class@method`) instead of introducing full-page wrappers where the behavior is only a redirect, download, or telemetry intake endpoint.
- Reuse existing endpoint naming conventions (`*Endpoint`) already present in the codebase.

</decisions>

<code_context>
## Existing Code Insights

### Reusable Assets
- `app/Livewire/Preferences/UpdateGuestLocaleEndpoint.php`
- `app/Livewire/PublicSite/ShowFaviconEndpoint.php`
- `app/Livewire/Shell/LogoutEndpoint.php`
- `app/Livewire/Shell/StopImpersonationEndpoint.php`
- Existing request and action classes already encapsulate locale switching, invoice downloads, CSP intake validation, and dashboard/export orchestration.

### Established Patterns
- Non-page web endpoints already use Livewire components with direct action methods instead of controller classes.
- Route handlers delegate to Actions, Services, and FormRequests instead of holding business logic inline.
- Tenant boundary enforcement relies on `WorkspaceResolver` and the tenant Livewire workspace contract.

### Integration Points
- `routes/web.php` defines every remaining controller-backed web route that needs migration.
- `tests/Feature/Livewire/ControllerRouteMigrationTest.php` is the route-contract regression guard.
- `tests/Feature/Architecture/WorkspaceBoundaryInventoryTest.php` tracks the tenant alias entrypoint as part of the shared workspace contract.

</code_context>

<specifics>
## Specific Ideas

No additional product-surface changes are in scope. This pass is strictly a structural migration from concrete route controllers to Livewire endpoint components.

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within the architecture cleanup boundary.

</deferred>
