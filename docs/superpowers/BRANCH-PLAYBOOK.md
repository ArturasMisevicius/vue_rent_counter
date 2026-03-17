# Tenanto Branch Playbook

This playbook translates the rollout guidance into recommended implementation branches and workstreams.

Use it when you want to answer:

- what branch should I open next
- which slices belong together
- what is safe to run in parallel
- what order should branches merge back

This document assumes the rollout guidance in:

- `README.md`
- `EXECUTION-ROADMAP.md`
- `PHASE-GATES.md`

## Default Branch Sequence

If one engineer or one main execution stream is driving the rollout, use this sequence:

1. `codex/foundation-auth-onboarding`
2. `codex/shared-interface-elements`
3. `codex/admin-ops-manager-parity`
4. `codex/tenant-self-service-portal`
5. `codex/superadmin-control-plane`
6. `codex/cross-cutting-behavioral-rules`
7. `codex/missing-information-closures`

This is the simplest recommended path because it minimizes dependency confusion and keeps each branch aligned with a real product boundary.

## Recommended Branch Bundles

### Branch 1: Foundation Auth and Onboarding

- Recommended name: `codex/foundation-auth-onboarding`
- Plan: `plans/2026-03-17-foundation-auth-onboarding.md`
- Spec: `specs/2026-03-17-foundation-auth-onboarding-design.md`
- Should include:
  - roles and statuses
  - organizations and subscriptions
  - invitations
  - login, register, onboarding, password reset
  - redirect and access middleware
- Should not include:
  - shared shell chrome
  - organization CRUD domains
  - superadmin governance features

### Branch 2: Shared Interface Elements

- Recommended name: `codex/shared-interface-elements`
- Plan: `plans/2026-03-17-shared-interface-elements.md`
- Spec: `specs/2026-03-17-shared-interface-elements-design.md`
- Should include:
  - topbar and sidebar shell work
  - tenant bottom navigation
  - locale switcher
  - notifications
  - global search scaffolding
  - profile entry point
  - impersonation banner and stop flow
- Should not include:
  - real organization CRUD modules
  - tenant portal business flows
  - platform-governance CRUD

### Branch 3: Admin Ops Plus Manager Parity

- Recommended name: `codex/admin-ops-manager-parity`
- Primary plan: `plans/2026-03-17-admin-organization-operations.md`
- Companion plan: `plans/2026-03-17-manager-role-parity.md`
- Primary spec: `specs/2026-03-17-admin-organization-operations-design.md`
- Companion spec: `specs/2026-03-17-manager-role-parity-design.md`
- Should include:
  - organization-scoped operational models
  - admin and manager resources/pages
  - organization dashboard widgets
  - settings/profile surfaces
  - manager visibility differences folded in from the start
- Why bundle these:
  - manager parity is intentionally a delta on the same workspace
  - bundling avoids manager-only duplicates and late cleanup

### Branch 4: Tenant Self-Service Portal

- Recommended name: `codex/tenant-self-service-portal`
- Plan: `plans/2026-03-17-tenant-self-service-portal.md`
- Spec: `specs/2026-03-17-tenant-self-service-portal-design.md`
- Should include:
  - tenant route layer
  - portal pages
  - reading submission
  - invoice history
  - property details
  - tenant profile flows
- Should assume:
  - shared shell exists
  - organization domain exists

### Branch 5: Superadmin Control Plane

- Recommended name: `codex/superadmin-control-plane`
- Plan: `plans/2026-03-17-superadmin-control-plane.md`
- Spec: `specs/2026-03-17-superadmin-control-plane-design.md`
- Should include:
  - platform dashboard
  - organization/user/subscription governance
  - audit, language, settings, security, and integration health surfaces
  - impersonation start actions
- Can start:
  - after foundation and shared shell are stable
- Easier to finish:
  - once core auth and shell layers are already real

### Branch 6: Cross-Cutting Behavioral Rules

- Recommended name: `codex/cross-cutting-behavioral-rules`
- Plan: `plans/2026-03-17-cross-cutting-behavioral-rules.md`
- Spec: `specs/2026-03-17-cross-cutting-behavioral-rules-design.md`
- Should include:
  - shared subscription access behavior
  - shared reading validation rules
  - finalized-invoice mutation guards
  - refresh and loading consistency
  - filter/sort persistence and locale fallback behavior
- Should not start early:
  - unless a specific shared rule is truly blocking an earlier branch

### Branch 7: Missing Information Closures

- Recommended name: `codex/missing-information-closures`
- Plan: `plans/2026-03-17-missing-information-closures.md`
- Spec: `specs/2026-03-17-missing-information-closures-design.md`
- Should include:
  - session timeout and suspension hardening
  - invitation resend and password-reset closure
  - tenant unassignment continuity rules
  - breadcrumbs and empty-state consistency
- Best used as:
  - a final hardening and ambiguity-closure branch after the main surfaces exist

## Safe Parallel Tracks

Once `codex/shared-interface-elements` is stable, the safest parallel split is:

- Track 1:
  - `codex/admin-ops-manager-parity`
  - then `codex/tenant-self-service-portal`

- Track 2:
  - `codex/superadmin-control-plane`

Then merge both tracks back before:

- `codex/cross-cutting-behavioral-rules`
- `codex/missing-information-closures`

## Merge Order

Recommended merge order:

1. `codex/foundation-auth-onboarding`
2. `codex/shared-interface-elements`
3. `codex/admin-ops-manager-parity`
4. `codex/tenant-self-service-portal`
5. `codex/superadmin-control-plane`
6. `codex/cross-cutting-behavioral-rules`
7. `codex/missing-information-closures`

If `codex/superadmin-control-plane` is developed in parallel, it should still merge before the cross-cutting and closure passes.

## Branch Readiness Checklist

Before opening the next branch:

- confirm the previous branch satisfies its gate in `PHASE-GATES.md`
- confirm the branch scope matches one of the bundles above
- avoid pulling a later hardening slice into an earlier feature branch unless it is truly blocking
- prefer folding a dependent visibility delta into the main feature branch instead of creating a duplicate experience

## Default Recommendation

If nobody knows what branch to open next, open:

- `codex/foundation-auth-onboarding`

If that branch is already complete, open:

- `codex/shared-interface-elements`

If both are complete, open:

- `codex/admin-ops-manager-parity`

That is still the clearest path from docs to a usable product.
