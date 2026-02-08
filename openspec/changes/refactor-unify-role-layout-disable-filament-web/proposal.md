# Change: Refactor Unified Role Layout and Disable Filament Web UI

## Why
The current web UI surface is mixed between custom Blade/Livewire pages and Filament panel pages, causing inconsistent UX, route overlap, and role-access complexity. The requested direction is a fully custom web interface for `superadmin`, `admin`, and `manager`, with `tenant` kept on its own dedicated template, while removing Filament interface exposure in browser routes.

## What Changes
- Introduce a single shared custom backoffice layout for `superadmin`, `admin`, and `manager`.
- Keep a separate tenant layout/template and tenant-only navigation patterns.
- Refactor role-based route and middleware mapping into a strict, testable access matrix.
- Remove Filament panel routes and Filament-auth/browser interface surface from public web routing.
- Replace Filament-dependent navigation links with custom Blade/Livewire route targets.
- Add/expand feature tests validating route access for each role and guest users.

## Impact
- **BREAKING**: Filament web panel endpoints and Filament route-name assumptions for browser navigation will be removed.
- Affected specs:
  - `backoffice-unified-layout`
  - `role-route-access-control`
  - `filament-web-surface-removal`
- Affected code (planned):
  - `routes/web.php`
  - `app/Providers/Filament/*.php` and `bootstrap/providers.php`
  - `resources/views/layouts/*.blade.php`
  - role-specific Blade and Livewire views under `resources/views/{superadmin,admin,manager,tenant,...}`
  - feature tests under `tests/Feature/**`
