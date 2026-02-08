---
inclusion_mode: "conditional"
file_patterns:
  - "app/Filament/**/RelationManagers/**"
---

# Filament Relation Managers

## Structure
- Name as `[Relation]RelationManager.php`; keep in resource’s RelationManagers folder.
- Table-first approach; use schemas for quick inline create/edit when helpful.

## Queries & performance
- Eager load parent-needed relationships; avoid N+1 in columns.
- Limit columns and `->searchable()` to essentials; use `->toggleable()` for optional data.
- Use `->beforeFill()` to hydrate defaults from parent; `->afterCreate()` for linking/pivots.

## Authorization
- Always gate on parent: `canViewForRecord`, `canCreateForRecord`, etc.
- Hide actions conditionally (e.g., delete disabled when children exist).

## UX patterns
- Empty states should offer a create action; keep modal widths small/medium.
- Use slideOver for dense forms; confirm destructive actions.
- Respect parent sorting (reorderable) only when model supports it.

## Validation & custom fields
- Mirror Form Request rules; align with model casts.
- If custom fields exist, include only those relevant to the relation.
