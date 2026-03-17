# Tenanto

Tenanto is a multi-tenant utility billing and property management application built on Laravel 12, Filament 5, and Livewire 4. The current repository is Filament-first for administration and uses Livewire for auth, shell interactions, public-site endpoints, preferences, and tenant self-service screens.

## Verified Stack

Verified from the checked-in manifests and the local CLI environment on 2026-03-17:

- PHP `8.5.4` locally, with Composer requiring PHP `^8.2`
- Laravel `12`
- Filament `5.3`
- Livewire `4`
- Tailwind CSS `4`
- Pest `4`
- PHPUnit `12`
- Alpine.js `3`
- Laravel Sanctum `4`

## Product Shape

- Multi-tenant SaaS for utility billing and property management
- Role enum values: `SUPERADMIN`, `ADMIN`, `MANAGER`, `TENANT`
- Filament foundation directories for requests, actions, and support services:
  - `app/Filament/Requests`
  - `app/Filament/Actions`
  - `app/Filament/Support`
- Current workspace snapshot:
  - 17 Filament resources
  - 27 Livewire components
  - 84 test files
  - 1 remaining base controller
  - 1 Filament panel provider

For a fuller current-state snapshot, see [docs/PROJECT-CONTEXT.md](docs/PROJECT-CONTEXT.md).

## Local Setup

The application is configured for SQLite by default.

1. `composer install`
2. `cp .env.example .env`
3. `php artisan key:generate`
4. `touch database/database.sqlite`
5. `php artisan migrate`
6. `npm install`
7. `npm run build`
8. `php artisan test --compact`

Or use:

```bash
composer run setup
```

For local development:

```bash
npm run dev
php artisan serve
```

## Working Conventions

- Prefer Eloquent models, relationships, and scopes over raw SQL
- Keep validation, actions, and support logic in the Filament foundation tree
- Do not create new classes in `app/Http/Requests`, `app/Actions`, or `app/Support`
- Run focused Pest tests plus `vendor/bin/pint --dirty` for changed behavior
- Treat `docs/superpowers/` as historical execution/design context, not the canonical source for current repository counts

## MCP

The repository-local [`.mcp.json`](.mcp.json) currently configures only the `herd` MCP server. If `laravel-mcp` or `laravel-boost` are available in your editor session, they are coming from user-global configuration rather than this repository file.

## Documentation

- Project context: [docs/PROJECT-CONTEXT.md](docs/PROJECT-CONTEXT.md)
- Delivery plans/specs map: [docs/superpowers/README.md](docs/superpowers/README.md)
- AI assistant/project instructions: [AGENTS.md](AGENTS.md)
