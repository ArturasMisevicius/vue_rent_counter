# Tenanto Historical Execution Roadmap

> **AI agent usage:** This is a historical rollout document. Read `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, `docs/AI-AGENT-DOCS.md`, `docs/PROJECT-CONTEXT.md`, and `docs/FEATURES.md` first. Verify current code before changing behavior.

Updated on 2026-06-15. The original March roadmap was mostly executed and then extended by later March, April, May, and June commits. This file now records how the old roadmap maps to the current product; it is not a current backlog.

## Current Status Summary

| Historical phase | Current interpretation |
| --- | --- |
| Foundation auth and onboarding | Implemented through Livewire auth pages, invitations, onboarding, route redirects, account accessibility, locale persistence, and tests. |
| Shared interface elements | Implemented through the shared shell, navigation config, topbar/sidebar, notifications, global search, locale switcher, impersonation banner, profile, and error pages. |
| Admin organization operations | Implemented and expanded: buildings, properties, tenants, assignments, meters, readings, service configurations, invoices, payments, extra charges, reports, documents, contracts, KYC, leads, and move-out. |
| Manager role parity | Implemented as shared admin workspace with manager memberships, presets, permission matrix, middleware, policies, and tests. |
| Tenant self-service portal | Implemented and expanded: home, readings, invoices, property, documents, verification/KYC, contracts/downloads, profile, and help. |
| Superadmin control plane | Implemented and expanded: organizations, subscriptions, users, platform dashboard, system configuration, translations, languages, audit/security, integration health, projects, exports, feature flags, and impersonation. |
| Cross-cutting behavioral rules | Implemented in several waves: subscription checks, reading validation, invoice immutability, security headers, public surface guardrails, localization, route boundaries, and role tests. |
| Missing information closures | Implemented in later hardening: invitation lifecycle, tenant continuity, breadcrumbs, empty states, public debug lockdown, and tenant portal isolation. |

## Current Roadmap Source

For current usage and future work, start with:

- `../FEATURES.md`
- `../PROJECT-CONTEXT.md`
- `../PERMISSION-MATRIX.md`
- `../operations/billing-reading-invoice-workflow.md`
- `../../CHANGELOG.md`

## Current High-Risk Follow-Up Areas

These are not old phase gates; they are the current places where new work needs careful verification:

- Tenant KYC local database state: the checked-in KYC migration was pending in local SQLite on 2026-06-15.
- Billing review and tenant reading requests: tenant readings are invoice-request-driven and must stay backend-scoped.
- Manager permissions: use `App\Enums\Permission`, the manager catalog, `EffectivePermissionsResolver`, middleware, policies, and action checks together.
- Tenant downloads: invoices, documents, KYC files, attachments, and rental contracts must be authorized server-side.
- Move-out: final readings, final invoices, occupancy state, contract closure, and portal access must stay coordinated.
- Historical docs: do not resurrect removed March plan files by following stale links from older summaries.

## How To Resume A Historical Slice

1. Open `docs/FEATURES.md` and identify the current implementation area.
2. Read the matching historical spec or plan only for intent.
3. Use `git log -- <path>` and current tests to confirm what landed.
4. Update current docs, not just this historical folder, when behavior changes.
