# Change: Migrate Core Pages to Full Livewire Modules

## Why
`profile`, `dashboard`, and `settings` are currently rendered through large shared Blade files with role switching, while similar logic also exists in page-level Livewire classes and role controllers. This mixed rendering model increases duplication, creates role-specific drift, and risks duplicate page output in production.

## What Changes
- Migrate `profile`, `dashboard`, and `settings` page modules to full Livewire-driven rendering and interactions.
- Keep existing URLs, route names, middleware chains, and role-based authorization behavior intact.
- Establish one render path per module route (no controller+Blade wrapper duplication for these modules).
- Normalize module entry views to shared page-type Blade entry files backed by Livewire components.
- Add role-matrix feature tests for module rendering and actions.

## Impact
- Affected specs:
  - `livewire-core-page-modules`
- Affected code:
  - `routes/web.php`
  - `app/Livewire/Pages/**`
  - `app/Http/Controllers/{Admin,Superadmin,Manager,Tenant}/*{Dashboard,Profile,Settings}Controller.php`
  - `resources/views/pages/{profile,dashboard,settings}/*.blade.php`
  - `tests/Feature/**`
