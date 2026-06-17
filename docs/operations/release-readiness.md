# Release Readiness Evidence

> **AI agent usage:** This is an operations runbook. Read `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, `docs/AI-AGENT-DOCS.md`, and `docs/FEATURES.md` first; do not run destructive, external, release, backup, or secret-bearing commands unless the user explicitly asks.

Updated on 2026-06-15.

## Generate The Evidence

Run the release evidence command before tagging or deploying a milestone:

```bash
php artisan ops:release-readiness
```

The command summarizes:

- integration health from the probe suite;
- backup and restore readiness via `php artisan ops:backup-restore-readiness`;
- queue worker guidance for the configured connection;
- architecture boundary readiness via `php artisan architecture:check`;
- remaining manual checks for the release operator.

## Required Companion Checks

Run these commands as part of the release checklist:

```bash
php artisan about
php artisan route:list
php artisan migrate:status
php artisan architecture:check
php artisan ops:backup-restore-readiness
php artisan queue:work --once
```

Then review:

- `/app/integration-health`
- `/app/platform-dashboard`
- `/app/billing-review-center`
- `/tenant`

## Current Migration Caveat

On 2026-06-15, local `php artisan migrate:status` showed the checked-in tenant KYC migration as pending:

```text
2026_06_15_000000_create_tenant_kyc_verification_tables.php
```

Apply pending migrations before claiming the local database is release-ready for KYC features.

## Manual Release Checks

- Confirm the integration health page has no failed probes.
- Confirm the architecture check ends with `Result: PASSED`.
- Confirm the backup readiness command ends with `Result: READY`.
- Confirm the target environment can run a real queue worker.
- Confirm `php artisan schedule:list` includes expected billing, KYC, rental contract, project alert, and model prune maintenance commands where the environment depends on them.
- Confirm `npm run build` has run after frontend changes.
- Confirm focused tests passed for changed behavior.
- Confirm docs and `CHANGELOG.md` are updated for user-facing workflow changes.

## Release Evidence Files

Keep evidence in the commit or release notes rather than relying on old console output. At minimum, record:

- commit hash;
- migration status summary;
- tests/build commands run;
- release readiness command result;
- integration health result;
- unresolved caveats.
