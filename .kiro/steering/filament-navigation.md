---
inclusion_mode: "conditional"
file_patterns:
  - "app/Filament/**/*"
---

# Filament Navigation & Clusters (v4)

## Navigation rules
- Group by workspace/ops/settings; keep sort values consistent (0–199 core, 200+ settings).
- Prefer cluster navigation for settings/config resources; keep icons consistent with meaning.
- Set `$recordTitleAttribute` for global search and detail labels.
- Add `getNavigationGroup()` translations; avoid hardcoded strings.
- Use `->hidden(fn () => ! auth()->user()->can(...))` for sensitive items.

## Global search
- Provide `getGlobalSearchResultDetails()` with 2–3 key fields; avoid heavy relationships.
- Limit to essential models; set `static int $globalSearchResultsLimit` when necessary.

## Tenancy awareness
- In multi-tenant panels, hide items that don’t apply: `static function shouldRegisterNavigation(): bool`.
- Ensure `getEloquentQuery()` leverages v4 auto-tenancy; avoid manual tenant filters unless overriding.

## Icons & colors
- Use heroicons outline for navigation. Primary color = product/core; warning/danger only for risky pages.
- Avoid decorative emojis; keep consistent across resources.
