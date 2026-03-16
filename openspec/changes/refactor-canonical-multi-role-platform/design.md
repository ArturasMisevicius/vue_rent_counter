## Context
The repository already contains multiple partial proposals that each solve one part of the requested upgrade, but there is not yet one approved architecture document that describes the complete end state. Meanwhile, the codebase still shows transitional behavior:

- multiple non-Filament layouts and role-scoped component folders,
- mixed policy and raw role-string authorization checks,
- controller and route-level inline validation alongside existing `FormRequest` coverage,
- tenant-facing custom pages still rendered through controllers and Blade while Livewire and Filament already exist,
- translations split between canonical and legacy handling,
- and temporary compatibility shims still present for Filament and translation migration.

The right next step is therefore one umbrella proposal that aligns the full target architecture and sequences implementation into safe phases.

## Goals / Non-Goals
- Goals:
  - One shared non-Filament Blade shell.
  - One shared component system and design language across roles.
  - Policy-driven visibility everywhere.
  - Superadmin as the only full global-control role.
  - `FormRequest`-first HTTP validation and no inline Livewire validation rules.
  - Tenant portal Livewire-first, backoffice CRUD Filament-first.
  - Complete translation support for every enabled locale.
  - Removal of dead legacy files, routes, and compatibility bridges.
  - Pest guardrails and a final legacy-removal report.
- Non-Goals:
  - Starting implementation before approval.
  - Replacing Filament itself.
  - Rewriting billing domain rules beyond what is needed to relocate UI, authorization, validation, and localization behavior.
  - Adding a new enabled locale beyond the configured locale set during this approval step.

## Architecture Options Considered
- Option A: Big-bang rewrite into one branch.
  - Rejected because the scope crosses UI, auth, validation, Livewire, Filament, and translation layers and would make regression isolation too difficult.
- Option B: Continue only with independent focused proposals.
  - Rejected because approval and sequencing stay fragmented and the target architecture remains implicit.
- Option C: One umbrella proposal with phased execution slices.
  - Recommended because it gives one reviewable architecture decision while still preserving safe implementation phases and targeted verification.

## Decisions
- The approved end state is one canonical multi-role platform surface made of:
  - one shared non-Filament Blade shell,
  - one shared component system,
  - policy-driven rendering and actions,
  - Livewire-first tenant modules,
  - Filament-first backoffice CRUD,
  - canonical translation files under `lang/`,
  - and no legacy compatibility bridges once migration is complete.
- Existing focused OpenSpec changes remain useful as implementation slices, but this umbrella change becomes the approval source of truth.
- Pest is the canonical regression framework for this program and must protect both business-critical flows and architectural guardrails.
- Legacy pattern guardrails may begin as baselines during migration and tighten to zero once each migration slice is completed.

## Risks / Trade-offs
- Risk: The scope is broad enough that stakeholders could approve the vision but underestimate delivery effort.
  - Mitigation: the phased task list makes the execution order explicit.
- Risk: Legacy routes, layouts, and shims are still referenced today.
  - Mitigation: all destructive cleanup is gated by zero-reference verification and Pest coverage.
- Risk: Translation and validation cleanup can expose hidden drift rather than merely removing code.
  - Mitigation: require targeted test coverage before deleting fallback bridges.
- Risk: The full repository test suite is currently noisy in places, which can hide migration regressions.
  - Mitigation: add focused guardrails per phase and treat full-suite stability as a separate completion gate during implementation.

## Migration Plan
1. Approve the umbrella architecture.
2. Execute Phase 1 mapping and confirm baselines.
3. Implement each focused slice in order: shared UI shell, authorization, validation, canonical surfaces, localization cleanup.
4. Verify with targeted Pest coverage after each slice.
5. Remove legacy files, routes, and shims only after callers are gone and tests are green.
6. Run the full repository suite and publish the legacy-removal report before closure.

## Open Questions
- None required for proposal creation. The target architecture and sequencing are sufficiently defined by the requested deliverables and the existing focused proposals.
