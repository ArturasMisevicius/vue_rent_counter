# AI Agent Docs Contract

> **AI agent usage:** Treat this file as the shared documentation contract for AI agents working in Tenanto. Read it before using older plans, specs, audits, or runbooks as implementation guidance.

## Purpose

Tenanto has several documentation layers that were written at different times for humans, command agents, and planning agents. This contract tells an agent which files are authoritative, which files are historical, and how to update docs without turning old assumptions into new bugs.

## Read Order

Start every non-trivial coding or review session with:

1. `AGENTS.md`
2. `docs/SESSION-BOOTSTRAP.md`
3. `docs/PROJECT-CONTEXT.md`
4. `docs/SKILLS-MCP-INVENTORY.md`
5. The feature-specific Markdown file you are acting on

Use `CLAUDE.md` and `GEMINI.md` as compatibility entrypoints for those agent surfaces. They should point back to the same project rules rather than diverging.

## Documentation Authority

Current source of truth:

- `AGENTS.md` defines repository rules, Laravel/Filament/Blade constraints, and response expectations.
- `docs/PROJECT-CONTEXT.md` summarizes the current product shape, stack, and architecture baseline.
- `docs/SESSION-BOOTSTRAP.md` documents session startup checks, MCP expectations, and verification commands.
- `docs/SKILLS-MCP-INVENTORY.md` lists the project skill and MCP inventory.
- Live code, migrations, policies, routes, tests, and language files override any stale documentation.

Historical or dated context:

- `docs/superpowers/plans/**` are implementation plans. They are not proof that the work still matches the live code.
- `docs/superpowers/specs/**` are design intent. Verify implementation before changing behavior.
- `docs/performance/**` and `docs/security/**` are dated audit evidence. Re-run checks before making current claims.
- `CHANGELOG.md` records verified changes. Do not rewrite historical entries without explicit user direction.

Operations runbooks:

- `docs/operations/**` may include commands that affect deployments, backups, branches, or releases.
- Do not run destructive, external, secret-bearing, release, or production-impacting commands unless the user explicitly asks for that operation.

Generated or hidden agent metadata:

- Hidden directories such as `.agent/`, `.claude/`, `.codex/`, `.gemini/`, and `.ai/skills/` may contain tool, skill, or runtime definitions.
- Do not mass-edit hidden generated agent metadata during a normal documentation pass unless the user explicitly asks for that scope and the files are verified as project-owned docs.

## Agent Update Rules

When changing behavior:

- Update the closest user-facing or developer-facing doc in the same turn.
- Keep docs factual and dated when they describe verified state.
- Prefer links to canonical docs instead of copying long rule blocks into every file.
- Mark assumptions as assumptions.
- Preserve existing planning history unless the user asks to rewrite it.
- Never add secrets, tokens, customer data, or private credentials to Markdown.

When reading docs:

- Treat examples as examples until the code confirms them.
- Verify routes with `php artisan route:list` before changing navigation or links.
- Verify schema through migrations/models before changing queries or validation.
- Verify translations through `lang/*` files before claiming a string is localized.
- Verify Filament APIs against installed versions before changing resources, pages, actions, or forms.

## Verification For Docs-Only Changes

For Markdown-only updates, use lightweight verification:

```bash
rg --files-without-match "AI agent usage|AI Agent Docs Contract" -g '*.md' -g '!vendor/**' -g '!node_modules/**' -g '!storage/**' -g '!public/build/**'
git diff --check -- $(rg --files -g '*.md' -g '!vendor/**' -g '!node_modules/**' -g '!storage/**' -g '!public/build/**')
```

Run application tests only when docs edits accompany code, route, UI, validation, translation, or authorization changes.
