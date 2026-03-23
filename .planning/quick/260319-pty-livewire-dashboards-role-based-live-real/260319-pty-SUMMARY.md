# Quick Task 260319-pty: Livewire dashboards role-based live realtime

## Outcome

The repository already contained most of the requested dashboard architecture before this pass:

- role-specific child Livewire dashboards already existed at `app/Livewire/Pages/Dashboard/*`
- `DashboardPage` already exposed computed role-specific builders
- polling intervals were already present on the admin, superadmin, and tenant dashboard Blade views
- broadcast events and service-layer dispatches for invoice finalization and meter reading submission were already implemented
- the dashboard data presenters already loaded through `App\Filament\Support\Dashboard\DashboardCacheService`, which extends `App\Services\DashboardCacheService`

This pass closed the remaining integration gaps so the requested parent dashboard flow is now the live path.

## Work Completed

- restored the missing onboarding routes:
  - `welcome.show`
  - `welcome.store`
- added the missing parent Livewire dashboard view:
  - `resources/views/livewire/pages/dashboard-page.blade.php`
- routed the Filament dashboard page through the shared `DashboardPage` Livewire component instead of duplicating role-dispatch logic in the Filament page view/class
- simplified `App\Filament\Pages\Dashboard` so the role-switching now lives in one place
- updated `DashboardUrlResolver` so tenant-facing shell/error links resolve to the tenant dashboard route
- added `tests/Feature/Livewire/Dashboard/DashboardPageTest.php` to verify:
  - the Filament dashboard route renders the parent Livewire dashboard component
  - the parent component dispatches to the correct dashboard per role

## Verification

- `php artisan config:clear`
- `php artisan test tests/Feature/Livewire/Dashboard/DashboardPageTest.php`
- `php artisan test tests/Feature/Livewire/Dashboard --compact`
- `php artisan test tests/Feature/Auth/RegistrationAndOnboardingTest.php`
- `php artisan test tests/Feature/Auth/AccessIsolationTest.php`
- `php artisan test tests/Feature/Shell/ErrorPagesTest.php`

All targeted verification passed.

## Caveats

- Laravel Boost MCP tools requested in the brief were not available in this session, so `browser-logs` and `database-query` verification could not be performed through Boost.
- The local sqlite file remains unreliable after the earlier malformed-db incident, so no “live data” dashboard verification was attempted against that file.
