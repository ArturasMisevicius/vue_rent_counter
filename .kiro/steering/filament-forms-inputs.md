---
inclusion_mode: "conditional"
file_patterns:
  - "app/Filament/**/Schemas/**"
  - "app/Filament/**/Resources/**"
---

# Filament Forms & Inputs

## Field selection
- TextInput for short strings; Textarea for notes; RichEditor only when HTML required (prefer JSON mode).
- Select vs. Radio: Select for >5 options; Radio for few mutually exclusive; Toggle for booleans.
- FileUpload/Spatie: set collections, max files, mime limits; use `->preserveFilenames()` only when necessary.
- Number/Money: use `numeric()`, `min/max`, suffix/prefix for currency; align with DB casts.

## Validation & consistency
- Mirror Form Request rules; set sensible defaults.
- Use `->rule('regex:...')` only with helper text; avoid silent failures.
- Dates: DatePicker for dates, DateTimePicker for timestamps; set `->native(false)` for consistency.

## Dependent fields
- Use `->live()` with `Get/Set` for conditional visibility; avoid heavy callbacks.
- Show/hide sections based on select/toggle; keep logic in small closures.

## Repeaters/relationship inputs
- Keep columns to 2–3; use `->collapsible()` when many items.
- For relationship selects, use `->relationship()` with `->preload()` and scoped queries; cache options when large.

## Accessibility & microcopy
- Every field needs a label; use helperText for constraints or side effects.
- Limit placeholder usage; do not rely on placeholder as label.

## Layout hints
- Use Section/Grid/Tabs to group logically; avoid nesting beyond 3 levels.
- Prefer slideOver modals for small edit forms; wide modals only for complex payloads.
