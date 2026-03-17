# Tenanto Session Bootstrap

Run this at the start of every work session when you need the current MCP status, project skills, and application baseline.

## 1. MCP Connection Check

Verify the available Laravel-side MCP commands before trying to start them:

```bash
php artisan list --raw | rg '^(boost:mcp|mcp:start)$'
```

Verified on 2026-03-17 in this repository:

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

Verified fallback state on 2026-03-17:

- `php artisan about` succeeds
- the app boots cleanly on Laravel `12.54.1`, Filament `5.3.5`, Livewire `4.2.1`, PHP `8.5.4`
- `php artisan migrate:status` shows pending migrations:
  - `2026_03_17_121700_create_invoice_payments_table`
  - `2026_03_17_121800_create_invoice_email_logs_table`
  - `2026_03_17_121900_create_invoice_reminder_logs_table`

## 2. Skill Activation Defaults

Activate these skills for each session as needed:

- `tenanto-laravel-stack` for all Laravel, Filament, and Livewire implementation work in this repo
- `pest-testing` whenever writing or modifying tests
- `tailwind-patterns` whenever modifying Blade templates or Livewire views
- `architecture` when deciding service boundaries, class responsibilities, or data flow
- `vulnerability-scanner` before finalizing work that touches authentication, authorization, impersonation, or tenant data access

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

Verified baseline on 2026-03-17:

- `php artisan route:list` succeeds
- `php artisan filament:cache-components` succeeds
- `php artisan test --stop-on-failure` passes with:
  - `301 passed (5883 assertions)`

## 4. Tool Preferences

When Boost MCP is actually connected, prefer:

- Boost `database-query` for data inspection
- Boost `database-schema` before writing migrations or changing model persistence assumptions
- Boost `browser-logs` when a Livewire component does not render as expected
- Boost `search-docs` before relying on framework or package behavior you have not verified

In the current verified repository state, do not assume those Boost MCP tools are available until the `boost:mcp` command exists and a working MCP connection has been confirmed.
