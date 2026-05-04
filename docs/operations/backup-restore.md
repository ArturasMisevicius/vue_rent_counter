# Backup And Restore Readiness

> **AI agent usage:** This is an operations runbook. Read `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, and `docs/AI-AGENT-DOCS.md` first; do not run destructive, external, release, backup, or secret-bearing commands unless the user explicitly asks.

## Purpose

Use the readiness command before a release or any maintenance window that depends on a recoverable application state.

```bash
php artisan ops:backup-restore-readiness
```

The command verifies:

- the configured database connection can be opened
- `storage/app/operations/backups` exists and is writable
- `storage/app/operations/restore` exists and is writable

## Backup Staging

- Backup directory: `storage/app/operations/backups`
- Restore staging directory: `storage/app/operations/restore`

Keep these directories writable for the deploy user and exclude transient restore files from committed source control.

## Minimum Release Gate

Do not proceed with a release unless the command ends with:

```text
Result: READY
```

If the command reports `NOT READY`, fix the reported database or filesystem issue first and rerun the command.
