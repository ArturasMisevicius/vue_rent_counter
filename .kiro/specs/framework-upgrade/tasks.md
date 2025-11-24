# Implementation Plan

## Status Summary

### Current State Analysis
- **Laravel**: 11.46.1 → **Needs upgrade to 12.x**
- **Filament**: 3.x → **Needs upgrade to 4.x** (major version change)
- **Tailwind CSS**: CDN (unversioned) → **Needs explicit 4.x CDN URL**
- **PHP**: 8.2+ (8.3+ recommended for Laravel 12)
- **Middleware**: ✅ Already using Laravel 11+ style (compatible with 12)
- **Baseline**: ✅ Captured (tag: `pre-upgrade-baseline`, test results, performance metrics)

### Resources to Migrate
- **Filament Resources**: 14 total (Property, Building, Meter, MeterReading, Invoice, Tariff, Provider, User, Subscription, Organization, OrganizationActivityLog, Faq, Language, Translation)
- **Filament Widgets**: 1 (DashboardStatsWidget)
- **Filament Pages**: 4 (Dashboard, GDPRCompliance, PrivacyPolicy, TermsOfService)

### Dependencies to Update
- **PHP Testing**: Pest 2.36 → 3.x, PHPUnit 10.5 → 11.x
- **PHP Packages**: Spatie Backup 9.3 → 10.x, Laravel Tinker, Pint, Sail (latest)
- **Node Packages**: Vite 5.x (latest), Axios 1.6.4 (latest), laravel-vite-plugin 1.0 (latest)

- [x] 1. Preparation and baseline capture
  - Create comprehensive backup of codebase and database
  - Document current versions of all dependencies in a baseline file
  - Run full test suite and save results to `test-results-baseline.txt`
  - Run performance benchmarks and save metrics to `performance-baseline.json`
  - Create Git tag `pre-upgrade-baseline`
  - _Requirements: 6.1, 10.1_

- [ ]* 1.1 Write property test for baseline verification
  - **Property 1: Functional regression prevention**
  - **Validates: Requirements 1.2, 7.1, 7.2, 7.3**
 
- [ ] 2. Update Laravel to version 12.x
  - **CRITICAL**: Currently on Laravel 11.46.1, need to upgrade to Laravel 12.x
  - Review Laravel 12 upgrade guide for breaking changes from Laravel 11
  - Update `composer.json` to require `laravel/framework: ^12.0`
  - Run `composer update laravel/framework --with-all-dependencies`
  - Review and resolve any dependency conflicts using `composer why-not`
  - Verify PHP 8.3+ compatibility (currently using PHP 8.2 minimum)
  - _Requirements: 1.1, 1.3, 4.1_

- [x] 3. Migrate middleware to Laravel 12 conventions
  - **Note**: Already using Laravel 11+ middleware style in `bootstrap/app.php` which is compatible with Laravel 12
  - Middleware aliases already configured (tenant.context, role, subscription.check, hierarchical.access, locale)
  - CSRF handling for tests already in place
  - No changes needed for this task
  - _Requirements: 1.3, 8.5_

- [ ] 4. Update configuration files for Laravel 12
  - Compare current config files with Laravel 12 skeleton
  - Update `config/app.php` for service provider changes
  - Update `config/auth.php` for authentication changes
  - Update `config/database.php` for any new options
  - Update `bootstrap/providers.php` if it exists
  - Remove deprecated configuration options
  - Add new required configuration options with defaults
  - _Requirements: 1.3, 8.1, 8.2, 8.4_

- [ ] 5. Update routing to Laravel 12 conventions
  - Review `routes/web.php` for deprecated syntax
  - Update controller action references if needed
  - Update route model binding if syntax changed
  - Test all routes are accessible
  - _Requirements: 1.3_

- [ ] 6. Update Eloquent models for Laravel 12
  - Review model casting syntax for changes (Laravel 11 → 12)
  - Update relationship method return types if needed
  - Update query builder method calls for deprecations
  - Test model relationships and scopes
  - _Requirements: 1.3, 9.2, 9.3_

- [ ] 7. Run Laravel 12 test suite and fix failures
  - Run `php artisan test` and capture results
  - Identify failing tests due to framework changes
  - Update test code to match Laravel 12 API
  - Ensure all tests pass
  - _Requirements: 1.4, 7.1, 7.2, 7.5_

- [x] 7.1 Write unit test for Laravel version verification
  - Verify Laravel 12.x is installed
  - _Requirements: 1.1_

- [x] 8. Checkpoint - Verify Laravel 12 upgrade
  - Ensure all tests pass, ask the user if questions arise.

- [x] 9. Update Filament to version 4.x
  - **CRITICAL**: Currently on Filament 3.x, need to upgrade to Filament 4.x
  - Review Filament 4 upgrade guide for breaking changes from Filament 3
  - Update `composer.json` to require `filament/filament: ^4.0`
  - Run `composer update filament/filament --with-all-dependencies`
  - Verify Livewire 3 compatibility and performance optimizations
  - Review form/table API changes and navigation updates
  - _Requirements: 2.1, 2.3, 4.1_

- [x] 10. Migrate core Filament resources to Filament 4 API (Batch 1)
  - Migrate PropertyResource
  - Migrate BuildingResource
  - Migrate MeterResource
  - Update form schema builder syntax for all three
  - Update table column builder syntax for all three
  - Update action button syntax for all three
  - Update navigation registration for all three
  - Test resource CRUD operations
  - _Requirements: 2.2, 2.3_

- [ ] 11. Migrate billing Filament resources to Filament 4 API (Batch 2)
  - Migrate MeterReadingResource
  - Migrate InvoiceResource
  - Migrate TariffResource
  - Migrate ProviderResource
  - Update form schema builder syntax for all four
  - Update table column builder syntax for all four
  - Update action button syntax for all four
  - Update navigation registration for all four
  - Test resource CRUD operations
  - _Requirements: 2.2, 2.3_

- [x] 12. Migrate user & organization Filament resources to Filament 4 API (Batch 3)
  - Migrate UserResource
  - Migrate SubscriptionResource
  - Migrate OrganizationResource
  - Migrate OrganizationActivityLogResource
  - Update form schema builder syntax for all four
  - Update table column builder syntax for all four
  - Update action button syntax for all four
  - Update navigation registration for all four
  - Test resource CRUD operations
  - _Requirements: 2.2, 2.3_

- [x] 13. Migrate content & localization Filament resources to Filament 4 API (Batch 4)
  - Migrate FaqResource
  - Migrate LanguageResource
  - Migrate TranslationResource
  - Update form schema builder syntax for all three
  - Update table column builder syntax for all three
  - Update action button syntax for all three
  - Update navigation registration for all three
  - Test resource CRUD operations
  - _Requirements: 2.2, 2.3_

- [x] 14. Update Filament widgets for version 4
  - Review DashboardStatsWidget
  - Update widget syntax to Filament 4 API
  - Test widget rendering for all user roles
  - _Requirements: 2.5_

- [x] 15. Update Filament pages for version 4
  - Review Dashboard page
  - Review GDPRCompliance page
  - Review PrivacyPolicy page
  - Review TermsOfService page
  - Update page syntax to Filament 4 API
  - Test page rendering and functionality
  - _Requirements: 2.2_

- [ ] 15.1 Write property test for Filament resource integrity
  - **Property 2: Filament resource integrity**
  - **Validates: Requirements 2.2, 2.4, 2.5, 7.4**

- [ ] 15.2 Write property test for Filament navigation visibility
  - Test role-based navigation for all user roles
  - **Validates: Requirements 2.4**

- [ ] 15.3 Write unit test for Filament version verification
  - Verify Filament 4.x is installed
  - _Requirements: 2.1_

- [x] 16. Checkpoint - Verify Filament 4 upgrade
  - Ensure all tests pass, ask the user if questions arise.

- [x] 17. Update Tailwind CSS to version 4.x
  - Update CDN URL in `resources/views/layouts/app.blade.php` from `https://cdn.tailwindcss.com` to Tailwind 4.x specific URL
  - Review Tailwind 4 migration guide for breaking changes in utility classes
  - Update inline Tailwind config (currently in `<script>tailwind.config = {...}</script>`) for v4 compatibility
  - Test custom theme extensions (fontFamily, colors, boxShadow) work with v4
  - _Requirements: 3.1, 3.2, 3.3, 3.4_
  - **Note**: Currently using CDN without version pinning; no `tailwind.config.js` file; inline config present

- [x] 18. Review and update Tailwind classes across all views
  - Review all Blade components in `resources/views/components/`
  - Review all layout files in `resources/views/layouts/`
  - Review role-specific views (admin, manager, tenant, superadmin)
  - Review error pages in `resources/views/errors/`
  - Update any deprecated Tailwind classes to Tailwind 4 syntax
  - Test rendering across all pages and user roles
  - _Requirements: 3.2_

- [ ] 18.1 Write property test for visual regression prevention
  - **Property 3: Visual regression prevention**
  - **Validates: Requirements 3.2**

- [ ] 18.2 Write unit test for Tailwind CDN version verification
  - Verify Tailwind 4.x CDN URL is present in layout
  - _Requirements: 3.1, 3.4_

- [x] 19. Checkpoint - Verify Tailwind 4 upgrade
  - Ensure all tests pass, ask the user if questions arise.

- [x] 20. Update PHP testing dependencies
  - Update `composer.json` to require `pestphp/pest: ^3.0`
  - Update `composer.json` to require `phpunit/phpunit: ^11.0`
  - Update `composer.json` to require latest `pestphp/pest-plugin-laravel`
  - Run `composer update` for testing packages
  - _Requirements: 4.1, 4.3_

- [x] 21. Update Pest tests for version 3.x
  - Review Pest 3.x upgrade guide
  - Update test syntax for any breaking changes
  - Update custom test helpers in `tests/TestCase.php` if needed
  - Run test suite and fix failures
  - _Requirements: 4.3, 7.5_

- [x] 22. Update Spatie packages
  - Update `composer.json` to require `spatie/laravel-backup: ^10.0`
  - Update `spatie/laravel-ignition` to latest version
  - Run `composer update` for Spatie packages
  - Review configuration files for changes
  - _Requirements: 4.1, 4.2_

- [ ] 23. Test backup functionality after Spatie upgrade
  - Run `php artisan backup:run` and verify success
  - Test backup restoration procedure
  - Verify WAL mode still works with SQLite
  - _Requirements: 4.2_

- [ ] 23.1 Write property test for multi-tenancy preservation
  - **Property 4: Multi-tenancy preservation**
  - **Validates: Requirements 1.2, 4.2**

- [x] 24. Update remaining PHP dependencies
  - Update `laravel/tinker` to latest version
  - Update `laravel/pint` to latest version
  - Update `laravel/sail` to latest version
  - Update `barryvdh/laravel-debugbar` to latest version
  - Run `composer update` for all packages
  - _Requirements: 4.1, 4.4_

- [ ] 25. Update Node dependencies
  - Update `package.json` to require latest `vite`
  - Update `package.json` to require latest `axios`
  - Update `package.json` to require latest `laravel-vite-plugin`
  - Run `npm update` for all packages
  - _Requirements: 5.1, 5.2, 5.3_

- [ ] 26. Update Vite configuration
  - Review `vite.config.js` for deprecated options (currently on Vite 5.x)
  - Update to match Vite latest conventions if needed
  - Verify laravel-vite-plugin compatibility with latest versions
  - _Requirements: 5.2_
  - **Note**: Currently no compiled assets; Vite config is minimal; no changes likely needed

- [ ] 26.1 Write property test for API request functionality
  - **Property 5: API request functionality**
  - **Validates: Requirements 5.3**

- [ ] 27. Checkpoint - Verify all dependencies updated
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 28. Run complete test suite verification
  - Run `php artisan test` for all Feature tests
  - Run `php artisan test` for all Unit tests
  - Run property-based tests with 100+ iterations
  - Run Security tests
  - Run Performance tests
  - Document any test failures
  - _Requirements: 7.1, 7.2, 7.3, 7.4_

- [ ] 28.1 Write property test for database driver compatibility
  - **Property 6: Database driver compatibility**
  - **Validates: Requirements 9.5**

- [ ] 29. Verify database migrations and seeders
  - Run `php artisan migrate:fresh` on clean database
  - Run `php artisan db:seed --class=TestDatabaseSeeder`
  - Verify all seeders execute without errors
  - Test with SQLite (primary), MySQL and PostgreSQL if available
  - _Requirements: 9.1, 9.4, 9.5_

- [-] 29.1 Write unit test for migration execution
  - Verify migrations run successfully
  - _Requirements: 9.1_

- [-] 29.2 Write unit test for seeder execution
  - Verify TestDatabaseSeeder runs successfully
  - _Requirements: 9.4_

- [ ] 30. Run performance benchmarks
  - Run dashboard load time benchmarks for all user roles (superadmin, admin, manager, tenant)
  - Run resource list page load time benchmarks
  - Run invoice generation time benchmark
  - Run report generation time benchmark
  - Measure query execution times
  - Measure memory usage for typical operations
  - Save results to `performance-post-upgrade.json`
  - _Requirements: 10.2_

- [ ] 30.1 Write property test for memory usage boundaries
  - **Property 7: Memory usage boundaries**
  - **Validates: Requirements 10.5**

- [ ] 31. Compare performance metrics
  - Load baseline metrics from `performance-baseline.json`
  - Load post-upgrade metrics from `performance-post-upgrade.json`
  - Calculate percentage changes for all metrics
  - Verify metrics are within acceptable ranges (response times +10%, memory +50%)
  - Document any performance regressions
  - _Requirements: 10.3, 10.4_

- [ ] 32. Update environment configuration
  - Review `.env.example` for new required variables
  - Add any new Laravel 12 or Filament 4 environment variables
  - Document environment variable changes
  - _Requirements: 8.3_

- [x] 33. Update README and setup documentation
  - Update `README.md` with new version requirements (if exists)
  - Update `docs/guides/SETUP.md` with new setup steps
  - Document any new dependencies or system requirements
  - _Requirements: 6.5_

- [x] 34. Update technology stack documentation
  - Update `.kiro/steering/tech.md` with new framework versions
  - Update Laravel 11.46.1 → Laravel 12.x references
  - Update Filament 3.x → Filament 4.x references and document Livewire 3 performance tips (lazy hydration, table eager loading)
  - Update Tailwind CSS (CDN, unversioned) → Tailwind CSS 4.x (CDN)
  - Update Pest 2.36 → Pest 3.x
  - Update PHPUnit 10.5 → PHPUnit 11.x
  - Update Spatie Backup 9.3 → Spatie Backup 10.x
  - Document new features available in upgraded frameworks
  - _Requirements: 1.5_

- [ ] 35. Create upgrade guide documentation
  - Create `docs/upgrades/LARAVEL_12_FILAMENT_4_UPGRADE.md`
  - Document all breaking changes encountered
  - Document resolution steps for each breaking change
  - Document rollback procedures
  - Include lessons learned and recommendations
  - _Requirements: 6.2, 6.3, 6.4_

- [ ] 36. Update project structure documentation
  - Review `.kiro/steering/structure.md` for accuracy
  - Update any structural changes from the upgrade
  - Document new conventions from Laravel 12
  - _Requirements: 6.5_

- [-] 37. Final checkpoint - Complete verification
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 38. Create final Git tags and prepare for deployment
  - Create Git tag `upgrade-complete`
  - Push main to remote
  - Document deployment plan for staging and production
  - _Requirements: 6.5_
