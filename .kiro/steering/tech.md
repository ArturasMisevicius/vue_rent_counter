# Technology Stack

## Backend

- **Framework**: Laravel 12 (PHP 8.3+)
- **Real-time**: Livewire 3 with Volt (single-file components)
- **UI Components**: Livewire Flux + Blade component primitives
- **Authentication**: Laravel Sanctum, email verification for comments
- **Database**: SQLite (dev), MySQL/PostgreSQL (production)
- **Caching/Queues**: File cache by default; Horizon-ready queue config; broadcast-less stack (Livewire handles interactivity)
- **Security**: CSP + security headers (`config/security.php`), FormRequests + Policies gate admin surface

## Frontend

- **CSS**: TailwindCSS 4 driven by design tokens
- **JavaScript**: AlpineJS 3, TypeScript-first utilities
- **Build Tool**: Vite 7 with Laravel plugin
- **Markdown Editor**: EasyMDE with autosave + preview
- **UI Patterns**: Optimistic UI helpers, modal-heavy workflows, keyboard shortcuts

## Testing

- **PHP Testing**: PestPHP 4 with PHPUnit 12 runner
- **Browser Testing**: Playwright for admin/public flows
- **Test Suites**: Browser, Feature, Unit, JS/TS
- **Property-Based Testing**: `tests/Helpers/PropertyTesting.php` powers property suites (posts, comments, news filters)
- **Accessibility**: Lighthouse + manual keyboard/screen-reader checks documented in `docs/`

## Code Quality

- **Style**: Laravel Pint (PSR-12 baseline)
- **Static Analysis**: PHPStan (max level)
- **Refactoring**: Rector automation + targeted audits
- **Frontend**: Prettier with Tailwind plugin; ESLint config co-located with Vite
- **Docs**: phpDocumentor configuration for API docs; rich in-repo guides

## Common Commands

### Setup
```bash
composer install           # Install PHP dependencies
npm install                # Install Node dependencies
php artisan key:generate   # Generate app key
php artisan migrate        # Run migrations
php artisan storage:link   # Link storage
npm run build              # Build assets
php artisan admin:create   # Create admin user
```

### Development
```bash
php artisan serve          # Start dev server
npm run dev                # Start Vite dev server
composer dev               # Run server + queue + logs + vite concurrently
php artisan pail           # Tail logs
```

### Testing
```bash
php artisan test                       # Run all tests
php artisan test --parallel            # Parallel runs
php artisan test --testsuite=Unit      # Specific suite
npm run playwright:install             # Install Playwright browsers (one-time)
```

### Code Quality
```bash
./vendor/bin/pint                 # Auto-format code
./vendor/bin/pint --test          # Check style compliance
./vendor/bin/phpstan analyse      # Run static analysis
rector                            # Automated refactoring
npm run lint                      # Frontend lint/format
composer test                     # Aggregate quality checks
```

### Database
```bash
php artisan migrate               # Run migrations
php artisan migrate:fresh --seed  # Fresh DB with seed data
php artisan db:seed               # Seed database
php artisan tinker                # Interactive shell
```

## Configuration Files

- `config/blog.php` - Blog-specific settings (tags, comments, demo mode, EasyMDE)
- `config/interface.php` - Admin UI configuration (debounce, bulk limits, optimistic UI)
- `config/design-tokens.php` - Design system tokens
- `config/security.php` - Security headers, CSP, frame/source policies
- `pint.json` - Code style rules
- `phpstan.neon` - Static analysis configuration
- `phpunit.xml` - Test configuration
- `vite.config.js` - Frontend build configuration
- `tailwind.config.js` - Tailwind customization
