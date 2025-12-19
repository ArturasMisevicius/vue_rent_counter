---
inclusion_mode: "conditional"
file_patterns:
  - "app/Filament/**"
---

# Filament Performance Guidance

## Queries
- Limit selected columns; eager load only used relationships.
- Avoid universal `searchable()/sortable()`; keep to key columns.
- Cache heavy option lists and aggregates; invalidate on write.

## Tables
- Prefer simple pagination for large datasets; offer 25/50/100 options.
- Use `->deferLoading()` for heavy content; `->lazy()` on expensive columns.
- Summarizers over custom aggregate queries when possible.

## Forms
- Avoid loading huge option sets; use async search/select when needed.
- Keep repeaters light; use `->simple()` for better performance.

## Actions & widgets
- Keep action callbacks thin; move business logic to services.
- Widget queries should be small and cached per-tenant; poll only when required.

## N+1 prevention
- Check debugbar/Clockwork in dev; add indexes for searchable/sortable fields.

## Frontend
- Limit real-time polling; reduce DOM size (toggleable columns, concise schemas).
