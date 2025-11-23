# Project Structure

## Directory Organization

### Application Code (`app/`)

```
app/
├── Console/Commands/      # Artisan commands (CreateAdminUser, ResetDemoApp)
├── Contracts/             # Interfaces (Paginatable, TableDisplayable)
├── Enums/                 # Enums (CommentStatus)
├── Http/
│   ├── Controllers/       # Public controllers (Category, Comment, Post, News)
│   ├── Middleware/        # SecurityHeaders, SetLocaleFromSession, etc.
│   └── Requests/          # Form requests (Store*, Update*, *IndexRequest)
├── Jobs/                  # Queue jobs (RunPostExport)
├── Livewire/Concerns/     # Shared traits (ManagesSearch, ManagesSorting, etc.)
├── Models/                # Eloquent models (Post, Category, Comment, User)
├── Policies/              # Authorization policies (PostPolicy, CommentPolicy)
├── Scopes/                # Global query scopes (PublishedScope)
├── Services/              # NewsFilterService and supporting services
├── Support/               # AdminConfig, DesignTokens, PageSize helpers
└── View/Components/       # Blade components (AppLayout, PostCard, etc.)
```

### Views (`resources/views/`)

```
resources/views/
├── auth/                  # Authentication views
├── categories/            # Category CRUD views
├── comments/              # Comment views
├── components/            # Blade components
│   ├── admin/             # Admin-specific components
│   ├── navigation/        # Navigation components
│   └── ui/                # Shared UI components
├── livewire/              # Livewire Volt components
│   ├── admin/             # Admin dashboard/posts/categories/users/comments
│   ├── categories/        # Category management
│   ├── post/              # Post-related components
│   └── posts/             # Posts index
├── news/                  # News/archive views
└── post/                  # Post display views
```

### Frontend Assets (`resources/`)

```
resources/
├── css/
│   └── app.css            # Tailwind entry
└── js/
    ├── admin-optimistic-ui.ts    # Optimistic UI implementation
    ├── admin-post-actions.ts     # Admin post actions
    ├── app.ts                    # Main JS entry
    ├── bootstrap.ts              # Axios/Alpine boot
    └── types/                    # TypeScript definitions
```

### Configuration (`config/`)

Key config files:
- `blog.php` - Blog features (tags, comments, demo mode, EasyMDE)
- `interface.php` - Admin UI settings (debounce, bulk limits, optimistic UI)
- `design-tokens.php` - Design system tokens
- `security.php` - Security headers and CSP
- `analytics.php` - Analytics configuration

### Database (`database/`)

```
database/
├── factories/             # Model factories for testing
├── migrations/            # Database migrations (chronological)
└── seeders/               # Database seeders (AdminUser, Category, Post, News, Demo)
```

### Tests (`tests/`)

```
tests/
├── Browser/               # Playwright browser tests
├── Feature/               # Feature tests (controllers, policies, workflows)
│   └── Admin/             # Admin-specific feature tests
├── Unit/                  # Unit tests (models, services, property tests)
├── Helpers/               # Test helpers (PropertyTesting)
└── js/                    # JavaScript/TypeScript tests
```

### Documentation (`docs/`)

Comprehensive documentation organized by topic:
- Admin features and configuration
- Accessibility guidelines
- Architecture and design patterns
- API references
- Testing guides and quick references

## Architectural Notes

- **Boundaries**: Public controllers handle reader flows; admin panel lives in Volt components under `resources/views/livewire/admin`. Policies + FormRequests enforce authorization/validation boundaries.
- **Data Access**: Query scopes centralize filters (categories, authors, published). NewsFilterService encapsulates complex filter logic.
- **State Management**: Livewire traits (`ManagesSearch`, `ManagesSorting`, `ManagesBulkActions`) keep table UX consistent and URL-synced.
- **UI Composition**: Flux UI + Blade components under `components/ui` and `components/navigation` provide primitives; design tokens drive visual consistency.
- **Routing**: `routes/web.php` for public, `routes/volt.php` for admin. Admin routes are namespaced and guarded.
- **Performance**: Eager-loading defaults in controllers/services, indexes on high-traffic columns (slug, published_at, foreign keys).

## Naming Conventions

### PHP
- Classes: PascalCase, `final` by default
- Methods/Properties: camelCase
- Constants: UPPER_SNAKE_CASE
- Strict types required (`declare(strict_types=1);`)

### Database
- Tables: plural snake_case (`posts`, `categories`, `category_post`)
- Columns: snake_case (`published_at`, `user_id`)
- Foreign keys: `{model}_id` pattern

### Views
- Blade files: kebab-case (`index.blade.php`, `admin-dashboard.blade.php`)
- Components: kebab-case with dot notation (`x-ui.button`, `x-navigation.main`)

### Routes
- Public: RESTful resource routes (`/posts`, `/categories/{category}`)
- Admin: Prefixed with `/admin` (`/admin/dashboard`, `/admin/posts`)

## Key Files

- `app/Support/helpers.php` - Global helper functions
- `app/Support/AdminConfig.php` - Admin configuration helper
- `app/Support/DesignTokens.php` - Design token management
- `routes/web.php` - Public routes
- `routes/volt.php` - Livewire Volt routes (admin panel)
- `lang/en.json` - JSON translations
- `lang/en/*.php` - PHP translation files

## Extensibility & Ops

- **Theme changes**: Update `design-tokens.php`, Tailwind config, and Blade UI components.
- **Content model tweaks**: Add migrations + policies; expose filters via query scopes to keep Livewire traits reusable.
- **Deployments**: Optimize with `php artisan optimize`, `npm run build`, cache config/routes/views; ensure `storage:link` run.
- **Observability**: Centralize logging with `pail`, add Playwright runs for smoke; monitor bulk actions for lock contention.
