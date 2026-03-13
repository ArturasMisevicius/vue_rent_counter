# Change: Remove API Surface and Move Interactions to Livewire/Filament

## Why
The project currently includes a JSON API layer and frontend code that depends on `/api/*` requests. The requested architecture is web-only interactions using Livewire and Filament, without API routes or API controllers.

## What Changes
- Disable API route registration from application bootstrap.
- Remove dedicated API route files and API controllers.
- Replace manager meter-reading form API fetch flow with a Livewire component.
- Update tests to validate Livewire form behavior and the absence of API endpoints.

## Impact
- **BREAKING**: All `/api/*` endpoints are removed.
- Affected specs:
  - `web-interaction-surface`
- Affected code:
  - `bootstrap/app.php`
  - `routes/api.php`, `routes/api-security.php`, `routes/api_v1_validation.php`
  - `app/Http/Controllers/Api/**`
  - manager meter-reading form views/components and related tests
