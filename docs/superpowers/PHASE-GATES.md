# Tenanto Phase Gates

> **AI agent usage:** This superpowers document may describe planning workflow rather than live implementation. Read `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, and `docs/AI-AGENT-DOCS.md`, then verify current code before changing behavior.

This file defines the recommended entry and exit criteria for each phase in the 2026-03-17 rollout.

Use it together with:

- `README.md` for the docs map
- `EXECUTION-ROADMAP.md` for rollout order and branch strategy
- the per-slice spec and plan files for implementation detail

The intent is simple: do not move to the next major slice just because code exists. Move when the previous slice is stable enough that downstream work can safely depend on it.

## Phase 1: Foundation Auth and Onboarding

### Entry Gate

- This is the first implementation phase.
- No downstream workspace work should start before this slice is materially present.

### Exit Gate

- Shared public auth pages exist for login, register, forgot password, reset password, and invitation acceptance.
- Admin self-registration works and redirects into onboarding.
- Admin onboarding creates organization ownership and the initial subscription.
- Manager and tenant invitation acceptance works end to end.
- Role-aware login redirects are in place for superadmin, admin, manager, and tenant users.
- Organization and account accessibility checks exist at the auth boundary.
- Locale persistence exists at the authenticated-user level.
- Tenant isolation foundations are present for later slices to inherit.

### Do Not Move On Until

- Later slices can reliably assume a signed-in user has a valid role, organization context when appropriate, and a stable post-login destination.

## Phase 2: Shared Interface Elements

### Entry Gate

- Phase 1 is stable enough that role-aware authenticated routes exist.

### Exit Gate

- The shared authenticated shell is present for admin-like users and tenant pages.
- Role-aware navigation primitives exist.
- The topbar, notifications, locale switching, and profile entry point exist.
- Tenant bottom navigation exists as a reusable shell primitive.
- Error pages and impersonation banner behavior are established.
- The shell is query-safe and built on support classes/components rather than view-local logic.

### Do Not Move On Until

- Later slices can plug into one shared shell instead of improvising their own chrome or navigation.

## Phase 3: Admin Organization Operations

### Entry Gate

- Phases 1 and 2 are stable.
- The team is ready to introduce the main organization-owned domain model.

### Exit Gate

- Organization-scoped models exist for the core operational domain.
- Admin-like Filament resources exist for buildings, properties, tenants, meters, readings, invoices, tariffs, providers, settings, and reports as planned.
- Shared action and support layers own the business logic for CRUD, assignment, validation, and billing workflows.
- Organization dashboard widgets are driven by real operational data.
- Organization-level settings and profile behavior are in place.
- Query scoping and policy boundaries enforce organization isolation.

### Do Not Move On Until

- Manager parity and tenant portal work can reuse the domain directly instead of inventing placeholder models or duplicate rules.

## Phase 4: Manager Role Parity

### Entry Gate

- Phase 3 is in progress or complete.
- If Phase 3 has not started yet, this work should be folded into the same branch instead of started separately.

### Exit Gate

- Managers can access the same organization workspace routes and resources as admins where the product intends parity.
- Managers keep the shared `Account` navigation with `Profile` and `Settings`.
- Managers do not see subscription-usage dashboard content.
- Managers do not see admin-only settings sections.
- No manager-only duplicate resources, pages, or routes were introduced.

### Do Not Move On Until

- The team can treat admin and manager operations as one shared workspace with only the documented differences.

## Phase 5: Tenant Self-Service Portal

### Entry Gate

- Phase 3 is stable enough that the property, meter, reading, invoice, and settings domain exists.
- Phase 2 shell primitives exist for tenant navigation and shared presentation.

### Exit Gate

- Tenant routes exist for home, readings, invoices, property details, and profile management.
- The four-item bottom navigation is enforced consistently.
- Tenant reads are scoped correctly to allowed property, meter, reading, and invoice data.
- Reading submission reuses shared validation behavior.
- Invoice history and download access work through tenant-safe routes.
- Tenant profile and password flows exist without mirroring the admin settings surface.

### Do Not Move On Until

- The tenant experience no longer depends on placeholder views or admin-only assumptions.

## Phase 6: Superadmin Control Plane

### Entry Gate

- Phases 1 and 2 are stable.
- The platform shell is ready to host superadmin-only navigation and pages.

### Exit Gate

- Superadmin-only platform dashboard exists with global governance signals.
- Platform resources exist for organizations, users, subscriptions, and the planned governance models.
- System settings, languages, notifications, audit visibility, security views, and integration-health surfaces exist as designed.
- Superadmin impersonation entry points use the shared impersonation contract.
- Platform behavior is clearly separated from organization-scoped operator behavior.

### Do Not Move On Until

- Platform-governance work no longer needs to piggyback on placeholder or ad hoc admin pages.

## Phase 7: Cross-Cutting Behavioral Rules

### Entry Gate

- The major user-facing surfaces now exist: admin workspace, tenant portal, and where relevant the superadmin plane.

### Exit Gate

- Shared subscription access enforcement exists across the relevant surfaces.
- Meter-reading validation is centralized and reused everywhere.
- Finalized-invoice mutability rules are enforced consistently.
- Shared refresh, loading, filter persistence, and locale-fallback behavior is applied where designed.
- The rules are implemented as shared guardrails, not copy-pasted into each page or workflow.

### Do Not Move On Until

- The major surfaces behave consistently under the same shared business rules.

## Phase 8: Missing Information Closures

### Entry Gate

- The earlier product surfaces are present and real enough to harden.

### Exit Gate

- Suspension and session-timeout behavior is explicit and consistent.
- Invitation resend and password-reset lifecycle behavior is confirmed.
- Tenant historical invoice continuity and post-unassignment limits are enforced.
- Breadcrumbs and empty-state behavior are applied consistently where intended.
- The remaining product ambiguities covered by this slice are resolved through shared middleware, actions, presenters, or components.

### Done Definition

- The system no longer relies on informal assumptions for the edge cases this slice covers.

## Global Rule For Advancing Phases

Do not advance a phase solely because the happy path works.

Advance when:

- the phase’s shared contracts are real
- downstream slices can depend on them without rewriting them
- the design intent in the matching spec is materially reflected in code

If those conditions are not true yet, the correct move is usually to finish or harden the current phase rather than start the next one.
