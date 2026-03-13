---
name: tenanto-lang-migration
description: Convert hardcoded user-facing strings in Tenanto to Laravel translations in `lang/*` (labels, placeholders, values, helper text, headings, notifications, validation text, and Blade content). Use when making UI text multilingual or replacing hardcoded strings with translation keys.
allowed-tools: Read, Write, Edit, Glob, Grep, Bash
---

# Tenanto Lang Migration

## Use This Skill

- Migrate hardcoded strings to translation keys under `lang/en`, `lang/lt`, and `lang/ru`.
- Replace labels, placeholders, helper text, values, headings, button text, table text, notifications, and validation messages.
- Keep locale files synchronized and prevent new hardcoded UI strings.

## Project Anchors

- Source code:
  - `app/`
  - `resources/views/`
  - `routes/`
- Translation files:
  - `lang/en/*.php`
  - `lang/lt/*.php`
  - `lang/ru/*.php`
  - `lang/locales.php`

## Execution Workflow

1. Run scanner to find likely hardcoded user-facing strings:
   - `python .agent/skills/tenanto-lang-migration/scripts/scan_hardcoded_strings.py --root . --fail-on-findings`
2. Group findings by feature (for example: `meters`, `invoices`, `dashboard`, `superadmin`).
3. Search existing translation keys first and reuse the same key when value/meaning already exists (avoid duplicate values under new keys).
4. Add keys to `lang/en/<feature>.php` first, then mirror the same key structure in `lang/lt/<feature>.php` and `lang/ru/<feature>.php`.
5. Replace hardcoded strings in code with translation calls.
6. Re-run scanner until findings are reduced to acceptable exceptions.
7. Run focused localization tests:
   - `php artisan test --compact --filter=Localization`
   - `php artisan test --compact --filter=Translation`
8. If PHP files changed, run formatting:
   - `vendor/bin/pint --dirty --format agent`

## Key Naming Rules

- Use feature-scoped keys, not global generic keys.
- Keep stable namespaces by UI intent:
  - `labels.*`
  - `placeholders.*`
  - `helper_text.*`
  - `headings.*`
  - `actions.*`
  - `messages.*`
  - `notifications.*`
  - `validation.*`
  - `values.*`
- Prefer snake_case segments.
- Keep identical key paths across all locales.
- Reuse keys for identical meaning/value; do not create multiple keys for the same text in the same context.
- If the same text is used in multiple modules, prefer a shared key namespace rather than duplicating feature-local keys.

## Replacement Patterns

### PHP / Filament / Livewire

```php
TextInput::make('title')
    ->label(__('notifications.form.title_label'))
    ->placeholder(__('notifications.form.title_placeholder'));

Notification::make()
    ->title(__('notifications.send.success_title'))
    ->body(__('notifications.send.success_body'));
```

### Blade

```blade
<h2>{{ __('dashboard.headings.overview') }}</h2>
<button>{{ __('dashboard.actions.refresh') }}</button>
```

### Interpolated values

```php
__('invoices.messages.generated', ['count' => $count])
```

### Enum and option values

- Keep stored values unchanged.
- Translate display labels only.
- For option arrays, use translation values:

```php
->options([
    'all' => __('shared.values.all'),
    'active' => __('shared.values.active'),
])
```

## Do Not Translate

- Route names, policy names, config keys, migration column names, and database enum values.
- API payload keys and machine identifiers.
- Validation rule syntax strings such as `required|string|max:255` unless shown as user-facing help text.
- Log-only messages that are not rendered to users.

## Completion Checklist

- [ ] All changed UI strings use translation keys.
- [ ] New keys exist in `en`, `lt`, and `ru` with matching structure.
- [ ] Duplicate translation values were avoided by reusing existing keys where meaning matches.
- [ ] Scanner run completed and reviewed.
- [ ] Localization/translation tests pass for touched behavior.
