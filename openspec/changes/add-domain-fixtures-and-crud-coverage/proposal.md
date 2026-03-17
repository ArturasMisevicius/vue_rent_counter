# Proposal: Add Domain Fixtures And CRUD Coverage

## Summary

Add realistic large-volume fixtures and regression coverage after the legacy
domain and Baltic reference-data foundations exist, producing at least 1,000
logical records and CRUD coverage for every existing/imported CRUD surface.

## Problem

The current repository has only a small set of seed records and focused tests.
The requested direction requires a much richer working dataset and broader CRUD
coverage, but that should only happen once the domain and reference-data shape
is stable.

## Proposed Change

- Add seed packs that generate at least 1,000 total records across the domain
- Ensure seeded geography, relationships, and language-driven names are logical
- Add CRUD and regression coverage for every surface that exists after import
- Cover authorization and isolation, not just happy-path create/edit/delete

## Dependencies

- `docs/superpowers/specs/2026-03-17-legacy-domain-expansion-design.md`
- `add-legacy-domain-import-foundation`
- `add-baltic-reference-localization`

## Out Of Scope

- Inventing CRUD surfaces that do not exist yet
- Non-Baltic geography packs
- Replacing the current auth/demo seed behavior

## Impact

- Expands the seeded demo environment substantially
- Adds broad feature/unit/regression coverage
- Establishes a realistic working dataset for future product development
