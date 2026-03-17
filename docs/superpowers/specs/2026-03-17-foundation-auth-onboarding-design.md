# Tenanto Foundation, Auth, and Onboarding Design

> **Workflow note:** Keep spec and implementation changes for this slice on `main` only. Do not create or use separate branches or worktrees.

## Goal

Establish the first production-ready vertical slice of Tenanto so the application can support one shared login experience, role-aware entry points, localized public auth pages, organization-scoped onboarding for new Admin users, and invitation-based account activation for Manager and Tenant users.

This design intentionally stops at the boundary where a user can securely enter the platform and land in the correct role-aware shell. Dashboard internals, CRUD modules, billing workflows, tenant mobile-style pages, reports, and platform operations are defined elsewhere in the product specification and are out of scope for this slice.

## Scope

This slice includes:

- Shared public authentication pages:
  - login
  - register
  - forgot password
  - password reset
  - invitation acceptance
- Admin self-registration
- Post-registration welcome/onboarding for organization creation and free-trial activation
- Role-aware login redirect rules
- Guest redirect to intended destination after login
- Locale persistence and immediate language switching foundations
- Account and organization suspension checks during authentication
- One-organization-per-user enforcement for Admin, Manager, and Tenant roles
- Invitation lifecycle for Manager and Tenant accounts
- Tenant isolation rules that later slices must inherit

This slice does not include:

- Detailed dashboard widgets or metrics
- CRUD pages for buildings, properties, tenants, meters, invoices, tariffs, providers, or reports
- Full notification center behavior
- Global search
- Real-time dashboard polling beyond the runtime hooks needed for auth and onboarding
- Translation-management interfaces
- Impersonation flows

## Approved Product Decisions

- Public authentication uses custom Laravel web pages rather than Filament-auth pages.
- Filament remains the authenticated application shell after sign-in.
- Every non-superadmin user belongs to exactly one organization.
- Superadmin exists outside the organization model.
- Admin self-registration creates only the user first; organization creation happens in onboarding.
- Invitation emails sent to an already-registered email address are rejected.
- Manager and Tenant accounts are created only through invitation, never through public self-registration.

## System Architecture

The application is divided into two top-level experience layers:

1. Public auth and onboarding pages, implemented as standard Laravel web routes and Blade-driven interfaces.
2. The authenticated application shell, implemented through the existing signed-in interface, which stays visually shared but adapts navigation and content by role.

The public auth layer owns guest-only entry points:

- `/login`
- `/register`
- `/forgot-password`
- `/reset-password/{token}`
- `/invite/{token}`
- `/welcome`

The authenticated shell owns role-aware application pages:

- superadmin platform area
- admin and manager operational area
- tenant-facing area

Authentication is centralized through Laravel's session-based web guard. Authorization is layered:

- Route and middleware checks decide whether a user may enter a route family.
- Model and query scoping enforce organization isolation.
- Tenant-facing record access adds a second-level restriction based on the tenant's assigned property context.

The onboarding gate sits between authentication and the normal application shell. A newly registered Admin user may authenticate successfully but still be blocked from organization-scoped application pages until onboarding is complete.

## Core Domain Model

### User

The `users` table is extended so auth state and role-aware routing are first-class:

- `name`
- `email`
- `password`
- `role`
- `status`
- `locale`
- `organization_id` nullable
- `last_login_at` nullable
- `email_verified_at` nullable if email verification is adopted later
- timestamps

Rules:

- `superadmin` must always have `organization_id = null`.
- `admin`, `manager`, and `tenant` must always have a non-null `organization_id` after onboarding or invitation acceptance is complete.
- `admin` may temporarily have `organization_id = null` only during the post-registration onboarding state.
- `tenant` and `manager` accounts cannot exist without an invitation origin in this slice.

### Organization

The `organizations` table stores:

- `name`
- `slug`
- `status`
- `owner_user_id`
- timestamps

Rules:

- `slug` is immutable after creation.
- `owner_user_id` must reference the Admin who completed onboarding or the owner assigned during organization creation by privileged flows in later slices.
- `status` supports at least active and suspended.

### Subscription

The `subscriptions` table stores the current commercial state created during onboarding:

- `organization_id`
- `plan`
- `status`
- `starts_at`
- `expires_at`
- `is_trial`
- timestamps

Rules:

- Completing onboarding creates the initial free-trial subscription.
- The first slice only needs enough subscription state to support trial onboarding and future read-only enforcement hooks.

### Organization Invitation

The `organization_invitations` table stores:

- `organization_id`
- `inviter_user_id`
- `email`
- `role`
- `full_name` nullable
- `token`
- `expires_at`
- `accepted_at` nullable
- timestamps

Rules:

- Only `manager` and `tenant` roles are valid invitation targets in this slice.
- Invitations expire after 7 days.
- Invitations are single use.
- An invitation may not be created if the target email already exists in `users`.

## Roles and Statuses

### Roles

Supported roles:

- `superadmin`
- `admin`
- `manager`
- `tenant`

### Account Status

Supported account statuses:

- `active`
- `inactive`
- `suspended`

Behavior:

- `active`: may authenticate if related organization state also allows it.
- `inactive`: reserved for non-destructive deactivation states in later slices.
- `suspended`: blocked from authentication.

Implementation rule for this slice:

- invited Manager and Tenant accounts are not pre-created in `users`
- the invitation record is the only pre-acceptance state
- the `users` row is created only when the recipient successfully accepts the invitation

### Organization Status

Supported organization statuses:

- `active`
- `suspended`

Behavior:

- If an organization is suspended, all organization users are blocked from authenticating.
- Existing sessions for organization users are invalidated when suspension is applied.

## Authentication and Routing Flows

### Login

The login page is the single entry point for all roles.

Behavior:

- Accepts email and password.
- Preserves the email field on failure.
- Shows a generic invalid-credentials message without exposing whether the email exists.
- Redirects guests who attempted a protected page to login, then restores the intended destination after successful authentication.
- If no intended destination exists, redirects by role:
  - `superadmin` -> platform dashboard
  - `admin` -> admin dashboard unless onboarding is incomplete
  - `manager` -> admin dashboard
  - `tenant` -> tenant home page
- If a newly registered Admin has not yet created an organization, redirect to `/welcome` instead of the dashboard.
- If the account or organization is suspended, reject authentication and show the suspension message from the product spec.
- Session timeout after 120 minutes of inactivity routes the user back to login and preserves the intended URL.

### Register

Public registration is available only for future Admin users.

Behavior:

- Creates a user with role `admin`.
- Initial account state is active unless email verification is introduced later.
- Does not create an organization yet.
- Logs the new user in immediately.
- Redirects the user to `/welcome`.

### Welcome / Onboarding

The welcome flow is required only for Admin users who do not yet belong to an organization.

Behavior:

- Explains the free-trial state.
- Collects organization name and slug.
- Creates the organization.
- Creates the initial trial subscription.
- Sets the registering Admin as the organization owner.
- Links `users.organization_id` to the newly created organization.
- Redirects the Admin into the normal admin dashboard after completion.

Guard rules:

- Accessible only to authenticated Admin users with no organization.
- Hidden from all other users.
- Any attempt to access organization-scoped app routes before completion redirects back to onboarding.

### Forgot Password

Behavior:

- Accepts an email address.
- Always shows the same success message regardless of whether the email exists.
- Sends a password reset email only if the account exists and is eligible.
- Does not reveal account existence.

### Password Reset

Behavior:

- Uses a tokenized reset link valid for 1 hour.
- Accepts new password and confirmation.
- Requires exact confirmation match.
- After success, shows the success message and returns the user to login.

### Invitation Acceptance

Behavior:

- Loads via a unique tokenized link.
- Shows the inviting organization name.
- Prefills full name when supplied by the inviter.
- Accepts password and password confirmation.
- On success:
  - creates the invited account
  - assigns the role encoded in the invitation
  - assigns `organization_id`
  - marks the invitation as accepted
  - authenticates the new user immediately
  - redirects to the correct role entry point

Failure states:

- Expired invitation -> show explicit expired message.
- Already used invitation -> show invalid or expired state.
- Invitation email already tied to an existing user -> invitation creation is blocked upstream, so acceptance should never need to merge accounts.

## Authorization and Isolation Model

### Route Access

Guest-only routes:

- login
- register
- forgot password
- reset password
- invitation acceptance

Authenticated routes:

- onboarding for incomplete Admin users
- application shell routes for completed users

Role routing rules:

- `superadmin` enters global platform routes.
- `admin` and `manager` enter organization-operational routes.
- `tenant` enters tenant-facing routes.

### Organization Isolation

All organization-scoped queries must filter by the authenticated user's `organization_id` before loading records.

Required invariants:

- Admin from Organization A never sees Organization B data.
- Manager from Organization A never sees Organization B data.
- Tenant from Organization A never sees Organization B data.
- URL tampering must resolve to `403` or `404`, never cross-org record rendering.

### Tenant Isolation

Tenants are further restricted inside their own organization:

- only their assigned property
- only meters assigned to that property
- only readings for those meters
- only invoices linked to that tenancy context

Later slices may implement this with explicit assignments or relationship chains, but the rule is fixed now so all future queries and policies must honor it.

## Localization Strategy

Localization is stored at the user level and applied per request.

Requirements:

- Supported locales: `en`, `lt`, `ru`, `es`
- Auth pages, onboarding copy, validation messages, notifications, and role labels must use translation keys rather than hard-coded strings
- Changing language from the top bar or profile updates the stored preference immediately
- The application re-renders visible text without a full page reload
- Missing translations fall back to English

Implementation boundary for this slice:

- The first slice must establish the locale persistence mechanism and translation key conventions.
- It does not need to implement the later translation-management admin pages yet.

## Validation and UX Behavior

The first slice inherits the foundation interaction rules:

- Every form validates field-by-field.
- Errors render directly under the relevant field.
- Buttons that submit server actions show a spinner and become unclickable while processing.
- Content loading states use local skeletons or partial indicators, never a blank screen.
- Notifications follow severity-based dismissal behavior from the product specification.

Specific validation rules in this slice:

- email fields must be valid email addresses
- registration password minimum length is 8 characters
- password confirmation fields must match immediately when focus leaves the field
- onboarding slug must be unique and immutable after creation
- invitation acceptance password confirmation must match
- reset password token must be valid and unexpired

## Edge Cases and Failure Handling

### Partially Onboarded Admin

If an Admin registers but closes the app before onboarding is complete:

- future logins succeed
- the user is redirected back to `/welcome`
- no application dashboard access is granted until onboarding completes

### Suspended Organization

If an organization is suspended:

- all related Admin, Manager, and Tenant users are denied login
- existing active sessions are invalidated
- the login page shows the suspension message

### Invitation Expiry

If an invitation is older than 7 days:

- acceptance is blocked
- the user sees the explicit expired message
- the system instructs them to contact their administrator for a new invitation

### Session Expiry

After 120 minutes of inactivity:

- the session expires
- the next attempted action routes the user to login
- the target URL is preserved and restored after successful re-authentication
- the login page shows the session-expired message

## Testing Strategy

This slice should be covered primarily by feature tests, with a small number of focused browser-level checks later if needed.

Required feature coverage:

- login success by role
- login failure with preserved email
- intended URL restoration after guest redirect
- partially onboarded Admin redirect to onboarding
- suspended account login rejection
- suspended organization login rejection
- Admin self-registration
- onboarding completion creates organization and trial subscription
- forgot-password request returns generic success messaging
- password reset completion with valid token
- expired reset token rejection
- invitation acceptance success for manager
- invitation acceptance success for tenant
- invitation expiry handling
- invitation creation rejection for already-registered emails
- organization-scoped route blocking for wrong organization access
- tenant-scoped route blocking for unauthorized records
- locale persistence for authenticated users

## Delivery Boundary for the First Slice

The slice is complete when:

- A new visitor can register as an Admin.
- That Admin is logged in and forced through welcome/onboarding.
- Onboarding creates an organization and free trial.
- The Admin is redirected into the shared signed-in app shell.
- Existing users of all supported roles can sign in through the same login page and reach the correct role-based entry point.
- Any role can request a password reset.
- Invited Manager and Tenant users can activate their accounts through a tokenized invitation link.
- Organization suspension and account suspension block access correctly.
- Locale preference is saved and restored for authenticated users.
- Cross-organization access is prevented at the route and data levels for the flows implemented in this slice.

## Out of Scope for the Next Planning Step

The implementation plan for this spec should not include:

- full dashboard widget implementations
- CRUD modules for domain entities beyond what onboarding and invitations require
- billing calculations
- report generation
- translation management UI
- impersonation
- notification-center UI

Those belong to later vertical slices built on top of this foundation.
