---
name: tenanto-tenant-security
description: Use for Tenanto auth, authorization, tenant isolation, impersonation, middleware, or sensitive multi-tenant boundary changes.
allowed-tools: Read, Write, Edit, Glob, Grep, Bash
---

# Tenanto Tenant Security

## Use This Skill When

- Changing auth, authorization, impersonation, policies, middleware, or tenant-facing Livewire pages.
- Auditing data isolation between organizations, properties, tenants, admins, managers, and superadmins.
- Touching Filament `canAccess()` rules or tenant portal queries.

## Security Middleware Chain

These four middleware classes form the current authenticated panel access chain:

1. `App\Http\Middleware\AuthenticateAdminPanel`
   - Protects the panel itself and redirects expired sessions back to login.
2. `App\Http\Middleware\EnsureAccountIsAccessible`
   - Logs out suspended users or users belonging to suspended organizations.
3. `App\Http\Middleware\EnsureOnboardingIsComplete`
   - Redirects org-less admins to onboarding unless they are on approved superadmin control-plane routes.
4. `App\Http\Middleware\CheckSubscriptionStatus`
   - Enforces active, grace-period, post-grace, and suspended subscription behavior for admins and managers.

Adjacent cross-cutting middleware still matters:

- `SetAuthenticatedUserLocale` applies the user locale.
- `SecurityHeaders` applies CSP and other response headers.

## HierarchicalScope Rule

- `HierarchicalScope` is the historical isolation model referenced in earlier Tenanto docs.
- In the verified live repo, it exists only in legacy `_old/` code and is not the active mechanism.
- Treat it as the required behavior model:
  - `SUPERADMIN`: may cross organizations intentionally
  - `ADMIN` and `MANAGER`: restricted to their `organization_id`
  - `TENANT`: restricted to their active `property_id` and their own invoices/readings
- Because the global scope is not active, you must enforce the same isolation explicitly with Eloquent scopes and tenant-aware predicates.

## Hard Isolation Rules

- No query may return records from a different organization unless the code explicitly checks `isSuperadmin()`.
- Tenant portal Livewire components must always resolve the current tenant property from trusted server-side state, then scope queries by both:
  - `organization_id`
  - `property_id`
- Tenant-owned billing data also needs a self-owned predicate such as `tenant_user_id = auth()->id()`.
- Request validation for foreign keys must scope `exists` / `in` checks to tenant-visible records.
- Fail closed on ambiguity: prefer `403`, `404`, or empty scoped results over permissive fallback behavior.

## Filament Access Pattern

- Every Filament resource and custom page defines `canAccess(): bool`.
- Navigation visibility should mirror access via `shouldRegisterNavigation()` when role visibility differs.
- Superadmin-only resources should use `App\Filament\Concerns\AuthorizesSuperadminAccess` when that matches the access model.
- Tenant portal pages should inherit the tenant-only access pattern already used by `TenantPortalPage`.

## Tenant Portal Guardrails

- Never trust a client-supplied `property_id`, `organization_id`, or tenant-owned foreign key.
- Resolve the tenant from the authenticated user, then derive the active property assignment on the server.
- Meter, invoice, reading, and property queries must be scoped before lookup, not filtered after retrieval.
- Livewire server-owned identifiers should stay locked or recomputed server-side when mutation risk exists.

## Isolation Tests That Must Stay Green

- `tests/Feature/Security/TenantIsolationTest.php`
- `tests/Feature/Security/TenantPortalIsolationTest.php`
- `tests/Feature/Tenant/TenantAccessIsolationTest.php`
- `tests/Feature/Auth/AccessIsolationTest.php`
- `tests/Feature/GlobalSearchTest.php`

## Completion Checklist

- [ ] Cross-organization access is impossible without explicit superadmin logic
- [ ] Tenant portal queries include property scope checks
- [ ] Filament `canAccess()` is defined and aligned with navigation
- [ ] Validation constrains foreign keys to visible tenant/org records
- [ ] Isolation regression tests stayed green
