# Tenanto Project Context

> **AI agent usage:** Read `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, and `docs/AI-AGENT-DOCS.md` before acting on this file. Treat examples as context; verify current code, routes, schema, translations, and tests before changing behavior.

This document is the verified repository snapshot for Tenanto as of 2026-05-04. When a pasted session brief or older plan document disagrees with the current workspace, prefer this file plus the live codebase.

## Stack

- Local CLI runtime: PHP `8.5.4`
- Composer requirement: PHP `^8.3`
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

## Product Summary

Tenanto is a multi-tenant utility billing and property management SaaS. The current codebase is Filament-first for the authenticated workspace and uses Livewire pages/components for auth, shell behavior, public-site endpoints, preferences, and tenant self-service flows.

The app exists to support utility/property operations across four audiences:

- Platform operators manage organizations, subscriptions, languages, audit/security surfaces, and global health.
- Organization admins manage buildings, properties, tenants, meters, readings, tariffs, providers, invoices, reporting, and settings.
- Managers work inside an organization with restricted permissions and reduced access to admin-only settings.
- Tenants use a self-service portal for property context, meter readings, invoice review/download, profile, language, and KYC maintenance.

## Role Model

The current role enum is defined in `app/Enums/UserRole.php`:

- `SUPERADMIN`
- `ADMIN`
- `MANAGER`
- `TENANT`

The exact behavior for each role should be verified from policies, Filament resource accessors, Livewire entry points, and route middleware before making authorization changes.

## Current Workspace Shape

Verified from the live repository on 2026-03-28:

- 1 controller file remains under `app/Http/Controllers` and it is the base controller only
- 33 Filament resource classes under `app/Filament/Resources`
- 41 PHP files under `app/Livewire`
- 164 `*Test.php` files under `tests` and 168 total PHP files in the `tests/` tree
- 1 Filament panel provider at `app/Providers/Filament/AppPanelProvider.php`
- No extra public PHP debug entrypoints; only `public/index.php` exists
- No standalone service worker is shipped; do not add `public/sw.js` unless a maintained PWA feature is actually introduced
- Repo-local assistant and command surfaces exist under:
  - `.agent/`
  - `.claude/`
  - `.codex/`
  - `.gemini/`

These counts are useful for orientation, but they are a point-in-time snapshot and may change as the codebase evolves.

## Architecture Baseline

- Prefer Eloquent-first data access and model scopes over raw SQL
- Keep request validation in `app/Http/Requests` and keep actions plus shared support logic in the Filament foundation tree:
  - `app/Http/Requests`
  - `app/Filament/Actions`
  - `app/Filament/Support`
- Do not create new classes in:
  - `app/Actions`
  - `app/Support`
- Do not create or reintroduce a `FrameworkStudio` Filament page or `/app/framework-studio` route
- Keep authorization explicit through policies, Filament authorization hooks, and route or component boundaries
- Keep new and touched PHP files on strict typing and explicit return types where possible, even if some older files still need modernization
- Keep tenant portal data in presenters, actions, policies, and Livewire component state; Blade views should stay display-only.
- Keep tenant UX task-focused: top navigation, direct reading/invoice/property/profile actions, and no admin-style left navigation for tenant users.

## Key Code Areas

- Filament resources/pages/widgets: `app/Filament`
- Livewire pages/components: `app/Livewire`
- Tenant portal read models: `app/Filament/Support/Tenant/Portal`
- Tenant portal actions: `app/Filament/Actions/Tenant`
- Shell navigation source of truth: `config/tenanto.php` plus `app/Filament/Support/Shell/Navigation`
- Policies: `app/Policies`
- Core domain models: `app/Models`
- Filament panel registration: `app/Providers/Filament`
- Public and shell views: `resources/views`
- Repo-local command/workflow tooling: `.agent`, `.claude`, `.codex`, `.gemini`
- Skill and MCP inventory: `docs/SKILLS-MCP-INVENTORY.md`

## Tenant Portal UX Contract

Tenant role UI is intentionally different from admin/superadmin UI:

- Tenant pages must not show the left sidebar as the primary navigation.
- Tenant navigation belongs in the authenticated topbar and uses the configured `tenanto.shell.navigation.roles.tenant` items.
- Tenant pages should avoid duplicated mobile-only navigation unless the topbar cannot cover the flow.
- Tenant pages are self-service surfaces, not management dashboards: prioritize Submit Reading, Invoice History, Property Details, and Profile.
- Keep tenant views dense enough for repeated use but calmer than admin tables/resources.

## Repo-Local MCP

The repository-local `.mcp.json` currently defines these MCP servers:

- `herd`
- `21st-dev-magic`
- `context7`
- `playwright`

`21st-dev-magic` is intended for UI inspiration, SVG icon search, and Magic Generate design variants. It requires `TWENTY_FIRST_DEV_API_KEY` in the host agent/editor process. If Laravel-specific MCP servers are available in your editor or Codex session beyond the repo-local entries, they are coming from user-global configuration. Verify before relying on them in documentation or instructions.

`context7` is for current framework/package documentation. `playwright` is for browser-level UI inspection and regression checks.

See `docs/SKILLS-MCP-INVENTORY.md` for the complete project skill registry, MCP server contract, and verification commands.

The current application also does not register `boost:mcp` or `mcp:start` Artisan namespaces. See `docs/SESSION-BOOTSTRAP.md` for the verified conditional startup flow.

## Working Defaults

- Follow sibling-file patterns before introducing a new abstraction
- Use Livewire 4 patterns for reactive state and Filament 5 patterns for admin UX
- Run focused Pest tests for changed behavior and `vendor/bin/pint --dirty` before closeout
- Treat historical planning docs under `docs/superpowers/` as useful context, not as the source of truth for current repository counts or structure
