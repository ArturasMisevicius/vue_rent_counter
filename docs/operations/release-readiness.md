# Release Readiness Evidence

## Generate The Evidence

Run the release evidence command before tagging or deploying the milestone:

```bash
php artisan ops:release-readiness
```

The command summarizes:

- integration health from the live probe suite
- backup and restore readiness via `php artisan ops:backup-restore-readiness`
- queue worker guidance for the configured connection
- remaining manual checks for the release operator

## Required Companion Checks

Run these commands as part of the release checklist:

```bash
php artisan ops:backup-restore-readiness
php artisan queue:work --once
```

Then review the superadmin health page:

- `/app/integration-health`

## Manual Release Checks

- Confirm the integration health page has no failed probes.
- Confirm the backup readiness command ends with `Result: READY`.
- Confirm the target environment can run a real queue worker and is not relying on the testing-only sync flow.
- Keep the backup and release runbooks together under `docs/operations/`.
