# Proposal: Add Legacy Domain Import Foundation

## Summary

Add the first one-way import foundation that brings missing domain models,
tables, columns, enums, factories, and supporting seed structure from `_old`
into the current Tenanto project without removing or renaming anything that
already exists.

## Problem

The current repository contains a focused Laravel 12 subset of the Tenanto
domain, while `_old` contains a much larger historical domain model. The user
request now requires one-way import from `_old`, but the project does not yet
have a durable specification for how to merge that legacy structure into the
current codebase safely.

Without a foundation slice:

- later Baltic geography and localization work would target unstable schema
- large logical seed packs would need to guess missing relationships
- CRUD coverage would be written against incomplete or shifting domain surfaces

## Proposed Change

- Inventory `_old` models, enums, seeders, and schema against the current app
- Import missing models and missing fields as additive changes only
- Extend overlapping current models instead of adding duplicate replacements
- Add supporting factories/seed entry points needed to construct imported models
- Preserve current runtime behavior and current field names while broadening the
  domain surface

## Dependencies

- `docs/superpowers/specs/2026-03-17-legacy-domain-expansion-design.md`
- Existing 2026-03-17 design docs for auth, admin organization operations,
  tenant portal, and cross-cutting behavioral rules

## Out Of Scope

- Removing `es` or finalizing the supported language list
- Importing Baltic country/city reference data
- Creating the 1,000-record logical demo dataset
- Writing CRUD tests for surfaces that do not exist yet

These are follow-up sub-projects that depend on this import foundation.

## Impact

- Adds new Eloquent models, enums, migrations, factories, and minimal seeds
- Broadens current models with additive columns/relations/casts where `_old`
  contains missing structure
- Establishes the merge rules for every later legacy-domain change
