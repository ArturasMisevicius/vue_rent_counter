---
name: tenanto-tenant-security
description: Use for Tenanto auth, authorization, tenant isolation, impersonation, middleware, or sensitive multi-tenant boundary changes.
---

# Tenanto Tenant Security

Mirror entry for the canonical skill at `.agent/skills/tenanto-tenant-security/SKILL.md`.

- Security chain: `AuthenticateAdminPanel` -> `EnsureAccountIsAccessible` -> `EnsureOnboardingIsComplete` -> `CheckSubscriptionStatus`
- `HierarchicalScope` is the historical isolation model; in the live repo its behavior must be enforced with explicit organization/property/self scopes
- No query may cross organizations without an explicit superadmin check
- Every Filament resource/page defines `canAccess()` and navigation visibility should match it
- Tenant portal Livewire components must derive trusted `property_id` server-side and scope queries by `organization_id` and `property_id`
- Isolation checks must keep these tests green:
  - `tests/Feature/Security/TenantIsolationTest.php`
  - `tests/Feature/Security/TenantPortalIsolationTest.php`
  - `tests/Feature/Tenant/TenantAccessIsolationTest.php`
  - `tests/Feature/Auth/AccessIsolationTest.php`
  - `tests/Feature/GlobalSearchTest.php`

Read the canonical `SKILL.md` for the full threat and verification checklist.
