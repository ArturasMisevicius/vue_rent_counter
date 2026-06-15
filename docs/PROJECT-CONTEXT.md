# Tenanto Project Context

> **AI agent usage:** Read `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, `docs/AI-AGENT-DOCS.md`, and `docs/FEATURES.md` before acting on this file. Treat examples as context; verify current code, routes, schema, translations, and tests before changing behavior.

This is the verified repository snapshot for Tenanto as of 2026-06-15. It supersedes older March and May project-count snapshots.

## Stack

Verified from manifests and local commands:

- Composer PHP requirement: `^8.3`
- Composer platform PHP: `8.3.24`
- Local CLI during this audit: PHP `8.5.7`
- Laravel `13.15.0`
- Filament `5.6.7`
- Livewire `4.3.1`
- Tailwind CSS `4.2.4`
- Vite `8.0.10`
- Pest `4.7.3`
- PHPUnit `12.5.29`
- SQLite as the default local database path
- Database-backed queue, cache, and session drivers in `.env.example`
- Locales: `en`, `es`, `lt`, `ru`

## Product Summary

Tenanto is a multi-tenant utility billing and property management SaaS. The authenticated workspace is Filament-first; Livewire backs public auth routes, role redirects, shell components, tenant portal routes, profile/download endpoints, locale preferences, and security intake.

The app supports four audiences:

- Platform operators manage organizations, subscriptions, users, localization, audit/security, integrations, platform notifications, projects, and global health.
- Organization admins manage buildings, properties, tenants, assignments, meters, readings, billing, invoices, payments, extra charges, documents, KYC, contracts, leads, reports, settings, and managers.
- Managers work inside one organization through manager memberships and permission presets.
- Tenants use a self-service portal for readings, invoices, property context, documents, KYC, contracts, profile, help, and authorized downloads.

## Role Model

The current role enum is `app/Enums/UserRole.php`:

- `SUPERADMIN`
- `ADMIN`
- `MANAGER`
- `TENANT`

Role behavior must be verified through policies, middleware, Filament accessors, `config/tenanto.php`, `App\Enums\Permission`, `EffectivePermissionsResolver`, route middleware, and tenant portal actions.

## Current Workspace Shape

Verified on 2026-06-15:

- 230 routes from `php artisan route:list`.
- 1 controller file under `app/Http/Controllers`, the base controller.
- 41 Filament resource classes.
- 27 Filament pages.
- 51 Livewire PHP classes.
- 79 top-level model classes under `app/Models`.
- 207 Filament action classes.
- 117 migration files.
- 222 `*Test.php` files.
- 1 Filament panel provider at `app/Providers/Filament/AppPanelProvider.php`.
- 399 local `SKILL.md` files across `.agent`, `.agents`, `.ai`, `.claude`, and `.codex` skill roots.
- No standalone service worker is part of the maintained application.
- No public debug PHP entrypoint should be added.

## Architecture Baseline

- Prefer Eloquent-first data access, relationships, scopes, presenters, and action classes over raw SQL.
- Request validation belongs in `app/Http/Requests`.
- New action and support work should normally live under `app/Filament/Actions` and `app/Filament/Support`.
- `app/Actions/Billing` exists for current billing workflow classes; treat it as a legacy/current billing-specific namespace, not as a general place for new foundation code.
- Keep controllers thin. The current web surface is deliberately Livewire/Filament-backed.
- Keep authorization explicit through policies, middleware, Filament authorization hooks, and action-level checks.
- Tenant and organization isolation are backend rules, not UI-only visibility decisions.
- Keep tenant portal data in presenters, query classes, actions, policies, and Livewire state; Blade templates should stay display-only.
- Do not reintroduce `FrameworkStudio` or `/app/framework-studio`.

## Key Code Areas

- Filament resources/pages/widgets/actions/support: `app/Filament`
- Livewire pages/components/endpoints: `app/Livewire`
- Tenant portal read models: `app/Filament/Support/Tenant/Portal`
- Tenant portal actions: `app/Filament/Actions/Tenant`, `app/Filament/Actions/TenantDocuments`, `app/Filament/Actions/TenantKyc`
- Admin billing actions/support: `app/Filament/Actions/Admin/Invoices`, `app/Filament/Actions/Admin/BillingReview`, `app/Filament/Support/Admin/BillingReview`, `app/Services/Billing`
- Billing console actions: `app/Actions/Billing`
- Shell navigation source of truth: `config/tenanto.php` and `app/Filament/Support/Shell/Navigation`
- Policies: `app/Policies`
- Core domain models: `app/Models`
- Filament panel provider: `app/Providers/Filament/AppPanelProvider.php`
- Public and shell views: `resources/views`
- Routes: `routes/web.php` and `routes/console.php`
- Current docs: `README.md`, `CHANGELOG.md`, `docs/FEATURES.md`, and `docs/operations/**`
- Historical plans/specs: `docs/superpowers/**`

## Tenant Portal UX Contract

Tenant role UI is self-service, not an admin-style workspace.

- Tenant navigation comes from `config/tenanto.php` under `tenanto.shell.navigation.roles.tenant`.
- Tenant pages should prioritize Home, Property, Readings, Invoices, Documents, Verification, Help, and Profile.
- Tenant routes must remain tenant-scoped and authorized even when reached directly by URL.
- Tenant documents, invoices, KYC files, rental contracts, and attachments must be downloaded through backend actions and policies.
- Tenant readings are currently invoice-request-driven; do not restore free-form tenant reading submission without updating billing workflow docs and tests.

## Current Route Families

- Public: `/`, `/favicon`, `/favicon.ico`, `/login`, `/register`, password reset, invitation acceptance, locale, CSP report.
- Shared auth: `/dashboard`, `/logout`, `/profile`, `/profile/avatar`, `/welcome`, `/impersonation/stop`.
- Filament app panel: `/app/**`.
- Tenant aliases: `/tenant`, `/tenant/readings/create`, `/tenant/invoices`, `/tenant/documents`, `/tenant/verification`, `/tenant/property`, `/tenant/profile`.
- Tenant downloads: invoice, document, KYC document, rental contract, and tenant attachment routes.
- Platform export helper: `/app/platform-dashboard/recent-organizations-export`.

## Repo-Local MCP

`.mcp.json` defines:

- `herd`
- `21st-dev-magic`
- `context7`
- `playwright`

`21st-dev-magic` expects `TWENTY_FIRST_DEV_API_KEY` in the host agent/editor environment. On 2026-06-15, `php artisan list --raw` did not include `boost:mcp` or `mcp:start`.

## Verification Snapshot

Commands run during the 2026-06-15 docs audit:

- `php artisan about` succeeded.
- `php artisan route:list` succeeded and showed 230 routes.
- `php artisan migrate:status` succeeded and showed all migrations ran except `2026_06_15_000000_create_tenant_kyc_verification_tables.php`, which was pending in the local SQLite database.
- `php artisan filament:cache-components` succeeded.

Full application tests were not run for this docs-only audit.

## Working Defaults

- Verify live code before relying on older docs.
- Use focused tests for behavior changes and markdown diff checks for docs-only changes.
- Keep `docs/FEATURES.md` and the closest runbook updated when user-facing workflow behavior changes.
- Treat `docs/performance/**` and `docs/security/**` as dated evidence.
- Treat `docs/superpowers/**` as historical plan/spec context unless a current issue explicitly asks to resume that planning track.
