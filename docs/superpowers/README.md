# Tenanto Superpowers Docs Map

> **AI agent usage:** This superpowers document may describe planning workflow rather than live implementation. Read `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, and `docs/AI-AGENT-DOCS.md`, then verify current code before changing behavior.

Before using this folder as current-state guidance, read `../PROJECT-CONTEXT.md` and `../SESSION-BOOTSTRAP.md`. The files here are planning and delivery artifacts; they may describe earlier target shapes, startup assumptions, or counts than the live repository now has.

This folder contains the 2026-03-17 Tenanto delivery set in two layers:

- `plans/` contains implementation plans with task-level execution detail
- `specs/` contains design documents that explain intent, scope, architecture, and acceptance behavior

Every major implementation plan now has a matching design spec. Use the design doc first to understand product intent and boundaries, then use the implementation plan to execute the work.

For the recommended rollout order and branch strategy, start with `EXECUTION-ROADMAP.md`.
For phase-by-phase entry and exit criteria, use `PHASE-GATES.md`.
For recommended branch names, fold-in rules, and merge order, use `BRANCH-PLAYBOOK.md`.

## Recommended Rollout Order

Follow this order unless you have a strong reason to restructure the branch plan:

1. Foundation auth and onboarding
   Plan: `plans/2026-03-17-foundation-auth-onboarding.md`
   Spec: `specs/2026-03-17-foundation-auth-onboarding-design.md`

2. Shared interface elements
   Plan: `plans/2026-03-17-shared-interface-elements.md`
   Spec: `specs/2026-03-17-shared-interface-elements-design.md`

3. Admin organization operations
   Plan: `plans/2026-03-17-admin-organization-operations.md`
   Spec: `specs/2026-03-17-admin-organization-operations-design.md`

4. Manager role parity
   Plan: `plans/2026-03-17-manager-role-parity.md`
   Spec: `specs/2026-03-17-manager-role-parity-design.md`
   Recommendation: if admin organization operations has not started yet, fold manager parity directly into that branch instead of treating it as a separate follow-up.

5. Tenant self-service portal
   Plan: `plans/2026-03-17-tenant-self-service-portal.md`
   Spec: `specs/2026-03-17-tenant-self-service-portal-design.md`

6. Superadmin control plane
   Plan: `plans/2026-03-17-superadmin-control-plane.md`
   Spec: `specs/2026-03-17-superadmin-control-plane-design.md`
   Recommendation: this can begin after the shared interface layer is stable, but it is easier to finish once the core auth and shell slices are already in place.

7. Cross-cutting behavioral rules
   Plan: `plans/2026-03-17-cross-cutting-behavioral-rules.md`
   Spec: `specs/2026-03-17-cross-cutting-behavioral-rules-design.md`
   Recommendation: apply this after the major product surfaces exist so the shared rules can be layered over real workflows instead of placeholders.

8. Missing information closures
   Plan: `plans/2026-03-17-missing-information-closures.md`
   Spec: `specs/2026-03-17-missing-information-closures-design.md`
   Recommendation: keep this last because it is a hardening and ambiguity-closure pass across the earlier slices.

## Parallelization Notes

- `foundation-auth-onboarding` must land before any role-aware workspace work.
- `shared-interface-elements` should land before most shell-dependent slices.
- `admin-organization-operations` is the main dependency for both `manager-role-parity` and `tenant-self-service-portal`.
- `superadmin-control-plane` depends mostly on foundation plus shared shell, so it can overlap with later organization work if the team needs parallel streams.
- `cross-cutting-behavioral-rules` and `missing-information-closures` are best treated as hardening layers after the main experiences exist.

## How To Use This Set

1. Read the relevant spec in `specs/` to understand product intent and boundaries.
2. Open the matching implementation plan in `plans/`.
3. Respect the prerequisite notes in the plan before starting implementation.
4. If a slice references another slice as a dependency, prefer reusing the shared domain or shell behavior instead of duplicating it locally.

## Slice Map

| Slice | Design Doc | Implementation Plan |
| --- | --- | --- |
| Foundation auth and onboarding | `specs/2026-03-17-foundation-auth-onboarding-design.md` | `plans/2026-03-17-foundation-auth-onboarding.md` |
| Shared interface elements | `specs/2026-03-17-shared-interface-elements-design.md` | `plans/2026-03-17-shared-interface-elements.md` |
| Admin organization operations | `specs/2026-03-17-admin-organization-operations-design.md` | `plans/2026-03-17-admin-organization-operations.md` |
| Manager role parity | `specs/2026-03-17-manager-role-parity-design.md` | `plans/2026-03-17-manager-role-parity.md` |
| Tenant self-service portal | `specs/2026-03-17-tenant-self-service-portal-design.md` | `plans/2026-03-17-tenant-self-service-portal.md` |
| Superadmin control plane | `specs/2026-03-17-superadmin-control-plane-design.md` | `plans/2026-03-17-superadmin-control-plane.md` |
| Cross-cutting behavioral rules | `specs/2026-03-17-cross-cutting-behavioral-rules-design.md` | `plans/2026-03-17-cross-cutting-behavioral-rules.md` |
| Missing information closures | `specs/2026-03-17-missing-information-closures-design.md` | `plans/2026-03-17-missing-information-closures.md` |
