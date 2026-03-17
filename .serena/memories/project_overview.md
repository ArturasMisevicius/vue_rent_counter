# Tenanto project overview
- Purpose: Laravel application scaffolded as an admin-oriented app using Filament, Blade, Livewire, and SQLite by default.
- Stack: PHP 8.5.x runtime in project guidance, Laravel 12, Filament 5, Livewire 4, Tailwind CSS 4, Pest 4, PHPUnit 12, Laravel Boost/MCP tooling.
- Frontend approach: server-rendered Blade / Livewire / Filament; no React/Vue/Inertia unless explicitly introduced by repo conventions.
- Data layer: Eloquent models only; AGENTS.md explicitly forbids raw SQL in normal application code and emphasizes eager loading, scopes, aggregates, and query optimization.
- Default database: SQLite via `database/database.sqlite` according to README.
- Important structure: `app/` application code, `app/Filament` admin resources/panels, `resources/views` Blade, `routes` route definitions, `tests` Pest tests, `bootstrap/providers.php` app providers.
- Special guidance: thin controllers, Form Requests for validation, Actions/Services for business logic, Policies for authorization, no queries in Blade, explicit selects/scopes, and Filament resources should eager load relationships in `getEloquentQuery()`.