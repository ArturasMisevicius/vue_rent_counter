# Technology Stack

**Analysis Date:** 2026-03-19

## Languages

**Primary:**
- PHP `^8.2` is required in `composer.json`, and the current local CLI/runtime is PHP `8.5.4` from `php -v`. Application code lives in `app/`, routes in `routes/`, config in `config/`, and tests in `tests/`.

**Secondary:**
- Blade templates power the server-rendered UI in `resources/views/`.
- JavaScript ES modules provide light client behavior from `resources/js/app.js` and `resources/js/bootstrap.js`.
- CSS is authored through Tailwind CSS in `resources/css/app.css`.

## Runtime

**Environment:**
- PHP `8.5.4` runs the Laravel application locally, and `php artisan --version` reports Laravel `12.54.1`.
- Node.js `22.22.1` is used for Vite/Tailwind asset builds, with npm `10.9.4` from the local CLI.
- The browser runtime is Blade + Filament + Livewire; no React, Vue, or Inertia entrypoint is present in `resources/`, `package.json`, or `vite.config.js`.

**Package Manager:**
- Composer `2.9.5` manages PHP packages, with `composer.lock` present at `composer.lock`.
- npm `10.9.4` manages frontend tooling, with `package-lock.json` present at `package-lock.json`.

## Frameworks

**Core:**
- Laravel `12.54.1` from `composer.lock` and `php artisan --version` is the application framework, configured through `bootstrap/app.php` and `config/*.php`.
- Filament `5.3.5` from `composer.lock` provides the admin panel, resources, pages, and widgets under `app/Filament/Resources/`, `app/Filament/Pages/`, and `app/Filament/Widgets/`, with the panel configured in `app/Providers/Filament/AppPanelProvider.php`.
- Livewire `4.2.1` from `composer.lock` powers interactive auth, public, shell, profile, and tenant pages under `app/Livewire/`, `routes/web.php`, `routes/web/guest.php`, `routes/web/authenticated.php`, and `routes/web/logout.php`.
- Blade is the server-side templating layer used in `resources/views/layouts/public.blade.php`, `resources/views/layouts/guest.blade.php`, `resources/views/components/shell/app-frame.blade.php`, and related components.
- `erag/laravel-pwa` `2.0.0` from `composer.lock` is installed, with shipped PWA assets in `public/manifest.json`, `public/sw.js`, and `public/offline.html`, and rendered output verified by `tests/Feature/Public/PwaIntegrationTest.php`.

**Testing:**
- Pest `4.4.2` from `composer.lock` is the primary test API used throughout `tests/`.
- `pestphp/pest-plugin-laravel` `4.1.0` from `composer.lock` integrates Pest with Laravel in `tests/Pest.php`.
- PHPUnit `12.5.12` from `composer.lock` and `phpunit.xml` provides the underlying test runner.

**Build/Dev:**
- Vite `7.3.1` from `package-lock.json` builds frontend assets via `vite.config.js`.
- `laravel-vite-plugin` `2.1.0` from `package-lock.json` wires Vite to Laravel entrypoints in `vite.config.js`.
- Tailwind CSS `4.2.1` and `@tailwindcss/vite` `4.2.1` from `package-lock.json` provide styling for `resources/css/app.css`.
- `axios` `1.13.6` from `package-lock.json` is bootstrapped in `resources/js/bootstrap.js` for XHR defaults.
- `concurrently` `9.2.1` from `package-lock.json` is used by the `dev` script in `composer.json` to run the Laravel server, queue listener, log tailer, and Vite together.

## Key Dependencies

**Critical:**
- `laravel/framework` `12.54.1` in `composer.lock` drives HTTP routing, Eloquent, notifications, queues, sessions, cache, broadcasting, and filesystem behavior configured in `config/*.php`.
- `filament/filament` `5.3.5` in `composer.lock` is the primary admin/application UI surface in `app/Providers/Filament/AppPanelProvider.php` and `app/Filament/*`.
- `livewire/livewire` `4.2.1` in `composer.lock` is the interactive page/component layer used across `app/Livewire/*` and `routes/web.php`.
- `erag/laravel-pwa` `2.0.0` in `composer.lock` underpins the shipped PWA manifest and service worker in `public/manifest.json` and `public/sw.js`.
- Tailwind CSS `4.2.1` in `package-lock.json` defines the utility-first styling pipeline used by `resources/css/app.css`.

**Infrastructure:**
- `laravel-vite-plugin` `2.1.0` in `package-lock.json` exposes the `@vite` asset pipeline used in `resources/views/layouts/public.blade.php`, `resources/views/layouts/guest.blade.php`, and `resources/views/components/shell/app-frame.blade.php`.
- `laravel/pail` `1.2.6` in `composer.lock` is part of the local development loop and is invoked by the `dev` script in `composer.json`.
- No direct package install for `laravel/sanctum`, `laravel/boost`, or `laravel/mcp` is present in `composer.lock`.

## Configuration

**Environment:**
- Runtime configuration is env-driven through `config/app.php`, `config/database.php`, `config/filesystems.php`, `config/mail.php`, `config/cache.php`, `config/queue.php`, `config/session.php`, and `config/logging.php`.
- The repository contains `.env` and `.env.example`, but those files were not read; current effective defaults instead come from `php artisan about` and the checked-in config files.
- Current local drivers from `php artisan about` are: broadcasting `log`, cache `database`, database `sqlite`, logs `stack / single`, mail `log`, queue `database`, and session `database`.
- Project-specific application settings live in `config/tenanto.php`; PWA settings live in `config/pwa.php`; repo-local MCP wiring lives in `.mcp.json`; AI/developer tooling metadata lives in `boost.json`.

**Build:**
- `vite.config.js` defines the asset build around `resources/css/app.css` and `resources/js/app.js`.
- `resources/css/app.css` imports Tailwind plus Filament package CSS from `vendor/filament/*/resources/css/index.css`.
- `composer.json` defines `setup`, `dev`, and `test` scripts for local bootstrapping and verification.
- A checked-in Vite build manifest exists at `public/build/manifest.json`.

## Platform Requirements

**Development:**
- Local PHP, Composer, Node.js, and npm are required to run the repository as configured by `composer.json`, `package.json`, and `vite.config.js`.
- The default local data store is SQLite through `config/database.php`, with the checked-in database file at `database/database.sqlite`.
- Tests use in-memory SQLite and array/log drivers through `phpunit.xml`.
- No Dockerfile, `docker-compose*.yml`, `.nvmrc`, `.node-version`, `.php-version`, or `.tool-versions` file was found at the repo root.
- Local Herd tooling is clearly integrated via `.mcp.json`, and `php artisan about` reports the local application URL as `tenanto.test`.

**Production:**
- No deployment platform is declared in the repository. No files were found under `.github/workflows/`, and no Docker or platform-specific deployment manifests were detected at the repo root.
- `app/Providers/AppServiceProvider.php` forces HTTPS in production.
- `app/Services/Security/CspHeaderBuilder.php` emits CSP headers and includes a `report-uri` for `/csp/report`.
- Built assets are expected from the Vite output referenced by `public/build/manifest.json`, and PWA assets are served from `public/manifest.json`, `public/sw.js`, and `public/offline.html`.

---

*Stack analysis: 2026-03-19*
*Update after major dependency changes*
