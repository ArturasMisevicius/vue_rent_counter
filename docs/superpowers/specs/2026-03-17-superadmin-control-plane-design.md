# Tenanto Superadmin Control Plane Design

> **Workflow note:** Keep spec and implementation changes for this slice on `main` only. Do not create or use separate branches or worktrees.

## Goal

Define the platform-level control plane that lets the Tenanto operator oversee organizations, users, subscriptions, security, localization, platform notifications, and integration health from one coherent superadmin experience.

This slice is the governance layer above every organization workspace. It should therefore reuse the shared authenticated shell while remaining clearly separate in responsibility from the admin, manager, and tenant experiences that operate inside individual organizations.

## Scope

This slice includes:

- a dedicated superadmin Filament workspace inside the shared shell
- platform dashboard widgets for global health and commercial visibility
- organization, user, and subscription management
- system configuration and language-management surfaces
- platform notifications and audit-log visibility
- security operations such as blocked IP management and violation review
- integration health checks and manual health actions
- superadmin-owned impersonation entry points for organization troubleshooting

This slice does not include:

- tenant-facing pages
- organization-scoped operational CRUD such as buildings, properties, meters, or invoices owned directly by superadmin
- a second admin framework or a shell separate from the shared Filament chrome
- speculative domain modules created only to fill out the platform UI
- deep export packaging for downstream domains that do not exist yet

## Dependency Context

This design depends on:

- `docs/superpowers/specs/2026-03-17-foundation-auth-onboarding-design.md` for role separation, organization ownership, and subscription primitives
- `docs/superpowers/specs/2026-03-17-shared-interface-elements-design.md` for the authenticated shell, superadmin navigation, and impersonation banner/runtime

It complements:

- `docs/superpowers/specs/2026-03-17-admin-organization-operations-design.md` by governing the organizations that later use the organization workspace
- `docs/superpowers/specs/2026-03-17-cross-cutting-behavioral-rules-design.md` by supplying shared audit, localization, and health primitives

## Approved Product Decisions

- All superadmin pages remain inside Filament and reuse the shared shell rather than introducing a separate platform runtime.
- Platform-owned models such as system settings, audit logs, platform notifications, languages, security violations, integration checks, and subscription payments are first-class domain records.
- Superadmin may manage organizations and subscriptions directly because those are platform concerns rather than organization self-service concerns.
- Read adapters and null-object services are acceptable when deeper product domains are not implemented yet, but they must be explicit and fail-safe.
- Impersonation start behavior belongs to the superadmin control plane because it is a platform-governance tool.
- Sensitive platform mutations must be routed through Actions and audit logging rather than page-local logic.

## System Architecture

The control plane is organized into four layers.

### Platform Governance Layer

This layer owns platform-wide configuration and oversight:

- system settings
- language catalog management
- platform notifications
- security violations and blocked IP administration
- integration health state

These concerns belong to the platform itself and should not be mixed into organization settings pages.

### Platform Entity Management Layer

This layer manages the global platform entities:

- organizations
- users
- subscriptions
- subscription payments

These flows let superadmin create, suspend, reinstate, extend, upgrade, or otherwise govern platform customers and access state.

### Platform Observability Layer

This layer explains what is happening across the platform:

- audit logs
- recently created organizations
- expiring subscriptions
- revenue-by-plan visibility
- security alerts
- integration-probe results

Widgets and tables in this layer should be backed by explicit support services or adapters rather than ad hoc query logic in resources.

### Filament Experience Layer

Filament owns the actual superadmin UI:

- dashboard widgets
- platform resources
- configuration and translation pages
- organization-view actions including impersonation and exports

As with the organization workspace, page and resource classes remain thin. Actions and support classes own the real behavior.

## Core Platform Model

### Organizations, Users, and Subscriptions

Superadmin governs the lifecycle of platform customers:

- organizations and their status
- users and their role/state
- subscriptions, renewals, upgrades, suspensions, and cancellations
- subscription payments and duration choices

These records are global from the platform perspective even though end-user operations later become organization-scoped.

### System Settings and Languages

System settings define platform behavior that cannot live inside an individual organization. Languages and translation-management surfaces define which locales are supported and how the platform-level catalog evolves.

### Audit and Notifications

Audit logs and platform notifications capture what the platform did and what it needs to communicate. Auditability is critical here because superadmin actions can affect many organizations and users at once.

### Security and Integration Health

Security violations, blocked IP addresses, and integration health checks define the platform’s operational defense and diagnostics layer. These are platform stewardship tools rather than customer-facing features.

## Superadmin Experience Behavior

### Dashboard

The superadmin dashboard should surface platform-wide signals such as:

- revenue or plan-distribution visibility
- recent organization creation
- subscription expiry pressure
- recent security violations
- current integration health

This dashboard is global by design and must not inherit organization-scoped assumptions from admin pages.

### Resource Management

Platform resources should follow the same structural discipline as the rest of the project:

- dedicated schema classes
- action-based writes
- policy-backed authorization
- eager-loaded relationships
- adapter services for counts or summaries that depend on not-yet-shipped domains

### Impersonation and Exports

Superadmin can initiate impersonation to inspect an organization’s experience using the shared impersonation session primitives from the shell layer. Export behavior should be modeled as an explicit platform action with a builder or null-adapter approach until deeper organization data is available.

## Authorization and Safety Rules

- Only superadmin users may access this control plane.
- Superadmin routes and navigation must never leak into admin, manager, or tenant surfaces.
- Sensitive mutations should be mediated through Actions and logged through the audit layer.
- Platform status and security views should remain available even when some downstream organization domains are incomplete.
- Null adapters must make missing downstream data explicit rather than pretending unsupported operations are fully implemented.

## Acceptance Scenarios

### Scenario 1: Platform dashboard access

Given an authenticated superadmin
When they open the platform dashboard
Then they see global commercial, security, and operational health signals
And they do not land in an organization-scoped workspace by default

### Scenario 2: Organization and subscription governance

Given a superadmin managing a customer organization
When they create, suspend, reinstate, extend, upgrade, or cancel organization-related records
Then those changes are performed through platform-owned actions
And the affected records remain auditable

### Scenario 3: Language and configuration management

Given a superadmin is working with system configuration or languages
When they update those settings
Then the change applies at the platform layer rather than inside any one organization

### Scenario 4: Security and integration operations

Given a superadmin reviews platform risk and health
When they inspect violations, blocked IPs, or integration checks
Then they can understand the current platform state and run the supported operational actions

### Scenario 5: Impersonation entry

Given a superadmin needs to inspect an organization experience
When they start impersonation from the control plane
Then the action uses the shared impersonation contract
And the resulting session context remains visible in the shared shell

## Operational Notes

- This slice should reuse shared shell primitives rather than rebuilding navigation, topbar behavior, or impersonation state.
- When downstream organization modules are incomplete, prefer explicit read adapters and null-object support services over speculative fake CRUD.
- Future platform-governance features should land here only when they truly operate above the organization boundary; otherwise they belong in the organization workspace.
