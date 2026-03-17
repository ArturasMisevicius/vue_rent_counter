---
name: tenanto-laravel-stack
description: Use when starting a Tenanto session or changing Laravel, Filament, Livewire, Blade, routes, or tests in this repository.
allowed-tools: Read, Write, Edit, Glob, Grep, Bash
---

# Tenanto Laravel Stack

## Use This Skill When

- Starting a new Tenanto session and you need the current repository baseline quickly.
- Editing Laravel application logic under `app/`, `routes/`, `resources/`, `database/`, or `tests/`.
- Implementing Filament resources/pages/widgets or Livewire components.
- Making architecture or refactor changes that touch multiple modules.

## Project Facts

- Verified workspace snapshot date: `2026-03-17`
- Local CLI runtime is PHP `8.5.4`; Composer currently requires PHP `^8.2`
- Stack: Laravel `12`, Filament `5.3`, Livewire `4`, Tailwind CSS `4`, Pest `4`, PHPUnit `12`, Alpine.js `3`, Sanctum `4`
- Tenanto is a Filament-first, Livewire-assisted multi-tenant utility billing and property management application
- Role enum values are `SUPERADMIN`, `ADMIN`, `MANAGER`, and `TENANT`
- Current repository snapshot includes 17 Filament resources, 27 Livewire components, 84 tests, 1 remaining base controller, and 1 Filament panel provider
- Repo-local MCP currently defines only `herd` in `.mcp.json`
- The current app does not register `php artisan boost:mcp` or `php artisan mcp:start tenanto`

## Session Bootstrap

- Read `docs/SESSION-BOOTSTRAP.md` at session start when MCP status, skill activation, or baseline verification matters.
- If `boost:mcp` and `mcp:start` are unavailable, use the fallback health checks instead of pretending MCP is connected.
- Use these session-default skills alongside this one when the work matches them:
  - `pest-testing`
  - `tailwind-patterns`
  - `architecture`
  - `vulnerability-scanner`

## Required Implementation Rules

- Follow existing conventions in sibling files before introducing new patterns.
- Prefer Filament request classes under `app/Filament/Requests`; do not create `app/Http/Requests`.
- Keep reusable write logic in `app/Filament/Actions` and shared read/support logic in `app/Filament/Support`.
- Prefer Eloquent models, relationships, and scopes over raw SQL and avoid `DB::` unless unavoidable.
- Keep tenant boundaries explicit when accessing data.
- Use strict typing and explicit return types in new and touched PHP files.
- Do not add ad hoc public debug PHP entrypoints.
- Run focused tests for changed behavior.

## Working Sequence

1. Check `docs/PROJECT-CONTEXT.md` if there is any uncertainty about the current repo shape.
2. Check `docs/SESSION-BOOTSTRAP.md` if you need the verified MCP and baseline startup flow.
3. Identify the feature path: Filament action/support/resource/page, Livewire component, model, policy, request, or view.
4. Reuse existing abstractions if available: `App\Filament\Actions`, `App\Filament\Support`, policies, model scopes, schema classes, and table classes.
5. Keep model queries scope-first and UI layers thin.
6. Add or update Pest tests for happy path plus authorization or failure path when behavior changes.
7. Run the smallest relevant test slice first.
8. Run `vendor/bin/pint --dirty` before finalizing.

## Quick File Map

- `app/Filament/Resources/*`: Filament resources and their pages, schemas, and tables
- `app/Filament/Pages/*`: custom Filament pages
- `app/Filament/Widgets/*`: dashboard widgets
- `app/Filament/Requests/*`: request validation foundation
- `app/Filament/Actions/*`: workflow and write-side actions
- `app/Filament/Support/*`: presenters, query objects, report builders, shell helpers
- `app/Livewire/*` and `resources/views/livewire/*`: Livewire components and views
- `app/Models/*`: Eloquent domain layer
- `app/Policies/*`: authorization rules
- `app/Providers/Filament/AdminPanelProvider.php`: current panel registration entry point
- `tests/*`: regression coverage

## Session Defaults

- Treat `docs/superpowers/` as historical planning context, not the source of truth for current counts or directory layout.
- When a pasted brief conflicts with the workspace, prefer the verified repository snapshot.
- If you need repo-local MCP, assume only `herd` is configured unless you verify otherwise.
- Do not promise Boost MCP tools like `search-docs`, `database-query`, or `browser-logs` unless MCP startup has been verified in the current environment.

## Completion Checklist

- [ ] Change follows existing project patterns.
- [ ] Tenant and authorization implications reviewed.
- [ ] Filament foundation placement respected.
- [ ] Pest tests added or updated when behavior changed.
- [ ] Focused verification ran.
- [ ] Formatting or linting ran for touched files.
