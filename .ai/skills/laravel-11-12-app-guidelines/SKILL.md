---
name: laravel-11-12-app-guidelines
description: Guidelines and workflow for working on Laravel 11 or Laravel 12 applications across common stacks (API-only or full-stack), including optional Docker Compose/Sail, Inertia + React, Livewire, Vue, Blade, Tailwind v4, Fortify, Wayfinder, PHPUnit, Pint, and Laravel Boost MCP tools. Use when implementing features, fixing bugs, or making UI/backend changes while following project-specific instructions (AGENTS.md, docs/).
author: Official
context: fork
---

# Laravel 11/12 App Guidelines

## Overview

Apply a consistent workflow for Laravel 11/12 apps with optional frontend stacks, Dockerized commands, and Laravel Boost tooling.

## Quick Start

- Read repository instructions first: `AGENTS.md`. If `docs/` exists, read `docs/README.md` and relevant module docs before decisions.
- Detect the stack and command locations; do not guess.
- Use Laravel Boost `search-docs` for Laravel ecosystem guidance; use Context7 only if Boost docs are unavailable.
- Follow repo conventions for naming, UI language, docs-first policies, and existing component patterns.
- In this repository, request validation belongs in `app/Http/Requests`, while shared action and support classes belong under `app/Filament`. Do not create `app/Actions` or `app/Support`.

## Stack Detection

- Check `composer.json`, `package.json`, `docker-compose.*`, and `config/*` to confirm:
  - Docker Compose/Sail vs host commands
  - API-only vs full-stack
  - Frontend framework (Inertia/React, Livewire, Vue, Blade)
  - Auth (Fortify, Sanctum, Passport, custom)

## Laravel 11/12 Core Conventions

- Use the Laravel 11/12 structure: configure middleware, exceptions, and routes in `bootstrap/app.php`; service providers in `bootstrap/providers.php`; console configuration in `routes/console.php`.
- Use Eloquent models and relationships first; avoid raw queries and `DB::` unless truly necessary.
- Create Form Request classes for validation instead of inline validation, and place them under `app/Http/Requests`.
- Use backed enums for fixed value sets and validate them with `Rule::enum(...)`.
- For UI-facing enums in this repository, prefer translated enum labels from `lang/*/enums.php` and dynamic option lists from `EnumClass::options()`.
- Prefer named routes and `route()` for URL generation.
- When altering columns, include all existing attributes in the migration to avoid dropping them.
- Ask before destructive database operations (e.g., reset/rollback/fresh).

## Project Foundation Placement

- Place new request classes in `app/Http/Requests` with `App\\Http\\Requests\\...` namespaces.
- Place new action classes in `app/Filament/Actions` with `App\\Filament\\Actions\\...` namespaces.
- Place new support classes in `app/Filament/Support` with `App\\Filament\\Support\\...` namespaces.
- Treat the legacy directories `app/Actions` and `app/Support` as forbidden in this repository.
- If you touch older code, prefer migrating request imports into `app/Http/Requests` and keeping action plus support imports in the Filament foundation tree rather than extending older structures.

## API-Only Mode

- Use `routes/api.php`; avoid Inertia and frontend assumptions.
- Prefer API Resources and versioning if the repo already uses them.
- Follow the repo's auth stack (Sanctum/Passport/custom) and response format conventions.
- Do not require Vite/Tailwind/NPM unless the repo already includes them.

## Inertia + React + Wayfinder (if present)

- Use `Inertia::render()` for server-side routing; place pages under `resources/js/Pages` unless the repo says otherwise.
- Use `<Form>` or `useForm` for Inertia forms; add skeleton/empty states for deferred props.
- Use `<Link>` or `router.visit()` for navigation.
- Use Wayfinder named imports for tree-shaking; avoid default imports; regenerate routes after changes if required.

## Livewire / Vue / Blade (if present)

- Follow existing component patterns and conventions; do not mix frameworks unless the repo already does.
- Keep UI strings in the repo's expected language.
- For Livewire 4 in this repository:
  - use `#[Computed]` for derived state
  - use `#[Validate]` for interactive property validation
  - use `#[Locked]` for client-immutable public properties like route IDs, tokens, and context flags
  - keep `render()` thin and move repeated query work into computed methods or presenter/query classes
  - avoid large public collections or full models in component state
  - add `wire:key` in dynamic loops and use `wire:init` when data can load after first paint
  - use `@island` for isolated update regions inside larger Livewire surfaces
  - use `@placeholder` only when an island is lazy-loaded and the loading state matters to UX
  - use `wire:show` for DOM-preserving visibility toggles and `wire:transition` for Livewire-controlled conditionals
  - use `wire:current`, `@persist`, and `wire:navigate:scroll` only when navigation persistence clearly improves the experience

## Models, Livewire, and Filament Performance

- Extract repeated Eloquent filters into small, composable scopes before repeating them in Livewire or Filament
- Use eager loading, constrained eager loading, `withCount()`, and `withExists()` when UI surfaces need related data
- Prefer `exists()` over `count() > 0` for boolean checks
- Select only the columns needed for summary/workspace queries
- Prefer explicit `$fillable` on mass-assigned models; avoid `Model::unguard()` and broad guarded patterns
- Keep models thin; move workflows and side effects into actions, support classes, observers, and policies
- For model-review requests, return the full refactored model first, then summarize practical improvements and any index or architecture follow-ups
- In Filament 5+ resources/pages/widgets/tables/forms:
  - preload relationships used by table columns, filters, infolists, and actions
  - keep heavy logic in Filament actions/support classes
  - extract reusable schema fragments when resource files become too large
  - avoid expensive formatting callbacks that depend on lazy-loaded relations

## Tailwind CSS v4 (if present)

- Use `@import "tailwindcss";` and `@theme` for tokens.
- Avoid deprecated utilities; use replacements (e.g., `shrink-*`, `grow-*`, `text-ellipsis`).
- Use `gap-*` for spacing between items; follow existing dark mode conventions if present.

## Testing and Formatting

- Use PHPUnit; generate tests with `php artisan make:test --phpunit` and prefer feature tests.
- Run the minimal relevant tests (`php artisan test <file>` or `--filter=`).
- Run `vendor/bin/pint --dirty` before finalizing code changes.
- After minimal tests pass, offer to run the full test suite.

## Laravel Boost MCP Tools (when available)

- `search-docs` before changing behavior or using framework features.
- `list-artisan-commands` to confirm Artisan options.
- `list-routes` to inspect routing changes.
- `tinker` for PHP debugging and `database-query` for read-only DB checks.
- `browser-logs` to inspect frontend errors.
- `get-absolute-url` for sharing project URLs.
- See `references/boost-tools.md` for query patterns and tool usage tips.

## Output Expectations

- Preserve existing architecture, structure, and dependencies unless the user explicitly requests changes.
- Reuse existing components and follow local patterns.
- Ask concise clarifying questions when repo guidance is missing or ambiguous.
