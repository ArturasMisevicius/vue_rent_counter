# Tenanto Missing Information Closures Design

## Goal

Close the remaining behavior gaps around authentication lifecycle, invitation recovery, tenant continuity after property unassignment, breadcrumb visibility, and first-run empty states so the previously defined Tenanto slices behave consistently in edge cases.

This design is not a new product area. It is a clarification and closure layer on top of the earlier foundation-auth, shared-shell, admin, superadmin, tenant-portal, and cross-cutting behavior work.

## Scope

This design includes:

- suspended-account login behavior and organization-wide session invalidation
- session-timeout messaging and intended-destination recovery for web and Filament routes
- password-reset eligibility and token-lifetime clarification
- resend-invitation behavior for inactive tenant and manager accounts
- expired-invitation guidance
- historical invoice retention after tenant unassignment
- invoice-generation cutoff after tenant unassignment
- tenant meter visibility scoped only to the tenant's current property
- breadcrumbs on non-dashboard pages
- friendly empty states for first-run organization list pages

This design does not include:

- a new authentication system
- a second password-reset flow
- a second invitation model or invitation table
- invoice proration for partially overlapping assignment periods
- tenant access to historical meters after unassignment
- breadcrumbs on dashboard pages
- one-off empty-state templates per page when a shared primitive or Filament API is sufficient

## Relationship To Existing Slices

This document assumes the baseline behavior defined by these earlier slices:

- `docs/superpowers/specs/2026-03-17-foundation-auth-onboarding-design.md`
- shared authenticated-shell behavior from the shared-interface-elements plan
- organization-scoped domain and policy behavior from the admin-organization-operations plan
- tenant routing and presenter behavior from the tenant-self-service-portal plan
- suspension and operational actions from the superadmin-control-plane plan
- shared runtime rules from the cross-cutting-behavioral-rules plan

The role of this document is to resolve product ambiguities that those slices intentionally left open.

## Approved Product Decisions

- Password-reset tokens remain valid for `60` minutes. The existing broker-based reset flow stays in place.
- Organization invitations remain single-use and expire after `7` days.
- Suspending an organization blocks new logins and invalidates all active sessions for users in that organization.
- Session-expired messaging appears only when a previously authenticated session has lapsed. A normal guest reaching a protected route does not see that banner.
- Historical invoices remain visible and downloadable after tenant unassignment.
- Invoice generation must not create invoices for billing periods that start after the tenant's unassignment timestamp.
- Tenant meter visibility is based only on the tenant's current property assignment, not on historical assignment, organization membership, or building membership.
- Breadcrumbs appear on non-dashboard pages only.
- Empty states should use Filament's built-in empty-state hooks where possible, backed by shared copy and presentation primitives.

## Behavioral Architecture

These closures should be implemented as shared rules rather than page-local exceptions.

- Authentication and suspension rules belong in auth actions, middleware, and request-time accessibility guards.
- Invitation recovery belongs in the existing invitation action/notification flow, not in a parallel invite system.
- Tenant continuity rules belong in shared invoice-eligibility services, presenters, and policies so invoice access and meter access can diverge safely.
- Breadcrumbs and empty states belong in shared shell or Filament primitives so the same UX contract applies across tenant, admin, and superadmin surfaces.

The key architectural principle is that each rule must live once and then be surfaced through thin controllers, Filament pages, Blade views, and presenters.

## Authentication, Suspension, And Session Lifecycle

### Login And Suspension Checks

The login form remains the single entry point for all roles. The earlier auth design already blocks suspended accounts and suspended organizations; this closure clarifies the behavior in two moments:

- before login completes, suspended users or users in suspended organizations must receive the explicit suspension message rather than a generic redirect outcome
- after suspension is applied by a superadmin, already-authenticated users in that organization must lose their active sessions immediately

Organization suspension therefore has two layers:

1. eager invalidation of stored sessions when the suspension action runs
2. request-time enforcement through the existing account-access middleware for any stale session that survives in flight

This preserves a clear operational guarantee: suspension is both immediate and durable.

### Session Timeout UX

Session timeout remains a standard Laravel session expiry, but the user experience is tightened.

When an authenticated session has expired and the user hits a protected route:

- web routes redirect to the login page
- admin-panel routes redirect to the same login page
- the login page shows the message `Your session expired. Please log in again.`
- the originally intended destination is preserved so the user returns to that page after successful login

When a guest who was never authenticated visits a protected route, the application still redirects to the login page, but without the session-expired message. The product should not imply that a guest user had an interrupted session.

### Password Reset Clarifications

The password-reset flow stays role-agnostic and continues to support:

- superadmin
- admin
- manager
- tenant

The closure here is confirmatory rather than structural:

- all existing roles may request reset links when their account exists and is eligible
- the system continues using the existing password broker
- reset tokens remain valid for `60` minutes and fail afterward

No secondary reset mechanism should be introduced.

## Invitation Lifecycle Recovery

### Existing Invitation Model

Manager and Tenant onboarding continues to use the existing organization-invitation model. Invitations remain:

- role-specific
- single-use
- time-limited to `7` days
- the only valid path into those roles

Accepted invitations remain immutable and may not be reactivated.

### Resend Invitation Behavior

If a Manager or Tenant has not activated their account and the invitation is expired, invalid, or otherwise no longer usable, an administrator may resend the invitation from the existing admin workflow.

Resend behavior must:

- issue a fresh token
- issue a fresh expiry window
- leave accepted invitations untouched
- remain unavailable once the target user/account is already activated

The resend action is therefore a renewal of pending access, not an edit of historical invitation state.

### Expired Invitation Experience

The invitation acceptance page should continue to use the same acceptance flow and route contract, but the expired state must clearly tell the recipient to contact their administrator for a new invitation.

The user-facing contract is:

- expired invitations fail clearly
- the guidance is actionable
- the freshest valid invitation governs access

## Tenant Continuity After Unassignment

Tenant unassignment creates two different continuity questions, and this design treats them separately:

1. what financial history stays visible
2. what current operational data stays actionable

The product must not collapse those into one rule.

### Historical Invoice Continuity

When a tenant is unassigned from a property:

- existing invoices remain linked to that tenant
- the tenant can still view and download their historical invoices
- admins and managers can still see those invoices in tenant and invoice views

Historical invoice access follows tenant identity and financial history, not current property assignment.

### Invoice Generation Cutoff

After unassignment, the system must stop producing new invoices for billing periods that begin after the recorded unassignment timestamp.

This means:

- invoice generation checks assignment history before generating single or bulk invoice output
- a tenant who is no longer assigned does not receive fresh invoices for later billing windows
- no retroactive deletion or ownership nulling occurs for already-issued invoices

This slice does not introduce proration for partially overlapping periods. If later product work requires billing logic for periods that straddle the cutoff, that should be designed separately.

### Current Meter Visibility

Tenant-visible meters are governed only by the tenant's current property assignment.

After unassignment or reassignment:

- only meters attached to the tenant's current property may be shown on tenant-facing property and reading surfaces
- the submit-reading experience may offer only meters from that current property
- manually posting another meter identifier must be rejected

The application must never infer tenant meter access from:

- shared organization membership
- shared building membership
- historical assignment

This deliberately allows a tenant to have zero visible meters while still retaining access to historical invoices.

## Authorization And Isolation Adjustments

The earlier isolation model continues to apply, but this closure sharpens the boundary between invoice access and meter access.

### Invoice Access Rule

Tenant invoice visibility and download authorization are tied to tenant ownership of the invoice record, even if the tenant no longer has a current property assignment.

### Meter Access Rule

Tenant meter visibility and reading submission authorization are tied to the tenant's current property assignment only.

These two rules must remain separate in presenters, queries, and policies. A single broad "tenant belongs to organization" check is insufficient for either rule.

## Shared Navigation And Empty-State UX

### Breadcrumb Contract

Breadcrumbs should appear on all non-dashboard pages where they help users orient themselves, including:

- tenant custom pages such as `My Property` and `Invoices`
- Filament resource view pages such as buildings, properties, tenants, meters, and invoices

Breadcrumbs should not appear on:

- tenant dashboard/home
- organization dashboard
- platform dashboard

The breadcrumb trail should be explicit per page rather than inferred from URL segments. The final breadcrumb item represents the current page and must render as plain text rather than a clickable link.

For admin and superadmin Filament pages, the application should use Filament's breadcrumb APIs where possible instead of bypassing the framework. For tenant and other custom Blade pages, the shared shell should expose a reusable breadcrumb component.

### First-Run Empty States

A newly created organization should not land on cold list pages that only show empty table chrome or generic framework copy.

The first-run list pages for:

- buildings
- properties
- tenants
- meters

should each show a friendly empty state with:

- a clear heading
- concise guidance about what the page is for
- a primary action that leads directly to the relevant create flow

The visual treatment should come from a shared empty-state primitive, while Filament's built-in empty-state heading, description, and action hooks should remain the preferred integration path.

## Edge Cases And Failure Handling

### Suspended Organization With Existing Sessions

If an organization is suspended while one of its users is active, the session should be terminated. Any in-flight request that still reaches the app must then fail safely through the accessibility middleware.

### Expired Session Versus Guest Access

The system must distinguish a timed-out authenticated user from a normal guest user. Only the former receives the timeout banner.

### Expired Invitation

Expired invitations do not silently fail. The user must be told that the invitation is no longer valid and that an administrator must resend it.

### Tenant Without Current Assignment

A tenant with no current assignment may still see historical invoices, but must not see meters or submit readings for unassigned or unrelated properties.

### Empty Organization Lists

The empty state should guide the first meaningful action, not merely confirm that the table has zero records.

## Testing Strategy

This closure should be protected primarily through regression-oriented feature tests.

Required coverage areas:

- login rejection and session invalidation for suspended organizations
- session-timeout redirect and flash-message behavior for web and admin routes
- role-wide password-reset eligibility and `60`-minute expiry behavior
- invitation resend eligibility, token refresh, and expired-invite messaging
- historical invoice visibility/download after tenant unassignment
- invoice-generation cutoff after unassignment
- tenant meter scoping on property views and reading submission
- breadcrumb rendering on non-dashboard pages and absence on dashboards
- friendly empty states on first-run organization lists

Where possible, the tests should verify visible product behavior rather than internal implementation details so the shared rules can later move between actions, middleware, and presenters without invalidating the spec.

## Delivery Boundary For This Closure Slice

This slice is complete when:

- auth and session lifecycle edge cases behave consistently across public and Filament routes
- invitation resend covers inactive tenant and manager activation recovery
- tenant unassignment preserves historical invoice continuity without leaking current operational access
- breadcrumbs are consistently present on non-dashboard pages
- first-run organization list pages present guided empty states instead of default empty tables

## Out Of Scope For The Closure Work

- redesigning the authentication system
- changing invitation roles or invitation-origin rules
- adding online payments
- exposing historical meter data after unassignment
- introducing dashboard breadcrumbs
- building custom empty-state pages outside shared component and Filament patterns
- implementing billing proration for partially overlapping assignment periods
