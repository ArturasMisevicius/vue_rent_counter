# Phase 2: Workspace Boundary and Role Contracts - Research

**Researched:** 2026-03-19
**Domain:** Laravel 12 unified-panel workspace resolution, role authority normalization, and tenant-safe boundary enforcement
**Confidence:** MEDIUM

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| SEC-01 | Every organization-scoped or tenant-scoped request resolves an explicit workspace context before accessing protected data. | Recommends one shared workspace-context object plus a resolver or middleware seam reused by policies, resources, and tenant routes. |
| SEC-02 | `SUPERADMIN` can perform platform-wide actions without tenant assignment, while non-superadmin roles cannot cross organization boundaries. | Recommends reconciling the current dual-source `SUPERADMIN` authority contract and adding regression proof for mismatched role or boolean states. |
| SEC-03 | `ADMIN` retains organization billing-management authority while `MANAGER` remains blocked from billing settings and equivalent privileged finance controls. | Recommends explicit finance/billing ability checks rather than relying on implicit UI hiding alone. |
| SEC-04 | `TENANT` can access only property-scoped self-service capabilities tied to that tenant. | Recommends tenant-safe query builders and route or action entrypoints that fail closed on malformed or out-of-scope assignments. |
</phase_requirements>

## Summary

The codebase already contains many of the ingredients Phase 2 needs: workspace-aware model scopes, policy classes, tenant support query objects, and organization context helpers. The risk is that these pieces are applied manually and inconsistently. Phase 2 should therefore create one explicit workspace contract and then migrate the highest-risk entrypoints to inherit it instead of letting each resource, page, or component decide for itself how scope is resolved.

The largest hidden risk is the current `SUPERADMIN` authority contract. The codebase concerns audit already flags `User::isSuperadmin()` as deriving platform access from both the role enum and a legacy boolean. That is exactly the kind of drift Phase 2 must close or explicitly stage for cleanup with regression proof. The other major risk is tenant scope: the current tenant portal is already query-safe in many places, but the safety depends on several separate builders, controller redirects, and policy checks that future work could bypass accidentally.

**Primary recommendation:** introduce a reusable `WorkspaceContext` plus resolver contract, normalize role authority at the `User` and policy layer, and extend the existing tenant isolation suite so future work breaks loudly when a scope or privileged ability drifts.

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| `laravel/framework` | `12.54.1` | Middleware, request lifecycle, authorization, container bindings, and route model binding | First-party primitives already installed; no new dependency is needed for boundary resolution. |
| `filament/filament` | `5.3.5` | Unified panel bootstrap plus resource/page authorization seams | Existing panel architecture already centralizes the role-aware admin surface. |
| `livewire/livewire` | `4.2.1` | Tenant and auth interaction layer | Existing tenant and shell flows already depend on Livewire-backed pages and actions. |
| Pest / PHPUnit | current repo versions | Boundary regression proof | Existing security and tenant isolation tests can be extended directly. |

### Recommended Project Structure
```text
app/
├── Filament/Support/Workspace/WorkspaceContext.php
├── Filament/Support/Workspace/WorkspaceResolver.php
├── Filament/Support/Admin/OrganizationContext.php
├── Http/Middleware/...
├── Policies/...
└── Models/User.php
tests/
├── Feature/Security/WorkspaceContextResolutionTest.php
├── Feature/Security/RoleAuthorityContractTest.php
├── Feature/Security/TenantPropertyBoundaryContractTest.php
└── Feature/Architecture/WorkspaceBoundaryInventoryTest.php
```

## Architecture Patterns

### Pattern 1: One explicit workspace contract
Use one immutable context object to carry platform, organization, and tenant boundary metadata through the request lifecycle. Resources, policies, and tenant support builders should consume that contract instead of recomputing scope ad hoc.

### Pattern 2: Authority derived from one canonical role contract
`SUPERADMIN` platform authority should be defined in one place and protected by regression proof for legacy-field mismatch cases. `ADMIN` versus `MANAGER` divergence should be expressed through explicit abilities or policy branches around finance-sensitive surfaces.

### Pattern 3: Tenant read and write paths share the same boundary inputs
Tenant invoice reads, invoice downloads, meter submissions, and property views should all resolve from the tenant's current assignment and organization, not from duplicated query fragments embedded in separate UI classes.

### Pattern 4: Boundary proof includes architecture and feature coverage
Feature tests should prove cross-organization and cross-property denials, while a thin inventory test should confirm that key entrypoints use the shared workspace contract instead of fresh naked model queries.

## Anti-Patterns to Avoid

- Adding another round of scattered `where('organization_id', ...)` logic directly inside resources or Livewire components
- Treating hidden menu items as sufficient proof that managers cannot reach billing-sensitive settings
- Allowing tenant routes to soft-fallback to the first related property or invoice when assignments are malformed
- Preserving both `role === SUPERADMIN` and `is_super_admin` as open-ended long-term sources of platform authority without regression proof

## Recommended Plan Shape

Phase 2 should be executed as one plan with four ordered tasks:

1. Introduce the shared workspace-context contract and wire it into protected entrypoints.
2. Normalize role authority, especially `SUPERADMIN` drift and admin-versus-manager finance controls.
3. Harden tenant property-scoped reads and writes around the new shared context.
4. Add architecture and regression proof so downstream phases inherit the boundary contract safely.

---

*Research date: 2026-03-19*
