# Design: Legacy Domain Import Foundation

## Context

The current Tenanto repository already contains a live Laravel 12 shape with
authentication, organizations, properties, tenants, invoices, notifications,
languages, and some control-plane models. `_old` contains a much broader domain
surface, including currencies, providers, tariffs, FAQ content, translations,
projects/tasks, comments, attachments, utility/billing reference models, and
other platform records.

The user request is explicitly one-way: bring missing structure from `_old`
into the current project, but never remove existing fields or replace the
current app wholesale.

## Goals

- Make `_old` imports safe and repeatable
- Preserve the current project as the canonical modern application
- Add all missing legacy domain structure needed for future reference-data,
  fixture, and CRUD work
- Avoid duplicate models when a current model already represents the same
  concept

## Non-Goals

- Porting every `_old` UI or workflow in this slice
- Deleting current models or fields
- Renaming current schema to match `_old`
- Finalizing seed volume, localized geography, or CRUD coverage

## Import Rules

### Additive only

Every migration must be forward-only and additive. Current columns stay in
place. Missing legacy fields are added to current tables; missing legacy tables
are introduced as new tables.

### Current-model-first mapping

If the current project already has a model for a concept from `_old`, the
import extends that model with missing fillable fields, casts, relations,
scopes, and supporting tests. It does not create a second “legacy” version of
that model.

### Explicit defer list

Some `_old` concepts may depend on workflows that do not exist in the current
project yet. Those concepts should be explicitly deferred in the implementation
ledger rather than imported blindly.

## Architecture Overview

This slice produces three durable artifacts:

1. additive schema
- new migrations for missing tables and columns

2. additive domain layer
- new Eloquent models for absent concepts
- extensions to overlapping current models

3. additive construction layer
- factories and seed hooks sufficient to instantiate the imported domain

## Mapping Strategy

Each `_old` model should be classified into one of three buckets:

- `merge into existing current model`
- `import as new current model`
- `defer with documented reason`

The implementation plan should group work by dependency clusters, for example:

- reference/platform data
- property/billing/utilities data
- collaboration/project/task data
- translation/content data

## Testing Strategy

Foundation tests should verify:

- migrations expose the expected additive columns/tables
- imported models can be instantiated by factories
- overlapping models preserve current behavior while gaining missing structure
- no existing auth/tenant/admin regression is introduced by the new schema

## Risks

- Blindly importing `_old` can duplicate current concepts like notifications,
  organizations, or invoices
- Large model waves can hide migration conflicts if the work is not chunked
- Some `_old` seeders assume obsolete tables and must be translated, not copied

## Follow-On Dependencies

This change is the prerequisite for:

- `add-baltic-reference-localization`
- `add-domain-fixtures-and-crud-coverage`
