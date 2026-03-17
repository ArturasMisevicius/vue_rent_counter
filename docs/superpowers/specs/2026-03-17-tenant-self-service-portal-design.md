# Tenanto Tenant Self-Service Portal Design

## Goal

Define the tenant-facing self-service portal as a mobile-first experience where tenants can review their property and billing context, submit readings, browse invoices, and manage their own profile without exposing the broader admin workspace.

This slice should feel like the same Tenanto product as the admin shell while staying intentionally simpler. Tenants live inside the shared authenticated shell language, but their runtime stays outside Filament and is organized around a fixed bottom-navigation model.

## Scope

This slice includes:

- a Blade-based tenant portal inside the shared authenticated shell
- fixed bottom navigation with `Home`, `Readings`, `Invoices`, and `Profile`
- tenant home summaries for current balance, meter activity, and contextual property information
- reading-submission flows
- invoice history and invoice-download access
- a secondary `My Property` route outside the bottom nav
- tenant profile and password management
- strict query and policy scoping to the signed-in tenant’s allowed property, meters, readings, and invoices

This slice does not include:

- a tenant Filament panel
- a sidebar or a fifth bottom-navigation item
- online payment processing
- tenant-specific copies of property, invoice, or meter domain logic
- cross-property or cross-organization browsing

## Dependency Context

This design depends on:

- `docs/superpowers/specs/2026-03-17-foundation-auth-onboarding-design.md` for tenant role rules, login redirects, and invitation-based account creation
- `docs/superpowers/specs/2026-03-17-shared-interface-elements-design.md` for the shared shell and bottom-navigation foundation
- `docs/superpowers/specs/2026-03-17-admin-organization-operations-design.md` for properties, meters, readings, invoices, and organization settings

It is also constrained by:

- `docs/superpowers/specs/2026-03-17-cross-cutting-behavioral-rules-design.md` for shared validation and runtime behavior
- `docs/superpowers/specs/2026-03-17-missing-information-closures-design.md` for invoice-history continuity and post-unassignment access rules

## Approved Product Decisions

- Tenant pages remain standard Laravel web routes with Blade views and small Livewire islands where responsiveness helps.
- The bottom navigation always contains exactly four items: `Home`, `Readings`, `Invoices`, and `Profile`.
- `My Property` is a secondary route, not a bottom-nav item.
- `Pay Now` is informational only in this slice. Payment instructions are resolved from organization billing/contact settings rather than through a gateway.
- Tenant queries must always be scoped to the signed-in tenant’s allowed property, meters, readings, and invoices.
- Reading submission reuses the shared reading-validation and domain-write rules rather than introducing a tenant-only rules engine.

## System Architecture

The tenant portal has three layers.

### Tenant Route and Presentation Layer

Blade pages define the tenant-facing routes for:

- home
- submit reading
- invoice history
- property details
- profile management

These pages render inside the shared authenticated shell and rely on the tenant bottom navigation for primary movement.

### Livewire Interaction Layer

Livewire is used where the portal benefits from responsive behavior:

- home summaries that compose multiple data points
- reading-submission flow and validation feedback
- other small interaction islands that do not justify a separate JavaScript application

The portal should remain mostly server rendered rather than turning into a second SPA.

### Shared Domain and Query Layer

Tenant-facing reads and writes depend on the organization domain already owned by the admin workspace:

- property and assignment context
- meter and reading models
- invoice and payment records
- organization settings for billing instructions
- shared validation services

Tenant presenters and query objects are translation layers over shared domain truth, not a parallel domain model.

## Navigation Model

The portal uses one fixed navigation system:

- `Home`
- `Readings`
- `Invoices`
- `Profile`

Rules:

- the bottom navigation appears on every tenant page
- it never appears on admin, manager, or superadmin pages
- the active destination is always visually obvious
- secondary destinations such as `My Property` are linked from the appropriate primary page rather than promoted into the bottom nav

This keeps the tenant experience predictable on mobile-sized layouts and aligned with the product spec.

## Tenant Data and Visibility Rules

### Home Summary

The tenant home page presents a concise summary of the tenant’s current context, such as:

- current balance
- recent or pending invoices
- meter activity or submission prompts
- property-level summary details

These summaries must be derived from the tenant’s scoped data only.

### Readings

Tenant reading submission uses the shared reading domain and validation engine. The portal may tailor the wording and form flow for tenants, but it may not alter the underlying validation semantics.

### Invoices

The invoices page is a tenant-facing history view for the tenant’s allowed invoices, including download access. Historical invoices remain important even if assignment state later changes, subject to the closure rules defined elsewhere.

### Property

`My Property` is a detail page that explains the tenant’s currently assigned property context. It is a secondary route because it deepens the tenant experience rather than acting as a primary navigation hub.

### Profile

Tenants need lightweight self-service profile editing and password changes, but this slice should not mirror the full admin settings surface.

## Isolation and Security Rules

- Tenants may only see records connected to their permitted property context and historical invoice entitlements.
- No tenant route may expose admin navigation, platform navigation, or organization-wide record lists.
- Meter visibility should derive from the current property assignment rules, not from invoice history alone.
- Billing instructions must come from organization settings, never from hardcoded UI text.
- Reading-submission actions must still enforce server-side authorization and validation even if the UI is bypassed.

## Acceptance Scenarios

### Scenario 1: Tenant navigation contract

Given an authenticated tenant
When they enter the portal
Then they see the four-item bottom navigation
And they do not see admin or platform navigation items

### Scenario 2: Reading submission

Given an authenticated tenant with an allowed meter context
When they submit a reading
Then the request is validated using the shared reading rules
And the resulting record is stored through shared domain behavior

### Scenario 3: Invoice history

Given an authenticated tenant with invoice history
When they open the invoices page
Then they see only their permitted invoices
And they can download those invoices through a tenant-safe route

### Scenario 4: Property details

Given an authenticated tenant with an assigned property
When they view `My Property`
Then they see only their current property context
And the page remains outside the primary bottom-navigation set

### Scenario 5: Profile self-service

Given an authenticated tenant
When they update profile details or change password
Then the portal handles those updates without exposing the broader admin settings surface

## Operational Notes

- This slice should not begin before the organization workspace has created the required property, meter, reading, invoice, and settings models, or it should be implemented in the same branch as those dependencies.
- Future tenant features should prefer tenant-specific presenters and query objects layered over shared domain logic rather than tenant-only copies of admin services.
- If product later adds online payments, that should be a separate design change because this slice intentionally stops at payment instructions and invoice visibility.
