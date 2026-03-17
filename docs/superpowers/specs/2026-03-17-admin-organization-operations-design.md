# Admin Organization Operations Design

## Goal

Build the shared organization-scoped operational workspace for Admin and Manager users so they can manage buildings, properties, tenants, meters, meter readings, billing configuration, invoices, reports, profile, and settings end to end inside the Tenanto authenticated shell.

This design starts from the foundation auth/onboarding slice and the shared shell slice, then fills in the actual day-to-day organization operations that make the admin area useful. It intentionally stops at the boundary where organization operators can run the platform for a single organization; global superadmin control-plane behavior and tenant self-service behavior belong to separate slices.

## Scope

This slice includes:

- organization-scoped operational data for buildings, properties, property assignments, providers, tariffs, service configuration, meters, readings, invoices, payments, reminders, and organization settings
- an Admin and Manager dashboard inside the existing Filament shell
- profile and settings pages for admin-like users
- Filament resources for buildings, properties, tenants, meters, meter readings, tariffs, providers, service configurations, utility services, and invoices
- single and bulk invoice generation flows
- reporting pages for consumption, revenue, outstanding balances, and meter compliance
- subscription-limit enforcement for creation flows that expand organization usage
- reuse of the existing invitation lifecycle when tenant accounts are created from the admin workspace
- organization isolation and role-aware differences between Admin and Manager

This slice does not include:

- superadmin organization-management features
- tenant-facing self-service pages beyond the already-defined auth and activation flows
- custom mobile or API-first operator experiences
- third-party billing gateways, accounting sync, or payment processor integrations
- translation-management interfaces
- analytics-only shadow tables or a separate reporting warehouse
- scheduled automation/orchestration beyond the action boundaries needed for manual operator workflows

## Approved Product Decisions

- Admin and Manager share the same operational workspace, navigation landmarks, and core resources unless the product spec explicitly says otherwise.
- Manager users do not see subscription usage widgets on the dashboard and do not see admin-only sections on the settings page.
- All operational data is organization-scoped by default. Users do not choose an organization in forms or filters inside this slice.
- Tenant accounts remain regular `users` rows with `role = tenant`; this slice does not introduce a separate tenant-auth table.
- Property occupancy is derived from assignment history, not stored as an independently edited flag.
- Property assignment history is durable. Assigning and unassigning tenants updates timestamps instead of deleting prior assignment records.
- Utility services are global reference records. Providers, tariffs, service configurations, meters, readings, invoices, and settings remain scoped to a single organization.
- Reports and dashboard metrics are computed from the same invoice and meter-reading models used by the operational workflows. This slice does not create report-only tables.
- Domain rules live in Actions, policies, and support services, not in Filament table closures, page classes, or Blade templates.

## System Architecture

This slice extends the existing Laravel 12 + Filament 5 authenticated shell rather than creating a second back office. The single admin panel continues to host superadmin, admin, and manager access, but this slice populates the organization-operator side of that shell with organization-aware resources and custom pages.

The design uses three layers:

1. Eloquent models and enums describe the organization operational domain.
2. Actions and support services own state changes, calculations, validation, and reporting logic.
3. Filament Resources and custom Pages provide the operator UI while delegating all real work downward.

Organization isolation is enforced in multiple places:

- role-based page access prevents the wrong actor from entering a route family
- resource queries resolve data through the authenticated user's organization context
- policies stop cross-organization record access and admin-only operations
- subscription guards stop create flows when the organization has reached commercial limits

The shared shell remains responsible for navigation chrome, locale switching, notifications, and global profile entry points. This slice plugs into that shell by adding organization resources, dashboard widgets, settings pages, and report pages, but it does not redefine shell primitives.

## Core Domain Model

### Organization Context and Settings

This slice builds on the existing `Organization`, `Subscription`, and `User` foundation.

`Organization` remains the top-level boundary for all operator data. `Subscription` remains the source of truth for plan state, read-only/grace behavior, and usage limits. `User` continues to encode the actor role (`admin`, `manager`, `tenant`) and organization membership.

`OrganizationSetting` adds the per-organization operational preferences that the admin workspace needs, including:

- billing contact details
- invoice footer or note copy
- reading cadence preferences
- notification preferences
- compliance thresholds used by dashboard/reporting surfaces

The settings record is organization-owned and should be treated as the writable configuration surface for the operational slice rather than sprinkling organization preferences across unrelated tables.

### Buildings, Properties, and Assignment History

`Building` represents a physical grouping of rentable or billable spaces inside one organization.

`Property` represents the billable unit managed by the organization. Important business rules:

- a property belongs to exactly one building and one organization
- property type is controlled through an enum
- occupancy is computed from the currently active assignment, not manually toggled
- list and detail pages can surface assignment-derived labels such as current tenant and assigned-since date without duplicating those fields on the property itself

`PropertyAssignment` is the historical ledger between a property and a tenant user. It stores:

- `property_id`
- `tenant_user_id`
- `unit_area_sqm`
- `assigned_at`
- `unassigned_at`

This model allows the workspace to show current occupancy, previous occupants, and assignment age while keeping unassignment reversible only through new history rows, never hard deletes.

### Utility Services, Providers, Tariffs, and Service Configuration

`UtilityService` is the shared catalog of service types that the product understands, such as water, electricity, gas, heating, or other billable services. Because service names and unit semantics should be consistent across organizations, this table is global reference data.

`Provider` represents the organization-scoped real-world service provider a building or property depends on.

`Tariff` represents the rate structure used to convert service usage into invoice line items. Tariffs are organization-scoped so each organization can preserve its own commercial configuration and lifecycle.

`ServiceConfiguration` ties operational billing defaults together. Its purpose is to connect:

- the organization
- the service being billed
- the provider and tariff that apply
- any organization-level defaults the invoice calculator or reporting layer needs

Rate-per-unit semantics and unit labels should be defined once in this configuration layer so meter entry, invoice generation, and reports all consume the same meaning.

### Metering

`Meter` represents a metered service endpoint linked to a property and organization. Important rules:

- meter type and status are controlled by enums
- the meter type determines the default measurement unit through a centralized mapping
- a meter may be deactivated without deleting its historical readings
- a meter cannot be deleted once readings exist

The meter detail experience may display historical readings and chart shells, but those views must be backed by the same normalized meter and reading records used everywhere else.

`MeterReading` stores the time-series operational data that powers validation, billing, dashboard reminders, and compliance reporting. It includes:

- meter linkage
- reading value and date
- submitting user when known
- submission method
- validation status
- optional operator notes

Validation rules are centralized in one support service so manual entry, editing, and bulk import all behave the same way. The core rules are:

- values cannot decrease relative to the prior valid reading
- future dates are not allowed
- anomalous spikes are flagged for review
- long collection gaps should surface notes for operator attention

### Invoices, Payments, and Communication Logs

`Invoice` is the financial aggregate for a property or tenant billing period. It represents the lifecycle from draft to finalized to paid or overdue. The invoice model owns:

- organization linkage
- billing period
- status
- finalized / due / paid dates
- the tenant/property context being billed

`InvoiceLineItem` stores the priced components that make up the invoice total. These rows are produced from tariffs, readings, and manual adjustments while the invoice is still editable.

`InvoicePayment` records money received against an invoice, including payment method and timing.

`InvoiceEmailLog` and `InvoiceReminderLog` store communication history so the workspace can show whether an invoice has been sent or reminded without inferring that state from notifications or mail logs elsewhere in the app.

Finalized invoices are historical records, not working drafts. After finalization, only status/payment-adjacent operations may still mutate the invoice.

## Roles and Workspace Rules

### Admin

Admins are the primary organization operators. They can:

- use the full organization dashboard
- manage settings for the organization
- create and manage operational records across the slice
- view subscription usage and initiate organization-scoped renewal actions

### Manager

Managers operate inside the same organization workspace but with a narrower presentation boundary:

- they can use the shared operational resources needed to run the organization day to day
- they do not see subscription usage widgets
- they do not see admin-only settings sections such as organization subscription controls
- direct URL access to admin-only settings sections must still be rejected even if a user tries to bypass hidden navigation

### Tenant Relationship to This Slice

Tenants are managed subjects in this slice, not shell operators. The admin workspace needs to create, invite, assign, bill, and review them, but tenant self-service screens belong to the tenant portal slice.

Tenant creation should reuse the existing organization-invitation mechanism so invitation tokens, email sending, and acceptance remain consistent with the auth foundation.

### Subscription Limits and Commercial State

The current subscription remains the source of truth for usage limits. This slice introduces a guard service that the UI must consult before creation flows such as:

- new property
- new tenant
- any other count-based entity the plan later ties to subscription limits

When a limit is reached, the create flow is blocked with a clear explanation while existing records remain visible according to the organization's commercial state.

## Operational Flows

### Organization Dashboard

The organization dashboard replaces the current placeholder page with real organization-scoped widgets.

Shared dashboard content for Admin and Manager:

- top-level operational count cards
- recent invoices
- upcoming reading deadlines

Admin-only content:

- subscription usage and limit visibility

Dashboard data should come from dedicated support classes so polling, caching, and eager loading can be tuned without rewriting page classes or Blade views. The polling interval for this slice is 30 seconds.

### Profile and Settings

The shared shell already provides a profile destination. This slice upgrades that lightweight destination into real operational profile/settings flows for admin-like users.

Profile responsibilities:

- personal identity fields
- password changes
- language preference persistence

Settings responsibilities:

- organization settings
- notification preferences
- billing contact / invoice footer configuration
- subscription state and renewal entry points for Admin only

If the shell already has a generic profile route, admin-like users should be redirected into the Filament-backed profile page rather than maintaining two separate editors.

### Buildings and Properties

Buildings and properties are managed through standard Filament Resources with:

- searchable and sortable tables
- guarded delete actions
- create/edit/view flows
- property detail views that surface occupancy and assignment history

Property assignment flows must support:

- initial assignment
- reassignment
- unassignment

These flows operate through the assignment ledger rather than mutating direct tenant fields on the property.

### Tenant Management

Tenant management uses a dedicated resource, but tenant account creation delegates to the invitation flow already defined in the auth slice.

The create flow may include:

- preferred language
- initial status
- optional property assignment

If a property is assigned during creation, that assignment must be written into `PropertyAssignment` immediately so the history remains complete.

Delete behavior is intentionally conservative. Once invoice history exists, the system preserves the tenant record rather than allowing destructive cleanup.

### Meter Management

Meter pages let operators create, edit, activate/deactivate, and review meters tied to properties. List filters must support the physical and operational axes users care about, such as building, property, type, and status.

The meter experience also needs a clear read-only history view so operators can understand recent readings without navigating away to a separate reporting feature.

### Meter Readings and Import

Meter-reading workflows support:

- manual create
- manual edit
- list and detail review
- validation actions
- bulk import with preview and invalid-row reporting

All paths share one validation engine. Once a reading has been consumed by finalized invoicing, edits are locked so billing history stays trustworthy.

### Billing Configuration

Operators configure utility services, providers, tariffs, and service configuration records through admin resources. These records are not just CRUD scaffolding; they define the financial inputs the invoice system consumes.

This means:

- delete restrictions must protect in-use pricing/configuration records
- units and rate semantics must be centralized
- invoice generation must not duplicate tariff interpretation logic inline

### Invoice Generation and Collection

The invoice workflow supports both single-record and bulk generation.

Required behavior:

- operators can preview calculated line items before finalization
- draft invoices remain editable
- finalized invoices become immutable except for payment/status operations
- bulk generation skips tenants who already have an invoice for the requested period
- payment recording updates the invoice lifecycle without rewriting historical line items
- sending and reminding invoices writes communication logs

Initial PDF delivery may render from Blade-backed invoice views, but the rendering pipeline must still consume normalized invoice data rather than embedding presentation logic inside controllers or resources.

### Reports

Reports are implemented as a custom Filament page backed by dedicated builder classes. The first four report families are:

- consumption
- revenue
- outstanding balances
- meter compliance

All tabs share a date-range baseline, then layer report-specific filters and result tables on top. Export actions should only appear after a dataset is loaded, and the export must match the filtered dataset currently visible in the UI.

## Authorization and Isolation Model

### Organization Scoping

Every organization operator route in this slice must resolve records through the authenticated user's organization context before rendering anything.

Required invariants:

- Admin from Organization A never sees Organization B operational data
- Manager from Organization A never sees Organization B operational data
- URL tampering returns `403` or `404`, never cross-organization content
- shell navigation only exposes routes that are meaningful for the current user's role and context

### Policy Enforcement

Policies must be the canonical source for destructive or role-sensitive decisions such as:

- who may delete a building, property, meter, tariff, or provider
- who may validate meter readings
- who may finalize invoices
- who may access organization settings or renewal flows

Filament action visibility should mirror these decisions, but policy checks remain authoritative.

### Immutable and Guarded States

Some records become progressively more protected as the workflow advances:

- properties/buildings with dependent records cannot be casually deleted
- meters with readings cannot be deleted
- readings tied to finalized billing become locked
- finalized invoices cannot be reopened into arbitrary editable drafts

These constraints are part of the domain, not just UI decoration, so they must live below the page layer.

## Localization and UX Behavior

This slice inherits the localization foundation established earlier:

- supported locales remain `en`, `lt`, `ru`, and `es`
- operator pages, form labels, statuses, filters, and notifications use translation keys
- changing language from profile or shell controls updates visible text immediately
- English remains the fallback locale

Operator UX rules for this slice:

- forms validate field-by-field with inline errors
- destructive actions require explicit confirmation
- long-running actions show loading states and disable repeated submission
- empty tables and report tabs show intentional empty states rather than blank screens
- filters that drive multi-step analysis, especially reports, should persist across short navigations when that improves continuity

## Edge Cases and Failure Handling

### Subscription Limit Reached

If the organization has reached a plan limit:

- create flows are blocked before records are written
- the user sees a clear explanation of what limit was reached
- existing records remain readable according to the organization's subscription state

### Assignment History Integrity

When a tenant is reassigned or unassigned:

- the prior active assignment is closed with `unassigned_at`
- the history remains queryable
- the property's current occupancy derives from whichever assignment is still active

### Reading Anomalies and Gaps

If a reading is suspicious or submitted after a long gap:

- the system records the reading with validation context
- operators can review the anomaly before it silently affects billing
- imports surface invalid or suspicious rows in preview rather than partially hiding them

### Finalized Billing Records

Once an invoice is finalized:

- line items are treated as historical billing evidence
- related readings used by that billing period cannot be casually edited
- later actions such as payment recording or reminder sending update status/log records instead of rewriting the invoice body

### Duplicate Bulk Billing

If bulk generation is requested for a period that already has invoices for some tenants:

- existing invoices are skipped, not overwritten
- the result summary makes the skipped tenants explicit

### Manager Access to Admin-Only Settings

If a Manager attempts to reach an admin-only configuration screen directly:

- navigation should not surface the route
- the route should still reject access if requested manually

## Testing Strategy

This slice should be covered mostly through feature tests because the value lies in end-to-end operator flows across policies, actions, Eloquent models, and Filament pages.

Required coverage includes:

- dashboard visibility and role-aware widget differences
- profile and settings flows, including immediate language persistence
- buildings and properties CRUD plus assignment history behavior
- tenant creation through invitation reuse and assignment-aware lifecycle behavior
- meter CRUD and meter deletion guards
- meter reading validation, import preview, anomaly behavior, and locked-state behavior
- billing configuration CRUD and delete restrictions
- invoice draft/finalize/pay/send/remind flows
- bulk invoice generation skip behavior for already-billed periods
- report tab rendering, filter behavior, and export gating
- organization scoping and policy enforcement across admin and manager users
- subscription-limit guard behavior on creation flows

Where calculations or report builders become non-trivial, unit tests may supplement the feature suite, but the primary acceptance signal for this slice is still feature-level proof that the workspace works end to end.

## Delivery Boundary for This Slice

This slice is complete when:

- Admin and Manager users can enter a real organization dashboard instead of a placeholder page
- Admin users can configure organization settings and subscription-related controls while Managers see only the allowed subset
- organization operators can manage buildings, properties, tenant assignments, meters, readings, pricing inputs, and invoices inside the shared shell
- tenant creation reuses the invitation lifecycle already established in the foundation slice
- invoice workflows support draft creation, finalization, payment tracking, and communication logging
- reports are generated from live operational models rather than duplicate reporting tables
- organization isolation prevents cross-org access throughout the implemented flows
- subscription limits block over-allocation without breaking access to existing organization data

## Out of Scope for the Next Planning Step

The implementation plan for this design should not expand into:

- superadmin control-plane operations for managing many organizations
- tenant self-service portal behavior beyond activation/onboarding dependencies already defined elsewhere
- automated recurring billing schedulers or cron-driven invoice runs
- advanced integrations with external accounting, banking, or payment systems
- cross-organization analytics or warehouse-style reporting

Those belong to later vertical slices built on top of this organization-operator foundation.
