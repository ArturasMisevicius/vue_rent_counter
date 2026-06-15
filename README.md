# Tenanto

> **AI agent usage:** Read `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, `docs/AI-AGENT-DOCS.md`, and `docs/FEATURES.md` before changing behavior. Treat older plans as context only; live code, routes, policies, tests, migrations, and language files are the source of truth.

Tenanto is a multi-tenant utility billing and property management application. It is Filament-first for authenticated workspace operations and uses Livewire for public auth, shell behavior, tenant portal routes, profile flows, downloads, preferences, and security endpoints.

The current product covers platform operations, organization operations, restricted manager workspaces, tenant self-service, invoice-driven meter readings, billing review, payments, extra charges, tenant documents, tenant KYC, rental contracts, leads, projects, localization, notifications, reports, and operational release checks.

## Current Stack

Verified from `composer.json`, `package.json`, `composer show`, `php artisan about`, and `php artisan route:list` on 2026-06-15:

| Area | Current value |
| --- | --- |
| PHP requirement | `^8.3` with Composer platform PHP `8.3.24` |
| Local CLI seen in this checkout | PHP `8.5.7` |
| Laravel | `13.15.0` |
| Filament | `5.6.7` |
| Livewire | `4.3.1` |
| Pest | `4.7.3` |
| PHPUnit | `12.5.29` |
| Tailwind CSS | `4.2.4` |
| Vite | `8.0.10` |
| Alpine.js | via Filament/Livewire frontend stack |
| Database default | SQLite at `database/database.sqlite` |
| Queue/cache/session defaults | database drivers |
| Mail default | log driver |
| Localized app locales | `en`, `es`, `lt`, `ru` |

Current inventory from the same audit:

- 230 registered routes.
- 41 Filament resources.
- 27 Filament pages.
- 51 Livewire PHP classes.
- 79 top-level Eloquent models.
- 207 Filament action classes.
- 117 migration files.
- 222 test files.
- 363 commits in the current history.

## Quick Start

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install
npm run build
```

The repository setup script runs the same broad sequence:

```bash
composer run setup
```

Use this local loop for normal development:

```bash
composer run dev
```

It starts the Laravel server, queue listener, log tailing, and Vite dev server through `concurrently`.

## Daily Commands

| Command | Purpose |
| --- | --- |
| `php artisan about` | Confirm installed Laravel, Filament, Livewire, driver, and locale state. |
| `php artisan route:list` | Inspect the real route surface before changing navigation or docs links. |
| `php artisan migrate:status` | Check local database drift. On 2026-06-15 the tenant KYC migration was present in code and pending in the local SQLite database. |
| `php artisan migrate` | Apply pending migrations after pulling feature work. |
| `php artisan filament:cache-components` | Rebuild Filament component cache. |
| `php artisan test --compact` | Run the test suite with compact output. |
| `composer test` | Clear config and run the Laravel test command. |
| `vendor/bin/pint --dirty` | Format changed PHP files. |
| `npm run build` | Build frontend assets. |
| `php artisan billing:open-reading-invoice-cycle` | Open reading-request invoices and notify tenants. |
| `php artisan billing:mark-overdue-invoices` | Mark unpaid invoices overdue. |
| `php artisan billing:send-payment-reminders` | Queue overdue payment reminders. |
| `php artisan kyc:maintain` | Expire KYC documents and send KYC reminders. |
| `php artisan rental-contracts:maintain` | Expire rental contracts and send contract reminders. |
| `php artisan ops:backup-restore-readiness` | Check database and backup/restore staging readiness. |
| `php artisan ops:release-readiness` | Collect release-readiness evidence. |
| `composer guard:phase1` | Run public-surface and security guardrails used by CI. |

## Product Surfaces

### Public And Auth

- Localized homepage at `/`.
- Login, registration, password reset, invitation acceptance, onboarding, logout, profile, avatar, and locale endpoints.
- CSP report intake with request validation and throttling.
- Route-based dashboard redirect that sends each role to the correct workspace.

### Superadmin

- Platform dashboard, organization dashboard, organization list/detail, subscriptions, users, organization users, projects, tasks, time entries, comments, attachments, tags, platform notifications, languages, translation management, audit logs, security violations, integration health, system configuration, billing inspection, and exports.
- Organization operations include suspension/reinstatement, plan changes, ownership transfers, announcements, GDPR-style exports, limit overrides, feature flags, invoice write-offs, impersonation, security review, and integration snapshots.

### Admin

- Organization-scoped operational workspace for buildings, properties, tenants, assignments, meters, readings, service configurations, utility services, providers, tariffs, invoices, invoice items, payments, reminders, email logs, extra charges, rental contracts, tenant documents, tenant KYC, reports, settings, manager/team administration, leads, help, and notifications.

### Manager

- Organization-scoped workspace controlled by manager memberships and the permission matrix.
- Presets include `full_manager`, `billing_manager`, `property_manager`, and `read_only_manager`.
- Managers are intentionally blocked from platform control-plane, subscription/team escalation, admin-only settings, and self-permission escalation unless a backend permission path explicitly allows an action.

### Tenant

- Self-service portal routes under `/tenant`.
- Home summary, invoice history and downloads, property details, rental contract downloads, meter reading submission, tenant documents, KYC verification, profile, help, and tenant-safe attachment access.
- Tenant pages use top/navigation shell patterns and tenant presenters; Blade views should remain display-only.

## Core Workflows

### Billing And Reading Cycle

The current billing workflow is invoice-driven:

1. Admin or authorized manager opens a reading cycle.
2. Tenanto creates `reading_request` draft invoices for eligible tenant/property assignments.
3. Tenants submit readings only in the context of an open request invoice and billing period.
4. Each reading is scoped to tenant, property, meter, billing period, and invoice, with previous/current value, consumption, lifecycle status, and version history.
5. Tenants may edit submitted or rejected readings before the deadline and before approval; approved, corrected, and voided readings are locked.
6. Billing reviewers approve, reject, correct, void, or request resubmission with audit/version history.
7. Only approved or corrected readings prepare invoice line items.
8. A reviewer finalizes and sends the invoice.
9. Payment proof, payment confirmation/rejection, reminders, and overdue handling run through action classes and notifications.

See [docs/operations/billing-reading-invoice-workflow.md](docs/operations/billing-reading-invoice-workflow.md).

### Property And Tenant Lifecycle

- Buildings and properties are organization-scoped.
- Tenants are users with tenant role, organization scope, portal state, and property assignments.
- Assignments preserve history and feed billing eligibility.
- Move-out actions schedule the move-out, record final readings, generate final invoice work, update occupancy, close/renew related contracts, and control post-move-out portal access.

### Documents, KYC, And Contracts

- Tenant documents are private by default and downloaded through authorized actions.
- Tenant-visible documents are exposed in the tenant portal through `TenantDocumentIndexQuery` and `TenantDocumentPresenter`.
- Tenant KYC has dedicated profiles, documents, admin review actions, tenant upload/download routes, expiry reminders, and settings gates.
- Rental contracts include lifecycle actions, file upload/download, expiry maintenance, and tenant visibility rules.

### Leads, Projects, And Collaboration

- Listing leads, lead contacts, lead sources, outreach activities/templates, import batches, CSV import/export, duplicate detection, assignment, follow-up, conversion, and reports are implemented under Filament resources/pages and lead actions.
- Projects, tasks, assignments, time entries, comments, reactions, attachments, tags, costs, project users, alerts, exports, and project lifecycle policies support platform and organization operations.

## Architecture Guardrails

- Prefer Eloquent models, relationships, scopes, and presenters over raw SQL.
- Request validation belongs in `app/Http/Requests`.
- New reusable domain/action/support code should prefer `app/Filament/Actions` and `app/Filament/Support`.
- `app/Actions/Billing` is an existing billing action namespace; do not treat its presence as permission to add unrelated foundation code there.
- Policies, middleware, and action authorization must back UI visibility.
- Tenant and organization isolation are backend requirements, not UI preferences.
- Tenant portal data should flow through actions, presenters, and query classes; Blade should not query.
- Do not reintroduce public debug PHP entrypoints or a service worker unless a maintained feature requires it.

## Documentation Map

Start here:

- Current feature guide: [docs/FEATURES.md](docs/FEATURES.md)
- Current project context: [docs/PROJECT-CONTEXT.md](docs/PROJECT-CONTEXT.md)
- AI agent docs contract: [docs/AI-AGENT-DOCS.md](docs/AI-AGENT-DOCS.md)
- Session bootstrap: [docs/SESSION-BOOTSTRAP.md](docs/SESSION-BOOTSTRAP.md)
- Skills and MCP inventory: [docs/SKILLS-MCP-INVENTORY.md](docs/SKILLS-MCP-INVENTORY.md)
- Permission matrix: [docs/PERMISSION-MATRIX.md](docs/PERMISSION-MATRIX.md)
- Changelog reconstructed from git: [CHANGELOG.md](CHANGELOG.md)

Operations:

- Billing workflow: [docs/operations/billing-reading-invoice-workflow.md](docs/operations/billing-reading-invoice-workflow.md)
- Service configuration guide: [docs/operations/service-configuration-guide.md](docs/operations/service-configuration-guide.md)
- Backup/restore readiness: [docs/operations/backup-restore.md](docs/operations/backup-restore.md)
- Release readiness: [docs/operations/release-readiness.md](docs/operations/release-readiness.md)
- Phase 1 guardrails branch protection: [docs/operations/phase-1-guardrails-branch-protection.md](docs/operations/phase-1-guardrails-branch-protection.md)

Historical:

- `docs/superpowers/**` contains planning, design, and execution history. It is useful context, not proof of current implementation.
- `docs/performance/**` and `docs/security/**` are dated audit evidence. Re-run checks before making current claims.

## MCP And Agent Tooling

Repo-local `.mcp.json` defines:

- `herd`
- `21st-dev-magic`
- `context7`
- `playwright`

`21st-dev-magic` requires `TWENTY_FIRST_DEV_API_KEY` in the host process. `boost:mcp` and `mcp:start` Artisan commands are not currently registered in this checkout unless a user-global/editor MCP layer provides them.

## Release Hygiene

- Install git hooks with `scripts/install-git-hooks.sh` if they are not already active.
- The changelog hook writes semantic staged summaries through `scripts/update_changelog.php`; this README/changelog refresh is a manual reconstruction requested by the user and is intentionally broader than one staged diff.
- Empty/template commit messages are filled by `.githooks/prepare-commit-msg` through `scripts/generate_commit_message.php`; the generated text describes the functional intent, added/removed/updated behavior, and diff size without listing file names.
- For code changes, update the closest docs in the same turn and run focused tests plus `vendor/bin/pint --dirty`.
- For docs-only changes, run markdown diff checks:

```bash
git diff --check -- $(rg --files -g '*.md' -g '!vendor/**' -g '!node_modules/**' -g '!storage/**' -g '!public/build/**')
```
