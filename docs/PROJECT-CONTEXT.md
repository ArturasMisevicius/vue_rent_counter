# Tenanto Project Context

This document is the verified repository snapshot for Tenanto as of 2026-03-17. When a pasted session brief or older plan document disagrees with the current workspace, prefer this file plus the live codebase.

## Stack

- Local CLI runtime: PHP `8.5.4`
- Composer requirement: PHP `^8.2`
- Laravel `12`
- Filament `5.3`
- Livewire `4`
- Tailwind CSS `4`
- Pest `4`
- PHPUnit `12`
- Alpine.js `3`
- Laravel Sanctum `4`

## Product Summary

Tenanto is a multi-tenant utility billing and property management SaaS. The current codebase is Filament-first for administration and uses Livewire page/components for auth, shell behavior, public-site endpoints, preferences, and tenant self-service flows.

## Role Model

The current role enum is defined in `app/Enums/UserRole.php`:

- `SUPERADMIN`
- `ADMIN`
- `MANAGER`
- `TENANT`

The exact behavior for each role should be verified from policies, Filament resource accessors, Livewire entry points, and route middleware before making authorization changes.

## Current Workspace Shape

Verified from the live repository on 2026-03-17:

- 1 controller file remains under `app/Http/Controllers` and it is the base controller only
- 17 Filament resource classes under `app/Filament/Resources`
- 27 Livewire component classes under `app/Livewire`
- 84 PHP test files under `tests`
- 1 Filament panel provider at `app/Providers/Filament/AdminPanelProvider.php`
- No extra public PHP debug entrypoints; only `public/index.php` exists
- No standalone service worker is shipped; do not add `public/sw.js` unless a maintained PWA feature is actually introduced

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
- Keep authorization explicit through policies, Filament authorization hooks, and route or component boundaries
- Keep new and touched PHP files on strict typing and explicit return types where possible, even if some older files still need modernization

## Key Code Areas

- Filament resources/pages/widgets: `app/Filament`
- Livewire pages/components: `app/Livewire`
- Policies: `app/Policies`
- Core domain models: `app/Models`
- Filament panel registration: `app/Providers/Filament`
- Public and shell views: `resources/views`

## Repo-Local MCP

The repository-local `.mcp.json` currently defines only one MCP server:

- `herd`

If Laravel-specific MCP servers are available in your editor or Codex session, they are coming from user-global configuration rather than this repository file. Verify before relying on them in documentation or instructions.

The current application also does not register `boost:mcp` or `mcp:start` Artisan namespaces. See `docs/SESSION-BOOTSTRAP.md` for the verified conditional startup flow.

## Working Defaults

- Follow sibling-file patterns before introducing a new abstraction
- Use Livewire 4 patterns for reactive state and Filament 5 patterns for admin UX
- Run focused Pest tests for changed behavior and `vendor/bin/pint --dirty` before closeout
- Treat historical planning docs under `docs/superpowers/` as useful context, not as the source of truth for current repository counts or structure
