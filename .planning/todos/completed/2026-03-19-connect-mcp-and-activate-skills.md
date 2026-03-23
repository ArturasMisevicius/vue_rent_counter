completed: 2026-03-19
---
created: 2026-03-19T14:34:17.054Z
title: Connect MCP and activate skills
area: tooling
files:
  - docs/SESSION-BOOTSTRAP.md
  - .mcp.json
  - composer.json
---

## Problem

Each work session needs a consistent bootstrap so MCP tooling, Laravel-specific skills, and the application baseline are verified before implementation starts. The requested workflow expects both `php artisan boost:mcp` and `php artisan mcp:start tenanto` to start successfully, confirms Boost connectivity via a docs search for `livewire component lifecycle`, and falls back to `php artisan migrate:status` plus `php artisan about` if either server fails. It also requires recording the starting test baseline after `php artisan route:list`, `php artisan filament:cache`, and `php artisan test --stop-on-failure`.

The current repository context already warns that repo-local MCP configuration may not expose both Laravel MCP servers by default, and the requested session skills include names that may need mapping to the actual installed skill set (`tenanto-laravel-stack`, `tailwind-patterns`, and `vulnerability-scanner` are not obvious one-to-one matches in the current skill registry). This bootstrap task should resolve that gap before any feature work begins.

## Solution

Create or update a repeatable session-start procedure that:

1. Starts `php artisan boost:mcp` and confirms it responds.
2. Starts `php artisan mcp:start tenanto` and confirms it boots.
3. Verifies Boost connectivity with a docs search for `livewire component lifecycle`.
4. If either MCP server fails, runs `php artisan migrate:status` and `php artisan about` to capture bootstrap or database issues.
5. Maps the requested session skills to the actual available project skills, or adds missing aliases/documentation if needed.
6. Runs `php artisan route:list`, `php artisan filament:cache`, and `php artisan test --stop-on-failure`, then records the initial pass count for the session.
7. Documents that Boost database and browser inspection tools should be the default path for schema checks, data inspection, and Livewire render debugging during future work.

Capture the final, verified workflow in `docs/SESSION-BOOTSTRAP.md` so later sessions can follow one authoritative bootstrap path.
