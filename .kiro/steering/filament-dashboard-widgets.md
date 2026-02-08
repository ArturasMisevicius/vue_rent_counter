---
inclusion_mode: "conditional"
file_patterns:
  - "app/Filament/Widgets/**"
---

# Filament Dashboard Widgets

## Types & usage
- **StatsOverview** for KPIs; **TableWidget** for recent items; **ChartWidget** for trends; **CustomWidget** for bespoke layouts.
- For quick trends, use Filament Chart widgets (Line/Bar) with Flowframe Trend; prefer the shared `ChartJsTrendWidget`/`LeadTrendChart` pattern to keep cache keys tenant-scoped and avoid duplicate query logic.
- Keep per-widget queries light; cache results (per-tenant) when feasible.

## Performance
- Limit to essential columns; eager load relationships used in the widget.
- Use `->poll()` sparingly (e.g., 30–60s) only when real-time matters.
- Avoid expensive aggregates in the Livewire render cycle—cache or precompute.

## Tenancy & authorization
- Scope queries to current tenant/team; hide widgets if user lacks permission.
- Prefer `canView()` checks; don’t leak counts across tenants.

## UX
- Provide actions (links) for drill-down to resources with filtered views.
- Handle empty states gracefully; avoid blank cards.
- Keep card height tight; avoid horizontal scrolling.

## Testing
- Cover tenant scoping, permission checks, and data correctness for displayed stats.
