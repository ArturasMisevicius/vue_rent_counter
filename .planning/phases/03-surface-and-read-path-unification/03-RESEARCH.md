# Phase 3: Surface and Read Path Unification - Research

**Researched:** 2026-03-19
**Domain:** Canonical role entry paths, single-source navigation, and shared workspace-aware read builders in a Laravel 12 + Filament 5 + Livewire 4 monolith
**Confidence:** MEDIUM

## Summary

Phase 3 should reduce duplication, not add another abstraction layer on top of existing duplication. The key move is to pick one authoritative source for entry paths and navigation, then make the existing support builders for reports, search, and tenant invoices the shared read contracts instead of allowing each surface to continue shaping data independently.

The highest-value work is already visible in the live tree. `LoginRedirector` and `DashboardUrlResolver` are natural candidates for canonical entry behavior. `AppPanelProvider`, `config/tenanto.php`, and `NavigationBuilder` represent the current navigation split that needs to be simplified. Report builders and tenant invoice support classes already exist; Phase 3 should unify their use and remove surface-specific drift rather than inventing a second set of read abstractions.

## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| ARCH-01 | One authoritative entry path per role | Recommends consolidating login redirects and destination aliases into one route-resolution contract. |
| ARCH-02 | One canonical source of truth for navigation and workspace switching | Recommends selecting either config-driven or builder-driven navigation and delegating everything else to it. |
| ARCH-03 | Standardized workspace-aware read paths | Recommends reusing support query and presenter classes for reports, search, and invoice surfaces. |
| PORT-01 | Coherent tenant invoice history and detail experience | Recommends one tenant invoice read contract plus aligned detail and download behavior. |
| PORT-03 | Authorized staff access the same billing-related documents and supporting records | Recommends aligning staff invoice and report surfaces to the same read models that tenant-facing invoice views rely on. |

## Recommended Plan Shape

1. Canonicalize role entry paths and route redirects.
2. Collapse navigation and dashboard targeting onto one source of truth.
3. Standardize shared read builders for reports, search, and workspace-heavy tables.
4. Unify invoice and document read experience for tenant and staff surfaces.

## Anti-Patterns to Avoid

- Keeping multiple active entry paths that diverge in behavior after login
- Adding more role checks to `AppPanelProvider` without removing the parallel config or builder source
- Leaving report and invoice consistency to presentation-layer formatting instead of shared builders
- Treating tenant invoice history and staff invoice views as separate products when they are reading the same invoice domain

---

*Research date: 2026-03-19*
