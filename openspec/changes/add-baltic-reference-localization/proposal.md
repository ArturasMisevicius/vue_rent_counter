# Proposal: Add Baltic Reference Localization

## Summary

Restrict the supported language/runtime footprint to English, Lithuanian, and
Russian, and add Baltic-only country and city reference data with multilingual
names.

## Problem

The current project previously carried a broader locale footprint and generic
faker geography. The requested product direction is narrower: only English,
Lithuanian, and Russian should be supported, and seed/reference data should be
limited to realistic Baltic countries and cities with multilingual names.

## Proposed Change

- Keep only `en`, `lt`, and `ru` as supported seeded languages
- Add Baltic-only country and city reference data
- Import multilingual country and city names for `en`, `lt`, and `ru`
- Use this localized geography as the source for later logical demo fixtures

## Dependencies

- `docs/superpowers/specs/2026-03-17-legacy-domain-expansion-design.md`
- `add-legacy-domain-import-foundation`

## Out Of Scope

- Importing non-Baltic geography
- Large-volume fixture generation
- CRUD regression coverage beyond the language/reference surfaces themselves

## Impact

- Changes language seed/runtime support
- Adds reference-data models/tables or equivalent import structure for country/city datasets
- Replaces ad-hoc geography with Baltic-only multilingual data
