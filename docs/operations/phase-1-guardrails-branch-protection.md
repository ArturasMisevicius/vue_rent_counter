# Phase 1 Guardrails Branch Protection

> **AI agent usage:** This is an operations runbook. Read `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, `docs/AI-AGENT-DOCS.md`, and `docs/FEATURES.md` first; do not run destructive, external, release, backup, branch-protection, or secret-bearing commands unless the user explicitly asks.

Updated on 2026-06-15.

Use this helper when Phase 1 guardrails are the only remaining blocker and you have a machine with GitHub network access plus an admin-scoped token.

## Target

Verified from `git remote -v` on 2026-06-15:

- Repository: `ArturasMisevicius/vue_rent_counter`
- Branch: `main`
- Required check: `Phase 1 Guardrails`
- Workflow file: `.github/workflows/phase-1-guardrails.yml`
- Helper command: `php artisan ops:phase1-guardrails-branch-protection`

## Helper Command

```bash
php artisan ops:phase1-guardrails-branch-protection
```

The command prints:

- the GitHub API endpoint for required status checks;
- the JSON payload for `PATCH /branches/main/protection/required_status_checks`;
- an apply command using `GITHUB_TOKEN`;
- a verify command that checks the required status check is present.

## Environment

Set `GITHUB_TOKEN` in the shell or environment before running the helper on an authorized machine:

```bash
export GITHUB_TOKEN=your-admin-scoped-token
php artisan ops:phase1-guardrails-branch-protection
```

Never commit a real token to `.env`, Markdown, shell history snippets, screenshots, or release notes.

## Remote Prerequisite

Before applying branch protection, make sure the workflow exists on remote `main` and has passed at least once:

```bash
gh workflow view "Phase 1 Guardrails" --repo ArturasMisevicius/vue_rent_counter
gh run list --workflow "Phase 1 Guardrails" --repo ArturasMisevicius/vue_rent_counter --limit 5
```

If `gh` is not available, use the verify command printed by the helper and inspect GitHub in the browser.

## Verification

After applying the payload, run the printed verify command and confirm it returns `Phase 1 Guardrails`.

Also run the local guard command before asking for branch protection:

```bash
composer guard:phase1
```
