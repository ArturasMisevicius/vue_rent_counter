# Organizations Module Design

> **AI agent usage:** This is a design/spec artifact. Read `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, and `docs/AI-AGENT-DOCS.md`, then verify live code and tests before assuming the behavior still matches this document.

## Goal

Define the Organization control-plane model for Tenanto so superadmins can manage the platform tenant lifecycle, billing state, support operations, compliance exports, audit/security visibility, and org-scoped operational health from one consistent module.

This design treats `Organization` as the top-level SaaS account boundary. It is the unit of billing, data isolation, configuration ownership, feature entitlement, compliance, and support tooling.

## Scope

This slice includes:

- the Organization domain model and lifecycle contract
- superadmin list and detail-page behavior for organizations
- support/control-plane actions such as suspension, reactivation, force plan change, ownership transfer, announcements, exports, impersonation, and limit overrides
- audit, security, integration, and billing visibility for a single organization
- CSV summary export and GDPR data export triggers
- organization-level feature flags and limit overrides
- org-scoped widgets on the superadmin detail page

This slice does not include:

- implementation of the full property-domain rename from `Tenant` to `Renter`/`Lessee`
- owner self-service plan checkout UX
- direct payment-provider checkout flows
- a separate analytics warehouse
- replacing Filament or the existing authenticated shell

## Approved Product Decisions

- `Organization` is the SaaS tenant. It is not the physical renter domain.
- The physical tenant domain should eventually be renamed to `Renter` or `Lessee`. Do not allow `Tenant` to mean both SaaS account and renter in future work.
- Invoice numbering must use per-organization sequence locking with a database-level pessimistic lock during generation.
- Superadmin impersonation is fully logged, time-limited, and dual-attributed.
- Suspension wins over impersonation. If an organization is suspended, an impersonated org-user session remains read-only.
- `MRR` means the latest active subscription charge normalized to a monthly amount, not the nominal plan list price.
- Hard deletes are never interactive. Permanent deletion is automated after the retention/quarantine rules are satisfied.
- All sensitive control-plane operations require immutable audit logging with actor, timestamp, reason when applicable, and before/after values where relevant.

## Domain Definition

### What an Organization Is

An `Organization` is the subscribed business account on Tenanto. It can represent a property management company, real estate agency, landlord group, or similar business operating a rental portfolio inside the platform.

Every business record belongs to exactly one organization. That includes users, buildings, properties, renters, meters, invoices, subscriptions, providers, projects, tags, audit logs, notifications, and security violations.

### Core Responsibilities

Organizations own:

- identity and legal profile
- subscription and plan state
- org user membership and permissions
- data isolation and tenancy scope
- locale/currency/timezone/configuration overrides
- branding and white-label settings
- audit retention and compliance state

### Core Fields

The design assumes an `organizations` table with the following business fields:

- identity: `id`, `name`, `slug`, `legal_name`, `registration_number`, `vat_number`
- contact and branding: `email`, `phone`, `website`, `logo_path`, `primary_color`
- lifecycle: `status`, `trial_ends_at`, `grace_period_ends_at`, `suspended_at`, `cancelled_at`
- ownership and billing: `owner_id`, `plan_id`
- localization: `locale`, `timezone`, `currency`
- invoice controls: `invoice_prefix`, `invoice_sequence`
- limits and usage: `max_users`, `max_properties`, `storage_quota_mb`, `storage_used_mb`
- support and extensibility: `notes`, `metadata`, `deleted_at`

### Direct Relationships

`Organization` should own or relate to:

- owner `User`
- current `Plan`
- many `User`
- many `Building`
- many `Property`
- many renter records currently represented by tenant-role users and related assignment models
- many `Invoice`
- many `Subscription`
- many `Meter`
- many `Tariff`
- many `Provider`
- many `Project`
- many `Tag`
- many `AuditLog`
- many `Notification`
- many `SecurityViolation`
- one org-level configuration/settings record
- many media assets for branding

### Pivot Relationships

The design assumes:

- `org_users` for organization/user membership metadata
- `org_features` for per-org feature overrides beyond plan defaults

## Lifecycle Model

### States

The lifecycle states are:

- `pending_verification`
- `trial`
- `active`
- `grace_period`
- `suspended`
- `cancelled`

### State Meanings

`pending_verification`

- email verification incomplete
- login blocked except verification flow
- auto-cleanup allowed after inactivity window

`trial`

- full or plan-shaped access during trial
- countdown visible
- may transition to `active` on successful billing

`active`

- paid, writable, in good standing

`grace_period`

- payment failed
- still writable
- dunning/retry sequence in progress

`suspended`

- read-only for org users
- API writes blocked
- tenant portal read-only

`cancelled`

- workspace access revoked
- data retained during retention window
- reactivation only through a new subscription flow, not a blind status flip

### Transition Rules

- `pending_verification -> trial` on verification or superadmin bypass
- `trial -> active` on payment method + successful first paid activation
- `trial -> suspended` when trial expires without conversion
- `active -> grace_period` on renewal payment failure
- `grace_period -> active` on successful payment recovery
- `grace_period -> suspended` when grace expires unresolved
- `suspended -> active` on explicit reactivation or payment resolution
- `suspended -> cancelled` after unresolved suspension window
- `cancelled -> active` only through a new subscription creation path
- `active -> pending_verification` is forbidden

## Onboarding And Creation Rules

### Registration Flow

Owner registration creates an organization in `pending_verification`. Email verification is required before regular access, except when superadmin performs manual onboarding for enterprise/support use cases.

After verification:

- the organization transitions to `trial`
- initialization runs synchronously to seed org defaults
- setup wizard progress can be resumed

### Creation Rules

- one owner email cannot register multiple organizations simultaneously
- `slug` must be globally unique, URL-safe, lowercase, and 3-100 chars
- reserved slugs are blocked
- disposable email domains are rejected during registration
- API-created organizations must reference an existing verified owner
- superadmin may create organizations without normal verification flow

## Configuration And Mutation Rules

- changing currency is blocked when non-cancelled invoices exist in the old currency
- changing locale after invoices exist must warn that historical PDFs remain unchanged
- changing slug should preserve a 30-day redirect window
- owner transfer requires the new owner to already exist as a verified user in the same org
- deleting an org while active renters/leases exist is blocked until those records are resolved
- every changed field must be written to audit history with before/after values

## Data Integrity Rules

- `invoice_sequence` must be locked per organization during invoice generation
- unpaid invoices block permanent deletion until written off
- `storage_used_mb` is updated synchronously on upload/delete
- child entities use soft delete; hard-delete cascade belongs only to the scheduled deletion flow

## Limit And Feature Enforcement

### Effective Limit Resolution

The system should resolve effective org limits in this order:

1. active org-specific override
2. current subscription snapshot or org-denormalized limit
3. plan default

### Limit Rules

- cannot invite a new user at the effective user limit
- cannot create a new property at the effective property limit
- cannot upload when `storage_used_mb + file_size > storage_quota_mb`
- warn at 80% of any limit
- show upgrade/support CTA at 100%

### Override Limits

Limit overrides are superadmin-only support instruments. Each override requires:

- target dimension
- override value
- reason note
- expiry timestamp
- actor metadata

Overrides auto-revert on expiry and every create/update/expiry/removal is audited.

### Feature Flags

Feature access should resolve in this order:

1. global kill switch
2. org-specific override
3. plan entitlement

Per-org feature flags live outside the subscription itself and should not be silently discarded during plan changes.

## Authorization Model

### Public

- create organization via registration
- verify email

### Organization Owner

- view/edit org profile within allowed boundaries
- change plan through normal customer flow
- invite users
- cancel subscription
- view own org audit history
- trigger self-service org data export
- change locale and currency subject to invoice/currency rules

### Organization Admin

- edit org profile
- invite users
- remove non-owner users

### Organization Staff

- no org-level support or billing control-plane powers by default

### Superadmin

- view all organizations
- suspend/reactivate org
- force plan change
- transfer ownership
- impersonate org users
- override limits
- manage feature flags
- view all audit logs
- view security violations
- trigger org data export on behalf of owner
- write internal notes

### Automated Lifecycle Only

- permanent hard-delete after retention/quarantine

## Superadmin Control-Plane Operations

All org operations should route through explicit actions/services, not table closure logic.

### Suspend / Suspend Selected

- immediate status transition into suspension flow
- reason note required for bulk/manual support suspension
- owner notification sent
- guard: cannot suspend orgs with active payment disputes
- audited with actor, reason, timestamp

### Reactivate

- lifts suspension for selected suspended orgs
- reason note required
- audited with actor and reason

### Extend Trial

- adds days to `trial_ends_at`
- available only for orgs currently in trial

### Force Plan Change

- bypasses checkout
- intended for migrations, grandfathering, support resolutions
- guard checks effective target plan limits against current usage
- updates plan and denormalized limits atomically
- audited with old/new plan and reason

### Transfer Ownership

- target user must already belong to the same organization
- target must be verified
- previous owner loses owner-only permissions
- new owner gains them immediately
- both users notified
- audited with old/new owner identities and reason

### Send Announcement

- bulk platform notification for selected orgs
- queued, not synchronous
- fan-out to all org users
- intended for maintenance notices and announcements
- audited with title, severity, scope, actor, recipient count

### Export CSV

- bulk support/reporting export for selected orgs
- honors current table filter/sort state and selected records
- exports visible columns
- default visible export contract:
  - `name`
  - `email`
  - `status`
  - `plan`
  - `property_count`
  - `user_count`
  - `MRR`
  - `created_at`

### Generate Data Export

- GDPR-compliant org export ZIP
- asynchronous
- delivered to org owner
- support-triggered on behalf of the owner
- audited with actor and reason

### Write Off Invoices

- required before permanent deletion when outstanding invoices remain
- explicit confirmation + reason required
- creates accounting write-off / credit-note records rather than erasing evidence
- audited per org and per affected invoice set

### Impersonate Org Admin

- enters the org panel as the owner/admin without password knowledge
- session limited to 1 hour
- persistent banner visible throughout session
- active security incident blocks impersonation
- every downstream action stores both:
  - real actor = superadmin
  - effective actor = impersonated org user
- rendered attribution should be `Superadmin (impersonating Owner Name)`

## Suspension, Dunning, And Recovery

### Suspension Flow

1. payment failure webhook or manual superadmin suspension
2. grace starts immediately
3. dunning sequence runs on day 0/3/6
4. unresolved day 7 transitions to `suspended`

### Enforcement

- middleware/gates block writes during suspension
- Filament shows suspension banners
- write APIs should fail consistently
- tenant portal becomes read-only

### Recovery

- owner can update payment method and retry
- superadmin may explicitly reactivate or resolve billing
- all recovery actions audited with actor and method

## Subscription Changes

### Upgrade / Downgrade Rules

- downgrade eligibility checks current usage against target limits
- proration is calculated before confirmation
- plan record and denormalized limits update atomically
- owner notification and audit trail required

### Subscription Timeline Data

Org detail should show:

- current plan
- current status
- next billing date
- billing cycle
- `trial_ends_at` when relevant
- `grace_period_ends_at` when relevant
- payment method on file indicator
- recent renewals and plan/billing events

## Cancellation, Retention, And Permanent Deletion

### Cancellation Flow

1. owner or superadmin cancels
2. status becomes `cancelled`
3. retention window begins
4. org may reactivate only via new subscription

### Deletion Pipeline

1. retention window completes
2. full export still available during retention
3. unpaid invoices must be written off first
4. child data is soft-deleted
5. files removed
6. org soft-deleted
7. hard delete only after quarantine

No human should directly trigger permanent hard-delete.

## Audit, Activity, And Security

### Audit Timeline

The audit timeline is the legal/compliance source of truth for an org.

Each event should store:

- timestamp
- real actor
- effective actor
- event type
- IP address
- before values
- after values
- metadata such as reason notes, impersonation context, correlation ids

The in-app timeline is filterable by:

- date range
- actor
- event type
- optionally IP

Legal export should support non-paginated export for the selected filter scope.

### Activity Feed

The detail dashboard should show the latest 10 high-signal audit events with:

- who
- what
- when

Highlighted event types include:

- user invites
- invoice sends
- plan changes

Each row deep-links into the full audit log.

### Security Violations

Security violations are distinct from generic audit events and cover:

- CSP violations
- rate-limit breaches
- cross-org access attempts
- policy violations

The full violations log should support:

- severity filtering
- date/type filtering
- review notes
- reviewed state

High-severity active incidents should block impersonation.

### Security Health Widget

Org detail should summarize:

- last 30 days of violation counts by severity
- last login per user
- unreviewed violation count with red badge

## Org Detail Page (Superadmin)

The org detail page should become a compact operational dashboard rather than only an infolist.

### Cached Widgets

The following widgets should read from dedicated org-dashboard data builders and be cached per organization for 15 minutes unless noted otherwise.

#### Portfolio Overview

- buildings count
- properties count
- occupied units
- vacant units
- occupancy rate
- active tenants

#### Financial Snapshot

- `MRR`
- outstanding invoice total
- overdue invoice total
- total collected this calendar month
- average days to pay

`Avg days to pay` should measure days from invoice finalization, falling back to creation date, through `paid_at`.

#### Plan Usage Meters

- users used / effective max
- properties used / effective max
- storage used / effective quota

Color rules:

- green: 0-79%
- amber: 80-99%
- red: 100%+

Red state should surface support CTA toward force plan change or limit override.

#### Subscription Timeline

- plan badge
- status
- next billing date
- billing cycle
- trial/grace deadlines when relevant
- payment method on file indicator
- renewal history

### Fresh Operational Surfaces

These should remain query-backed or lightly cached for freshness:

#### Activity Feed

- latest 10 audit events
- deep-link to full audit timeline

#### Security Health

- recent violation counts
- user last-login summaries
- unresolved violation badge

#### User Roster

All org users with:

- name
- role
- last login
- status: active / invited / suspended

Inline support actions:

- resend invite
- suspend/reactivate
- change role

Owner transfer remains a separate control-plane action, not a generic inline edit.

#### Integration Health

This widget should merge:

- global platform runtime probes:
  - payment provider link
  - email delivery
  - storage bucket
  - queue
- org-specific configured integrations and their verification state

Each status should show red/amber/green + last-checked timestamp.

## Superadmin List View

### Table Columns

- Name
- Slug
- Owner email
- Status badge
- Plan
- Properties count
- Users count
- MRR
- Trial ends / Grace ends
- Created at

`Trial ends / Grace ends` should display whichever lifecycle deadline is relevant for the current org status.

### Filters

- status (multi-select)
- plan (multi-select)
- created date range
- trial expiry range
- has overdue invoices (yes/no)
- has security violations (yes/no)

### Search

Search should cover indexed fields only:

- name
- slug
- owner email
- registration number
- VAT number

### Sort

Default:

- `created_at desc`

Secondary sort options:

- `MRR desc`
- `name asc`
- `status`

### Row Coloring

- red row: suspended / cancelled
- amber row: grace period
- blue row: trial
- default/white: active

### Pinnable Presets

Named filter presets should include:

- `Overdue orgs`
- `Expiring trials (7 days)`
- `High-value (MRR > €500)`
- `New this month`

## Reporting, Query, And Caching Boundaries

- list-page counts and dashboard widgets should use org-scoped aggregate builders, not Blade-time queries
- export builders should reuse control-plane query contracts where possible
- live support tables should remain query-backed and paginated
- widget caches must be invalidated by the relevant mutations:
  - building/property changes
  - user membership changes
  - assignment changes
  - invoice finalization/payment/write-off changes
  - subscription changes
  - security-violation creation/review changes
  - integration probe refreshes
  - limit override changes

## Implementation Boundaries

### Keep

- Filament as the control-plane UI shell
- Eloquent as the query layer
- explicit Actions/Services for control-plane mutations
- existing probe infrastructure for global integration health

### Avoid

- raw SQL scattered in table closures
- view-driven relationship loading
- synchronous fan-out work in request cycle for large announcements/exports
- duplicate audit/security logic across pages and resources
- bypassing suspension via impersonation

## Testing Expectations

Implementation should include:

- feature tests for organization list filters, row states, presets, and exports
- feature tests for each support action and its guards
- tests for impersonation TTL, incident guard, banner visibility, and dual attribution
- tests for invoice sequence locking under concurrency
- tests for limit override expiry/reversion
- tests for audit and security timeline filtering/export
- widget/data-builder tests for portfolio, financial, usage, and subscription snapshots

## Open Follow-Up

- The broader renter-domain rename remains a separate future slice. This design only locks the naming rule so new work does not deepen the `Organization`/`Tenant` collision.
