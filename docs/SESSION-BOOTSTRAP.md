# Tenanto Session Bootstrap

> **AI agent usage:** Read `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, `docs/AI-AGENT-DOCS.md`, `docs/PROJECT-CONTEXT.md`, and `docs/FEATURES.md` before acting on this file. Treat examples as context; verify current code, routes, schema, translations, and tests before changing behavior.

Run this at the start of any non-trivial Tenanto session.

## 1. Confirm The Checkout

```bash
pwd
git status --short --branch
```

Expected working directory for this project:

```text
/Users/andrejprus/Herd/tenanto
```

Do not revert unrelated user changes. If the tree is dirty, keep work scoped to the requested files and inspect touched files before editing them.

## 2. Current Application Baseline

Verified on 2026-06-15:

- `php artisan about` succeeds.
- Laravel `13.15.0`
- Filament `5.6.7`
- Livewire `4.3.1`
- PHP CLI `8.5.7`
- SQLite database driver.
- Database-backed cache, queue, and session drivers.
- Installed locales: `en`, `es`, `lt`, `ru`.
- `php artisan route:list` succeeds and reports 230 routes.
- `php artisan filament:cache-components` succeeds.
- `php artisan migrate:status` reports one local pending migration: `2026_06_15_000000_create_tenant_kyc_verification_tables.php`.

Baseline commands:

```bash
php artisan about
php artisan route:list
php artisan migrate:status
php artisan filament:cache-components
```

Run `php artisan migrate` only when the task requires the local database to include pending migrations.

## 3. MCP Connection Check

Verify available Laravel-side MCP commands before trying to start them:

```bash
php artisan list --raw | rg '^(boost:mcp|mcp:start)$'
```

Verified on 2026-06-15:

- `php artisan boost:mcp` is not registered in this checkout.
- `php artisan mcp:start tenanto` is not registered in this checkout.
- repository-local `.mcp.json` defines `herd`, `21st-dev-magic`, `context7`, and `playwright`.
- `21st-dev-magic` requires `TWENTY_FIRST_DEV_API_KEY` in the host agent/editor process.
- the current skill and MCP inventory lives in `docs/SKILLS-MCP-INVENTORY.md`.

If either command becomes available in a future environment, verify it before documenting it as project-local behavior.

## 4. Skill Activation Defaults

Use installed skill names rather than older aliases:

- `laravel-11-12-app-guidelines` for Laravel repository work, even though this checkout currently runs Laravel 13.
- `pest-testing` when writing or debugging tests.
- `tailwindcss-development` for Tailwind styling changes.
- `21st-dev-design` for UI redesign work that should use 21st.dev Magic MCP.
- `filament` for Filament resources, pages, actions, widgets, schemas, and tables.
- `livewire-development` for Livewire component work.
- `laravel-security-audit` for auth, authorization, impersonation, tenant isolation, sensitive document/KYC/download, or policy changes.
- `mcp-development` only for Laravel MCP tools/resources/prompts/server work.
- `documentation-and-adrs` for docs, public API docs, architecture records, and current-state documentation.
- `update-changelog-before-commit` when preparing a normal commit that should refresh `CHANGELOG.md` from the staged diff.

## 5. Application Checks By Task Type

Docs-only:

```bash
git diff --check -- $(rg --files -g '*.md' -g '!vendor/**' -g '!node_modules/**' -g '!storage/**' -g '!public/build/**')
```

Routes/navigation:

```bash
php artisan route:list
php artisan test tests/Feature/Shell/NavigationSourceOfTruthTest.php --compact
```

Filament resources/actions:

```bash
php artisan filament:cache-components
php artisan test tests/Feature/Admin/FilamentCrudCoverageInventoryTest.php --compact
```

Tenant portal:

```bash
php artisan test tests/Feature/Tenant --compact
php artisan test tests/Feature/Security/TenantPortalIsolationTest.php --compact
```

Billing/readings/invoices:

```bash
php artisan test tests/Feature/Billing --compact
php artisan test tests/Feature/Tenant/TenantReadingWorkflowConsistencyTest.php --compact
```

Security/isolation:

```bash
php artisan test tests/Feature/Security --compact
composer guard:phase1
```

Formatting after PHP edits:

```bash
vendor/bin/pint --dirty
```

## 6. Tenant UX Baseline

Before changing tenant screens, verify:

- Tenant navigation source: `config/tenanto.php` under `tenanto.shell.navigation.roles.tenant`
- Tenant topbar/sidebar shell: `app/Livewire/Shell/Topbar.php`, `app/Livewire/Shell/Sidebar.php`, `resources/views/livewire/shell/*.blade.php`
- Tenant Filament aliases: `app/Filament/Pages/Tenant*.php`
- Tenant Livewire portal components: `app/Livewire/Tenant`
- Tenant read models/actions: `app/Filament/Support/Tenant/Portal`, `app/Filament/Actions/Tenant`, `app/Filament/Actions/TenantDocuments`, and `app/Filament/Actions/TenantKyc`

Tenant UI should not become an admin-style workspace. Keep tenant navigation direct and self-service oriented.

## 7. High-Risk Domains

Re-check live code, policies, tests, and docs before touching:

- billing/readings/invoice approval/finalization/payment actions;
- manager permission matrix and `EffectivePermissionsResolver`;
- tenant document, KYC, attachment, invoice, and rental-contract downloads;
- impersonation;
- tenant move-out/occupancy/portal access;
- organization suspension, plan changes, feature flags, and limit overrides;
- localization and translation sync;
- public security routes and CSP/reporting behavior.
