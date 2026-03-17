---
name: "filament"
description: "A reusable AI agent skill for filament."
---

# Filament

## Project Foundation Rule

In this repository, Filament is the base application foundation for request validation, actions, and support classes.

- Create all new request classes under `app/Filament/Requests` with namespaces rooted at `App\\Filament\\Requests`.
- Create all new action classes under `app/Filament/Actions` with namespaces rooted at `App\\Filament\\Actions`.
- Create all new support/service/helper classes under `app/Filament/Support` with namespaces rooted at `App\\Filament\\Support`.
- Do not create or recreate `app/Http/Requests`, `app/Actions`, or `app/Support`.
- When editing older code, migrate references toward the Filament foundation tree instead of adding new legacy references.

## Placement Examples

- `App\\Filament\\Requests\\Auth\\LoginRequest`
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
