# Tenanto Skills And MCP Inventory

> **AI agent usage:** Read `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, `docs/AI-AGENT-DOCS.md`, `docs/PROJECT-CONTEXT.md`, and `docs/FEATURES.md` before acting on this file. Treat examples as context; verify current code, routes, schema, translations, and tests before changing behavior.

Updated on 2026-06-15 from the live checkout.

## Current Skill Inventory

The workspace currently contains 399 local `SKILL.md` files across these roots:

| Root | Purpose |
| --- | --- |
| `.agents/skills` | Codex-visible project skills for this workspace. |
| `.agent/skills` | Broader local assistant skill library, including Tenanto-specific legacy skills. |
| `.ai/skills` | PHP, Laravel, Filament, testing, and analysis skills used by generated assistant instructions. |
| `.claude/skills` | Claude-facing workspace skill subset. |
| `.codex/skills` | GSD workflow skills plus Addy Osmani's and Superpowers engineering lifecycle skills. |
| `.codex/superpowers/skills` | Project-local Superpowers skill source. |

The Boost registry in `boost.json` is narrower than the raw file count. It exposes skills that are relevant to this Laravel, Filament, Livewire, Tailwind, tenant-management project.

## Project-Active Skill Groups

Use the smallest relevant set for a task:

| Area | Skills |
| --- | --- |
| Core Laravel implementation | `laravel-11-12-app-guidelines`, `laravel-best-practices`, `laravel-models`, `laravel-multi-tenancy`, `eloquent-best-practices`, `spatie-laravel-php-standards` |
| Filament and Livewire | `filament`, `livewire-development`, `fluxui-development` |
| Tenant domain | `tenanto-laravel-stack`, `tenanto-tenant-security`, `tenanto-billing-reporting`, `tenanto-lang-migration` when available in the active skill roots |
| UI and design | `21st-dev-design`, `frontend-design`, `tailwindcss-development`, `mobile-design` |
| Testing and quality | `pest-testing`, `laravel-testing`, `laravel-quality`, `phpstan-fixer`, `phpcs-check-fix` |
| Architecture and safety | `architecture`, `laravel-security-audit`, `security-and-hardening`, `complexity-guardrails` |
| Documentation and release notes | `documentation-and-adrs`, `analyze-document`, `update-changelog-before-commit` |
| MCP/server work | `mcp-development`, `php-mcp-server-generator` |
| Superpowers workflow | `using-superpowers`, `brainstorming`, `writing-plans`, `executing-plans`, `test-driven-development`, `systematic-debugging`, `verification-before-completion`, `finishing-a-development-branch` |
| Addy engineering lifecycle | `using-agent-skills`, `spec-driven-development`, `planning-and-task-breakdown`, `incremental-implementation`, `debugging-and-error-recovery`, `code-review-and-quality`, `performance-optimization`, `shipping-and-launch` |

When a user names a skill directly, use that skill. When a task crosses domains, combine skills only where they change the work.

## Repo-Local MCP Servers

`.mcp.json` currently defines:

| Server | Purpose | Secret Requirements |
| --- | --- | --- |
| `herd` | Local Laravel/Herd project integration with `SITE_PATH=/Users/andrejprus/Herd/tenanto`. | None in repository files. |
| `21st-dev-magic` | 21st.dev Magic MCP for UI inspiration, SVG icon search, and Magic Generate. | `TWENTY_FIRST_DEV_API_KEY` must exist in the host agent/editor environment. |
| `context7` | Current framework and package documentation lookup. | None in repository files. |
| `playwright` | Browser-level UI inspection and regression checks. | None in repository files. |

On 2026-06-15, `php artisan list --raw` did not expose `boost:mcp` or `mcp:start`. If a Laravel MCP server appears in an editor session, verify whether it is user-global before documenting it as repository-local behavior.

## Boost Registry Notes

`boost.json` currently lists:

- agents: `claude_code`, `codex`, `cursor`, `gemini`
- MCP enabled: true
- Herd MCP: true
- Sail: false
- packages: `filament/filament`, `spatie/laravel-backup`

Important caveat: `composer show --direct` on 2026-06-15 did not show `spatie/laravel-backup` installed. The current backup documentation therefore describes the local readiness command, not Spatie package configuration.

## MCP Implementation Rules

- Keep secrets out of repository files; document only environment variable names.
- Verify Laravel MCP commands before relying on them:

```bash
php artisan list --raw | rg '^(boost:mcp|mcp:start)$'
```

- If a Laravel MCP server is added later, register it in `.mcp.json`, document the startup flow here, and update `docs/SESSION-BOOTSTRAP.md`.
- For 21st.dev design work, use `21st-dev-design` plus the `21st-dev-magic` server when `TWENTY_FIRST_DEV_API_KEY` is configured.
- For package docs, prefer the project-approved MCP source when available; otherwise verify against installed versions before changing APIs.

## Verification Commands

Use these checks after changing skill or MCP wiring:

```bash
jq . boost.json
jq . .mcp.json
php artisan list --raw | rg '^(boost:mcp|mcp:start)$' || true
php artisan about
php artisan route:list
```

Use application tests and frontend builds for behavior changes:

```bash
php artisan test --compact
npm run build
```

Use markdown checks for docs-only changes:

```bash
git diff --check -- $(rg --files -g '*.md' -g '!vendor/**' -g '!node_modules/**' -g '!storage/**' -g '!public/build/**')
```
