# Tasks: Add Legacy Domain Import Foundation

Source design:
`docs/superpowers/specs/2026-03-17-legacy-domain-expansion-design.md`

## 1. Build The Import Ledger

- [ ] Inventory all `_old` models, enums, factories, and seeders against the
      current project
- [ ] Classify each `_old` model as merge/import/defer
- [ ] Record overlapping current models and the missing fields/relations they need

## 2. Import Missing Domain Structure

- [ ] Add missing enums and value objects required by imported models
- [ ] Add missing Eloquent models for concepts absent from the current project
- [ ] Extend overlapping current models with missing fillable fields, casts,
      relations, and helper methods

## 3. Import Missing Schema Additively

- [ ] Add forward-only migrations for missing tables
- [ ] Add forward-only migrations for missing columns and indexes on current tables
- [ ] Ensure rollback safety without mutating old migration history

## 4. Add Construction Support

- [ ] Create or extend factories for imported models
- [ ] Add minimal seeder hooks so imported models can be instantiated safely
- [ ] Keep seed logic compatible with current runtime assumptions

## 5. Verify The Foundation

- [ ] Add migration/domain smoke tests for imported tables and models
- [ ] Run focused current auth/admin/tenant regression coverage
- [ ] Run `pint` and the focused test suite
- [ ] Prepare the foundation slice for the Baltic localization follow-up
