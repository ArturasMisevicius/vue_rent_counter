---
inclusion_mode: "conditional"
file_patterns:
  - "app/Filament/**/Schemas/**"
---

# Filament Content & Layout Patterns

## Composition rules
- Use Section/Grid/Tabs/Split/Wizard; avoid nesting deeper than ~3 levels.
- Group related fields; keep max 2–3 sections per screen for readability.
- Use vertical Tabs for dense settings; card-based layouts for dashboards.

## Responsiveness
- Leverage container queries for width-based adjustments.
- Grid: set responsive columns (default/sm/md/lg) instead of fixed spans.

## Mixing components
- Combine editable fields with read-only entries when helpful (Forms + Infolist components).
- Use aside descriptions for sections to reduce clutter inside fields.

## Wizards
- Use for multi-step flows; keep steps small; add summaries where possible.

## SlideOvers & modals
- Prefer slideOver for quick edits; set widths sensibly (sm/md/lg/xl).
- Don’t overuse modal nesting; avoid deep forms inside repeaters in modals.

## Empty states / helper text
- Provide context and actions in empty states.
- Keep helper text concise; avoid repeating validation messages.
