# Organizations Seeding Design

## Goal

Upgrade the Organizations demo data so superadmins see a richly populated control plane backed by realistic seeded organizations, users, subscriptions, buildings, properties, meters, invoices, integrations, and support signals.

The dataset should make the Organizations list and detail pages feel fully exercised out of the box while staying within the current schema. This is a seed and factory enrichment slice, not a domain-schema rewrite.

## Scope

This slice includes:

- richer `OrganizationFactory` and related factory states
- plan-aware `SubscriptionFactory` states and limit snapshots
- organization demo seed generation for all `SubscriptionPlan` values
- seeded organization users for owner, admins, managers, and tenant-role users
- plan-shaped building, property, meter, provider, invoice, and activity density
- seeded organization settings and related records that fill the superadmin admin panel with realistic data
- idempotent reruns for both curated login-demo seeds and operational demo seeds
- database and feature tests proving the seeded graph stays complete and repeatable

This slice does not include:

- new columns on `organizations`
- moving legal/profile fields out of `organization_settings`
- rewriting the Organizations admin UI itself
- changing the meaning of plans or subscription enforcement logic

## Current Constraints

The current codebase already seeds a large operational demo graph, but it has two important gaps:

1. the main operational dataset creates many `demo-baltic-*` organizations, but they all use the same subscription plan instead of covering all plans
2. the curated login demo organization is useful for authentication flows, but it is not guaranteed to carry the same subscription richness and related support data expected in the superadmin control plane

The `organizations` table itself is intentionally minimal today. Richer business/contact/billing data currently lives in:

- `organization_settings`
- `subscriptions`
- `users`
- `buildings`
- `properties`
- `meters`
- `providers`
- `service_configurations`
- `invoices`
- `audit_logs`
- `organization_activity_logs`
- `security_violations`

Because that split already exists in production code, this design keeps it. “Maximum filled organization” means filling the existing related graph completely, not forcing a migration just to move seed data onto the `organizations` row.

## Approved Decisions

- Keep the existing schema. No new `organizations` columns are added in this slice.
- Seed one showcase organization per `SubscriptionPlan` in the operational demo dataset:
  - `starter`
  - `basic`
  - `professional`
  - `enterprise`
  - `custom`
- Every seeded organization must have a current subscription and a valid plan snapshot.
- Buildings, properties, users, meters, invoices, and support signals scale by plan tier.
- Seed data should deliberately exercise the superadmin panel:
  - mixed lifecycle/status states
  - mixed usage percentages
  - some overdue invoices
  - some security violations
  - some integration gaps
  - some feature overrides / limit overrides where already supported
- All seeders remain idempotent through stable slugs, emails, identifiers, and `updateOrCreate()` contracts.

## Target Dataset Model

### Organization Core

Each seeded organization should provide:

- stable `name`
- stable `slug`
- meaningful `status`
- `owner_user_id`
- `system_tenant_id` where required by the current platform support layer

### Organization Settings

Each seeded organization should also have an `organization_settings` row containing:

- billing contact name
- billing contact email
- billing contact phone
- payment instructions
- invoice footer
- notification preferences

These settings are the current home for “maximum fill” business-facing seed data and should be treated as part of the organization profile.

### Subscription And Plan Coverage

Every organization must have an active current subscription record with:

- plan
- status
- starts/expiry timestamps
- trial flag when appropriate
- property / tenant / meter / invoice snapshots matching the seeded plan

The showcase dataset should intentionally cover all plan tiers, not only `professional`.

### Users

Each seeded organization should include:

- owner/admin user
- at least one manager
- tenant-role users sized by plan tier
- some last-login variation so roster/security widgets are populated

The login-demo organization should continue to expose the curated fixed credentials, but it also needs a plan and richer related data so it no longer feels like a special thin exception.

### Portfolio And Operations

Each seeded organization should include:

- buildings
- properties
- active assignments / leases
- meters and readings
- invoices and invoice items
- providers, tariffs, utility services, and service configurations
- projects, tasks, activity logs, and audit signals where appropriate

The seeded graph should be dense enough that the superadmin Organizations list and detail view show meaningful usage, financial, integration, security, and activity data immediately after `db:seed`.

## Plan-Shaped Volumes

The dataset should not try to hit actual plan maximums. It should scale clearly by tier while keeping local seed runs practical.

Recommended shape:

| Plan | Status target | Buildings | Properties | Tenant-role users | Meters | Invoices | Intent |
|------|---------------|-----------|------------|-------------------|--------|----------|--------|
| `starter` | `trial` | 1 | 2-3 | 2-3 | 4-6 | 4-8 | tiny showcase, countdown/trial behavior |
| `basic` | `active` | 2 | 6-8 | 5-8 | 10-16 | 12-20 | login/demo-friendly small production org |
| `professional` | `grace_period` or `active` | 3 | 12-18 | 10-14 | 20-30 | 24-36 | realistic midsize operations |
| `enterprise` | `active` | 4-6 | 24-36 | 16-22 | 40-60 | 48-80 | large portfolio, strong list/detail density |
| `custom` | `suspended` or `active` | 6-8 | 40-60 | 24-32 | 70-100 | 90-140 | richest support/control-plane showcase |

These are target bands, not rigid exact counts. The important requirement is relative scaling and visible UI richness.

## Superadmin Panel Scenarios

The seeded org set should intentionally cover the control-plane scenarios already built into the Organizations module:

- one org close to property or user limits for usage gauges
- one org in `trial` for trial-expiry visibility
- one org in `grace_period` or with overdue invoices for billing/support workflows
- one org with a security violation history for the security widget
- one org with missing or partial provider integration coverage
- one org with rich activity/audit data for the detail-page feed
- one org with roster variety:
  - owner
  - active managers
  - active tenants
  - invited/suspended states where the current schema supports them

This dataset should make the Organizations list filters, row highlighting, badges, counters, and widgets useful without manual post-seed edits.

## Factory Strategy

Factories should become expressive enough that tests and seeders can create coherent organization graphs without hand-assembling every relation.

### OrganizationFactory

Add stable showcase-oriented states such as:

- `starterShowcase()`
- `basicShowcase()`
- `professionalShowcase()`
- `enterpriseShowcase()`
- `customShowcase()`

These states should set predictable names/slugs/status values instead of pure randomness.

### SubscriptionFactory

Add plan-aware states such as:

- `forPlan(SubscriptionPlan $plan)`
- `starter()`
- `basic()`
- `professional()`
- `enterprise()`
- `custom()`

Each state should set the snapshot fields from `SubscriptionPlan::limits()` automatically so tests and seeders do not duplicate that mapping.

### Related Factories

Where helpful, add lightweight plan-aware or showcase-aware helpers to:

- `UserFactory`
- `OrganizationSettingFactory`
- `BuildingFactory`
- `PropertyFactory`
- `MeterFactory`
- `ProviderFactory`

The goal is not to create a full DSL. The goal is to stop repeating brittle seed arrays in multiple places.

## Seeder Strategy

### Canonical Organization Demo Seed

The operational organization dataset should become the canonical multi-plan showcase seed. The easiest path is to evolve `OperationalDemoDatasetSeeder` rather than inventing a second unrelated organization seed.

That seeder should:

- own a stable catalog of organization blueprints
- map each blueprint to a plan
- map each plan to volume targets
- create/update the full related graph for that org

### Login Demo Seed Alignment

`LoginDemoUsersSeeder` should continue to create the curated login accounts, but it should stop producing a “thin” demo organization.

It should either:

- reuse one of the showcase blueprints, or
- enrich its dedicated login-demo organization with the same subscription/settings expectations as other orgs

The important contract is that every seeded organization has a plan and a coherent graph, including the login-demo org.

### Idempotency

Stable identifiers should be used for:

- organization slugs
- user emails
- provider names where used as natural keys
- meter identifiers
- invoice numbers
- project/task names when seeded as deterministic demo data

Rerunning `DatabaseSeeder` should refresh the demo graph without uncontrolled growth.

## Testing Strategy

### Factory Coverage

Add focused tests for showcase factory states proving:

- org states produce stable slugs/statuses
- subscription plan states set correct snapshot limits
- related helper states remain attached to the same organization

### Seeder Coverage

Upgrade the current operational dataset seeder test so it verifies:

- all plans are represented
- every seeded organization has a current subscription
- buildings/properties/users scale by plan
- the dataset remains idempotent

### Login Demo Coverage

Upgrade the login-demo account test so it also proves:

- the curated org has a current subscription
- the curated org has organization settings
- the curated org remains rich enough for admin/superadmin flows

### Superadmin Data Coverage

Where needed, add a superadmin-focused seed smoke test that verifies the seeded organizations expose:

- mixed statuses
- mixed plans
- non-empty counts for core relations

## Non-Goals

- no data migration to move settings fields onto `organizations`
- no new superadmin UI behavior
- no attempt to perfectly mirror real production volume
- no artificial “max everything” seed that makes local setup too slow

## Success Criteria

This slice is successful when:

- `db:seed` produces a multi-plan organization showcase
- every seeded organization has a plan and owner/admin context
- buildings and related records scale visibly by plan
- the login demo org is no longer an under-filled exception
- factories can create plan-aware organization graphs for tests
- the superadmin Organizations admin panel looks richly populated immediately after seeding
