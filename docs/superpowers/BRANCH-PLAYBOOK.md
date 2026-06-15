# Tenanto Historical Branch Playbook

> **AI agent usage:** This is historical branch-planning guidance. Read `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, `docs/AI-AGENT-DOCS.md`, `docs/PROJECT-CONTEXT.md`, and `docs/FEATURES.md` first. Verify current code and `git status` before changing behavior.

Updated on 2026-06-15. The original March branch sequence has already been merged and later expanded. This file now documents the historical branch strategy and the current safe workflow for future branches.

## Historical Branch Sequence

The March rollout was originally organized around:

1. foundation auth and onboarding;
2. shared interface elements;
3. admin organization operations plus manager parity;
4. tenant self-service portal;
5. superadmin control plane;
6. cross-cutting behavioral rules;
7. missing information closures.

Those branches should be treated as historical context. Do not create a new branch solely because this old sequence says it comes next.

## Current Branching Defaults

For new work:

- Start from a clean `git status --short --branch`.
- Keep docs-only work separate from behavior changes when practical.
- Scope feature branches by current product boundary, not old phase names.
- Update the closest docs and `CHANGELOG.md` in the same branch when behavior changes.
- Avoid broad hidden-agent metadata edits unless explicitly requested.
- Preserve unrelated user changes.

Recommended naming examples:

| Work type | Example branch |
| --- | --- |
| Tenant portal KYC hardening | `codex/tenant-kyc-hardening` |
| Billing review workflow | `codex/billing-review-reading-cycle` |
| Manager permission fix | `codex/manager-permission-scope-fix` |
| Docs refresh | `codex/docs-feature-guide-refresh` |
| Security guardrail | `codex/public-surface-guardrail` |

## Current Merge Readiness Checklist

Before merging:

- `git status --short --branch` is understood.
- Focused tests passed for changed behavior.
- `vendor/bin/pint --dirty` ran after PHP changes.
- `npm run build` ran after frontend asset changes.
- `php artisan route:list` was checked after route/navigation changes.
- `php artisan migrate:status` was checked after schema work.
- Current docs were updated.
- `CHANGELOG.md` was updated either manually for broad history/docs work or by `scripts/update_changelog.php`/hooks for normal staged changes.

## Historical Docs Safety

If a current task mentions an old plan/spec:

1. Read the historical doc for intent.
2. Verify the corresponding live files.
3. Search `CHANGELOG.md` and `git log -- <path>` for later changes.
4. Update current docs, not only the old planning file, when behavior changes.
