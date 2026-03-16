# Change: Refactor Canonical Multi-Role Platform Surface

## Why
The platform is already partway through several migrations, but the target architecture is still fragmented across multiple proposals, legacy UI shells, mixed authorization patterns, inconsistent validation surfaces, overlapping controller/Blade and Filament/Livewire flows, and transitional translation and compatibility bridges. That makes it hard to approve, schedule, and execute the upgrade as one coherent architecture program.

This change creates the single umbrella OpenSpec proposal for the full Laravel 12 + Filament 5 + Livewire 4 refactor program requested for the multi-role billing platform. It defines the approved end state first and defers implementation until that end state is explicitly approved.

## What Changes
- Establish one shared non-Filament Blade layout as the canonical shell for custom pages.
- Establish one shared design system and shared component library for all roles.
- Make authorization policy-driven across Blade, Livewire, and Filament instead of role-specific templates and raw role checks.
- Make superadmin the only global full-control role with deep metadata visibility and full CRUD across managed resources.
- Standardize validation so every mutating HTTP action uses a dedicated `FormRequest`, and Livewire components do not define inline validation rules.
- Make tenant-facing custom pages Livewire-first while keeping admin, manager, and superadmin CRUD Filament-first.
- Complete translations for every enabled locale and remove translation fallback bridges.
- Remove dead legacy files, duplicate UI pieces, compatibility shims, and compatibility routes once their callers are migrated.
- Add Pest guardrails, targeted migration tests, and a final legacy-removal report as required program outputs.

## Impact
- **BREAKING**: legacy layouts, duplicate components, inline validation patterns, compatibility aliases, duplicate controller-rendered CRUD surfaces, and fallback translation behavior will be removed or replaced once their canonical replacements are verified.
- Affected specs:
  - `canonical-platform-surface`
- Related focused changes that become implementation slices under this umbrella:
  - `refactor-shared-layout-components`
  - `normalize-authorization-surfaces`
  - `enforce-validation-architecture`
  - `migrate-tenant-portal-to-livewire-filament-backoffice`
  - `remove-translation-fallback-and-legacy-shims`
  - `refactor-unified-ui-system`

## Deliverables
- One approved umbrella proposal and design.
- One phased task list for execution sequencing.
- Implementation only after approval.
- Pest coverage for role access, CRUD, shared components, locale completeness, and legacy regression guardrails.
- A legacy-removal report at the end of execution documenting deleted files, removed routes, removed shims, and remaining follow-up work.

## Scope Notes
- This proposal does **not** start implementation.
- This proposal does **not** require literal Filament internals to share a Blade layout file; the shared-layout requirement applies to the non-Filament custom UI shell, while Filament remains the canonical CRUD surface with aligned design tokens and authorization behavior.
