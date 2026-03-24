# Phase 1 Guardrails Branch Protection

Use this helper when Phase `01-05` is the only remaining blocker and you have a machine with GitHub network access plus an admin-scoped token.

## Target

- Repository: `ArturasMisevicius/vue_rent_counter`
- Branch: `main`
- Required check: `Phase 1 Guardrails`
- API endpoint: `https://api.github.com/repos/ArturasMisevicius/vue_rent_counter/branches/main/protection/required_status_checks`

## Helper Command

```bash
php artisan ops:phase1-guardrails-branch-protection
```

The command prints:

- the exact GitHub API endpoint for required status checks
- the JSON payload for `PATCH /branches/main/protection/required_status_checks`
- an apply command using `GITHUB_TOKEN`
- a verify command that checks the required status check is present

## Environment

Set `GITHUB_TOKEN` in the shell or `.env` before running the helper on an authorized machine:

```bash
export GITHUB_TOKEN=your-admin-scoped-token
php artisan ops:phase1-guardrails-branch-protection
```

## Remote Prerequisite

Before applying branch protection, make sure the workflow already exists on remote `main` and has passed once:

- workflow file: `.github/workflows/phase-1-guardrails.yml`
- workflow name: `Phase 1 Guardrails`

## Verification

After applying the payload, run the printed verify command and confirm it returns `Phase 1 Guardrails`.
