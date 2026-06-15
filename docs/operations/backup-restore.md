# Backup And Restore Readiness

> **AI agent usage:** This is an operations runbook. Read `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, `docs/AI-AGENT-DOCS.md`, and `docs/FEATURES.md` first; do not run destructive, external, release, backup, restore, or secret-bearing commands unless the user explicitly asks.

Updated on 2026-06-15.

## Purpose

Use the readiness command before a release or any maintenance window that depends on a recoverable application state.

```bash
php artisan ops:backup-restore-readiness
```

The command checks:

- the configured database connection can be opened;
- `storage/app/operations/backups` exists and is writable;
- `storage/app/operations/restore` exists and is writable.

This is a local readiness command backed by `App\Services\Operations\BackupRestoreReadinessService`. It is not currently documented as a Spatie Laravel Backup setup because `composer show --direct` did not show `spatie/laravel-backup` installed on 2026-06-15.

## Backup Staging

| Path | Purpose |
| --- | --- |
| `storage/app/operations/backups` | Backup staging location checked by the readiness command. |
| `storage/app/operations/restore` | Restore staging location checked by the readiness command. |

Keep both directories writable for the deploy user and keep transient restore files out of committed source control.

## Minimum Release Gate

Do not proceed with a release unless the command ends with:

```text
Result: READY
```

If the command reports `NOT READY`, fix the reported database or filesystem issue first and rerun the command.

## Companion Checks

For release work, pair this command with:

```bash
php artisan migrate:status
php artisan ops:release-readiness
php artisan queue:work --once
```

If `migrate:status` shows pending checked-in migrations, decide whether the release should apply them before starting backup or restore operations.
