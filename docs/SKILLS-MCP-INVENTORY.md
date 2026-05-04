# Tenanto Skills And MCP Inventory

This document records the project-local agent skills and MCP server contract for Tenanto as of 2026-05-04. Use it when starting a new assistant session, adding an MCP server, or deciding which skill should guide a task.

## Current Skill Inventory

The workspace currently contains 337 local `SKILL.md` files across these roots:

| Root | Purpose |
| --- | --- |
| `.agents/skills` | Codex-visible project skills for this workspace. |
| `.agent/skills` | Broader local assistant skill library, including Tenanto-specific legacy skills. |
| `.ai/skills` | PHP, Laravel, Filament, testing, and analysis skills used by generated assistant instructions. |
| `.claude/skills` | Claude-facing workspace skill subset. |
| `.codex/skills` | GSD workflow skills. |

The Boost skill registry in `boost.json` is intentionally narrower than the raw file count. It exposes the skills that are relevant to this Laravel, Filament, Livewire, Tailwind, tenant-management project and keeps unrelated local skills out of default project instructions.

## Project-Active Skill Groups

Use these skills first for Tenanto work:

| Area | Skills |
| --- | --- |
| Core Laravel implementation | `laravel-11-12-app-guidelines`, `laravel-best-practices`, `laravel-models`, `laravel-multi-tenancy`, `eloquent-best-practices`, `spatie-laravel-php-standards` |
| Filament and Livewire | `filament`, `livewire-development`, `fluxui-development` |
| Tenant domain | `tenanto-laravel-stack`, `tenanto-tenant-security`, `tenanto-billing-reporting`, `tenanto-lang-migration` |
| UI and design | `21st-dev-design`, `frontend-design`, `tailwindcss-development`, `tailwind-patterns`, `web-design-guidelines`, `mobile-design` |
| Testing and quality | `pest-testing`, `laravel-testing`, `testing-patterns`, `tdd-workflow`, `laravel-quality`, `phpstan-fixer`, `phpcs-check-fix` |
| Architecture and safety | `architecture`, `database-design`, `laravel-security-audit`, `security-best-practices`, `complexity-guardrails` |
| MCP/server work | `mcp-development`, `mcp-builder`, `php-mcp-server-generator` |
| Documentation and release notes | `doc`, `analyze-document`, `update-changelog-before-commit` |

When a task names a skill directly, use that skill. When several skills apply, prefer the smallest set that covers the task and follow the project rules in `AGENTS.md`.

## MCP Servers

The repository-local `.mcp.json` currently defines:

| Server | Purpose | Secret Requirements |
| --- | --- | --- |
| `herd` | Local Laravel/Herd project integration. | None in repository files. |
| `21st-dev-magic` | 21st.dev Magic MCP for UI inspiration, SVG icon search, and Magic Generate. | `TWENTY_FIRST_DEV_API_KEY` must exist in the host agent/editor environment. |
| `context7` | Current framework and package documentation lookup. | None in repository files. |
| `playwright` | Browser-level UI inspection and regression checks. | None in repository files. |

Do not add personal or machine-specific MCP servers to `.mcp.json` unless they are portable for the whole project. User-global MCP servers such as Browser Use URLs, Render, Snyk, Select Star, Git, Chrome DevTools, OpenContext, or editor-specific tools may be available in a local agent session, but they are not part of the repository contract unless they are added here with documented commands and required environment variables.

## MCP Implementation Rules

- Keep secrets out of repository files; document only environment variable names.
- Verify Laravel MCP commands before relying on them:

```bash
php artisan list --raw | rg '^(boost:mcp|mcp:start)$'
```

- If a Laravel MCP server is added later, register it in `.mcp.json`, document the startup flow here, and update `docs/SESSION-BOOTSTRAP.md`.
- For 21st.dev design work, use `21st-dev-design` plus the `21st-dev-magic` server when `TWENTY_FIRST_DEV_API_KEY` is configured.

## Verification Commands

Use these checks after changing skill or MCP wiring:

```bash
jq . boost.json
jq . .mcp.json
php artisan list --raw | rg '^(boost:mcp|mcp:start)$' || true
```

Use application tests and frontend builds for behavior changes:

```bash
php artisan test --compact
npm run build
```
