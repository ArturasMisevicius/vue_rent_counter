# Changelog

## 2026-06-15

<!-- changelog:auto:start:staged-20260615062849 -->
### Изменения Codex

- обновлен `.agent/ARCHITECTURE.md`
- обновлен `.agent/agents/laravel-code-quality-architect.md`
- обновлен `.agent/agents/laravel-database-optimizer.md`
- обновлен `.agent/agents/laravel-function-test-coverage-enforcer.md`
- обновлен `.agent/agents/laravel-livewire-filament-quality-auditor.md`
- обновлен `.agent/agents/laravel-privacy-compliance-auditor.md`
- обновлен `.agent/agents/laravel-translation-corrector.md`
- обновлен `.agent/agents/laravel-validation-policy-auditor.md`
- обновлен `.agent/agents/orchestrator.md`
- обновлен `.agent/agents/tenanto-architecture-simplifier.md`
- добавлен `.agent/agents/tenanto-audit-security-observability-auditor.md`
- обновлен `.agent/agents/tenanto-billing-money-auditor.md`
- обновлен `.agent/agents/tenanto-docs-release-auditor.md`
- добавлен `.agent/agents/tenanto-documents-kyc-contracts-auditor.md`
- обновлен `.agent/agents/tenanto-filament-resource-auditor.md`
- обновлен `.agent/agents/tenanto-i18n-ui-auditor.md`
- добавлен `.agent/agents/tenanto-leads-imports-auditor.md`
- обновлен `.agent/agents/tenanto-migration-schema-auditor.md`
- добавлен `.agent/agents/tenanto-move-out-occupancy-auditor.md`
- добавлен `.agent/agents/tenanto-notifications-mail-auditor.md`
- добавлен `.agent/agents/tenanto-operations-release-auditor.md`
- добавлен `.agent/agents/tenanto-permission-matrix-auditor.md`
- обновлен `.agent/agents/tenanto-pest-coverage-engineer.md`
- добавлен `.agent/agents/tenanto-projects-collaboration-auditor.md`
- обновлен `.agent/agents/tenanto-query-performance-auditor.md`
- добавлен `.agent/agents/tenanto-reading-invoice-cycle-auditor.md`
- добавлен `.agent/agents/tenanto-shell-navigation-auditor.md`
- обновлен `.agent/agents/tenanto-tenant-isolation-auditor.md`
- обновлен `.agent/agents/tenanto-upgrade-compatibility-auditor.md`
- добавлен `.agent/agents/tenanto-utility-services-auditor.md`
- обновлен `app/Filament/Resources/BillingPeriods/Pages/CreateBillingPeriod.php`
- обновлен `app/Filament/Support/Admin/BillingPeriods/BillingPeriodScopeSnapshotBuilder.php`
- обновлен `app/Filament/Support/Changelog/GitChangelogUpdater.php`
- обновлен `docs/operations/billing-reading-invoice-workflow.md`
- обновлен `tests/Feature/Admin/FilamentCrudCoverageInventoryTest.php`
- обновлен `tests/Unit/Filament/Support/Changelog/GitChangelogUpdaterTest.php`
<!-- changelog:auto:end:staged-20260615062849 -->

<!-- changelog:auto:start:pending -->
### Ожидающие staged-изменения

- обновлен `app/Filament/Resources/BillingPeriods/Pages/CreateBillingPeriod.php`
- обновлен `app/Filament/Support/Admin/BillingPeriods/BillingPeriodScopeSnapshotBuilder.php`
- обновлен `app/Filament/Support/Changelog/GitChangelogUpdater.php`
- обновлен `docs/operations/billing-reading-invoice-workflow.md`
- обновлен `tests/Feature/Admin/FilamentCrudCoverageInventoryTest.php`
- обновлен `tests/Unit/Filament/Support/Changelog/GitChangelogUpdaterTest.php`
<!-- changelog:auto:end:pending -->

> **AI agent usage:** This changelog was reconstructed on 2026-06-15 from the full git history in this checkout. For exact file-level diffs, use `git show <hash>` or `git log --stat`. Normal future commits can continue using `scripts/update_changelog.php` and the git hooks for staged-diff entries.

## 2026-06-15 Documentation Reconstruction

This file replaces the previous append-only staged-file ledger with a product-oriented history reconstructed from all 363 commits currently reachable from `HEAD`, starting at `f12619cd` and ending at `8cbfc808`.

### Current Product State

- Laravel 13.15, Filament 5.6, Livewire 4.3, Pest 4, PHPUnit 12, Tailwind 4, Vite 8.
- 230 routes, 41 Filament resources, 27 Filament pages, 51 Livewire classes, 79 top-level models, 207 Filament action classes, 117 migration files, and 222 tests were present during the docs audit.
- Current feature documentation now lives in `README.md`, `docs/FEATURES.md`, `docs/PROJECT-CONTEXT.md`, `docs/PERMISSION-MATRIX.md`, and `docs/operations/**`.
- Historical planning remains in `docs/superpowers/**` and should be verified against live code before use.

### Major Product History

#### 2025-11: Initial Laravel/Filament Billing Foundation

- Project initialization, configuration, default seed accounts, dependency setup, hierarchical user management, role hierarchy, subscription management, and the first Filament admin panel work landed.
- Billing calculation was refactored around a calculator factory, and early localization/privacy/invoice-finalization documentation was added.

#### 2025-12: Security, Universal Services, Reporting, And Platform Expansion

- Security middleware and documentation were expanded.
- Reusable UI components, invoice PDF/email support, subscription caching, tariffs, universal service validation, universal reading collection, utility management, superadmin dashboards, audit features, tagging, project management, and repository/caching patterns were added.
- Documentation was repeatedly reorganized, which is why current docs now mark older planning files as historical.

#### 2026-01 To Early 2026-02: Local Stabilization And Backoffice Reshaping

- Several local update snapshots were committed.
- Agent tooling, `.mcp.json`, shared translations, role-based layout work, custom route migration, profile/settings Livewire components, role-specific UI consolidation, and comprehensive tenant seeding were added.

#### 2026-03: Major Product Rebuild And Guardrails

- Laravel/Filament/Livewire dependencies and skills were refreshed.
- Auth, invitations, onboarding, password reset, shared shell, locale switcher, notification center, global search, impersonation banner, error pages, tenant portal, superadmin control plane, admin organization operations, audit/security, legacy reference/operations/platform foundations, and localization work were merged.
- Public debug exposure, CSP intake, PWA removal, phase guardrails, route/workspace boundaries, and security tests were introduced.
- Organization module work added health metrics, MRR/list contracts, CSV exports, queued announcements, data exports, plan and ownership actions, limit overrides, feature flags, invoice write-offs, impersonation hardening, detail snapshots, security health, user roster actions, integration snapshots, showcase seeding, manager permissions, manager memberships, deferred relation badges, hidden slugs, and changelog automation.
- Projects were implemented with costs, alerts, exports, lifecycle behavior, and tests.

#### 2026-04: Project Export Expansion

- Project export functionality and related project management behavior were enhanced.

#### 2026-05: Deployment, Accessibility, Settings, And Auth Routing

- Project configuration and docs were refreshed.
- Topbar accessibility improved.
- Organization feature management/localization improved.
- Settings access was restricted to admins.
- Shared hosting deployment preparation and app panel routing through Laravel were added.

#### 2026-06: Billing Cycle, Leads, Documents, KYC, Move-Out, And Docs Refresh

- Dependencies and Playwright configuration were refreshed.
- Agent skills/personas were added.
- The invoice-driven reading cycle landed through `billing:open-reading-invoice-cycle`, reading request invoices, tenant reading submission, billing review, and notifications.
- Manual invoice line items, invoice draft validation, optional quantities, extra charge approval, billing actions, invoice management, payment notifications, and overdue/reminder flows expanded.
- Manager creation gained invitation lifecycle support.
- Listing leads, lead sources/imports/outreach/reports, and help center pages were added.
- Tenant documents gained secure tenant portal visibility/downloads, admin relation manager workflow, metadata/visibility/replacement/verification/rejection/archive behavior, and notifications.
- Property dashboard and move-out lifecycle features were added, including final readings, final invoices, occupancy status, contract closure, portal access, and attention-card surfacing.
- Tenant KYC workflow landed with profiles, documents, tenant upload/download, admin review, organization settings, maintenance command, notifications, and tests.
- Billing/reading workflow documentation was clarified and the docs were reconstructed from current code plus full git history.

## Complete Commit Ledger

### 2025-11-18
- `f12619cd` init
- `2159d244` feat(vilnius-utilities-billing): Add project specification and configuration

### 2025-11-20
- `60fbea5f` Add project files
- `6fa9e336` Seed default admin and user accounts
- `928c27d6` feat(dependencies): Update composer and package configurations

### 2025-11-21
- `b2c8e1a2` feat(hierarchical-user-management): Implement user role hierarchy and subscription management

### 2025-11-22
- `5896b9fd` feat(hierarchical-user-management): Add comprehensive user guide and implement migration command
- `9ad2ea79` feat(hierarchical-user-management): Finalize implementation of hierarchical user management system

### 2025-11-23
- `7e5a216d` feat(filament-admin-panel): Complete integration and testing of Filament admin panel
- `c5529961` Fix formatting of User Hierarchy section in README
- `7da74960` refactor(auth): Update login redirection and user model scope handling
- `98fc71d6` Merge branch 'main' of https://github.com/ArturasMisevicius/vue_rent_counter
- `3613bed4` refactor(billing): Implement calculator factory to streamline billing calculations
- `895b8cf1` feat(kiro-hooks): Add comprehensive AI agent hooks and multi-tenant architecture
- `52030364` chore: Update environment configuration and remove obsolete files
- `a700d8e5` feat(filament-admin-panel): Complete testing documentation and validation integration
- `c9d22699` feat(filament-admin-panel): Add comprehensive localization, privacy pages, and invoice finalization

### 2025-11-24
- `1eb3141a` Pre-upgrade: Save current state before Laravel 12 + Filament 4 upgrade
- `aaba9ed6` docs: Add dependency baseline and modernize login view
- `1c588398` feat(filament-admin-panel): Complete baseline capture and dashboard implementation
- `f8db36c1` feat(filament-admin-panel): Add admin panel guide and performance baseline
- `23e7c7d2` feat(filament-admin): Implement authorization fix and complete admin panel documentation
- `c09c60f1` refactor(filament-admin): Update authorization and role management for superadmin access
- `a023c2e9` chore: Update dependencies and enhance documentation
- `fddb77ae` feat(reports): Enhance report views and add advanced filters
- `1bd7e7cb` feat(superadmin-dashboard): Implement comprehensive platform management and analytics
- `c63317c7` feat(middleware): Implement impersonation session timeout handling
- `cb3611e4` feat(middleware): Add impersonation session handling middleware
- `fef8a2db` feat(navigation): Implement view composer for dynamic navigation and Laravel 12 upgrades

### 2025-11-25
- `5e3d46d2` refactor: Remove deprecated file and enhance README with verification scripts
- `97e3cd32` refactor: Enhance summer average calculation command and improve documentation

### 2025-11-27
- `5797f0a9` feat(documentation): Enhance README and user guides for hierarchical user management

### 2025-12-02
- `f133c3c0` feat(security): Update security configurations and enhance middleware functionality
- `87a37a24` docs: Reorganize and consolidate documentation structure
- `12e2a8e4` docs: reorganize markdown files into categorized subdirectories
- `a4bb8633` feat(components): Implement StatusBadge view component with enum support
- `0c46f11b` feat(components): Enhance StatusBadge component with null handling and caching

### 2025-12-03
- `938cb2fb` chore(dependencies): Update nette/utils to version 4.1.0 and adjust PHP requirements

### 2025-12-04
- `ece0bb74` feat(invoice): Add PDF download and email notification features to InvoiceResource

### 2025-12-05
- `77ed5eea` feat(subscription): Enhance SubscriptionChecker with caching and batch processing

### 2025-12-06
- `0c6518ad` feat(tariff): Implement manual entry mode and external system integration
- `f75015c5` docs: Update README, Vite config, and setup guide for Alpine.js bundling

### 2025-12-08
- `648d8048` docs: Add project handover section to README with key documents

### 2025-12-13
- `d9f57afc` docs: Reorganize steering documentation and add system architecture guides
- `855f2978` chore(.kiro): Reorganize steering documentation and consolidate project structure
- `d8bc1251` feat(universal-service-framework): Implement MCP server and enhance tenant/utility management
- `302a09e0` chore(.kiro): Add translation hooks and reorganize steering documentation
- `bff09e8c` chore(.kiro): Clean up hooks, fix naming conventions, and enhance universal billing
- `37235a7a` feat(validation): Implement comprehensive service validation engine with extensible validators
- `1cd32bce` docs(validation): Complete service validation engine documentation and enhance framework integration
- `e2f01076` feat(reading-collection): Implement universal reading collector with mobile interface and validation performance optimization
- `33faeefd` perf(database): Add defensive checks to performance index migrations
- `7092202d` feat(universal-utility-management): Implement comprehensive utility management system with security enhancements
- `ad3cb104` feat(superadmin): Implement building and property management interfaces with multilingual support
- `250d677e` feat(enhanced-service-layer): Implement comprehensive service layer enhancements with performance monitoring and platform notifications
- `f734f905` feat(superadmin-dashboard): Implement comprehensive dashboard enhancements with notifications and service management
- `3159bd1d` refactor(billing-and-subscriptions): Remove legacy gyvatukas system and implement subscription automation with export capabilities
- `932e1f6b` feat(superadmin-dashboard): Implement performance optimization, background jobs, and security enhancements

### 2025-12-15
- `d42a7e5e` feat(core-platform): Implement comprehensive platform enhancements with service layer improvements and organizational management
- `b5239829` feat(performance): Optimize HasTags trait with bulk operations and eliminate N+1 queries
- `9b2b0977` temp: Remove performance monitoring workflow to allow push
- `7ea0acb9` feat(property-scopes): Enhance Property model with comprehensive query scopes and tag factory
- `19c38843` feat(tagging-system): Optimize tag operations and enhance documentation with integration tests
- `5deaa843` feat(project-management): Implement comprehensive project and task management system with enhanced relationships

### 2025-12-16
- `32433f99` feat(repository-pattern): Implement comprehensive repository pattern with criteria system and caching decorators
- `9d699508` Refactor User model with value objects and services - Enhanced user state management with UserState and UserCapabilities value objects - Added PanelAccessService and UserRoleService for better separation of concerns - Comprehensive test coverage for all new components - Updated multi-tenant architecture migration

### 2025-12-19
- `94836fa9` docs: reorganize markdown files and update links

### 2025-12-22
- `2b31c2fe` docs: relocate markdown files and update links

### 2025-12-23
- `edef5afc` docs: Update markdown documentation structure and links

### 2025-12-24
- `f9b873c6` feat(tenant-initialization): Implement TenantInitializationService for universal service setup
- `beb0dd5b` feat(audit): Enhance audit functionality and user experience

### 2025-12-26
- `6e706ed2` feat(security): Enhance security notification system and user permissions

### 2025-12-29
- `1739993a` chore: Update documentation, landing pages, and clear cached views
- `0fec623b` feat(authorization): Implement workflow strategy pattern and authorization context value objects

### 2026-01-07
- `07b8d4a1` update

### 2026-01-08
- `473a6217` update08,01

### 2026-01-09
- `4cc2bf67` fresh update

### 2026-01-10
- `f17a94c9` update10,1
- `1ba6098b` AM10,1

### 2026-01-19
- `f4ac66f4` for Ardrey

### 2026-02-08
- `299068e6` feat: Add agent system components including skills, workflows, agents, and shared UI/UX assets.
- `5bb06b24` Initial commit
- `881695d0` Merge remote main into local main
- `d5b42132` chore: cleared compiled view cache.
- `de02ee09` chore: Remove temporary files, legacy Docker configurations, and Kiro documentation, and add .mcp.json.
- `369e31be` feat: update login request validation, adjust login view, and update application translations
- `a3dba105` feat: add backward-compatible accessors and methods for 'Invoice' and 'BillingPeriod', improve 'UserRoleService' and 'MeterReadingPolicy' role/permission checks, register 'SubscriptionChecker', add Filament superadmin route aliases, and make 'audit_logs.tenant_id' nullable.
- `1a33ad89` feat: Add CompatibilityRegistry to register Filament v4 action class aliases for Laravel 12 compatibility.
- `45bcfe1d` refactor: improve anomaly detection output precision, refine API authentication type hint, and convert health monitor endpoints to a class constant.
- `2c04837f` refactor: Unify backoffice layout, disable Filament web interface, and implement role-based access control.
- `64d36a3e` feat: Add role-based route access matrix tests and refactor navigation tests to assert specific routes.
- `6c2d77d6` refactor: Remove Filament web interface and migrate to custom routes, notifications, and error pages.
- `6d59045a` chore: Remove numerous unused vendor and application language files and update the manager section card Blade component.
- `de4e0da2` feat: Update and reformat various language files, and make minor adjustments to the meter reading controller and related tests.
- `5c7a6708` feat: Refactor user profile pages with a shared design, introduce currency preference, and update translations across all user roles.
- `fdae574f` refactor: standardize view paths across admin, manager, and tenant controllers to a new 'pages.{resource}.{action}-{role}' structure.
- `1ff1995e` feat: Exclude admin role from locale switcher, add tenant admin edit view, and update Lithuanian superadmin dashboard translations.
- `aa35b221` refactor: unify application layout and introduce new pages for various entities and roles.
- `b32c31f5` feat: Implement a shared translation key system with fallback logic and update numerous UI components and views to utilize it.
- `eff54130` refactor: Consolidate role-specific views and language files into unified structures, and introduce Livewire components for profile and settings.
- `3f6fc65d` refactor: streamline Blade layout inheritance by moving '@extends' directives to the top of page templates.
- `20bc52c9` feat: Add specifications for Livewire core page modules and comprehensive tenant seeding, and refactor flash message display in tenant views.

### 2026-02-09
- `135777ae` feat: Implement a comprehensive tenant seeder with new factories and tests to generate a realistic dataset for 'tenant_id = 1'.

### 2026-03-12
- `1cea30dc` feat: Enhance project documentation with detailed skill routing and add algorithmic art skill templates, including best practices for p5.js generative art.
- `c6198773` chore: Update dependencies in composer.json and composer.lock, including upgrading PHPUnit to version 12.5 and various package versions for improved compatibility.
- `96002c7b` feat: Introduce new skills for Tenanto domain including billing, tenant security, and Laravel stack implementation, along with a skill installer for managing Codex skills from GitHub.
- `8dddb8c8` feat: Enhance Filament components with improved modal synchronization, checkbox list functionality, and color picker updates for better user experience.
- `eed86daa` feat: Update MCP server configuration and introduce new language findings files for enhanced localization support and improved project documentation.
- `4e4da714` chore: Remove archived design, proposal, tasks, and specifications for comprehensive tenant seeding to streamline project documentation.
- `b006e9a1` feat: Add new language files for system health in English, Lithuanian, and Russian to enhance localization support and improve system monitoring capabilities.
- `14be5db4` chore: Remove outdated API patterns documentation files to streamline project resources and improve maintainability.
- `8132a300` feat: Add Laravel Boost configuration and new language findings file to enhance project capabilities and improve localization support.
- `a98fc2ee` feat: Enhance ReportController with building and status filters for revenue and compliance exports, and add strict types declaration for improved type safety.
- `4eda7f0d` fix(dashboard): Update task list formatting and enhance user role checks

### 2026-03-13
- `e3607aa2` chore: Update dependencies and enhance tenant context management
- `51def266` refactor: Improve validation message handling and enhance admin access middleware
- `7d98eb5a` refactor: Enhance PropertiesRelationManager and improve validation handling
- `f0737844` Initial snapshot

### 2026-03-16
- `4eccf211` feat: Update environment configuration and enhance skill set for Laravel MCP
- `a0d01601` update
- `e328c0b0` chore: Update composer.lock and remove deprecated config file
- `efe00723` feat: Enhance language files with new translations and improve accessibility
- `7f153546` feat: Enhance language files with new translations and descriptions
- `9b634684` feat: Add MeterDetails component and enhance Button component
- `75435777` fix: Update translation resource and remove deprecated documentation
- `ef86d1e8` feat: Revamp buildings index for manager role with enhanced UI and functionality
- `2e593e73` feat: Refactor managers index view with improved UI components and data presentation
- `58c5a1b5` feat: Refactor organizations index view with enhanced UI components and improved data presentation
- `8a9253d0` feat: Refactor subscriptions index view with improved UI components and enhanced user experience
- `117bc8e2` feat: Refactor tariffs index view with improved UI components and enhanced user experience
- `605cee55` feat: Refactor tenants index view for admin role with enhanced UI components and improved data presentation
- `cf1c5720` chore: Remove docker-compose.yml file as it is no longer needed for the project
- `cf1dc331` feat: Refactor properties index view for manager role with enhanced UI components and improved functionality
- `dac82729` feat: Refactor impersonation history view with enhanced UI components and improved user experience
- `471478f7` feat: Enhance table styling in app.css for improved UI consistency
- `9cadfc27` feat: Refactor subscriptions dashboard view with enhanced UI components and improved responsiveness

### 2026-03-17
- `e51d791c` refactor: Update Vite configuration and refactor DTO imports for improved organization
- `c9d0c172` fresh system
- `0111f0e0` docs: add foundation auth onboarding design spec
- `3b72f37b` docs: clarify invitation lifecycle in auth spec
- `eced1b71` chore: install pest and publish language scaffolding
- `e0706db7` feat: add auth foundation domain models
- `1037d62d` feat: add auth middleware and redirect foundation
- `ad5d72ca` feat: add public login and registration flows
- `c8df853f` feat: add password reset flow
- `ef3d7353` feat: add admin onboarding and trial activation
- `45f9a8f4` feat: add invitation acceptance flow
- `944040ee` feat: connect shared auth shell entry points
- `645903e4` fix: harden tenant and invitation access guards
- `45fb373b` docs: add foundation auth onboarding plan
- `522b2f77` feat: add shared authenticated shell foundation
- `b54d9ab6` feat: add role-aware shared navigation shell
- `1126cca2` feat: add live locale switcher
- `2bd882c6` feat: add shared notification center
- `f9a49c4d` feat: add pluggable global search shell
- `5723f7e2` feat: add impersonation banner and stop flow
- `fbe704a9` feat: add branded shared error pages
- `a8d7da57` docs: add superadmin control plane design spec
- `8eeffaae` fix: refresh topbar copy after locale switches
- `cc74477a` chore: ignore local worktrees
- `bb3ffe03` docs: add public homepage design
- `9adc7ead` feat: add superadmin platform data foundation
- `4a6d8a42` Merge branch 'codex/shared-interface-elements' into codex/superadmin-control-plane
- `c006b447` feat: deliver shared interface elements shell
- `a64892e7` feat: wire superadmin access foundation
- `849421ff` feat: add localized public homepage
- `38ce8646` feat: add superadmin dashboard widgets
- `c99e3771` fix: serve favicon from app route
- `02391660` feat: recover tenant continuity portal slice
- `26ebd61e` feat: build tenant self-service portal
- `59e18963` feat: add tenant portal shell and domain foundation
- `0993acfd` feat: add superadmin organization management
- `7fbf7c59` feat: close auth and invitation lifecycle gaps
- `1a428408` feat: add admin resource breadcrumbs and empty states
- `f6fa8211` feat: add superadmin organizations resource
- `68cee1ab` feat: add organization dashboard widgets
- `c0faac1d` feat: add superadmin users resource
- `422275ca` feat: add superadmin subscriptions resource
- `b8f41a86` feat: add superadmin control plane (#5)
- `975a75ba` feat: add admin profile and settings pages
- `d60e4a0e` feat: add superadmin governance pages
- `0b2fecaf` feat: recover tenant continuity portal slice
- `c923ef07` Complete missing information closures
- `8d7d67c5` feat: add platform policy and audit infrastructure
- `48b4d1c0` feat: add legacy reference foundation
- `56577ac3` feat: add admin resource breadcrumbs and empty states
- `20fce59e` chore: snapshot tenant portal shell workspace before main reconcile
- `908fc838` feat: add legacy operations foundation
- `ffdc80fa` chore: ignore local superpowers artifacts
- `f610e543` feat: add admin organization operations
- `3c0575d5` test: align replayed shell and tenant regressions
- `ddda38ea` feat: add superadmin localization and notifications
- `57c7921a` refactor: centralize admin organization context
- `7b2990ed` feat: add legacy platform foundation
- `78f8a35e` Merge origin/main into codex/missing-information-closures-follow-on-b-clean
- `ba95dc71` Add active language scope and link implementation specs
- `d221ab58` Restrict locales to Baltic languages and geography data
- `6969f532` Merge branch 'codex/tenant-portal-shell' into main
- `791679a5` Merge branch 'codex/admin-organization-operations' into main
- `6ff21f7d` Merge branch 'codex/missing-information-closures-follow-on-b' into main
- `744b3d41` Merge branch 'codex/missing-information-closures-follow-on-b-clean' into main
- `b8188fae` Merge branch 'codex/superadmin-control-plane' into main
- `68220834` Merge branch 'codex/tenant-portal-shell-reconcile-main' into main
- `16a5d157` Merge branch 'fleet-local-history' into main
- `e61d7835` chore: remove executed public homepage docs
- `ba4bd10d` fix: complete spanish guest locale support
- `a0534a81` feat: capture pending admin tenant and profile workspace
- `c22d2412` feat: capture pending admin organization operations workspace
- `534df09f` feat: integrate pending admin, superadmin, and tenant workspaces
- `7715a187` merge: integrate admin organization operations branch
- `66b72212` feat: continue admin organization operations workspace
- `4d6f0be8` Merge branch 'codex/admin-organization-operations'
- `68411695` merge: integrate remaining admin organization operations worktree
- `fb233009` Enhance Filament structure and guidelines for request validation, actions, and support services. Introduce new enum trait for translated labels and implement it across various enums. Add actions for managing buildings and invoices, including creation, deletion, and payment processing. Ensure adherence to the Filament foundation directory structure and update documentation accordingly.
- `5bab4afb` Update documentation and guidelines for Tenanto application, including verified stack details and project context. Enhance Filament and Livewire component practices, emphasizing state management and query optimization. Revise security and authorization guidelines for tenant isolation and data protection. Ensure consistency across skills and improve clarity in usage instructions.
- `c6c615de` Harden public surface and document Tenanto session setup

### 2026-03-18
- `f0501a18` PROJECT CONTEXT (include at the start of every session) Tenanto is a multi-tenant utility billing and property management SaaS built on the following confirmed stack from the live repository. PHP 8.4, Laravel 12, Livewire 4, Filament 5.3, Tailwind CSS 4, Pest 4, PHPUnit 12, Alpine.js 3, Laravel Sanctum 4, Laravel MCP, Laravel Boost. The application uses strict typing in all PHP files, Eloquent-first data access, middleware-driven security chains, and role-segmented routing. The four user roles are SUPERADMIN who has platform-wide access with no tenant_id, ADMIN who owns an organization and is subscription-gated with a tenant_id, MANAGER who is a legacy operational role identical to ADMIN in permissions but cannot manage organization billing settings, and TENANT who is an apartment resident scoped to a single property_id and can only submit meter readings and view their own invoices. The existing codebase has 54 controllers across five directories (Admin, Manager, Superadmin, Tenant, Enhanced), 25+ Filament resources, 7 Livewire components, 463 test files, 3 Filament panel providers (AdminPanelProvider, SuperadminPanelProvider, TenantPanelProvider), 3 BillingService classes, role-prefixed Form Request files, inline validation in Livewire components, and 10 publicly accessible debug PHP files in the public directory. The goal is to consolidate, clean, and standardize the entire application following the specifications in this prompt library. The MCP servers available in .mcp.json are laravel-mcp (php artisan mcp:start tenanto), laravel-boost (php artisan boost:mcp), and herd (local development server control). The project has 90 workspace skills in .agent/skills including tenanto-laravel-stack, tenanto-tenant-security, tenanto-billing-reporting, tenanto-lang-migration, pest-testing, tailwind-patterns, mcp-builder, architecture, database-design, testing-patterns, tdd-workflow, and vulnerability-scanner.
- `7a20c98b` test: harden public debug exposure regression checks
- `418e1350` refactor: streamline dashboard data retrieval and enhance user role redirection
- `91fc1fce` feat: introduce new events for invoice finalization and meter reading submission

### 2026-03-19
- `1b2de467` feat: expand enums for enhanced functionality and clarity
- `2e7eb99e` docs: map existing codebase
- `09be994e` docs: initialize project
- `ad655304` chore: add project config
- `0dd0c0dd` docs: complete project research
- `5178f9aa` docs: define v1 requirements
- `89d4d844` docs: create roadmap (6 phases)
- `8c0d3714` docs(01): capture phase context
- `d420d868` docs(state): record phase 1 context session
- `0e4a2aa5` docs(01): research safety freeze phase
- `552ca112` docs(phase-1): add validation strategy
- `18a7f805` docs(01-safety-freeze-and-guardrails): create phase plan
- `2e928f43` fix(01-safety-freeze-and-guardrails): revise plans based on checker feedback
- `1c4bbb05` fix(01-safety-freeze-and-guardrails): require phase 1 guardrails check
- `8ce5a2fe` fix(01): make guardrail task 1 plans green-completing
- `0588c6ed` docs(01): finalize phase plans
- `22c826f5` fix(01-01): remove live test route exposure
- `a8ce7366` docs(01-01): complete public surface inventory plan
- `967181b1` chore(01-02): remove pwa package and public surface
- `9b53e5d0` chore(01-02): refresh pwa package discovery caches
- `67663bc8` docs(01-02): complete pwa surface removal plan
- `30a5c0a2` fix(01-03): harden csp violation intake
- `abc1dbd6` docs(01-03): complete csp telemetry hardening plan
- `438bd623` chore(01-04): add local phase 1 guard command
- `d39b5ee4` ci(01-04): add phase 1 guardrails workflow
- `8b969dbb` docs(01-04): complete guardrails plan
- `ef102078` fix(01-04): guard app layout vite assets
- `9691e987` docs(state): defer phase 1 enforcement follow-up
- `78a2d562` docs(planning): add remaining milestone phase plans
- `52f5a48c` docs: capture todo - Connect MCP and activate skills
- `eea46a49` docs: capture todo - Verify public debug surface lockdown
- `b0443ce2` docs: capture todo - Finalize unified app panel behavior
- `37672feb` feat(02): enforce shared workspace boundary contracts
- `fd428865` docs: capture todo - Finalize shared design system migration
- `3f562443` test: add public surface lockdown safety verification
- `6048f835` docs: close phase 07-01 execution and roadmap progress
- `f6ff5b36` refactor: standardize parameter annotations and remove unnecessary comments in action classes

### 2026-03-23
- `11362bd1` chore: remove AGENTS.md and update composer dependencies for Laravel 13 and PHP 8.3

### 2026-03-24
- `e83aa7f9` chore: update README and configuration files; enhance property actions with superadmin checks
- `dc5fde31` feat: enrich tenant demo seeds with full readings and billing data
- `b55f25c4` feat: introduce draft for authentication foundation and role-specific pages with detailed requirements and flows
- `7c7fceda` feat: Introduce new Filament resources for tasks, tags, and assignments, enhance superadmin and tenant dashboards, and improve localization and testing.
- `e3b7c498` feat: implement superadmin user management actions including deletion, status updates, impersonation, and password resets, alongside enhanced user deletion blocking logic.
- `115d81e5` feat: Implement new Filament resources for managing billing, subscriptions, comments, attachments, and time entries, alongside new policies, jobs, and tests.
- `b2a9094d` feat: Introduce new Filament UI components, update numerous resources and models, enhance testing coverage, and refine database schema and business logic across the application.
- `c345e472` feat: Implement comprehensive localization for superadmin panel, including new translation management and system configuration pages.
- `2801450e` feat: Implement internationalization for Superadmin UI elements, audit logs, and integration health probe messages.
- `2e53b620` feat: Introduce UnitOfMeasurement enum, automatic slug generation, and update various Filament resources, models, and localization files.
- `66d83239` chore: remove unused UserStatus enum import from FormRequestScenarioFactory.
- `95d2b2f3` feat: Defer relation manager tab badge loading on organization view pages, including activity log counts, and add related tests.
- `a1d44a25` chore: Remove the 'get-shit-done' system, 'openspec' changes, and various internal project files, while adding 'ralph-loop.local.md'.
- `1b3a9315` feat: remove Framework Showcase feature and related components, tests, and configurations.
- `8b554189` feat: Add sum summarizers to BuildingsTable and refactor environment checks to use 'app()->environment('production')'.

### 2026-03-25
- `25c452ce` feat: Add translation UI recheck skill and KYC profile management actions, enhancing localization and user verification processes.

### 2026-03-28
- `7a20c86e` Fix tenant workspace consistency and regression contracts
- `aa6d1fa4` Upgrade README for current stack and workflow
- `35e6480e` Add resettable init command and refresh project context
- `2296851b` feat: Enhance organization management by adding health metrics, subscription usage tracking, and bulk actions for suspending and reinstating organizations. Update related views and tests for improved functionality.
- `1f278106` Add organizations module design spec
- `dfb20dc4` Add organizations module implementation plan
- `324d9883` feat: normalize organization list query and mrr
- `8b64dd89` feat: expand organizations list operations view
- `7c6e860a` feat: align organization csv export with list contract
- `14f2ac58` feat: queue organization announcements
- `8224a24a` feat: queue support-triggered organization exports
- `685cb04b` feat: add organization plan and ownership actions
- `ce85ed8c` feat: add organization limit overrides and feature flags
- `4897db0b` feat: add organization invoice write-off workflow
- `fb3d9291` feat: harden organization audit and impersonation flows
- `f1ebc57a` feat: add organization detail dashboard snapshots
- `794d8664` feat: add organization security health and review workflow
- `b6228967` feat: expand organization user roster support actions
- `5864fa77` feat: add organization integration health snapshot
- `3912cd53` Add organizations seeding design spec
- `cf3bdf9e` Add organizations seeding implementation plan
- `f0279e7b` feat: add organization showcase factory states
- `5e06afca` feat: seed showcase organizations across every plan
- `280d87d1` feat: scale showcase organization seed density by plan
- `26f20832` feat: align login demo seeding with showcase contracts
- `5247e312` chore: update composer.lock with package version upgrades and remove cached service files
- `2aac5196` feat: manage current org subscription from relation tab
- `28d2d2ba` feat: add manager permission matrix controls
- `57db6a00` test: isolate manager permission cache in Pest bootstrap
- `ddf3c828` feat: add organization user CRUD to roster relation
- `a43ccce3` Align manager permission coverage with explicit grants
- `294138cb` feat: refine manager permission matrix behavior
- `4204cec3` feat: hide generated slugs from admin ui
- `55dc18ea` feat: add deferred relation tab count badges
- `3df26a49` feat: seed showcase manager permission presets
- `c622e320` feat: automate changelog updates before commit
- `8b66c3d5` fix: align changelog updater support namespace
- `4868acef` docs: finalize changelog namespace cleanup entry
- `18ca4539` fix: keep changelog updates inside the current commit
- `72cdc01d` feat: let admins manage org manager memberships
- `2a70d53a` test: avoid admin nav label substring collisions
- `57202b39` feat: make changelog commits append stable entries
- `1458c8bf` fix: make post-commit changelog cleanup executable
- `c9bc5281` fix: align organization user admin surfaces
- `070d37d4` fix: seed organization user memberships consistently
- `6d628128` docs: note showcase membership seed backfill
- `02cabef0` fix: tighten organization user list affordances
- `7f13faef` test: cover manager permission summary on org user view
- `f187698e` feat: show manager permission summary on org user view
- `3fac61f7` feat: implement projects module
- `2b38ea57` fix: restore full demo login account table
- `ddede771` fix: preserve seeded tenant memberships in collaboration seeds
- `376a1789` fix: surface organization form save errors
- `73b814bf` feat: expand seeded reports and manager trial restrictions

### 2026-03-31
- `e57547a6` fix: restrict manager billing permissions for starter and basic trial subscriptions
- `ea627766` feat: add support for PHP translation files in missing translations command
- `47acc9fa` docs: map existing codebase

### 2026-04-01
- `37fc547d` feat: implement project export functionality and enhance project management features

### 2026-05-04
- `e2697f48` chore: update project configuration and documentation
- `79e15020` refactor: update topbar layout and improve accessibility features
- `45068e08` feat: enhance organization feature management and localization support
- `820d76dc` docs: update AI agent usage guidelines across documentation and enhance localization for meter readings
- `1d516d1b` feat: restrict settings page access to admins and update navigation

### 2026-05-15
- `37be4e57` chore: prepare shared hosting deployment
- `a7bd0618` chore: refresh seeded sqlite database
- `d494d6a1` docs: remove sqlite refresh changelog entry
- `e587a937` fix(auth): route app panel through Laravel

### 2026-06-14
- `20d64067` chore: update dependencies and add new Playwright test configurations
- `243d7a76` chore: update dependencies and refactor project overview data
- `28f15da4` feat: introduce agent skills and personas for AI coding agents
- `82d2f1fc` feat: add OpenReadingInvoiceCycle command and associated actions
- `606cf361` fix: correct method signature in SubmitReadingPage
- `8d07361b` feat: add prepareReadingRequestInvoice functionality and related UI actions
- `33988b8f` feat: enhance organization manager creation with invitation system
- `62969add` feat: add edit action to view pages for meter readings, meters, providers, tariffs, and users
- `3c36c01c` feat: implement manual invoice line item functionality
- `085c632a` feat: enhance invoice draft request validation with additional item fields
- `4f031d51` fix: allow optional draft invoice quantities
- `d5722d3a` feat: enhance billing command and introduce new enums for billing processes
- `f54d9921` feat: enhance extra charge types management with organization context and approval actions
- `71faac26` feat: implement new billing actions and enhance invoice management
- `599a8c9d` feat: introduce ListingLead resource with CRUD functionality
- `45986e76` feat: enhance billing actions with notification features
- `7166878f` feat: enhance property management dashboard with move-out features
- `7b12113b` fix: update fsevents version in package-lock.json and remove redundant entry

### 2026-06-15
- `45aee8ed` feat: implement tenant kyc workflow and codex auto-push
- `11162327` feat: clarify tenant billing reading workflow
- `6d2253cf` feat: clarify invoice-driven reading UX
- `8cbfc808` docs: refresh Tenanto workflow guidance
