# Tenanto Execution Roadmap

This roadmap turns the 2026-03-17 design-and-plan set into a recommended execution sequence.

Use this file when you want one place that answers:

- what to build first
- what can be folded into the same branch
- what can run in parallel
- what should wait until the main surfaces exist

For phase entry and exit criteria, pair this roadmap with `PHASE-GATES.md`.
For recommended branch names and merge order, pair it with `BRANCH-PLAYBOOK.md`.

## Recommended Primary Path

- [ ] Phase 1: Foundation auth and onboarding
  Plan: `plans/2026-03-17-foundation-auth-onboarding.md`
  Spec: `specs/2026-03-17-foundation-auth-onboarding-design.md`
  Why first: every later slice depends on role-aware authentication, organization ownership, invitation flows, and redirects.

- [ ] Phase 2: Shared interface elements
  Plan: `plans/2026-03-17-shared-interface-elements.md`
  Spec: `specs/2026-03-17-shared-interface-elements-design.md`
  Why second: this establishes the shared shell, navigation primitives, profile entry point, locale switcher, and notification/search scaffolding that most later slices plug into.

- [ ] Phase 3: Admin organization operations
  Plan: `plans/2026-03-17-admin-organization-operations.md`
  Spec: `specs/2026-03-17-admin-organization-operations-design.md`
  Why third: this is the main domain rollout for buildings, properties, tenants, meters, readings, invoices, settings, and reports.

- [ ] Phase 4: Fold manager role parity into admin organization operations if possible
  Plan: `plans/2026-03-17-manager-role-parity.md`
  Spec: `specs/2026-03-17-manager-role-parity-design.md`
  Recommendation: if Phase 3 has not started yet, implement this inside the same branch as admin organization operations instead of treating it as a separate slice.

- [ ] Phase 5: Tenant self-service portal
  Plan: `plans/2026-03-17-tenant-self-service-portal.md`
  Spec: `specs/2026-03-17-tenant-self-service-portal-design.md`
  Why after Phase 3: it depends on the organization workspace models and shared validation/invoice behavior.

- [ ] Phase 6: Superadmin control plane
  Plan: `plans/2026-03-17-superadmin-control-plane.md`
  Spec: `specs/2026-03-17-superadmin-control-plane-design.md`
  Recommendation: this can start once Phases 1 and 2 are stable, but it is easier to complete after the core auth and shell foundations exist.

- [ ] Phase 7: Cross-cutting behavioral rules
  Plan: `plans/2026-03-17-cross-cutting-behavioral-rules.md`
  Spec: `specs/2026-03-17-cross-cutting-behavioral-rules-design.md`
  Why late: these rules are best layered onto real workflows instead of placeholders.

- [ ] Phase 8: Missing information closures
  Plan: `plans/2026-03-17-missing-information-closures.md`
  Spec: `specs/2026-03-17-missing-information-closures-design.md`
  Why last: this is a hardening and ambiguity-closure pass across the earlier slices.

## Recommended Branch Strategy

### Track A: Core Sequential Path

Use one main execution stream in this order:

1. foundation auth and onboarding
2. shared interface elements
3. admin organization operations
4. manager role parity folded into the admin branch
5. tenant self-service portal
6. cross-cutting behavioral rules
7. missing information closures

This is the safest path if one engineer or one main branch is driving the rollout.

### Track B: Safe Parallel Split After Phase 2

After the shared shell is in place, two streams become reasonable:

- Stream 1:
  - admin organization operations
  - manager role parity
  - tenant self-service portal

- Stream 2:
  - superadmin control plane

Then merge back into:

- cross-cutting behavioral rules
- missing information closures

This is the best recommendation if multiple engineers are available and the team wants meaningful parallel work without forcing fake dependencies.

## Fold-In Recommendations

- Fold `manager-role-parity` into `admin-organization-operations` unless the admin workspace already exists.
- Keep `cross-cutting-behavioral-rules` out of the early branches unless a slice truly cannot ship without a specific shared rule.
- Keep `missing-information-closures` as a follow-up hardening pass, not as a replacement for the main feature branches.

## Dependency Gates

Do not start these slices before their main dependencies are materially present:

- `shared-interface-elements` before `foundation-auth-onboarding`
- `admin-organization-operations` before `foundation-auth-onboarding` and `shared-interface-elements`
- `manager-role-parity` before `admin-organization-operations`
- `tenant-self-service-portal` before `admin-organization-operations`
- `cross-cutting-behavioral-rules` before the major product surfaces exist
- `missing-information-closures` before the earlier surfaces exist

## Suggested Execution Ritual

For each phase:

1. Read the spec first.
2. Open the matching implementation plan.
3. Respect the prerequisite notes before touching code.
4. Reuse shared shell and domain behavior instead of copying logic into the new slice.
5. Keep the branch focused on the phase unless the roadmap explicitly recommends folding a dependent slice into it.

## Quick Start Recommendation

If someone asks “what should I implement next?” the default answer should be:

1. `foundation-auth-onboarding`
2. `shared-interface-elements`
3. `admin-organization-operations` plus `manager-role-parity`

That gets the product from an empty shell to a functioning organization workspace in the most direct way.
