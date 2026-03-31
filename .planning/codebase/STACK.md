# Technology Stack Map

## Repository profile

- Primary app type: Laravel monolith with a server-driven admin/workspace UI built around Filament and Livewire.
- Product focus: multi-tenant utility billing, property management, tenant self-service, and superadmin operations.
- Main entrypoint and bootstrap live in `artisan` and `bootstrap/app.php`.
- The repo is already mapped as a Laravel/Filament/Livewire application in `README.md`, while the authoritative dependency manifests are `composer.json` and `package.json`.

## Core languages and frameworks

### PHP / Laravel backend

- PHP requirement is `^8.3` in `composer.json`.
- Laravel framework is installed directly as `laravel/framework:^13.0` in `composer.json`.
- Application bootstrapping uses the newer Laravel bootstrap style in `bootstrap/app.php`:
  - web routes: `routes/web.php`
  - console routes/commands: `routes/console.php`
  - broadcast channels: `routes/channels.php`
  - health endpoint: `/up`
- Application service registration and runtime bootstrapping are centralized in `app/Providers/AppServiceProvider.php` and `app/Providers/AuthServiceProvider.php`.

### Filament admin/workspace shell

- Filament is a direct dependency via `filament/filament:^5.0` in `composer.json`.
- The main panel provider is `app/Providers/Filament/AppPanelProvider.php`.
- The default panel is mounted at `/app` via `->path('app')` in `app/Providers/Filament/AppPanelProvider.php`.
- Filament auto-discovers most admin surface code from:
  - `app/Filament/Resources`
  - `app/Filament/Pages`
  - `app/Filament/Widgets`
- Database-backed Filament notifications are enabled in `app/Providers/Filament/AppPanelProvider.php` via `->databaseNotifications()`.

### Livewire-driven route surface

- Livewire is a first-class runtime layer even though it is not declared directly in the root `composer.json`; evidence is spread across `config/livewire.php`, `app/Livewire/**/*.php`, and `routes/web.php`.
- Public/auth endpoints are implemented as Livewire pages and endpoint-style components in `app/Livewire/Auth`, `app/Livewire/PublicSite`, `app/Livewire/Tenant`, and `app/Livewire/Security`.
- Livewire configuration is in `config/livewire.php`, including:
  - class namespace `App\\Livewire`
  - class path `app/Livewire`
  - view path `resources/views/livewire`
  - Tailwind pagination theme
  - temporary upload support

### Blade views and server-rendered UI

- Blade remains the template layer under `resources/views`.
- Shared layouts and shell pieces live in:
  - `resources/views/layouts/app.blade.php`
  - `resources/views/layouts/guest.blade.php`
  - `resources/views/layouts/public.blade.php`
  - `resources/views/components/shell/*`
- Public/auth pages still render server HTML through Blade views such as `resources/views/auth/login.blade.php` and `resources/views/welcome.blade.php`.

## Frontend asset stack

### Vite

- Frontend bundling is handled by Vite via `vite.config.js`.
- Root frontend manifest is `package.json` with only two scripts: `dev` and `build`.
- Vite input files are declared in `vite.config.js`:
  - `resources/css/app.css`
  - `resources/js/app.js`

### Tailwind CSS v4

- Tailwind is installed as `tailwindcss:^4.0.0` in `package.json`.
- Tailwind Vite integration is `@tailwindcss/vite:^4.0.0` in `package.json`.
- The project uses CSS-first Tailwind v4 conventions in `resources/css/app.css`:
  - `@import 'tailwindcss'`
  - `@theme` custom tokens
  - `@source` scanning rules
  - custom `@utility` definitions

### JavaScript footprint

- The JavaScript layer is intentionally light.
- `resources/js/bootstrap.js` installs Axios and the standard `X-Requested-With` header.
- `resources/js/app.js` handles form submission UX, demo-account autofill, and a small amount of Livewire browser event dispatching.
- There is no separate SPA framework manifest such as React/Vue/Svelte in the root `package.json`.

## Runtime configuration defaults

### Application and locale

- Base app settings are in `config/app.php`.
- Supported locales are defined directly in `config/app.php` and mirrored in `config/tenanto.php`.
- Translation files are organized under `lang/en`, `lang/lt`, `lang/ru`, and `lang/es`.

### Authentication model

- Auth config is session/Eloquent based in `config/auth.php`.
- The only configured guard is `web` with the `session` driver in `config/auth.php`.
- There is no external OAuth/SAML/social login provider configured in `config/auth.php`.
- Access control is role- and policy-driven through `app/Models/User.php`, `app/Providers/AuthServiceProvider.php`, and `app/Policies/*`.

### Database and persistence defaults

- Local default DB connection is `sqlite` in `.env.example` and `config/database.php`.
- Test DB is in-memory SQLite via `phpunit.xml` and `tests/TestCase.php`.
- Alternate connection templates exist for `mysql`, `mariadb`, `pgsql`, and `sqlsrv` in `config/database.php`.
- Redis configuration is available in `config/database.php`, but the default local flow still points to SQLite/database-backed services.

### Session / cache / queue defaults

- Sessions default to the `database` driver in `.env.example` and `config/session.php`.
- Cache defaults to the `database` store in `.env.example` and `config/cache.php`.
- Queue defaults to the `database` connection in `.env.example` and `config/queue.php`.
- Failed jobs use the `database-uuids` driver in `config/queue.php`.

### Filesystem defaults

- Filesystem defaults live in `config/filesystems.php`.
- Default disk is `local`.
- The repo supports `local`, `public`, and optional `s3` disks.
- Public storage linking is declared in `config/filesystems.php` as `public/storage -> storage/app/public`.

## Code organization that matters for planning

- Domain/data models: `app/Models`
- HTTP middleware and requests: `app/Http/Middleware`, `app/Http/Requests`
- Livewire pages/endpoints: `app/Livewire`
- Filament admin pages/resources/widgets/actions: `app/Filament`
- Domain services and operations helpers: `app/Services`
- Notifications: `app/Notifications`
- Queued jobs: `app/Jobs`
- DB migrations/factories/seeders: `database/migrations`, `database/factories`, `database/seeders`
- Feature tests: `tests/Feature`
- Performance tests: `tests/Performance`

Useful examples:

- Multi-tenant user model: `app/Models/User.php`
- Billing orchestration: `app/Services/Billing/BillingService.php`
- Security telemetry: `app/Services/Security/SecurityMonitoringService.php`
- Admin panel wiring: `app/Providers/Filament/AppPanelProvider.php`
- Tenant-facing routes: `routes/web.php`

## Build, test, and developer tooling

### Composer scripts

- `composer.json` defines the main workflows:
  - `setup` for fresh local bootstrap
  - `dev` for concurrent server/queue/log/vite development
  - `test` for Laravel test execution
  - `guard:phase1` for a focused CI-style quality/security suite
- `composer run dev` starts:
  - `php artisan serve`
  - `php artisan queue:listen --tries=1 --timeout=0`
  - `php artisan pail --timeout=0`
  - `npm run dev`

### Testing stack

- Pest is installed via `pestphp/pest:^4.4` and `pestphp/pest-plugin-laravel:^4.0` in `composer.json`.
- PHPUnit is installed via `phpunit/phpunit:^12` in `composer.json` and configured in `phpunit.xml`.
- Pest bootstrap/helpers live in `tests/Pest.php`.
- Test base case lives in `tests/TestCase.php`.
- Example perf coverage is in `tests/Performance/DashboardPerformanceTest.php`.

### Formatting and editor conventions

- Laravel Pint is installed via `laravel/pint:^1.24` in `composer.json`.
- Editor defaults are in `.editorconfig`.
- Git hooks are stored under `.githooks` and installed by `scripts/install-git-hooks.sh`.
- The pre-commit hook updates `CHANGELOG.md` through `scripts/update_changelog.php` and `.githooks/pre-commit`.

### CI / automation

- The checked-in GitHub Actions workflow is `.github/workflows/phase-1-guardrails.yml`.
- CI currently:
  - checks out code
  - sets up PHP 8.5
  - installs Composer deps
  - prepares `.env`
  - runs `composer guard:phase1`

## Operations and local runtime environment

- Herd is the only checked-in MCP integration in `.mcp.json`.
- Boost/editor metadata is present in `boost.json`.
- Runtime ops commands and scheduled tasks are registered in `routes/console.php`.
- Release/ops runbooks exist under `docs/operations`:
  - `docs/operations/backup-restore.md`
  - `docs/operations/release-readiness.md`
  - `docs/operations/phase-1-guardrails-branch-protection.md`

## Practical planning notes

- For UI work, inspect both `app/Filament/*` and `app/Livewire/*`; this repo mixes Filament pages/resources with direct Livewire route endpoints.
- For auth/tenant-boundary changes, start in `bootstrap/app.php`, `config/auth.php`, `app/Providers/AuthServiceProvider.php`, and `app/Models/User.php`.
- For reporting/export work, inspect `app/Services/ExportService.php`, `app/Services/PdfReportService.php`, `app/Jobs/GenerateAdminReportExportJob.php`, and the report pages under `app/Filament/Pages`.
- For environment-sensitive work, assume SQLite/database-backed local defaults first, then check optional Redis/S3/SQS/mail transports in `config/*.php` before introducing new infrastructure assumptions.
