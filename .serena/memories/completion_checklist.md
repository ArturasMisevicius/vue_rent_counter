# Completion checklist
- Follow AGENTS.md and Laravel Boost guidance before changing code.
- Prefer Eloquent scopes/relations over raw SQL or duplicated controller logic.
- Ensure no queries are introduced in Blade, loops, or Filament column renderers.
- Eager load all relationships needed by views and Filament tables.
- Use Form Requests for validation and Policies / `->authorize()` for protected actions.
- Run the smallest relevant tests first, then `php artisan test --compact` when appropriate.
- Run `vendor/bin/pint --dirty` before finalizing PHP changes.
- If Composer/package state changes, verify package discovery with `composer install` and rerun tests.
- Before claiming success, confirm behavior with real commands instead of assuming.