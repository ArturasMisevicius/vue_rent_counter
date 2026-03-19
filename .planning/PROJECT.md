# Tenanto

## What This Is

Tenanto is a brownfield multi-tenant utility billing and property management SaaS built on Laravel, Filament, Livewire, and Tailwind. The current application already serves platform administration, organization-level operations, billing workflows, and tenant self-service, and this project is focused on consolidating, hardening, and standardizing that existing system rather than inventing a new product surface.

## Core Value

Tenanto must deliver tenant-safe utility billing and property management workflows on a clean, consistent application foundation that the team can evolve confidently.

## Requirements

### Validated

- ✓ Multi-tenant organizations, role-segmented access, and tenant-scoped user flows already exist in the current application — existing
- ✓ Utility billing, invoicing, meter-reading, and property-management capabilities already exist in the current product surface — existing
- ✓ Filament- and Livewire-driven administrative and tenant workflows are already part of the working brownfield codebase — existing

### Active

- [ ] Consolidate the application into a standardized Laravel 12 + Filament 5.3 + Livewire 4 architecture with clear boundaries, strict typing, and Eloquent-first patterns.
- [ ] Remove unsafe or legacy platform surfaces, including publicly exposed debug entrypoints, and align the app with middleware-, policy-, and role-driven security rules.
- [ ] Standardize billing, invoice, meter-reading, reporting, and organization-management flows to eliminate duplicate services, role drift, and inconsistent request or panel patterns.
- [ ] Bring controllers, requests, panels, validation, tests, documentation, and development conventions into prompt-library-compliant project standards.

### Out of Scope

- Net-new product capabilities unrelated to cleanup and standardization — Milestone 1 is focused on foundation repair, not feature expansion.
- Preserving every legacy route, panel, controller shape, or workflow unchanged — aggressive standardization is allowed when tenant and billing correctness are maintained.
- Re-platforming away from the confirmed Laravel, Filament, Livewire, and Tailwind stack — the goal is to clean and unify the existing foundation, not replace it.

## Context

Tenanto is an existing brownfield SaaS with meaningful domain coverage already in production-oriented code. The live repository and `.planning/codebase/` map are the source of truth whenever pasted notes, historical docs, or prompt-library inventory disagree with the current tree. The application is expected to support four roles: `SUPERADMIN` for platform-wide control, `ADMIN` for organization ownership and billing-gated management, `MANAGER` as a restricted organization operator, and `TENANT` as a resident limited to property-scoped self-service actions such as meter readings and invoice viewing.

The first milestone is intentionally cross-cutting. It must optimize for foundation cleanup first, while also advancing architecture consolidation, security hardening, billing-domain cleanup, and prompt-library compliance. The user explicitly wants aggressive standardization in Milestone 1, which means legacy duplication and weak patterns may be retired or reshaped broadly as long as tenant isolation, billing correctness, and core product behavior remain trustworthy.

## Constraints

- **Tech stack**: Laravel 12, Filament 5.3, Livewire 4, Tailwind CSS 4, Pest 4, PHPUnit 12, Alpine.js 3, Sanctum 4, and modern PHP remain the implementation foundation — this is a cleanup of the existing stack, not a rewrite.
- **Brownfield safety**: Tenant isolation, billing correctness, and role-boundary enforcement must be preserved while the internal architecture is reshaped — cleanup cannot silently break core business flows.
- **Source of truth**: The live repository plus `.planning/codebase/` documents override stale counts, panel/provider names, and MCP assumptions from pasted briefs — planning must reflect the current codebase.
- **Project standards**: Strict typing, Eloquent-first data access, middleware-driven security chains, and prompt-library conventions are required targets for touched code — the cleanup should reduce variance, not add more.

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Treat Tenanto as a brownfield cleanup project | The product already exists and the work is focused on consolidation, hardening, and standardization | — Pending |
| Use the live repository and codebase map as the planning source of truth | Pasted counts and historical notes may drift from the current tree | — Pending |
| Make Milestone 1 a foundation-cleanup milestone | The user wants the roadmap to optimize first for structural consolidation and consistency | — Pending |
| Allow aggressive standardization in Milestone 1 | Retiring legacy patterns is acceptable if tenant and billing correctness stay intact | — Pending |
| Fold architecture, security, billing cleanup, and prompt-library compliance into the same milestone | The user wants the roadmap to optimize across all four priorities, not just one isolated stream | — Pending |

---
*Last updated: 2026-03-19 after initialization*
