# Tenanto Legacy Domain Expansion Design

> **AI agent usage:** This is a design/spec artifact. Read `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, and `docs/AI-AGENT-DOCS.md`, then verify live code and tests before assuming the behavior still matches this document.

## Goal

Expand the current Tenanto Laravel 12 codebase toward the richer `_old`
domain without regressing the live project. This program is explicitly
one-way and additive: import missing models, tables, columns, enums, factories,
and seed capabilities from `_old`; keep the current project as the source of
truth; and layer Baltic-only multilingual reference data plus large logical
fixtures on top of that foundation.

## Scope

This program includes:

- additive import of missing `_old` domain models and fields into the current project
- overlap resolution where current models are extended instead of duplicated
- runtime language support restricted to `en`, `lt`, and `ru`
- removal of `es` from supported language seed/runtime surfaces
- Baltic-only country and city reference data with multilingual names
- logical seed packs that create at least 1,000 related records
- CRUD and regression coverage for all existing/imported CRUD surfaces

This program does not include:

- destructive renames or removal of current fields
- raw SQL migration shortcuts
- importing every `_old` UI surface before the underlying domain exists
- non-Baltic geography packs
- speculative localization beyond `en`, `lt`, and `ru`

## Program Decomposition

This work is intentionally split into three sub-projects.

### 1. Legacy Domain Import Foundation

Import the missing schema and domain structure from `_old` into the current
project using additive migrations and Eloquent-first models. This is the
foundation slice because all later seed, geography, and CRUD work depends on
the final shape of the domain.

### 2. Baltic Reference Localization

Constrain the supported language/runtime footprint to English, Lithuanian, and
Russian, then introduce valid Baltic geography with multilingual country and
city names. This slice establishes realistic, localized reference data for the
seed and CRUD layers.

### 3. Domain Fixtures And CRUD Coverage

Generate realistic, relationship-safe fixtures with at least 1,000 total
records and add regression coverage for every CRUD surface that exists once the
foundation and reference-data slices land.

## Core Rules

- Add only what is missing from `_old`
- Never remove or rename current project fields as part of the import
- Prefer extending current models over creating parallel duplicates
- Keep all queries in Eloquent/query-object/Form Request/Action layers
- Seed realistic relationships instead of disconnected random rows
- Use real country-city combinations for all generated geography
- Keep translation storage and publishing compatible with Laravel’s existing
  `lang/*.php` runtime

## Legacy Import Strategy

The import begins with an inventory and mapping pass:

- which `_old` models are entirely absent
- which current models overlap but miss fields/relations/casts
- which `_old` enums, factories, observers, and seeders are prerequisites
- which `_old` concepts are obsolete and should stay out of scope

The implementation should produce an explicit mapping ledger so every `_old`
model is classified as:

- imported as a new model/table
- merged into an existing current model
- deferred because no current surface depends on it yet

This avoids blind porting and keeps the repository understandable.

The first implementation artifact for this strategy is
`docs/superpowers/legacy-domain-import-ledger.md`, which tracks each legacy
top-level model as `merge`, `import`, or `defer`, names the current target,
and records the missing schema/model support that still needs to land.

## Localization And Geography Strategy

The geography slice uses authoritative public datasets for naming and
verification:

- Unicode CLDR for localized country/territory naming conventions
- EU Vocabularies / Eurostat country reference data for canonical country lists
- GeoNames and Eurostat city datasets for valid city-country relationships

The product should only ship Baltic geography in this slice:

- Lithuania
- Latvia
- Estonia

Cities must belong to the correct country and expose localized names in:

- English
- Lithuanian
- Russian

This slice is limited to the supported runtime locales: English, Lithuanian,
and Russian.

## Fixture Strategy

The large seed pack should feel like a coherent demo environment rather than a
bag of random records. Data should be distributed logically across the
available domain:

- organizations and settings
- buildings and properties
- users by role
- property assignments
- providers/tariffs/utilities if imported
- invoices, readings, notifications, audit/log history, and related reference data

At least 1,000 records total should be created, but with believable ratios and
dependencies. For example, a city should match its country, a tenant should map
to a property assignment, and billing rows should point at valid domain owners.

## CRUD Coverage Strategy

CRUD coverage is not only for new controllers. It includes:

- current and future Filament resources
- web CRUD controllers/pages
- Livewire-backed create/update flows
- authorization and isolation regressions

Tests should be added only when the corresponding CRUD surface exists. The
program should avoid writing tests for theoretical resources that have not been
ported yet.

## Risks

- `_old` includes models that depend on legacy concepts not present in the new
  codebase; these require careful merge/defer decisions
- a big-bang port could duplicate current models or create conflicting seeds
- geographic localization can drift if names are hand-written instead of
  imported from durable datasets
- 1,000-row seed packs can become slow or flaky if relationships are not built
  with factories and scopes deliberately

## Acceptance Shape

The program is complete when:

- all required `_old` domain structure has been additively imported or
  explicitly deferred with rationale
- the runtime language set is only `en`, `lt`, and `ru`
- Baltic-only multilingual geography exists and is used by seed data
- the seed suite can produce at least 1,000 logical records
- every existing/imported CRUD surface has regression coverage appropriate to
  the way it is exposed
