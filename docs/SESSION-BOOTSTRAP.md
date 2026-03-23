# Tenanto Session Bootstrap

Run this at the start of every work session when you need the current MCP status, project skills, and application baseline.

## 1. MCP Connection Check

Verify the available Laravel-side MCP commands before trying to start them:

```bash
php artisan list --raw | rg '^(boost:mcp|mcp:start)$'
```

Verified on 2026-03-19 in this repository:

- `php artisan boost:mcp` is not registered
- `php artisan mcp:start tenanto` is not registered
- repository-local `.mcp.json` currently defines only `herd`

If both commands become available in a future environment, use this startup order:

```bash
php artisan boost:mcp
php artisan mcp:start tenanto
```

Then verify the Boost MCP connection by using the Boost `search-docs` tool with:

- `livewire component lifecycle`

If either command is missing or fails, run these fallback health checks instead:

```bash
php artisan about
php artisan migrate:status
```

Verified fallback state on 2026-03-19:

- `php artisan about` succeeds
- the app boots cleanly on Laravel `12.54.1`, Filament `5.3.5`, Livewire `4.2.1`, PHP `8.5.4`
- `php artisan migrate:status` reports all checked-in migrations as `Ran`

## 2. Skill Activation Defaults

Use the installed skill names below rather than the older shorthand aliases:

- `superpowers/using-superpowers` first so the current session loads the required skill workflow
- `laravel-11-12-app-guidelines` instead of `tenanto-laravel-stack`
- `pest-testing` whenever writing or debugging tests
- `tailwindcss-development` instead of `tailwind-patterns`
- `filament` for Filament resources, pages, actions, and widgets
- `architecture` for service boundaries, class responsibilities, and data flow
- `laravel-security-audit` instead of `vulnerability-scanner` when work touches auth, authorization, impersonation, or tenant-scoped data

## 3. Application Baseline Checks

Run these before making behavioral changes:

```bash
php artisan route:list
php artisan filament:cache-components
php artisan test --stop-on-failure
```

Notes:

- `php artisan route:list --compact` is not supported in this app
- `php artisan filament:cache` does not exist; use `php artisan filament:cache-components`

Verified baseline on 2026-03-19:

- `php artisan route:list` succeeds
- `php artisan filament:cache-components` succeeds
- `php artisan test --stop-on-failure` reaches:
  - `316 passed, 2 failed, 405 pending (1852 assertions)`
  - first failures: `Tests\\Feature\\Admin\\MeterReadingsResourceTest` (parse error in `RejectMeterReadingAction`) and `Tests\\Feature\\Admin\\MetersResourceTest` (missing `Usage Chart` text)
- `phpunit.xml` expects `DB_DATABASE=:memory:`, but with cached config direct `php artisan test` runs still bound to `database/database.sqlite`
- do not run multiple `php artisan test` processes concurrently until test database isolation is fixed; use a single serial run, preferably after `php artisan config:clear` or via `composer test`

## 4. Tool Preferences

When Boost MCP is actually connected, prefer:

- Boost `database-query` for data inspection
- Boost `database-schema` before writing migrations or changing model persistence assumptions
- Boost `browser-logs` when a Livewire component does not render as expected
- Boost `search-docs` before relying on framework or package behavior you have not verified

In the current verified repository state, do not assume those Boost MCP tools are available until the `boost:mcp` command exists and a working MCP connection has been confirmed.
