---
name: translation-ui-recheck
description: Detect likely untranslated hardcoded user-facing strings in Filament PHP files, Blade templates, and Livewire views/components. Use when asked to scan for untranslated UI text, hardcoded labels/headings/placeholders/buttons, or to recheck changed Laravel UI files for missing __(), trans(), or @lang() usage.
---

# Translation UI Recheck

Scan only active application UI code, then report likely untranslated human-language strings without editing anything unless separately asked.

## Scope

- Include:
  - `app/Filament/**/*.php`
  - `app/Livewire/**/*.php`
  - `resources/views/**/*.blade.php`
- Exclude:
  - `lang/**`
  - `tests/**`
  - `storage/**`
  - `_old/**`
  - `vendor/**`
  - `database/**`
  - `config/**`

## What to flag

### Blade

Flag likely user-facing literals in:

- visible text nodes
- `placeholder`, `title`, `alt`, `aria-label`, `aria-description`
- echoed string literals like `{{ 'Save changes' }}`

### Filament / Livewire PHP

Flag direct string literals passed to presentation methods such as:

- `label()`
- `heading()`
- `description()`
- `placeholder()`
- `helperText()`
- `tooltip()`
- `modalHeading()`
- `modalDescription()`
- `title()` for notifications and user-facing UI
- `emptyStateHeading()`
- `emptyStateDescription()`

## What to ignore

Never flag:

- strings already wrapped in `__()`, `trans()`, `trans_choice()`, `@lang()`
- translation keys like `superadmin.users.fields.name`
- machine strings in `make()`, `route()`, `routeIs()`, `view()`, `statePath()`, `icon()`, `disk()`, `relationship()`
- URLs, file paths, CSS classes, icon names, event names
- likely internal identifiers such as snake_case, kebab-case, or dot.notation keys
- acronyms/brands when clearly not copy-only problems (`PDF`, `API`, `IBAN`, `SWIFT`, `Laravel`, `Livewire`, `Filament`)

## Confidence levels

- **High**: sentence-like or title-like UI text in visible Blade or known UI methods
- **Medium**: short one-word labels in known UI methods
- **Low**: ambiguous single tokens outside strong UI context

Prefer false negatives over noisy false positives.

## Output format

Return grouped findings only:

1. `High confidence`
2. `Medium confidence`
3. `Ignored examples`

For each finding include:

- file path
- line number
- literal text
- why it was flagged
- suggested wrapper, for example `__('Delete account')`

## Workflow

1. Use `grep` for Blade-visible text and attribute literals.
2. Use `ast_grep_search` or targeted `grep` for PHP UI methods.
3. Read the highest-density files before concluding.
4. Check nearby translation usage to recommend the correct namespace pattern.
5. Report only. Do not auto-fix unless the user explicitly asks.

## Repo convention reminder

When suggesting namespaces, prefer existing domain files and nested keys over inventing new translation files:

- `admin.*`
- `superadmin.*`
- `shell.*`
- `tenant.*`
- `dashboard.*`

## Useful starter searches

- Blade visible text candidates
- Blade translatable attributes
- PHP UI methods with string literals
- Existing nearby `__()` keys in the same module
