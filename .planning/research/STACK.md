# Stack Research

**Domain:** Brownfield Laravel multi-tenant utility billing and property management SaaS modernization
**Researched:** 2026-03-19
**Confidence:** HIGH

## Recommended Stack

The standard current approach for a cleanup like Tenanto is not a rewrite. It is a Laravel monolith standardized around Laravel 12, Filament 5, Livewire 4, Tailwind 4, Redis-backed background work, Pest 4, and aggressive static analysis. The goal is to reduce stack variance, not add another frontend or tenancy abstraction layer.

Pragmatically, that means:
- keep the UI PHP-first with Filament + Livewire + Blade
- standardize one production database and one cache/queue backplane
- use first-party Laravel packages where they fit cleanly
- add third-party packages only where they remove real domain risk, such as money math or RBAC drift

### Core Technologies

| Technology | Version | Purpose | Why Recommended | Confidence |
|------------|---------|---------|-----------------|------------|
| PHP | 8.4 as team default; validate 8.5 separately | Runtime baseline | Laravel 12 supports PHP 8.2-8.5, but Pest 4 already requires PHP 8.3+. PHP 8.4 is the conservative modern target for a brownfield cleanup: new enough for current tooling, safer than standardizing immediately on the newest branch. | HIGH |
| Laravel | 12.x | Application framework | Laravel 12 is the current mainstream base for modernization work and its release notes emphasize minimal breaking changes. That makes it the right standardization target for a brownfield app that needs consolidation more than reinvention. | HIGH |
| Filament | 5.x | Admin and operator workspace UI | Filament 5 is the current stable panel builder, explicitly targeting PHP 8.2+, Laravel 11.28+, and Tailwind 4.1+. It is the best fit for CRUD-heavy SaaS surfaces like organizations, properties, meters, invoices, workflows, and dashboards. | HIGH |
| Livewire + Blade | Livewire 4.x + Blade SSR | Interactive tenant, auth, and public flows | Livewire 4 keeps the app PHP-first, works directly with Blade, bundles Alpine by default, and integrates cleanly with Filament. For a brownfield standardization effort, that is a better trade than introducing a JS SPA rewrite. | HIGH |
| Tailwind CSS + Vite | Tailwind 4.x + `@tailwindcss/vite` | Styling and asset build pipeline | Tailwind 4 simplifies installation, improves build speed, and aligns with Filament 5's Tailwind 4.1+ requirement. The first-party Vite plugin is now the cleanest default for Laravel projects. | HIGH |
| Database | PostgreSQL 16/17 preferred; MySQL 8.4 LTS acceptable | Primary relational datastore | Standardize on one production RDBMS. Prefer PostgreSQL if database standardization is still open because it gives the cleanest long-term footing for reporting, indexing, JSON queries, and future tenant-isolation options. If the brownfield app is already solid on MySQL, do not make an engine migration part of cleanup; fix schema and indexes first. | MEDIUM |
| Redis | 7.x | Cache, queues, locks, rate limiting | Redis is the standard operational backplane once the app has real jobs, exports, reminders, notifications, or queue monitoring. Horizon requires Redis, which makes Redis the practical default for serious Laravel SaaS operations. | HIGH |
| Pest + PHPUnit | Pest 4 + PHPUnit 12 | Automated test suite | Livewire 4 recommends Pest, and Pest 4 now includes first-party browser testing powered by Playwright. This is the standard testing stack for current Laravel + Livewire applications. | HIGH |
| Sanctum | Latest Laravel 12-compatible release | API token and SPA auth layer | Sanctum is the right first-party auth add-on when the app needs API tokens or a same-origin API. It is simpler than OAuth and should complement normal session auth, not replace Blade or Filament's standard web auth flow. | HIGH |

### Supporting Libraries

| Library | Version | Purpose | When to Use | Confidence |
|---------|---------|---------|-------------|------------|
| `spatie/laravel-permission` | v7 | Roles and permissions backed by the database | Use when the cleanup needs one canonical role and permission system across policies, Blade directives, Filament actions, and optional multi-guard access. This is the cleanest way to stop RBAC drift. | HIGH |
| `brick/money` | Current stable | Exact money calculations | Use for tariffs, invoice totals, prorations, adjustments, credits, fees, and any domain logic where float math is unacceptable. Billing cleanup without a money library usually leaves precision bugs behind. | HIGH |
| `laravel/pennant` | Latest Laravel 12-compatible release | Feature flags | Use to roll out refactored billing calculators, search backends, tenant navigation changes, or new authorization paths incrementally instead of forcing a big-bang switch. | HIGH |
| `laravel/scout` | Latest Laravel 12-compatible release | Search abstraction | Use when search is becoming a real product surface. Start with Scout's `database` engine for brownfield cleanup, then move to Typesense only if typo tolerance, faceting, or more advanced relevance becomes important. | HIGH |
| `laravel/horizon` | Latest Laravel 12-compatible release | Queue supervision and monitoring | Use once the app standardizes production queues on Redis. Horizon is the standard choice for Laravel queue visibility and worker control. | HIGH |
| `laravel/telescope` | Latest Laravel 12-compatible release | Deep local and staging diagnostics | Use during cleanup to inspect queries, jobs, mail, requests, exceptions, and model events. Keep it out of the public production surface. | HIGH |
| `laravel/pulse` | Latest Laravel 12-compatible release | App performance and usage dashboards | Use when you want lightweight ongoing observability for slow endpoints, slow jobs, queue throughput, and operational regressions after modernization. | HIGH |
| `stancl/tenancy` | v4 | Full tenancy package | Do not make this the default cleanup move. Use it only if the roadmap deliberately introduces custom tenant domains, automatic tenant bootstrapping, or per-tenant databases. | MEDIUM |

### Development Tools

| Tool | Purpose | Notes | Confidence |
|------|---------|-------|------------|
| Laravel Pint | Code style enforcement | Pint is automatically installed with new Laravel apps and should be the baseline formatter for touched code. Use it as a CI gate, not an optional cleanup step. | HIGH |
| PHPStan + Larastan | Static analysis | This is the core standardization toolchain for a brownfield Laravel codebase. Raise levels gradually, but make it mandatory in CI so controller, policy, request, enum, and relation drift stops accumulating. | HIGH |
| Rector | Mechanical modernization | Use Rector for low-risk refactors: type declarations, dead code cleanup, legacy syntax upgrades, and repetitive framework migrations. Do not use it as a substitute for architectural review. | MEDIUM |
| Pest browser testing + Playwright | Critical flow verification | Use browser tests only for tenant login, meter submission, invoice viewing, billing actions, and role-gated panel flows. Keep the rest in fast feature and unit tests. | HIGH |
| CI pipeline | Lint + static analysis + tests on every PR | GitHub Actions is the common default, but the exact runner is less important than enforcing the same gates everywhere: Pint, PHPStan/Larastan, Pest, and any smoke browser tests. | MEDIUM |
| Composer + Node LTS | Package and asset management | Use Composer for PHP dependencies and a current Node LTS for Vite, Tailwind, and Playwright. Standardize versions across local, CI, and production build images. | MEDIUM |

## Installation

Treat this as the target package set for a standardized Laravel monolith, not as a copy-paste upgrade plan for the existing repo:

```bash
# Core platform
composer require filament/filament:^5.0 livewire/livewire:^4.0 laravel/sanctum laravel/pennant laravel/scout spatie/laravel-permission:^7.0 brick/money

# Ops
composer require laravel/horizon laravel/pulse

# Dev and diagnostics
composer require --dev larastan/larastan phpstan/phpstan rector/rector laravel/telescope pestphp/pest:^4.0 pestphp/pest-plugin-browser

# Frontend tooling
npm install -D tailwindcss @tailwindcss/vite vite playwright
```

## Alternatives Considered

| Recommended | Alternative | When to Use Alternative |
|-------------|-------------|-------------------------|
| Blade + Livewire + Filament | Inertia 2 + React starter kit | Only use the SPA stack if the roadmap explicitly calls for a client-heavy public app, offline-rich UX, or frontend specialization that Filament + Livewire cannot reasonably support. Not for cleanup by default. |
| Eloquent scopes + policies + org context | Filament tenancy | Use Filament tenancy when users genuinely belong to multiple organizations and need tenant switching in the panel UI. |
| No tenancy package by default | `stancl/tenancy` v4 | Use a dedicated tenancy package only when the architecture is moving to custom domains, automatic tenant identification, or multi-database tenancy. |
| Scout `database` engine | Typesense via Scout | Use Typesense when search becomes fuzzy, faceted, typo-tolerant, or user-facing enough that database search is no longer good enough. |
| Redis + Horizon | Database queue | Use database queues only for local development or very small deployments where queue throughput and worker visibility are not operational concerns. |
| PostgreSQL 16/17 | MySQL 8.4 LTS | Use MySQL if the brownfield production estate already runs there cleanly and a database-engine migration would slow down the cleanup roadmap more than it helps. |

## What NOT to Use

| Avoid | Why | Use Instead | Confidence |
|-------|-----|-------------|------------|
| `php artisan filament:install --scaffold` on an existing app | Filament's docs explicitly warn that scaffolding overwrites modified files and is only suitable for new Laravel projects. It is the wrong move in a brownfield modernization. | Manual Filament install and upgrade steps. | HIGH |
| Laravel Breeze or Jetstream as the modernization target | Laravel 12 release notes state that Breeze and Jetstream will no longer receive additional updates. Adding them now increases churn without buying a future-proof path. | Keep the existing auth flow, or selectively adopt current starter-kit patterns only where they truly simplify a new surface. | HIGH |
| `laravel/passport` for first-party admin or tenant APIs | Sanctum exists specifically to issue personal access tokens without OAuth complexity. Passport is justified only when you are actually acting as an OAuth server. | Sanctum. | HIGH |
| Scout `collection` engine for real production search | Laravel's docs say it is meant for prototypes, tiny datasets, or tests, and that it is significantly less efficient than the database engine. | Scout `database` engine first, then Typesense if needed. | HIGH |
| Filament tenancy for simple one-to-many organization scoping | Filament's tenancy docs say that if your case is simpler and not many-to-many, you do not need tenancy and can use observers and global scopes instead. | Eloquent scopes, policies, and org context services. | HIGH |
| Manual Livewire asset publishing or manual bundling by default | Livewire 4's docs say most applications do not need published assets or manual bundling. Making this the default just adds build surface area. | Standard Livewire install with automatic asset injection unless a real routing or CDN requirement exists. | HIGH |
| A React or Inertia rewrite as part of cleanup | This does not solve the core brownfield problems of tenant scoping, billing correctness, policy consistency, or architectural drift. It adds migration risk. | Keep the app PHP-first with Blade, Livewire, and Filament. | MEDIUM |

## Stack Patterns by Variant

**If the app remains single-database and row-scoped:**
- Use Eloquent scopes, policies, and organization context services as the default tenancy model.
- Keep Filament tenant handling simple; do not introduce a full tenancy package.
- Because this preserves brownfield stability while still standardizing boundaries.

**If users belong to multiple organizations and need to switch context in the panel:**
- Use Filament 5 tenancy.
- Because Filament's tenancy model is designed for many-to-many tenant membership and panel switching.

**If the roadmap introduces custom domains or per-tenant databases:**
- Use `stancl/tenancy` v4.
- Because its documented model explicitly covers both single-database and multi-database tenancy and is better suited to automatic tenant bootstrapping than ad hoc app code.

**If search is mostly admin lookup and filtered retrieval:**
- Use `laravel/scout` with the `database` driver.
- Because the official docs say this is all many applications need and it avoids new infrastructure.

**If search becomes product-critical and fuzzy or faceted:**
- Use Scout with Typesense.
- Because that is the point where extra search infrastructure starts paying for itself.

**If the app has meaningful background work:**
- Use Redis queues with Horizon and Pulse.
- Because exports, reminders, notifications, and billing jobs become operationally visible and manageable instead of opaque.

## Version Compatibility

| Package A | Compatible With | Notes |
|-----------|-----------------|-------|
| Laravel 12.x | PHP 8.2-8.5 | Practical brownfield target should still be PHP 8.4 because Pest 4 requires 8.3+ and 8.4 is the safer standardization point. |
| Filament 5.x | PHP 8.2+, Laravel 11.28+, Tailwind 4.1+ | Clean fit on Laravel 12. |
| Livewire 4.x | PHP 8.1+, Laravel 10+ | Pairs cleanly with Laravel 12, Blade, and Filament 5. |
| Pest 4 | PHP 8.3+ | This effectively raises the minimum useful CI and local test runtime above Laravel's minimum. |
| Horizon | Redis | Do not plan Horizon on top of database queues. |
| Scout `database` engine | MySQL / PostgreSQL full-text or `LIKE` search | Use `collection` only for prototypes, tiny datasets, or tests. |

## Sources

- https://laravel.com/docs/12.x/releases - Laravel 12 PHP support matrix, starter-kit direction, and Breeze/Jetstream deprecation signal
- https://laravel.com/docs/12.x/starter-kits - current Laravel starter-kit direction and Livewire stack positioning
- https://laravel.com/docs/12.x/sanctum - Sanctum token and SPA auth model
- https://laravel.com/docs/12.x/pint - Pint as first-party Laravel formatting
- https://laravel.com/docs/12.x/horizon - Horizon and Redis requirement
- https://laravel.com/docs/12.x/telescope - request, query, job, and exception inspection
- https://laravel.com/docs/12.x/pulse - runtime performance and usage observability
- https://laravel.com/docs/12.x/pennant - first-party feature flags
- https://laravel.com/docs/12.x/scout - database search engine guidance and collection-engine limitations
- https://filamentphp.com/docs/5.x/introduction/installation - Filament 5 requirements and brownfield-safe installation guidance
- https://filamentphp.com/docs/5.x/introduction/overview - Filament as a server-driven Laravel UI layer built on Livewire, Alpine, and Tailwind
- https://filamentphp.com/docs/5.x/users/tenancy - when to use Filament tenancy and when simple scopes/observers are enough
- https://livewire.laravel.com/docs/4.x/installation - Livewire 4 prerequisites, zero-config install, automatic assets, multi-tenant route customization
- https://livewire.laravel.com/docs/4.x/testing - Pest recommendation and browser testing guidance
- https://tailwindcss.com/blog/tailwindcss-v4 - Tailwind 4 installation, Vite plugin, and performance improvements
- https://pestphp.com/docs/support-policy - Pest 4 PHP compatibility
- https://spatie.be/docs/laravel-permission/v7/introduction - current Spatie RBAC package direction
- https://github.com/brick/money - money-value-object library for exact calculations
- https://v4.tenancyforlaravel.com/what-is-multitenancy/ - tenancy-model tradeoffs and when a dedicated package is justified

---
*Stack research for: brownfield Laravel multi-tenant utility billing and property management SaaS modernization*
*Researched: 2026-03-19*
