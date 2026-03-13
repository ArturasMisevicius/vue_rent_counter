## Context
The project currently includes both custom Blade/Livewire interfaces and Filament panel providers (`admin`, `superadmin`, `tenant`) that expose overlapping route surfaces. This creates route ambiguity (`/admin`, `/superadmin`, `/tenant`), mixed navigation behavior, and higher maintenance overhead for role authorization checks.

The target state is custom web UI only, with Tailwind + Livewire, using one shared backoffice layout for `superadmin/admin/manager` and a separate tenant layout.

## Goals / Non-Goals
- Goals:
  - Eliminate Filament browser-facing UI endpoints.
  - Standardize backoffice layout for `superadmin/admin/manager`.
  - Preserve tenant-specific layout and UX.
  - Enforce explicit role-based route access for every web route.
  - Add automated route access tests by role matrix.
- Non-Goals:
  - Replacing all backend domain services or policies.
  - Rebuilding business logic already used by controllers.
  - Removing Filament package dependencies from `composer.json` in this change (unless needed later).

## Decisions
- Decision: Keep Laravel + Livewire + Tailwind for all browser interfaces.
  - Why: Matches project stack and user request.
- Decision: Remove Filament panel providers from active app provider registration to disable Filament panel route bootstrapping.
  - Why: Hard guarantee that Filament UI is not exposed over web routes.
- Decision: Maintain one shared backoffice layout and use role-aware navigation composition.
  - Why: Reduces duplicate templates and makes permissions easier to verify.
- Decision: Keep tenant layout as dedicated template.
  - Why: Tenant UX and scope differ from operational backoffice roles.

## Alternatives Considered
- Hide Filament routes behind non-linked URLs while keeping providers active.
  - Rejected: Still exposes Filament UI in browser and violates the “remove” requirement.
- Keep separate layouts for each role.
  - Rejected: Increases maintenance and diverges from requested unified backoffice design.

## Risks / Trade-offs
- Risk: Existing links, redirects, and legacy route names may break.
  - Mitigation: Provide explicit compatibility redirects only when pointing to custom pages.
- Risk: Tests may initially fail due to changed route expectations.
  - Mitigation: Add role-matrix route tests first, then refactor routing/layouts.
- Risk: Some Livewire/Blade components may assume Filament assets/context.
  - Mitigation: Audit and replace those dependencies in custom templates.

## Migration Plan
1. Define route access matrix by role and convert to feature tests.
2. Introduce unified backoffice layout and migrate `superadmin/admin/manager` pages.
3. Keep tenant layout isolated and verify tenant pages.
4. Remove/disable Filament panel providers and browser-exposed Filament routes.
5. Update navigation/menu generation to custom routes only.
6. Run route list checks and full role access test suite.

## Open Questions
- None. Scope clarified: fully remove Filament web interface exposure.
