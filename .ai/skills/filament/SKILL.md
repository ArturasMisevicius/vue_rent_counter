---
name: "filament"
description: "Use when creating, reviewing, or refactoring Filament 5 resources, pages, forms, tables, widgets, relation managers, infolists, or actions in this repository."
---

# Filament

This repository uses Filament 5+ patterns and treats Filament as both the admin UI layer and the foundation namespace for requests, actions, and support classes.

## Project Foundation Rule

In this repository, Filament is the base application foundation for request validation, actions, and support classes.

- Create all new request classes under `app/Http/Requests` with namespaces rooted at `App\\Http\\Requests`.
- Create all new action classes under `app/Filament/Actions` with namespaces rooted at `App\\Filament\\Actions`.
- Create all new support/service/helper classes under `app/Filament/Support` with namespaces rooted at `App\\Filament\\Support`.
- Do not create or recreate `app/Actions` or `app/Support`.
- When editing older code, migrate request references toward `app/Http/Requests` and keep action plus support references in the Filament foundation tree instead of adding new legacy references.

## Placement Examples

- `App\\Http\\Requests\\Auth\\LoginRequest`
- `App\\Filament\\Actions\\Admin\\Invoices\\GenerateBulkInvoicesAction`
- `App\\Filament\\Support\\Shell\\Navigation\\NavigationBuilder`

## Verification

- Keep [AGENTS.md](/Users/andrejprus/Herd/tenanto/AGENTS.md) aligned with this rule.
- Keep [tests/Feature/Architecture/FilamentFoundationPlacementTest.php](/Users/andrejprus/Herd/tenanto/tests/Feature/Architecture/FilamentFoundationPlacementTest.php) passing so CI blocks regressions.

## Enum Presentation Rule

When a field is backed by an application enum:

- Use `EnumClass::options()` for `Select`, `SelectFilter`, `Radio`, and similar option lists.
- Let Filament render enum labels automatically when the enum implements `Filament\\Support\\Contracts\\HasLabel`.
- Prefer simple column and entry definitions such as `TextColumn::make('status')->badge()` over `ucfirst(...)`, `str_replace(...)`, or hand-built label maps.
- Keep enum translations in `lang/*/enums.php`, not scattered across individual resources.

## Filament 5+ Review Checklist

When reviewing or refactoring Filament resources, pages, forms, tables, widgets, relation managers, and actions:

- Prefer Filament schema-first APIs and idiomatic components over custom view hacks
- Keep heavy business logic in `App\\Filament\\Actions` or `App\\Filament\\Support`, not inline closures
- Select only the columns needed by the resource/page/widget query
- Eager load every relationship used by columns, infolists, filters, badges, and actions
- Use `withCount()` / `withExists()` when the UI only needs counts or booleans
- Keep authorization explicit on actions, bulk actions, and relation managers
- Reuse extracted schema fragments or dedicated schema classes when forms/tables become too large

## Resource Query Rules

- Override the resource query path intentionally and preload required relations there
- Avoid formatting that triggers relationship access unless the relation is eager loaded
- Push filtering into the database layer instead of filtering records in PHP
- Prefer expressive model scopes or query classes over repeating `where('organization_id', ...)` everywhere

Common patterns:

```php
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->select(['id', 'organization_id', 'name', 'status', 'created_at'])
        ->forOrganization(app(OrganizationContext::class)->currentOrganizationId())
        ->with(['organization:id,name'])
        ->withCount('members');
}
```

## Form Schema Guidance

- Break large forms into `Section`, `Tabs`, `Fieldset`, and `Grid` structures
- Extract schema classes when multiple resources/pages share the same field groups or when a resource becomes difficult to scan
- Use relationship-backed `Select` / `BelongsToSelect` patterns correctly with `searchable()`, `preload()`, and `createOptionForm()` where justified
- Be deliberate with reactive behavior; do not make large forms reactive by default
- Prefer enum-driven options from `EnumClass::options()`

Keep schema inline when:
- the form is small and only used once

Extract dedicated schema classes when:
- the schema is reused
- the resource/page is getting large
- dynamic sections obscure the resource file

## Table Schema Guidance

- Use only columns the table truly needs
- Make columns `searchable()`, `sortable()`, and `toggleable()` intentionally
- Avoid `formatStateUsing()` logic that triggers extra queries or complex branching
- Use filters that map cleanly to database constraints and model scopes
- Prefer eager-loaded badge/status columns over ad-hoc label lookups

## Actions, Widgets, and Dashboards

- Action confirmation, validation, and authorization must be explicit
- Move multi-step writes, exports, notifications, and fan-out logic into action classes
- Keep widgets focused; do not run expensive raw metrics on every render if caching or pre-aggregation is appropriate
- Use cached aggregates only when the metric is expensive and shared; otherwise keep queries lean and direct

## Relation Managers and Nested UI

- Watch for duplicated resource query logic across relation managers and pages
- Ensure relation managers enforce the same organization/tenant boundaries as the parent resource
- If a resource is overloaded, consider a custom page, widget, infolist, or dedicated action instead of adding more inline complexity
