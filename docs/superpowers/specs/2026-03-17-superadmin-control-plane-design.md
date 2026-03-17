# Tenanto Superadmin Control Plane Design

## Goal

Establish the first production-ready superadmin control-plane slice of Tenanto so the platform owner can operate the application from a real Filament-backed interface instead of placeholder pages. This slice gives superadmin users a complete platform dashboard, platform-wide organization and user oversight, subscription governance, localization management, security operations, and integration monitoring.

This design intentionally extends the authentication and shared-shell slices instead of creating a second admin experience. It also intentionally stops at the boundary where the platform owner can govern the system safely without forcing the project to invent unfinished organization, billing, property, metering, or reporting modules.

## Scope

This slice includes:

- a real superadmin dashboard with platform-wide widgets
- organization list, create, edit, and view flows
- platform-wide user list, create, edit, and view flows
- subscription list, create, edit, and view flows
- system-configuration management for platform-owned settings
- audit-log visibility for superadmin actions and governance events
- platform notifications with draft and send flows
- language management and translation-catalog operations
- security-violation monitoring and blocked-IP management
- integration-health monitoring with manually runnable probes
- shared-shell navigation and Filament registration for all superadmin pages
- platform-owned persistence for settings, audit logs, notifications, subscription payments, languages, security violations, blocked IPs, and integration checks
- adapter-based read models for organization usage and export data that do not yet exist elsewhere in the application

This slice does not include:

- CRUD modules for buildings, properties, tenants, meters, readings, invoices, providers, tariffs, or reports
- a full platform reporting suite beyond the dashboard widgets and operational summaries defined here
- deep organization-export packaging for downstream billing or metering data that the repository does not yet contain
- customer-facing UX changes outside the shared authenticated shell already defined by earlier slices
- automated remediation workflows for failed integrations beyond recording status and offering reset or rerun actions
- a separate superadmin panel or router outside the existing Filament application shell

## Approved Product Decisions

- Superadmin remains a platform-level role outside the organization model.
- The superadmin experience lives inside the existing authenticated Filament shell rather than a bespoke admin frontend.
- The shared sidebar, topbar, locale switcher, notifications center, and impersonation banner from the shared-interface slice are reused instead of duplicated.
- The control plane owns its own persistence for platform concerns such as settings, audit logs, platform notifications, security events, integration checks, and language state.
- Cross-domain summaries that depend on not-yet-built organization modules are delivered through read-model interfaces and null-safe adapters so this slice can ship without inventing unrelated CRUD domains.
- Translation management operates through a translation-catalog service rather than placing translation logic directly into Blade views or Filament closures.
- Every destructive or state-changing superadmin operation is policy-protected, confirmable in the UI, and audit logged.
- Subscription records store enough plan and limit snapshot data for platform governance, historical review, and safe plan transitions.
- Security blocking happens at request entry through middleware rather than only as a dashboard warning.

## System Architecture

The superadmin control plane sits on top of two earlier slices:

- the authentication and onboarding foundation, which establishes role-aware login and the superadmin role
- the shared interface elements slice, which establishes the authenticated shell, shared navigation, locale behavior, notifications presentation, and impersonation-session contract

Within that shell, the control plane is implemented as a platform-owned Filament suite made up of:

- dashboard widgets for platform metrics and operational signals
- Resources for organizations, users, subscriptions, audit logs, platform notifications, languages, and security violations
- custom Filament Pages for system configuration, translation management, and integration health

The architectural boundary is intentionally strict:

- Filament Resources and Pages own presentation and request orchestration only
- Actions own state changes such as suspending an organization, upgrading a subscription, sending a platform notification, or blocking an IP address
- support services own read-model aggregation, export assembly, translation-catalog access, integration probing, and audit logging
- models own explicit relationships, casts, scopes, and payload shaping

This keeps governance logic out of Blade templates, out of ad-hoc Resource closures, and out of controllers, which is especially important because this slice spans several independent operational concerns.

## Core Domain Model

### Organization

`Organization` remains the primary tenant container, but the superadmin slice extends it for platform oversight rather than tenant-scoped operations.

The control plane needs each organization to expose:

- ownership information
- current subscription relationship
- platform-facing status
- created-at ordering for recent-organization widgets
- usage snapshot placeholders or adapter-backed data for downstream modules

Superadmin can inspect every organization regardless of tenant scope. Organization records are still treated as the source of truth for status and ownership, but detailed operational data such as meters, invoices, or properties is intentionally not introduced here.

### User

`User` remains the single account model for every role:

- `superadmin`
- `admin`
- `manager`
- `tenant`

The control plane needs platform-wide visibility into:

- account role
- account status
- organization membership when applicable
- last-login information
- ownership or membership relationships needed for organization views

This slice does not introduce a separate user directory model or role-specific account tables.

### Subscription

`Subscription` remains the commercial state for an organization, but this slice upgrades it into a governable platform record.

The control plane needs subscriptions to support:

- plan and status display
- trial visibility
- starts-at and expires-at governance
- limit snapshot fields so later limit changes do not erase historical context
- payment history through platform-owned `SubscriptionPayment` records

The superadmin experience can extend, upgrade, suspend, or cancel subscriptions without needing the later organization billing modules to exist first.

### System Setting

`SystemSetting` stores platform-owned configuration entries grouped by category. These values represent application-wide operational settings rather than organization-scoped preferences.

The design assumes settings are:

- typed or castable at the model layer
- organized by category for a manageable configuration UI
- updated through actions rather than inline page logic
- audit logged when changed

### Audit Log

`AuditLog` captures platform-governance activity in a read-only record stream.

The control plane needs audit entries to represent:

- the acting user
- a translated action type
- the affected model or domain object
- contextual metadata needed for later forensic review
- timestamps suitable for ordered review and filtering

Audit logging is a first-class part of this slice because the control plane introduces sensitive platform-wide operations.

### Platform Notification and Delivery

`PlatformNotification` stores the authorable notification itself, while `PlatformNotificationDelivery` tracks send outcomes or recipient targeting.

The control plane needs notifications to support:

- draft and sent states
- severity classification
- platform-wide or targeted delivery intent
- visibility into recipient or delivery outcomes where available

This slice intentionally treats notification drafting and sending as a platform concern rather than reusing tenant-specific notification concepts that do not exist yet.

### Language and Translation Catalog

`Language` stores the platform's supported locales and their activation status. It represents the platform policy layer for localization.

Translation content itself is managed through a translation-catalog service that reads and writes the application's translation sources. That separation is intentional:

- `Language` answers which locales are available and which locale is default
- the translation service answers which keys exist and what their values are
- translation management remains a platform workflow rather than a direct file-editing concern exposed to the UI

### Security Violation and Blocked IP Address

`SecurityViolation` records security-relevant events such as abusive access attempts or policy breaches. `BlockedIpAddress` stores active network blocks enforced by middleware.

The control plane needs these records to support:

- severity and type classification
- source-IP tracking
- contextual metadata for review
- manual blocking actions
- recent-incident dashboard visibility

This slice keeps the incident model simple and platform-owned instead of trying to implement a full SOC workflow.

### Integration Health Check

`IntegrationHealthCheck` persists the latest known status of platform probes such as database, queue, or mail connectivity.

The control plane needs each record to represent:

- which probe was executed
- the latest status
- the time checked
- a human-readable summary
- any limited diagnostic payload needed for support

The probe runtime itself is owned by a registry plus probe contracts so the UI does not need to know how each dependency is tested.

## Roles and Authorization Model

### Superadmin Access

Only authenticated users with the `superadmin` role may access the control plane.

That means:

- only superadmins may reach control-plane routes, pages, widgets, and actions
- admin, manager, and tenant users are blocked from every control-plane entry point
- platform dashboard visibility is not shared with any organization-bound role

### Cross-Organization Oversight

Superadmin is intentionally allowed to inspect every organization, every subscription, and every user across organizations.

This is not a breach of tenant isolation; it is the platform-owner exception built into the product model. The rule is:

- superadmin may inspect platform-wide records directly
- organization-scoped roles continue to operate inside organization boundaries
- impersonation is the only time a superadmin should enter an organization-scoped runtime context

When impersonation is active, the shared-shell impersonation session contract from the earlier slice is reused so the acting user can safely return to the platform context.

### Policy and Action Safety

Every Resource action and custom page action in this slice is policy-gated.

In practice, that means:

- list, view, create, edit, delete, suspend, send, block, reset, and export operations have explicit authorization rules
- destructive actions use confirmation dialogs in the UI
- actions write audit-log entries after successful state changes

The control plane must not rely on implicit "only superadmins can see this page" assumptions alone.

### Request-Time IP Blocking

Blocked IPs are enforced at middleware entry before the control plane renders. This keeps security blocking effective across the whole application rather than leaving enforcement up to individual pages.

## Control-Plane Experience

### Platform Dashboard

The platform dashboard replaces the current placeholder page with real widgets sourced from platform-owned models and adapter-backed summaries.

The dashboard should surface:

- high-level platform counts
- revenue or subscription-plan distribution summaries
- expiring subscriptions
- recently created organizations
- recent security violations

Widgets should be driven by Eloquent scopes, cached model methods, or support readers. They must not hide data-fetching logic inside Blade templates.

### Organizations

The organizations resource is the primary governance surface for tenant containers.

The superadmin can:

- list organizations with searchable and filterable platform metadata
- create organizations for privileged setup flows
- edit organization status and core details
- view organization ownership, subscription state, and usage snapshot data
- suspend or reinstate an organization
- trigger organization notifications
- begin impersonation using the shared-shell impersonation contract
- request an organization data export through an export builder interface

Because the repository does not yet contain the full downstream domain, usage and export details are intentionally adapter based and may render partial or empty-state data without breaking the page.

### Users

The users resource gives superadmin platform-wide account oversight.

The core user-management responsibilities in this slice are:

- viewing all users across roles and organizations
- filtering by role, status, and organization context
- editing safe account fields and status
- inspecting account ownership or organization relationships

This slice can include a functional create flow, but it intentionally avoids inventing a more detailed future user-administration product than the current specification requires.

### Subscriptions

The subscriptions resource exposes the current commercial state of each organization.

The superadmin can:

- list and filter subscriptions by plan, status, and expiry
- create or edit subscription records when needed for platform operations
- extend the current term
- change the plan
- suspend or cancel access
- inspect limit snapshots and payment history

This keeps subscription governance in one place without requiring the later billing rollout to be complete.

### System Configuration and Audit Logs

System configuration is modeled as a custom page rather than a generic CRUD resource because the interaction is category based and operational in nature.

The superadmin can:

- view grouped platform settings
- update setting values through validated actions
- rely on audit logging for every change

Audit logs are exposed as a read-only resource optimized for search and inspection rather than mutation.

### Platform Notifications

Platform notifications give the platform owner a controlled way to communicate system-wide or targeted messages from inside the control plane.

The workflow supports:

- drafting a message
- editing a draft
- reviewing severity and targeting
- sending the notification
- inspecting delivery outcomes where they exist

Drafting and sending are separate behaviors so the platform owner can review content before broad distribution.

### Languages and Translation Management

Localization is split into two related but different experiences:

- language management controls which locales are supported and which locale is default
- translation management controls the actual translated copy for keys in the catalog

This allows the platform owner to:

- activate or deactivate locales
- set the default locale
- create or edit language metadata
- review translation keys by locale
- edit missing or outdated values
- import translations
- export missing translation work

The translation-management page is intentionally custom because it behaves more like an operational workspace than a simple row-by-row resource.

### Security Operations

The security-violations resource exposes platform-level incident visibility.

The superadmin can:

- review recent and historical security events
- filter by severity or type
- inspect contextual metadata
- block a source IP when needed

The dashboard also surfaces recent violations so the superadmin does not need to navigate away from the landing page to see operational risk.

### Integration Health

Integration health is a custom operational page driven by a probe registry.

The superadmin can:

- review the last-known status of each probe
- manually rerun health checks
- reset a probe's circuit-breaker state when applicable

The control-plane page shows stored status even when a probe is currently failing so the user can diagnose degradation without losing historical context.

## Data Availability and Adapter Strategy

This slice must coexist with an intentionally incomplete repository. The current codebase does not yet contain the full organization-operations domain, but the control plane still needs usage, export, and summary affordances.

To make that safe, the design introduces explicit adapter seams:

- `OrganizationUsageReader` provides usage-summary data for organization views and dashboard summaries
- `OrganizationDataExportBuilder` provides export-package assembly for organization exports
- null implementations provide deterministic empty-state behavior when downstream modules do not exist yet

This is a deliberate product and engineering decision. The control plane is allowed to ship with graceful partial views instead of waiting for every downstream module to exist first.

## Localization Strategy

Localization for the control plane builds on the shared locale foundations from earlier slices.

The platform layer is responsible for:

- defining supported locales in configuration
- persisting language status and default-language choices
- storing superadmin-facing translations for control-plane labels and content
- exposing a translation workspace for non-technical platform operators

The control plane must remain usable even when some translations are missing. Missing values should surface as manageable gaps in the translation workspace rather than as silent failures.

## Edge Cases and Failure Handling

### Missing Downstream Organization Data

If properties, invoices, meter readings, or similar downstream data do not exist yet, the control plane should:

- render stable empty states
- show zero or unavailable usage values through null adapters
- allow governance actions that do not depend on those modules
- avoid failing entire pages because one downstream domain is absent

### Suspended Organizations and Subscriptions

The control plane must still let superadmin users inspect and manage suspended organizations or subscriptions. Suspension blocks organization users from normal access; it does not block the platform owner from governance.

### Blocked IP Requests

If an IP address is blocked, middleware should reject the request before the control plane renders. The control plane itself should still expose the blocked-IP list and allow safe unblock flows through authorized actions.

### Probe Failures

If a dependency check fails during an integration-health run:

- the failed result is persisted
- the health page still loads
- the failed probe is marked with the correct degraded or failed status
- the superadmin can rerun the probe later after remediation

### Translation Import and Missing Keys

Translation-management operations should surface conflicts, missing files, or invalid payloads as actionable errors rather than silently dropping data. Existing translations should not be overwritten accidentally without an explicit update path.

## Testing Strategy

This slice should be covered primarily by feature tests, supported by focused unit tests around the action and support-service boundaries.

Required feature coverage:

- superadmin can access the platform dashboard and non-superadmin roles cannot
- dashboard widgets render platform-wide summaries from seeded data
- organizations resource supports list, create, edit, view, suspend, and reinstate flows
- organization-specific superadmin actions such as send notification, impersonation start, and export initiation are correctly authorized
- users resource supports platform-wide list, filter, view, and edit flows
- subscriptions resource supports list, create, edit, extend, upgrade, suspend, and cancel flows
- system-configuration updates succeed only for authorized users and create audit-log entries
- audit logs are visible and ordered correctly
- platform notifications support draft and send flows
- languages resource supports create, edit, activation changes, and default-language changes
- translation-management page supports viewing keys, updating values, importing data, and exporting missing translations
- security-violations resource supports list and IP-block flows
- integration-health page supports viewing stored status, rerunning checks, and resetting probe state
- blocked IP middleware denies requests from blocked addresses
- null adapter behavior produces stable empty states when downstream domains are absent

Recommended focused unit coverage:

- `AuditLogger`
- `TranslationCatalogService`
- `OrganizationUsageReader` and `NullOrganizationUsageReader`
- `OrganizationDataExportBuilder` and its null implementation
- `IntegrationProbeRegistry` and individual probes
- action classes that perform sensitive state changes

## Delivery Boundary for This Slice

The slice is complete when:

- a superadmin can sign in and land on a real platform dashboard inside the shared authenticated shell
- the control-plane navigation exposes the platform areas defined in this design
- organizations, users, and subscriptions can all be governed through working Filament resources
- system configuration and audit logs are available from the control plane
- platform notifications can be drafted and sent
- languages can be managed and translation keys can be reviewed and updated
- security violations and blocked IPs can be reviewed and acted on
- integration checks can be reviewed and manually rerun
- all mutating actions are policy gated and audit logged
- missing downstream domain data degrades gracefully through adapter-backed empty states rather than page failures

## Out of Scope for the Next Planning Step

The implementation plan for this spec should not include:

- organization-scoped CRUD modules for buildings, properties, tenants, meters, readings, providers, tariffs, or invoices
- the full report module
- advanced export packaging that depends on those not-yet-built domains
- customer self-service features
- manager-role parity work outside what the shared shell already provides
- automated incident-remediation workflows
- a second platform UI stack outside Filament

Those belong to later vertical slices built on top of this control-plane foundation.
