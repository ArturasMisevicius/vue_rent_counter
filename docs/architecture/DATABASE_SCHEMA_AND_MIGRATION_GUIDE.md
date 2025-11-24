# Database Schema & Zero-Downtime Migration Guide

**Stack:** Laravel 12, Filament v4, single-database multi-tenant (`tenant_id`).  
**Hooks covered:** `database-schema-designer`, `zero-downtime-migration`, `database-query-optimization`, `n-plus-one-analyzer`.

## Schema Design Principles
- **Tenant isolation:** every tenant-owned table carries `tenant_id` (indexed). Preserve existing `BelongsToTenant` scope expectations.
- **Foreign keys:** prefer FK constraints (restrict/cascade as appropriate). For legacy SQLite test DB, keep FK intent in migrations even if SQLite enforces loosely.
- **Indexes:** add composite indexes for frequent filters (`tenant_id` + status/date/type). Keep pivot tables indexed on both sides plus activity timestamps (see `property_tenant`).
- **Enums:** use PHP enums in code and DB enums where supported; otherwise strings with validation.
- **Audit/history tables:** include `created_at` indexes; avoid wide JSON blobs in hot paths.

## Zero-Downtime Migration Pattern
1. **Additive phase:** add nullable columns/tables, default-safe values, and indexes concurrently.
2. **Backfill phase:** chunked updates (`->whereKeyBetween`, `->chunkById`) to populate new columns; guard with feature flags when possible.
3. **Dual-write phase:** update models/events to write old + new columns; keep reads on old until confidence is reached.
4. **Cutover phase:** switch reads to new columns/relations; keep dual writes briefly.
5. **Cleanup phase:** remove deprecated columns/indexes only after verifying logs/metrics.

## Backfill Checklist
- Use queued jobs or artisan commands with chunking; avoid table scans.
- Wrap in small transactions; avoid long locks.
- For time-based data (e.g., `property_tenant`), preserve chronological integrity (`assigned_at`, `vacated_at`).
- Verify with counts and sampled records; log progress.

## Query Optimization
- Eager load relations in Filament resources and controllers to avoid N+1; prefer `withCount` for aggregates.
- Use `select` projections for tables to limit payload.
- Add covering indexes for common filters; avoid redundant indexes.
- For history/analytics queries, consider read-only replicas (future) or background materialization (reports).

## Testing & Rollback
- Add Pest tests for migrations/backfills when logic is non-trivial (e.g., data moves, pivots).
- Keep a rollback note in each migration when feasible; document irreversible steps.
- Use `migrate:fresh --seed --seeder=TestDatabaseSeeder` to validate schema + seeds locally.

## When Adding New Tables/Columns
- Include `tenant_id` and indexes up front.
- Add created/updated timestamps; index `created_at` for history queries.
- Document the change in this guide and in `docs/reference/HOOKS_DOCUMENTATION_MAP.md`.
