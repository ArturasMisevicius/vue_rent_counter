---
inclusion: always
---

# Translation Implementation Guide

## Overview
- Supported locales: English (`en`, base/fallback) and Lithuanian (`lt`) (`config/locales.php`).
- Static UI translations live in `resources/lang/{locale}` (PHP groups are the source of truth; JSON files are only for string-based lookups). Includes vendor + Filament overrides.
- Dynamic content translations use the `Translatable` trait and the `translations` table for models like `Activity`, `FaqCategory`, `FaqItem`, and `UserActivity`.
- Locale is stored in session via `App\Http\Middleware\SetLocale` and switched through `App\Http\Controllers\LanguageController@switch`.

## Locale Configuration
- `config/locales.php` lists available locales (`available`) and the fallback (`fallback`). Labels use translation keys (`common.english`, `common.lithuanian`).
- `App\Support\Localization` exposes `availableLocales()`, `fallbackLocale()`, and `currentLocale()`, which the language switcher components consume.
- `GET /language/{locale}` (`route('language.switch')`) writes the locale to session, applies it to the current request, and tries to redirect back to the previous/Filament page.
- Switcher UI lives in `resources/views/panels/components/topbar-language-switcher.blade.php` and `resources/views/components/language/toggle.blade.php` (also embedded in the user layout/navigation Blade files).

## Static Translation Files
### Layout
- Domain files per locale in `resources/lang/{locale}/*.php` (examples: `common.php`, `invoice.php`, `expense.php`, `tax_declaration.php`, `dashboard.php`, `guest.php`, `content_page.php`, `numbers.php`).
- Filament-specific overrides live in `resources/lang/{locale}/filament*` and `resources/lang/vendor/*` (already synced for `lt`).
- JSON translations for string-based lookups (e.g., Filament/Blade calls that pass the literal string) live in `resources/lang/lt.json`; add `{locale}.json` when introducing new languages.
- `numbers.php` powers `App\Services\Localization\NumberToWordsService`; keep currencies and grammar complete when adding locales.

### Key Conventions
- Use `snake_case` keys and nest by concern (sections/actions/messages/etc). See `docs/admin/translation-keys-snake-case-migration.md` for rationale.
- Keep strings in the relevant domain file (avoid duplicating keys across `common.php` and feature files).
- Common namespaces: `app.labels.*`, `app.actions.*`, `app.navigation.*`, `app.helpers.*`, `app.modals.*`, `requests.*` (validation), `notifications.*`, `table.*`.
- Always call `__()` or `trans_choice()` with the full path:
  ```php
  // resources/lang/en/invoice.php
  return [
      'navigation' => 'Invoices',
      'fields' => [
          'number' => 'Invoice Number',
          'issue_date' => 'Issue Date',
      ],
  ];

  // Usage (Blade/PHP/Filament)
  ->label(__('invoice.fields.number'))
  ```

## Filament Usage Patterns
- Never hardcode user-facing strings in Filament resources/pages/widgets/actions/notifications; set labels/placeholders/descriptions/headings with translation keys.
- For enums, prefer `->getLabel()` returning `__()` so tables/forms stay translated.
- When touching inline text, replace with translation keys while you are there.

### Adding or Updating UI Strings
1) Add the English key/value to the correct file under `resources/lang/en/`.
2) Add the Lithuanian equivalent to the matching file under `resources/lang/lt/` (and `{locale}.json` if you referenced the raw string).
3) Reference the key with `__()`/`trans_choice()` in code (no hardcoded UI text).
4) Run localization tests (see below) and clear caches if needed (`php artisan optimize:clear`).

## Dynamic Translations (Database)
- Models that translate user-managed content use `App\Support\Translatable` with the `translations` table.
- Common pattern in Filament forms: hydrate `translations[field][locale]` in `mutateFormDataBeforeFill`, keep fields `dehydrated(false)`, and persist via `setTranslations()` in `afterSave/afterCreate`.
- Reference: `docs/functionality/multilanguage-system.md` for full trait usage and Filament examples.

## Language Switching UX
- UI components rely on `config/locales.php` for labels/abbreviations; add new locales there to surface them automatically.
- Switchers use `common.language`, `common.english`, `common.lithuanian`, etc. Ensure these keys exist for every locale.
- Session-backed locale persists across panels; `LanguageController` preserves Filament URLs when possible.

## Testing & QA
- `tests/Feature/LocalizationTest.php` verifies base PHP/vendor translations resolve.
- `tests/Unit/Localization/FilamentTranslationTest.php` scans Filament classes/enums/views and fails if any referenced key is missing in Lithuanian (reads PHP + JSON + vendor translations).
- Useful commands:
  ```bash
  php artisan test --filter=LocalizationTest
  php artisan test --filter=FilamentTranslationTest
  ```
- Manual checklist when touching translations:
  - No inline strings in Filament resources/pages/widgets/actions/notifications.
  - Modal headings/descriptions, empty states, table column labels, filter labels, and bulk action labels use translation keys.
  - English remains the base locale; structure stays mirrored when adding locales.

## Adding a New Locale
1) Add the locale to `config/locales.php` with `label` (translation key) and `abbreviation`.
2) Create `resources/lang/{locale}/` by copying `resources/lang/en/` and translating all values (include `numbers.php` for number-to-words support).
3) Create `resources/lang/{locale}.json` for string-based lookups and mirror any vendor overrides under `resources/lang/vendor/`.
4) Add `common.{language_name}` translations so switcher labels resolve.
5) Verify the switcher shows the locale and rerun the localization tests.

## References
- Static translations: `resources/lang/{en,lt}/`, `resources/lang/{en,lt}.json`, `resources/lang/vendor/`
- Locale config: `config/locales.php`, `App\Support\Localization`, `App\Http\Middleware\SetLocale`, `App\Http\Controllers\LanguageController`
- Dynamic translations: `App\Support\Translatable`, `app/Models/Translation`, `docs/functionality/multilanguage-system.md`
- Key conventions: `docs/admin/translation-keys-snake-case-migration.md`

