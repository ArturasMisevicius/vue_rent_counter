# Tenanto Manager Role Parity Design

## Goal

Define the manager experience as a deliberate role-parity layer on top of the shared organization workspace so managers can operate the same organization data and workflows as admins without introducing duplicated resources, routes, dashboards, or settings pages.

This design keeps the manager surface intentionally narrow only where the product requires it: managers must not see subscription-usage bars on the organization dashboard, and they must not see admin-only sections on the Settings page. Everywhere else in the organization workspace, managers should move through the same shell, the same resources, and the same route families as admins.

## Scope

This slice includes:

- manager access to the shared organization dashboard
- manager access to the shared `Account` navigation group
- manager access to the same organization-scoped operational resources as admins
- dashboard composition rules that hide subscription-usage widgets or sections from managers only
- settings composition rules that keep profile-related sections shared while hiding admin-only sections from managers
- regression boundaries that ensure admins still retain the full organization workspace

This slice does not include:

- separate manager-only Filament resources
- separate manager-only dashboard, profile, or settings pages
- changes to superadmin platform behavior
- changes to tenant-facing pages or tenant authorization
- new CRUD domain models beyond the organization workspace already defined elsewhere
- billing or subscription logic changes beyond visibility in the dashboard and settings UI

## Dependency Context

This design depends on three broader slices:

- `docs/superpowers/specs/2026-03-17-foundation-auth-onboarding-design.md` for authentication, role definitions, and role-aware redirects
- `docs/superpowers/plans/2026-03-17-shared-interface-elements.md` for the shared shell, navigation, and account entry points
- `docs/superpowers/plans/2026-03-17-admin-organization-operations.md` for the underlying organization-scoped resources, policies, actions, widgets, and settings features

Manager parity is therefore not a standalone product surface. It is a refinement of the admin organization workspace once that workspace exists.

## Approved Product Decisions

- Managers and admins share one organization workspace inside the same authenticated shell.
- Managers use the same organization-scoped resource pages and CRUD workflows as admins for buildings, properties, tenants, meters, meter readings, invoices, tariffs, providers, and reports.
- Managers keep the `Account` navigation group with `Profile` and `Settings`.
- Managers must not see any `Platform` navigation or other superadmin-only destinations.
- The organization dashboard remains one shared page. The only manager-specific difference is that subscription-usage bars are hidden.
- The Settings area remains one shared page. The only manager-specific difference is that `Organization Settings`, `Notification Preferences`, and `Subscription` sections are hidden.
- The Profile experience is identical for admins and managers.
- Role differences must be implemented through route access, navigation composition, widget registration, and page schema or view composition. They must not be implemented by cloning pages or hiding content with CSS after render.

## Experience Architecture

### Shared Organization Workspace

Managers are organization operators. After authentication, they land in the same organization route family and shell structure used by admins.

The shared workspace includes:

- the same organization dashboard entry point
- the same operational sidebar groups and destinations
- the same global shell behavior for search, notifications, locale switching, and profile access
- the same organization-scoped resource pages once those resources are available

Managers do not receive a reduced or alternate shell. The role difference is about selective visibility inside shared pages, not about a different application mode.

### Navigation Behavior

Navigation must be built from shared definitions rather than separate admin and manager trees.

Rules:

- managers see the same organization navigation groups and items that admins see for operational work
- managers see the `Account` group with `Profile` and `Settings`
- managers do not see `Platform` or other superadmin-only navigation groups
- navigation registration should stay route-safe and organization-scoped
- manager parity must not require duplicated page registrations or parallel resource definitions

### Dashboard Behavior

The organization dashboard remains one shared page for admins and managers.

Shared dashboard content for both roles should include:

- organization-level operational stats
- recent invoice visibility
- upcoming reading deadline visibility
- any other non-subscription operational widgets defined for the shared admin workspace

Manager-specific dashboard rule:

- hide the subscription-usage row or widget group entirely for managers

Admin-specific dashboard rule:

- admins continue to see the subscription-usage row or widget group

The role gate should be applied before the final dashboard composition is rendered so the manager page never advertises unavailable subscription data.

### Profile and Settings Behavior

The `Profile` page remains fully shared between admins and managers.

The `Settings` page also remains a single shared destination, but its sections compose differently by role.

Shared settings content for both roles:

- personal information
- password change

Admin-only settings content:

- organization settings
- notification preferences
- subscription management or subscription detail sections

Manager-specific settings rule:

- keep the page heading and route the same as admin
- hide all admin-only sections at the schema or view-composition layer

Managers should still perceive `Settings` as a normal account page, not as a restricted or broken destination.

## Authorization and Composition Rules

Managers should reuse the same authorization model that admins use for organization-scoped operational work unless a capability is explicitly reserved for admins.

Implementation-level guardrails for this design:

- prefer shared policies, shared actions, and shared resources over manager-only copies
- keep organization scoping identical for admins and managers
- isolate admin-only behavior to clearly named role checks around dashboard and settings composition
- any helper added for readability should stay small and reusable, such as a role helper on `User`
- do not add query duplication just to express manager visibility differences

Because this project uses Eloquent-only data access and strict query hygiene, manager parity should not introduce separate query paths when the same eager-loaded admin data already exists.

## Acceptance Scenarios

### Scenario 1: Manager workspace parity

Given an authenticated manager with an active organization
When they enter the authenticated application shell
Then they can reach the same organization dashboard, profile page, settings page, and organization-scoped operational resources as an admin
And they do not see any platform-only navigation

### Scenario 2: Shared account navigation

Given an authenticated manager
When the shell navigation is built
Then the `Account` group includes `Profile` and `Settings`
And no manager-only duplicate account routes are introduced

### Scenario 3: Dashboard visibility difference

Given an authenticated manager on the organization dashboard
When the dashboard renders
Then they see the shared operational widgets
And they do not see subscription-usage bars or subscription-capacity rows

### Scenario 4: Admin dashboard regression

Given an authenticated admin on the organization dashboard
When the dashboard renders
Then they continue to see the shared operational widgets
And they continue to see the subscription-usage row

### Scenario 5: Settings visibility difference

Given an authenticated manager on the shared Settings page
When the page renders
Then they see personal-information and password sections
And they do not see `Organization Settings`, `Notification Preferences`, or `Subscription`

### Scenario 6: Admin settings regression

Given an authenticated admin on the shared Settings page
When the page renders
Then they continue to see the personal account sections
And they continue to see the admin-only organization, notification, and subscription sections

## Operational Notes

- This slice should be implemented only after the shared shell and admin organization workspace foundations exist, or alongside them in the same branch.
- If the admin workspace is still under construction, the cleanest path is to embed these role-difference rules into the shared implementation instead of layering them in later with duplicate code.
- Future organization features should default to admin-and-manager parity unless the product specification explicitly marks a capability as admin-only.
