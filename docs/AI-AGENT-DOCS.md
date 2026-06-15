# AI Agent Docs Contract

> **AI agent usage:** Treat this file as the shared documentation contract for AI agents working in Tenanto. Read it before using older plans, specs, audits, or runbooks as implementation guidance.

## Purpose

Tenanto has documentation for live usage, operations, historical planning, and agent tooling. This contract tells an agent which files are authoritative, which files are historical, and how to update docs without turning old assumptions into new bugs.

## Read Order

Start every non-trivial coding, review, or documentation session with:

1. `AGENTS.md`
2. `docs/SESSION-BOOTSTRAP.md`
3. `docs/AI-AGENT-DOCS.md`
4. `docs/PROJECT-CONTEXT.md`
5. `docs/FEATURES.md`
6. `docs/SKILLS-MCP-INVENTORY.md`
7. The feature-specific Markdown file you are acting on

Use `CLAUDE.md` and `GEMINI.md` as compatibility entrypoints for those agent surfaces. They should point back to the same project rules rather than diverging.

## Documentation Authority

Current source of truth:

- Live code, migrations, policies, routes, tests, language files, and checked-in config override stale documentation.
- `AGENTS.md` defines repository rules, Laravel/Filament/Blade constraints, and response expectations.
- `README.md` is the human-facing project entrypoint.
- `CHANGELOG.md` records reconstructed commit history and notable product evolution.
- `docs/FEATURES.md` is the current feature and usage guide.
- `docs/PROJECT-CONTEXT.md` summarizes the current product shape, stack, inventory, and architecture baseline.
- `docs/SESSION-BOOTSTRAP.md` documents session startup checks, MCP expectations, and verification commands.
- `docs/SKILLS-MCP-INVENTORY.md` lists the project skill and MCP inventory.
- `docs/operations/**` contains current runbooks for billing, service configuration, release readiness, branch protection, and backup readiness.
- `docs/PERMISSION-MATRIX.md` captures the current role/permission contract and required test expectations.

Historical or dated context:

- `docs/superpowers/plans/**` are execution plans. They are not proof that work is still incomplete or still shaped the same way.
- `docs/superpowers/specs/**` are design intent. Verify implementation before changing behavior.
- `docs/performance/**` and `docs/security/**` are dated audit evidence. Re-run checks before making current performance or security claims.
- The old auto-generated changelog entries were replaced by a 2026-06-15 git-history reconstruction at the user's request; use `git log` when exact historical details matter.

Operations runbooks:

- `docs/operations/**` may include commands that affect deployments, backups, branches, queues, releases, reminders, or external services.
- Do not run destructive, external, secret-bearing, release, or production-impacting commands unless the user explicitly asks for that operation.

Generated or hidden agent metadata:

- Hidden directories such as `.agent/`, `.agents/`, `.claude/`, `.codex/`, `.gemini/`, and `.ai/skills/` contain tool, skill, or runtime definitions.
- Do not mass-edit hidden generated agent metadata during a normal documentation pass unless the user explicitly asks for that scope and the files are verified as project-owned docs.

## Agent Update Rules

When changing behavior:

- Update the closest user-facing or developer-facing doc in the same turn.
- Any change to billing, readings, invoices, tenant onboarding, KYC, tenant documents, rental contracts, or tenant portal access must update `docs/FEATURES.md` or the related operations runbook.
- Any change to role, manager permission, tenant isolation, impersonation, policy, or audit behavior must update `docs/PERMISSION-MATRIX.md` when the contract changes.
- Keep docs factual and dated when they describe verified state.
- Prefer links to canonical docs instead of copying long rule blocks into every file.
- Mark assumptions as assumptions.
- Preserve historical planning history unless the user asks to rewrite it.
- Never add secrets, tokens, customer data, or private credentials to Markdown.

When reading docs:

- Treat examples as examples until the code confirms them.
- Verify routes with `php artisan route:list` before changing navigation or links.
- Verify schema through migrations/models before changing queries or validation.
- Verify translations through `lang/*` files before claiming a string is localized.
- Verify Filament APIs against installed versions before changing resources, pages, actions, or forms.
- Check `php artisan migrate:status` when docs refer to newly checked-in schema.

## Verification For Docs-Only Changes

For Markdown-only updates, use lightweight verification:

```bash
rg --files-without-match "AI agent usage|AI Agent Docs Contract|Current feature entrypoint|Historical" -g '*.md' -g '!vendor/**' -g '!node_modules/**' -g '!storage/**' -g '!public/build/**'
git diff --check -- $(rg --files -g '*.md' -g '!vendor/**' -g '!node_modules/**' -g '!storage/**' -g '!public/build/**')
```

Run application tests only when docs edits accompany code, route, UI, validation, translation, authorization, schema, or command behavior changes.
