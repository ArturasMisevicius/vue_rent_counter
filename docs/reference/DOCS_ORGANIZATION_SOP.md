# Docs Organization SOP

**Hooks covered:** `docs-relocator`, `md-file-organizer`.  
**Purpose:** Keep documentation structure predictable as new guides are added.

## Structure
- `docs/architecture/` — architecture, migrations, services/repositories, relationships.
- `docs/api/` — API design/versioning/auth/error guides.
- `docs/filament/` — Filament-specific patterns and validation/localization.
- `docs/database/` — seeders, schema references, DB notes.
- `docs/testing/` — testing strategies and summaries.
- `docs/performance/`, `docs/security/`, `docs/reference/`, `docs/frontend/`, etc. — topic-specific.

## Naming
- Use UPPER_SNAKE_CASE for broad guides (e.g., `API_ARCHITECTURE_GUIDE.md`).
- Use specific names for features/tests (e.g., `TENANT_HISTORY_SEEDING.md`, `FACTORY_AND_SEEDING_GUIDE.md`).

## Cross-linking
- Update `docs/reference/HOOKS_DOCUMENTATION_MAP.md` when adding or moving docs.
- If reorganizing folders, update links in related guides and any navigation/index files.

## When Moving Docs
- Keep file paths consistent with topic folders.
- Update the hook map and any README/index references.
- If removing/merging docs, leave a short pointer in the old location if needed (or note in changelog).
