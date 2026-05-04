# Tenanto

> **AI agent usage:** Read `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, and `docs/AI-AGENT-DOCS.md` before acting on this file. Treat examples as context; verify current code, routes, schema, translations, and tests before changing behavior.

Tenanto is a multi-tenant utility billing and property management application built on Laravel, Filament, and Livewire. The repository is Filament-first for the authenticated workspace and uses Livewire pages/components for public entrypoints, authentication, shared shell behavior, preferences, and tenant self-service flows.

## What The App Covers

- Platform-wide control plane for `SUPERADMIN`
- Organization workspace for `ADMIN`
- Limited organization workspace parity for `MANAGER`
- Tenant self-service portal for `TENANT`
- Utility billing, invoice generation, reminders, payments, meter readings, KYC, and localized tenant/admin experiences

## Verified Stack

Verified from the checked-in manifests and local CLI on 2026-03-28:

- PHP `8.5.4` locally, with Composer requiring PHP `^8.3`
- Laravel `13.2.0`
- Filament `5`
- Livewire `4`
- Tailwind CSS `4`
- Vite `7`
- Pest `4`
- PHPUnit `12`
- Alpine.js `3`
- Laravel Sanctum `4`
- SQLite as the default local database path

## Product Surfaces

### Superadmin

- Global platform dashboard
- Organizations, subscriptions, languages, audit logs, security violations, system configuration, translation management

### Admin

- Buildings, properties, tenants, meters, meter readings, providers, tariffs, invoices, KYC, reports, settings

### Manager

- Organization-scoped read/write access across most workspace resources
- Intentionally restricted from admin-only settings and platform control-plane surfaces

### Tenant

- Dashboard/home summary
- Invoice history and invoice downloads
- Property details
- Meter reading submission
- Shared profile and KYC maintenance

Tenant UX is intentionally self-service oriented. Tenant users should use top navigation rather than admin-style left navigation, and every tenant page should preserve a clear route to Home, Readings, Property, Invoices, and Profile.

## Local Setup

The app is configured for SQLite by default.

1. `composer install`
2. `cp .env.example .env`
3. `php artisan key:generate`
4. `touch database/database.sqlite`
5. `php artisan migrate`
6. `npm install`
7. `npm run build`

Or use the repository script:

```bash
composer run setup
```

Note: if you are using the default SQLite setup on a fresh checkout, create `database/database.sqlite` before running migrations manually.

## Daily Development

For the standard local loop:

```bash
composer run dev
```

That starts:

- `php artisan serve`
- `php artisan queue:listen --tries=1 --timeout=0`
- `php artisan pail --timeout=0`
- `npm run dev`

If you want to run services separately:

```bash
php artisan serve
php artisan queue:listen --tries=1 --timeout=0
npm run dev
```

## Testing And Quality

Common commands:

```bash
php artisan test --stop-on-failure
php artisan test --compact
vendor/bin/pint --dirty
composer test
```

Use focused Pest runs for changed behavior and `vendor/bin/pint --dirty` before committing. The repo-wide suite was green locally on 2026-03-28 at:

```bash
php artisan test --stop-on-failure
```

with `1005 passed`.

## Architecture Guardrails

- Prefer Eloquent models, relationships, and scopes over raw SQL
- Keep request validation in `app/Http/Requests`
- Keep reusable mutation logic in `app/Filament/Actions`
- Keep shared read-model and support logic in `app/Filament/Support`
- Do not create new shared foundation classes in `app/Actions` or `app/Support`
- Keep `app/Http/Controllers` minimal; the codebase is deliberately moving route handling toward Livewire-backed endpoints and pages
- Do not reintroduce `FrameworkStudio` or `/app/framework-studio`
- Keep `public/` minimal: no debug PHP entrypoints and no stray service worker without a maintained PWA feature
- Treat `docs/superpowers/` as execution history, not the source of truth for current repo state

## Important Paths

- Filament resources/pages/widgets: `app/Filament`
- Livewire pages/components: `app/Livewire`
- Core models: `app/Models`
- Policies: `app/Policies`
- Shared views/components: `resources/views`
- Route surface: `routes/web.php`
- Assistant/project instructions: `AGENTS.md`
- Current project context and role UX contracts: [docs/PROJECT-CONTEXT.md](docs/PROJECT-CONTEXT.md)

## MCP

The repo-local [`.mcp.json`](.mcp.json) configures these MCP servers:

- `herd`
- `21st-dev-magic`
- `context7`
- `playwright`

The `21st-dev-magic` server uses `npx @21st-dev/magic@latest` and expects a `TWENTY_FIRST_DEV_API_KEY` environment variable in the host agent/editor process. Generate the key from the 21st.dev Magic console and keep it out of repository files. This single server exposes all current 21st.dev Magic MCP capabilities:

- Inspiration Search
- SVG Icon Search
- Magic Generate

`context7` is available for current framework/package documentation lookups. `playwright` is available for browser-level UI inspection and regression checks.

If Laravel-specific MCP servers are available in your editor or Codex session, they are coming from user-global configuration rather than this repository file.

## Additional Docs

- Session bootstrap: [docs/SESSION-BOOTSTRAP.md](docs/SESSION-BOOTSTRAP.md)
- Skills and MCP inventory: [docs/SKILLS-MCP-INVENTORY.md](docs/SKILLS-MCP-INVENTORY.md)
- Delivery plans/specs index: [docs/superpowers/README.md](docs/superpowers/README.md)
- AI assistant and repo instructions: [AGENTS.md](AGENTS.md)
