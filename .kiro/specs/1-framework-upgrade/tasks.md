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
 
- [x] 2. Update Laravel to version 12.x
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

- [x] 4. Update configuration files for Laravel 12
  - Compare current config files with Laravel 12 skeleton
  - Update `config/app.php` for service provider changes
  - Update `config/auth.php` for authentication changes
  - Update `config/database.php` for any new options
  - Update `bootstrap/providers.php` if it exists
  - Remove deprecated configuration options
  - Add new required configuration options with defaults
  - _Requirements: 1.3, 8.1, 8.2, 8.4_

- [x] 5. Update routing to Laravel 12 conventions
  - Review `routes/web.php` for deprecated syntax
  - Update controller action references if needed
  - Update route model binding if syntax changed
  - Test all routes are accessible
  - _Requirements: 1.3_

- [x] 6. Update Eloquent models for Laravel 12
  - Review model casting syntax for changes (Laravel 11 → 12)
  - Update relationship method return types if needed
  - Update query builder method calls for deprecations
  - Test model relationships and scopes
  - _Requirements: 1.3, 9.2, 9.3_
  - **Note**: NavigationComposer refactored to Laravel 12 standards with DI, enums, constants; all 7 tests passing



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

- [ ] 10. Migrate core Filament resources to Filament 4 API (Batch 1)
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

- [ ] 12. Migrate user & organization Filament resources to Filament 4 API (Batch 3)
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

- [ ] 13. Migrate content & localization Filament resources to Filament 4 API (Batch 4)
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

- [x] 35. Create upgrade guide documentation
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
