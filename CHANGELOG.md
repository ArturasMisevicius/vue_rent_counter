# Changelog

> **AI agent usage:** Update this changelog only with verified changes, preserve historical entries, and read `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, and `docs/AI-AGENT-DOCS.md` before editing release notes.

## 2026-05-04

<!-- changelog:auto:start:staged-20260504125053 -->
### Commit updates

- updated `AGENTS.md`
- updated `CLAUDE.md`
- updated `GEMINI.md`
- updated `README.md`
- updated `app/Filament/Support/Admin/ReadingValidation/ValidateReadingValue.php`
- updated `app/Filament/Support/Tenant/Portal/TenantHomePresenter.php`
- updated `app/Http/Requests/Tenant/StoreMeterReadingRequest.php`
- updated `app/Livewire/Pages/Dashboard/TenantDashboard.php`
- updated `app/Livewire/Tenant/SubmitReadingPage.php`
- updated `app/Providers/AppServiceProvider.php`
- added `docs/AI-AGENT-DOCS.md`
- updated `docs/PROJECT-CONTEXT.md`
- updated `docs/SESSION-BOOTSTRAP.md`
- updated `docs/SKILLS-MCP-INVENTORY.md`
- updated `docs/legacy-billing-service-method-surface.md`
- updated `docs/operations/backup-restore.md`
- updated `docs/operations/phase-1-guardrails-branch-protection.md`
- updated `docs/operations/release-readiness.md`
- updated `docs/performance/2026-03-18-query-audit.md`
- updated `docs/performance/dashboard-query-audit-2026-03-18.md`
- updated `docs/security/2026-03-18-csp-rate-limits-threat-model.md`
- updated `docs/superpowers/BRANCH-PLAYBOOK.md`
- updated `docs/superpowers/EXECUTION-ROADMAP.md`
- updated `docs/superpowers/PHASE-GATES.md`
- updated `docs/superpowers/README.md`
- updated `docs/superpowers/legacy-domain-import-ledger.md`
- updated `docs/superpowers/plans/2026-03-28-organization-single-subscription-relation.md`
- updated `docs/superpowers/plans/2026-03-28-organization-user-admin-access.md`
- updated `docs/superpowers/plans/2026-03-28-organizations-module-implementation.md`
- updated `docs/superpowers/plans/2026-03-28-organizations-seeding-implementation.md`
- updated `docs/superpowers/plans/2026-03-28-projects-module-implementation.md`
- updated `docs/superpowers/plans/2026-03-28-tenant-phone-consistency.md`
- updated `docs/superpowers/specs/2026-03-17-admin-organization-operations-design.md`
- updated `docs/superpowers/specs/2026-03-17-cross-cutting-behavioral-rules-design.md`
- updated `docs/superpowers/specs/2026-03-17-foundation-auth-onboarding-design.md`
- updated `docs/superpowers/specs/2026-03-17-legacy-domain-expansion-design.md`
- updated `docs/superpowers/specs/2026-03-28-organization-single-subscription-relation-design.md`
- updated `docs/superpowers/specs/2026-03-28-organizations-module-design.md`
- updated `docs/superpowers/specs/2026-03-28-organizations-seeding-design.md`
- updated `docs/superpowers/specs/2026-03-28-projects-module-design.md`
- updated `docs/superpowers/specs/2026-03-28-tenant-phone-consistency-design.md`
- updated `lang/en/admin.php`
- added `lang/en/calendar.php`
- updated `lang/en/tenant.php`
- updated `lang/es/admin.php`
- added `lang/es/calendar.php`
- updated `lang/es/tenant.php`
- updated `lang/lt/admin.php`
- added `lang/lt/calendar.php`
- updated `lang/lt/tenant.php`
- updated `lang/ru/admin.php`
- added `lang/ru/calendar.php`
- updated `lang/ru/tenant.php`
- updated `resources/js/app.js`
- added `resources/js/calendar-modal.js`
- added `resources/views/components/shared/calendar-modal.blade.php`
- updated `resources/views/components/shell/user-avatar.blade.php`
- added `resources/views/components/tenant/action.blade.php`
- added `resources/views/components/tenant/card.blade.php`
- added `resources/views/components/tenant/detail-card.blade.php`
- added `resources/views/components/tenant/field-error.blade.php`
- added `resources/views/components/tenant/reading-row.blade.php`
- added `resources/views/components/tenant/recent-readings.blade.php`
- added `resources/views/components/tenant/section-heading.blade.php`
- added `resources/views/components/tenant/select-field.blade.php`
- added `resources/views/components/tenant/text-field.blade.php`
- updated `resources/views/filament/pages/generate-bulk-invoices.blade.php`
- updated `resources/views/filament/pages/partials/account-profile-sections.blade.php`
- updated `resources/views/livewire/pages/dashboard/tenant-dashboard.blade.php`
- updated `resources/views/livewire/pages/reports/reports-page.blade.php`
- updated `resources/views/livewire/shell/topbar.blade.php`
- updated `resources/views/livewire/tenant/home-summary.blade.php`
- updated `resources/views/livewire/tenant/invoice-history.blade.php`
- updated `resources/views/livewire/tenant/property-details.blade.php`
- updated `resources/views/livewire/tenant/submit-reading-page.blade.php`
- added `tests/Feature/Architecture/CalendarFieldContractTest.php`
- updated `tests/Feature/Tenant/TenantHomePageTest.php`
- updated `tests/Feature/Tenant/TenantProfilePageTest.php`
- updated `tests/Feature/Tenant/TenantSubmitReadingTest.php`
<!-- changelog:auto:end:staged-20260504125053 -->

<!-- changelog:auto:start:staged-20260504113042 -->
### Commit updates

- updated `.env.example`
- updated `app/Filament/Actions/Superadmin/Organizations/ToggleOrganizationFeatureAction.php`
- updated `app/Filament/Resources/Organizations/Pages/ViewOrganization.php`
- updated `app/Filament/Resources/Organizations/Tables/OrganizationsTable.php`
- updated `app/Filament/Resources/Projects/Pages/EditProject.php`
- updated `app/Filament/Resources/Projects/Pages/ViewProject.php`
- updated `app/Filament/Resources/Projects/ProjectResource.php`
- updated `app/Filament/Resources/Projects/Schemas/ProjectForm.php`
- updated `app/Filament/Resources/Projects/Tables/ProjectsTable.php`
- added `app/Filament/Support/Billing/InvoiceContentLocalizer.php`
- added `app/Filament/Support/Features/OrganizationFeatureCatalog.php`
- added `app/Filament/Support/Features/OrganizationFeatureManager.php`
- added `app/Filament/Support/Formatting/CurrencyMetadata.php`
- updated `app/Filament/Support/Formatting/EuMoneyFormatter.php`
- updated `app/Filament/Support/PublicSite/HomepageContent.php`
- updated `app/Filament/Support/Superadmin/Projects/ProjectOverviewData.php`
- updated `app/Filament/Support/Superadmin/Users/UserDossierData.php`
- updated `app/Filament/Support/Tenant/Portal/PaymentInstructionsResolver.php`
- updated `app/Filament/Support/Tenant/Portal/TenantHomePresenter.php`
- added `app/Filament/Support/Tenant/Portal/TenantMeterNameLocalizer.php`
- updated `app/Filament/Support/Tenant/Portal/TenantPropertyPresenter.php`
- updated `app/Livewire/Tenant/SubmitReadingPage.php`
- updated `app/Models/Organization.php`
- added `app/Providers/FeatureServiceProvider.php`
- updated `app/Services/Billing/InvoicePresentationService.php`
- updated `bootstrap/providers.php`
- updated `composer.json`
- updated `composer.lock`
- added `config/pennant.php`
- added `database/migrations/2026_05_04_101901_create_features_table.php`
- updated `lang/en/admin.php`
- updated `lang/en/landing.php`
- updated `lang/en/shell.php`
- updated `lang/en/superadmin.php`
- updated `lang/en/tenant.php`
- updated `lang/es/admin.php`
- updated `lang/es/enums.php`
- updated `lang/es/landing.php`
- updated `lang/es/shell.php`
- updated `lang/es/superadmin.php`
- updated `lang/es/tenant.php`
- updated `lang/lt/admin.php`
- updated `lang/lt/landing.php`
- updated `lang/lt/shell.php`
- updated `lang/lt/superadmin.php`
- updated `lang/lt/tenant.php`
- updated `lang/ru/admin.php`
- updated `lang/ru/landing.php`
- updated `lang/ru/shell.php`
- updated `lang/ru/superadmin.php`
- updated `lang/ru/tenant.php`
- updated `resources/js/app.js`
- updated `resources/views/components/shared/invoice-summary.blade.php`
- added `resources/views/components/tenant/invoice-card.blade.php`
- updated `resources/views/filament/pages/partials/account-profile-sections.blade.php`
- updated `resources/views/filament/resources/projects/audit-log-modal.blade.php`
- updated `resources/views/filament/resources/projects/overview.blade.php`
- updated `resources/views/filament/resources/users/dossier.blade.php`
- updated `resources/views/filament/resources/users/partials/dossier-tree.blade.php`
- updated `resources/views/filament/tables/columns/project-progress-bar.blade.php`
- updated `resources/views/livewire/tenant/invoice-history.blade.php`
- updated `resources/views/livewire/tenant/submit-reading-page.blade.php`
- updated `resources/views/welcome.blade.php`
- updated `tests/Feature/Auth/LoginFlowTest.php`
- added `tests/Feature/Localization/TemplateTranslationCoverageTest.php`
- updated `tests/Feature/Superadmin/OrganizationFeatureFlagsTest.php`
- updated `tests/Feature/Tenant/InvoiceExplainabilityContractTest.php`
- updated `tests/Feature/Tenant/InvoiceHistoryItemsFallbackTest.php`
- updated `tests/Feature/Tenant/TenantHomePageTest.php`
- updated `tests/Feature/Tenant/TenantInvoiceHistoryTest.php`
- updated `tests/Feature/Tenant/TenantProfilePageTest.php`
- updated `tests/Feature/Tenant/TenantSubmitReadingTest.php`
- added `tests/Unit/Support/Billing/InvoiceContentLocalizerTest.php`
- added `tests/Unit/Support/Formatting/CurrencyMetadataTest.php`
- updated `tests/Unit/Support/Formatting/EuMoneyFormatterTest.php`
- added `tests/Unit/Support/Tenant/TenantMeterNameLocalizerTest.php`
<!-- changelog:auto:end:staged-20260504113042 -->

<!-- changelog:auto:start:staged-20260504101448 -->
### Commit updates

- updated `resources/views/livewire/shell/topbar.blade.php`
- updated `tests/Feature/Shell/AuthenticatedShellTest.php`
<!-- changelog:auto:end:staged-20260504101448 -->

<!-- changelog:auto:start:staged-20260504101320 -->
### Commit updates

- added `.agents/skills/21st-dev-design/SKILL.md`
- added `.agents/skills/source-command-gsd-join-discord/SKILL.md`
- added `.agents/skills/source-command-gsd-reapply-patches/SKILL.md`
- added `.agents/skills/update-changelog-before-commit/SKILL.md`
- removed `.codex/agents/gsd-codebase-mapper.md`
- removed `.codex/agents/gsd-codebase-mapper.toml`
- removed `.codex/agents/gsd-debugger.md`
- removed `.codex/agents/gsd-debugger.toml`
- removed `.codex/agents/gsd-executor.md`
- removed `.codex/agents/gsd-executor.toml`
- removed `.codex/agents/gsd-integration-checker.md`
- removed `.codex/agents/gsd-integration-checker.toml`
- removed `.codex/agents/gsd-nyquist-auditor.md`
- removed `.codex/agents/gsd-nyquist-auditor.toml`
- removed `.codex/agents/gsd-phase-researcher.md`
- removed `.codex/agents/gsd-phase-researcher.toml`
- removed `.codex/agents/gsd-plan-checker.md`
- removed `.codex/agents/gsd-plan-checker.toml`
- removed `.codex/agents/gsd-planner.md`
- removed `.codex/agents/gsd-planner.toml`
- removed `.codex/agents/gsd-project-researcher.md`
- removed `.codex/agents/gsd-project-researcher.toml`
- removed `.codex/agents/gsd-research-synthesizer.md`
- removed `.codex/agents/gsd-research-synthesizer.toml`
- removed `.codex/agents/gsd-roadmapper.md`
- removed `.codex/agents/gsd-roadmapper.toml`
- removed `.codex/agents/gsd-ui-auditor.md`
- removed `.codex/agents/gsd-ui-auditor.toml`
- removed `.codex/agents/gsd-ui-checker.md`
- removed `.codex/agents/gsd-ui-checker.toml`
- removed `.codex/agents/gsd-ui-researcher.md`
- removed `.codex/agents/gsd-ui-researcher.toml`
- removed `.codex/agents/gsd-user-profiler.md`
- removed `.codex/agents/gsd-user-profiler.toml`
- removed `.codex/agents/gsd-verifier.md`
- removed `.codex/agents/gsd-verifier.toml`
- removed `.codex/config.toml`
- added `.codex/hooks.json`
- added `.codex/hooks/gsd-check-update.js`
- added `.codex/hooks/gsd-context-monitor.js`
- added `.codex/hooks/gsd-statusline.js`
- updated `.gitignore`
- updated `.mcp.json`
- updated `.serena/project.yml`
- removed `.vscode/settings.json`
- added `AGENTS.md`
- updated `CLAUDE.md`
- updated `GEMINI.md`
- updated `README.md`
- removed `_old/bootstrap/cache/pacBB22.tmp`
- updated `app/Console/Commands/LaravelMissingTranslationsPhpFilesCommand.php`
- updated `app/Filament/Actions/Admin/Tenants/CreateTenantAction.php`
- added `app/Filament/Actions/Profile/UpdateProfileAvatarAction.php`
- updated `app/Filament/Actions/Superadmin/Projects/ExportProjectsCsvAction.php`
- added `app/Filament/Pages/Concerns/InteractsWithProfileAvatarForms.php`
- updated `app/Filament/Pages/Dashboard.php`
- updated `app/Filament/Pages/GenerateBulkInvoices.php`
- updated `app/Filament/Pages/Profile.php`
- updated `app/Filament/Pages/TenantDashboard.php`
- updated `app/Filament/Pages/TenantInvoiceHistory.php`
- updated `app/Filament/Pages/TenantPortalPage.php`
- updated `app/Filament/Pages/TenantPropertyDetails.php`
- updated `app/Filament/Pages/TenantSubmitMeterReading.php`
- updated `app/Filament/Resources/Invoices/Schemas/CreateInvoiceForm.php`
- updated `app/Filament/Resources/Invoices/Schemas/InvoiceInfolist.php`
- updated `app/Filament/Resources/Organizations/Schemas/OrganizationForm.php`
- updated `app/Filament/Resources/Projects/Tables/ProjectsTable.php`
- updated `app/Filament/Resources/Properties/RelationManagers/InvoicesRelationManager.php`
- updated `app/Filament/Resources/Tenants/Pages/CreateTenant.php`
- updated `app/Filament/Resources/Tenants/Pages/ViewTenant.php`
- updated `app/Filament/Resources/Tenants/RelationManagers/InvoicesRelationManager.php`
- updated `app/Filament/Resources/Tenants/Schemas/TenantForm.php`
- updated `app/Filament/Resources/Tenants/Schemas/TenantInfolist.php`
- updated `app/Filament/Resources/Tenants/Tables/TenantsTable.php`
- updated `app/Filament/Resources/Tenants/TenantResource.php`
- updated `app/Filament/Support/Admin/Dashboard/AdminDashboardStats.php`
- updated `app/Filament/Support/Admin/Invoices/BulkInvoicePagePresenter.php`
- updated `app/Filament/Support/Admin/Invoices/InvoiceTablePresenter.php`
- updated `app/Filament/Support/Admin/Invoices/InvoiceViewPresenter.php`
- updated `app/Filament/Support/Admin/Reports/AbstractReportBuilder.php`
- added `app/Filament/Support/Formatting/EuMoneyFormatter.php`
- added `app/Filament/Support/Formatting/LocalizedDateFormatter.php`
- added `app/Filament/Support/Formatting/LocalizedNumberFormatter.php`
- added `app/Filament/Support/Formatting/MeasurementFormatter.php`
- added `app/Filament/Support/Profile/CroppedAvatarImage.php`
- updated `app/Filament/Support/Shell/Navigation/NavigationBuilder.php`
- updated `app/Filament/Support/Shell/Navigation/NavigationItemData.php`
- updated `app/Filament/Support/Superadmin/Dashboard/PlatformDashboardData.php`
- updated `app/Filament/Support/Superadmin/Organizations/OrganizationFinancialSnapshot.php`
- updated `app/Filament/Support/Superadmin/Organizations/OrganizationMrrResolver.php`
- updated `app/Filament/Support/Superadmin/Projects/ProjectOverviewData.php`
- updated `app/Filament/Support/Tenant/Portal/TenantHomePresenter.php`
- updated `app/Filament/Support/Tenant/Portal/TenantPropertyPresenter.php`
- updated `app/Filament/Widgets/Superadmin/PlatformStatsOverview.php`
- updated `app/Filament/Widgets/Superadmin/RevenueByPlanChart.php`
- updated `app/Http/Requests/Admin/Tenants/StoreTenantRequest.php`
- added `app/Http/Requests/Profile/UpdateProfileAvatarRequest.php`
- updated `app/Livewire/Pages/Dashboard/AdminDashboard.php`
- added `app/Livewire/Profile/ShowProfileAvatarEndpoint.php`
- updated `app/Livewire/PublicSite/HomepagePage.php`
- updated `app/Livewire/Shell/Sidebar.php`
- updated `app/Livewire/Shell/Topbar.php`
- updated `app/Livewire/Tenant/SubmitReadingPage.php`
- updated `app/Models/User.php`
- updated `app/Notifications/InvoiceOverdueReminderNotification.php`
- updated `app/Notifications/Projects/ProjectOverBudgetNotification.php`
- updated `app/Providers/Filament/AppPanelProvider.php`
- updated `app/Services/Billing/InvoicePdfDocumentFactory.php`
- updated `app/Services/Billing/InvoicePresentationService.php`
- updated `app/Services/Localization/PhpFileMissingTranslationsScanner.php`
- updated `boost.json`
- updated `composer.json`
- updated `composer.lock`
- added `config/laravel-missing-translations.php`
- removed `config/laravelmissingtranslations.php`
- updated `config/tenanto.php`
- added `database/migrations/2026_05_04_000000_add_profile_avatar_fields_to_users_table.php`
- updated `docs/PROJECT-CONTEXT.md`
- updated `docs/SESSION-BOOTSTRAP.md`
- added `docs/SKILLS-MCP-INVENTORY.md`
- updated `lang/en/admin.php`
- updated `lang/en/dashboard.php`
- updated `lang/en/requests.php`
- updated `lang/en/shell.php`
- updated `lang/en/tenant.php`
- updated `lang/es/admin.php`
- updated `lang/es/dashboard.php`
- updated `lang/es/requests.php`
- updated `lang/es/shell.php`
- updated `lang/es/tenant.php`
- updated `lang/lt/admin.php`
- updated `lang/lt/dashboard.php`
- updated `lang/lt/requests.php`
- updated `lang/lt/shell.php`
- updated `lang/lt/tenant.php`
- updated `lang/lt/validation.php`
- updated `lang/ru/admin.php`
- updated `lang/ru/dashboard.php`
- updated `lang/ru/requests.php`
- updated `lang/ru/shell.php`
- updated `lang/ru/tenant.php`
- updated `package-lock.json`
- updated `package.json`
- updated `resources/js/app.js`
- updated `resources/views/components/shared/form-section.blade.php`
- updated `resources/views/components/shared/invoice-summary.blade.php`
- updated `resources/views/components/shared/language-switcher.blade.php`
- updated `resources/views/components/shared/page-header.blade.php`
- updated `resources/views/components/shared/tenant-bottom-nav.blade.php`
- updated `resources/views/components/shell/app-frame.blade.php`
- updated `resources/views/components/shell/user-avatar.blade.php`
- updated `resources/views/components/superadmin/revenue-trend-chart.blade.php`
- added `resources/views/components/tenant/aside-panel.blade.php`
- added `resources/views/components/tenant/main-panel.blade.php`
- added `resources/views/components/tenant/page.blade.php`
- added `resources/views/components/tenant/split.blade.php`
- updated `resources/views/filament/pages/dashboard.blade.php`
- updated `resources/views/filament/pages/partials/account-profile-sections.blade.php`
- updated `resources/views/filament/pages/profile.blade.php`
- updated `resources/views/filament/pages/tenant-dashboard.blade.php`
- updated `resources/views/filament/pages/tenant-invoice-history.blade.php`
- updated `resources/views/filament/pages/tenant-property-details.blade.php`
- updated `resources/views/filament/pages/tenant-submit-meter-reading.blade.php`
- updated `resources/views/filament/resources/organizations/subscription-history.blade.php`
- updated `resources/views/livewire/pages/dashboard/tenant-dashboard.blade.php`
- updated `resources/views/livewire/shell/global-search.blade.php`
- updated `resources/views/livewire/shell/language-switcher.blade.php`
- updated `resources/views/livewire/shell/sidebar.blade.php`
- updated `resources/views/livewire/shell/topbar.blade.php`
- updated `resources/views/livewire/tenant/invoice-history.blade.php`
- updated `resources/views/livewire/tenant/property-details.blade.php`
- updated `resources/views/livewire/tenant/submit-reading-page.blade.php`
- updated `resources/views/welcome.blade.php`
- updated `routes/web.php`
- updated `tests/Feature/Admin/AdminDashboardTest.php`
- updated `tests/Feature/Admin/BulkInvoiceGenerationTest.php`
- updated `tests/Feature/Admin/FinancialAuditTrailTest.php`
- updated `tests/Feature/Admin/InvoicesResourceTest.php`
- updated `tests/Feature/Admin/TenantsResourceTest.php`
- updated `tests/Feature/Auth/LoginFlowTest.php`
- updated `tests/Feature/Billing/BillingModuleTest.php`
- updated `tests/Feature/Billing/ReportsTest.php`
- updated `tests/Feature/Livewire/Dashboard/AdminDashboardComponentTest.php`
- updated `tests/Feature/Localization/MissingTranslationsPhpFilesTest.php`
- updated `tests/Feature/Manager/ManagerSettingsVisibilityTest.php`
- updated `tests/Feature/Manager/ManagerWorkspaceParityTest.php`
- updated `tests/Feature/Notifications/NotificationSystemTest.php`
- updated `tests/Feature/Public/PublicHomepageTest.php`
- updated `tests/Feature/Shell/AuthenticatedShellTest.php`
- updated `tests/Feature/Superadmin/OrganizationExportsTest.php`
- updated `tests/Feature/Superadmin/OrganizationsListPageTest.php`
- updated `tests/Feature/Superadmin/OrganizationsViewPageTest.php`
- updated `tests/Feature/Tenant/InvoiceExplainabilityContractTest.php`
- updated `tests/Feature/Tenant/InvoiceHistoryItemsFallbackTest.php`
- updated `tests/Feature/Tenant/TenantHomePageTest.php`
- updated `tests/Feature/Tenant/TenantInvoiceHistoryTest.php`
- updated `tests/Feature/Tenant/TenantPageHeaderConsistencyTest.php`
- updated `tests/Feature/Tenant/TenantPortalNavigationTest.php`
- updated `tests/Feature/Tenant/TenantProfileKycPageTest.php`
- updated `tests/Feature/Tenant/TenantProfilePageTest.php`
- updated `tests/Feature/Tenant/TenantPropertyPageTest.php`
- updated `tests/Feature/Tenant/TenantSubmitReadingTest.php`
- updated `tests/Support/FormRequestScenarioFactory.php`
- added `tests/Unit/Support/Formatting/EuMoneyFormatterTest.php`
- added `tests/Unit/Support/Formatting/LocalizedDateFormatterTest.php`
- added `tests/Unit/Support/Formatting/LocalizedNumberFormatterTest.php`
- added `tests/Unit/Support/Formatting/MeasurementFormatterTest.php`
<!-- changelog:auto:end:staged-20260504101320 -->

## 2026-04-01

<!-- changelog:auto:start:staged-20260401000409 -->
### Commit updates

- updated `.planning/config.json`
- added `app/Filament/Actions/Superadmin/Projects/ExportProjectsCsvAction.php`
- updated `app/Filament/Resources/Projects/Pages/CreateProject.php`
- updated `app/Filament/Resources/Projects/Pages/EditProject.php`
- updated `app/Filament/Resources/Projects/Pages/ViewProject.php`
- updated `app/Filament/Resources/Projects/ProjectResource.php`
- updated `app/Filament/Resources/Projects/Schemas/ProjectForm.php`
- updated `app/Filament/Resources/Projects/Schemas/ProjectInfolist.php`
- updated `app/Filament/Resources/Projects/Tables/ProjectsTable.php`
- updated `app/Filament/Support/Superadmin/Dashboard/PlatformDashboardData.php`
- added `app/Filament/Support/Superadmin/Projects/ProjectOverviewData.php`
- updated `app/Http/Middleware/CheckManagerPermission.php`
- updated `app/Livewire/Pages/Dashboard/SuperadminDashboard.php`
- updated `app/Models/Project.php`
- updated `app/Models/Task.php`
- updated `app/Models/TimeEntry.php`
- added `app/Notifications/Projects/ProjectCancelledNotification.php`
- added `app/Notifications/Projects/ProjectCompletedNotification.php`
- updated `app/Observers/OrganizationObserver.php`
- updated `app/Observers/ProjectObserver.php`
- updated `app/Policies/ProjectPolicy.php`
- updated `app/Services/ProjectService.php`
- updated `lang/en/dashboard.php`
- updated `lang/es/dashboard.php`
- updated `lang/lt/dashboard.php`
- updated `lang/ru/dashboard.php`
- added `opencode.json`
- added `resources/views/filament/resources/projects/audit-log-modal.blade.php`
- added `resources/views/filament/resources/projects/overview.blade.php`
- added `resources/views/filament/tables/columns/project-progress-bar.blade.php`
- updated `resources/views/livewire/pages/dashboard/superadmin-dashboard.blade.php`
- updated `routes/console.php`
- added `tests/Feature/Admin/ManagerPermissionMiddlewareTest.php`
- updated `tests/Feature/Admin/ManagerPermissionSystemTest.php`
- updated `tests/Feature/Livewire/Dashboard/DashboardPageTest.php`
- updated `tests/Feature/Livewire/Dashboard/SuperadminDashboardComponentTest.php`
- updated `tests/Feature/Projects/ProjectCostsAndAlertsTest.php`
- updated `tests/Feature/Projects/ProjectLifecycleTest.php`
- updated `tests/Feature/Projects/ProjectResourceTest.php`
- updated `tests/Feature/Superadmin/SuperadminDashboardTest.php`
<!-- changelog:auto:end:staged-20260401000409 -->

## 2026-03-31

<!-- changelog:auto:start:staged-20260331200925 -->
### Commit updates

- updated `.codex/config.toml`
- updated `.planning/codebase/ARCHITECTURE.md`
- updated `.planning/codebase/CONCERNS.md`
- updated `.planning/codebase/CONVENTIONS.md`
- updated `.planning/codebase/INTEGRATIONS.md`
- updated `.planning/codebase/STACK.md`
- updated `.planning/codebase/STRUCTURE.md`
- updated `.planning/codebase/TESTING.md`
<!-- changelog:auto:end:staged-20260331200925 -->

<!-- changelog:auto:start:pending -->
### Pending staged changes

- updated `.codex/config.toml`
- updated `.planning/codebase/ARCHITECTURE.md`
- updated `.planning/codebase/CONCERNS.md`
- updated `.planning/codebase/CONVENTIONS.md`
- updated `.planning/codebase/INTEGRATIONS.md`
- updated `.planning/codebase/STACK.md`
- updated `.planning/codebase/STRUCTURE.md`
- updated `.planning/codebase/TESTING.md`
<!-- changelog:auto:end:pending -->

<!-- changelog:auto:start:staged-20260331194242 -->
### Commit updates

- removed `.sisyphus/ralph-loop.local.md`
- added `app/Console/Commands/LaravelMissingTranslationsPhpFilesCommand.php`
- updated `app/Providers/AppServiceProvider.php`
- added `app/Services/Localization/PhpFileMissingTranslationsScanner.php`
- updated `composer.json`
- updated `composer.lock`
- added `config/laravelmissingtranslations.php`
- updated `lang/en/admin.php`
- updated `lang/en/superadmin.php`
- updated `lang/es/admin.php`
- updated `lang/es/superadmin.php`
- updated `lang/lt/admin.php`
- updated `lang/lt/superadmin.php`
- updated `lang/ru/admin.php`
- updated `lang/ru/superadmin.php`
- added `lang/vendor/filament-actions/en/edit.php`
- added `lang/vendor/filament-actions/en/view.php`
- added `lang/vendor/filament-actions/es/edit.php`
- added `lang/vendor/filament-actions/es/view.php`
- added `lang/vendor/filament-actions/lt/edit.php`
- added `lang/vendor/filament-actions/lt/view.php`
- added `lang/vendor/filament-actions/ru/edit.php`
- added `lang/vendor/filament-actions/ru/view.php`
- added `lang/vendor/filament-panels/en/layout.php`
- added `lang/vendor/filament-panels/es/layout.php`
- added `lang/vendor/filament-panels/lt/layout.php`
- added `lang/vendor/filament-panels/ru/layout.php`
- updated `tests/Feature/Admin/AdminDashboardTest.php`
- updated `tests/Feature/Admin/BulkInvoiceGenerationTest.php`
- updated `tests/Feature/Admin/FinancialAuditTrailTest.php`
- updated `tests/Feature/Admin/InvoicesResourceTest.php`
- updated `tests/Feature/Admin/ReportsPageTest.php`
- updated `tests/Feature/Admin/TenantUnassignmentInvoiceRetentionTest.php`
- updated `tests/Feature/Billing/BillingEligibilityConsistencyTest.php`
- updated `tests/Feature/Billing/BillingModuleTest.php`
- updated `tests/Feature/Billing/InvoiceOverduePolicyTest.php`
- updated `tests/Feature/Billing/ReportsTest.php`
- updated `tests/Feature/Livewire/Dashboard/AdminDashboardComponentTest.php`
- added `tests/Feature/Localization/MissingTranslationsPhpFilesTest.php`
- updated `tests/Feature/Models/ModelScopeOptimizationTest.php`
- updated `tests/Feature/Notifications/NotificationSystemTest.php`
- updated `tests/Support/TenantPortalFactory.php`
<!-- changelog:auto:end:staged-20260331194242 -->

<!-- changelog:auto:start:staged-20260331184152 -->
### Commit updates

- updated `.sisyphus/ralph-loop.local.md`
- updated `app/Filament/Resources/Attachments/Pages/ViewAttachment.php`
- updated `app/Filament/Resources/Buildings/Pages/ViewBuilding.php`
- updated `app/Filament/Resources/CommentReactions/Pages/ViewCommentReaction.php`
- updated `app/Filament/Resources/Comments/Pages/ViewComment.php`
- updated `app/Filament/Resources/InvoiceEmailLogs/Pages/ViewInvoiceEmailLog.php`
- updated `app/Filament/Resources/InvoiceItems/Pages/ViewInvoiceItem.php`
- updated `app/Filament/Resources/InvoicePayments/Pages/ViewInvoicePayment.php`
- updated `app/Filament/Resources/InvoiceReminderLogs/Pages/ViewInvoiceReminderLog.php`
- updated `app/Filament/Resources/Invoices/Pages/ViewInvoice.php`
- updated `app/Filament/Resources/MeterReadings/Pages/ViewMeterReading.php`
- updated `app/Filament/Resources/Meters/Pages/ViewMeter.php`
- updated `app/Filament/Resources/OrganizationUsers/Pages/ViewOrganizationUser.php`
- updated `app/Filament/Resources/Organizations/Pages/ViewOrganization.php`
- added `app/Filament/Resources/Pages/ViewRecord.php`
- updated `app/Filament/Resources/Projects/Pages/ViewProject.php`
- updated `app/Filament/Resources/Properties/Pages/ViewProperty.php`
- updated `app/Filament/Resources/PropertyAssignments/Pages/ViewPropertyAssignment.php`
- updated `app/Filament/Resources/Providers/Pages/ViewProvider.php`
- updated `app/Filament/Resources/ServiceConfigurations/Pages/ViewServiceConfiguration.php`
- updated `app/Filament/Resources/SubscriptionPayments/Pages/ViewSubscriptionPayment.php`
- updated `app/Filament/Resources/SubscriptionRenewals/Pages/ViewSubscriptionRenewal.php`
- updated `app/Filament/Resources/Subscriptions/Pages/ViewSubscription.php`
- updated `app/Filament/Resources/Tags/Pages/ViewTag.php`
- updated `app/Filament/Resources/Tariffs/Pages/ViewTariff.php`
- updated `app/Filament/Resources/TaskAssignments/Pages/ViewTaskAssignment.php`
- updated `app/Filament/Resources/Tasks/Pages/ViewTask.php`
- updated `app/Filament/Resources/Tenants/Pages/ViewTenant.php`
- updated `app/Filament/Resources/TimeEntries/Pages/ViewTimeEntry.php`
- updated `app/Filament/Resources/UserKycProfiles/Pages/ViewUserKycProfile.php`
- updated `app/Filament/Resources/Users/Pages/ViewUser.php`
- updated `app/Filament/Resources/Users/Schemas/UserInfolist.php`
- updated `app/Filament/Resources/UtilityServices/Pages/ViewUtilityService.php`
- updated `app/Filament/Support/Auth/LoginDemoAccountPresenter.php`
- added `app/Filament/Support/Superadmin/Users/UserDossierData.php`
- updated `app/Livewire/Auth/LoginPage.php`
- updated `resources/views/auth/login.blade.php`
- added `resources/views/filament/resources/users/dossier.blade.php`
- added `resources/views/filament/resources/users/partials/dossier-tree.blade.php`
- updated `tests/Feature/Auth/LoginDemoAccountsTest.php`
- added `tests/Feature/Filament/ViewRecordWidthContractTest.php`
- updated `tests/Feature/Superadmin/UsersResourceTest.php`
<!-- changelog:auto:end:staged-20260331184152 -->

## 2026-03-28

<!-- changelog:auto:start:staged-20260328183354 -->
### Commit updates

- updated `app/Filament/Resources/Organizations/Pages/CreateOrganization.php`
- updated `app/Filament/Resources/Organizations/Pages/EditOrganization.php`
- updated `app/Filament/Resources/Organizations/Schemas/OrganizationForm.php`
- added `app/Filament/Resources/Pages/Concerns/InteractsWithRecordFormValidationExceptions.php`
- updated `tests/Feature/Superadmin/OrganizationsCreateEditPageTest.php`
<!-- changelog:auto:end:staged-20260328183354 -->

<!-- changelog:auto:start:staged-20260328183013 -->
### Commit updates

- updated `database/factories/OrganizationUserFactory.php`
- updated `database/seeders/LegacyCollaborationFoundationSeeder.php`
- added `tests/Feature/Admin/LegacyCollaborationFoundationSeederTest.php`
<!-- changelog:auto:end:staged-20260328183013 -->

<!-- changelog:auto:start:staged-20260328182459 -->
### Commit updates

- updated `app/Filament/Support/Auth/LoginDemoAccountPresenter.php`
- updated `app/Models/User.php`
- updated `tests/Feature/Auth/LoginDemoAccountsTest.php`
<!-- changelog:auto:end:staged-20260328182459 -->

<!-- changelog:auto:start:staged-20260328180833 -->
### Commit updates

- added `app/Enums/ProjectCostRecordType.php`
- added `app/Enums/ProjectPriority.php`
- added `app/Enums/ProjectStatus.php`
- added `app/Enums/ProjectTeamRole.php`
- added `app/Enums/ProjectType.php`
- added `app/Exceptions/InvalidProjectTransitionException.php`
- added `app/Exceptions/ProjectApprovalRequiredException.php`
- added `app/Exceptions/ProjectCostPassthroughException.php`
- added `app/Exceptions/ProjectDeletionBlockedException.php`
- updated `app/Filament/Resources/Projects/Pages/CreateProject.php`
- updated `app/Filament/Resources/Projects/Pages/ViewProject.php`
- updated `app/Filament/Resources/Projects/ProjectResource.php`
- updated `app/Filament/Resources/Projects/Schemas/ProjectForm.php`
- updated `app/Filament/Resources/Projects/Schemas/ProjectInfolist.php`
- updated `app/Filament/Resources/Projects/Tables/ProjectsTable.php`
- added `app/Jobs/Projects/RescopeProjectChildrenJob.php`
- added `app/Models/CostRecord.php`
- updated `app/Models/InvoiceItem.php`
- updated `app/Models/OrganizationSetting.php`
- updated `app/Models/Project.php`
- added `app/Models/ProjectUser.php`
- updated `app/Models/Task.php`
- updated `app/Models/TimeEntry.php`
- added `app/Notifications/Projects/ProjectApprovalRequestedNotification.php`
- added `app/Notifications/Projects/ProjectApprovedNotification.php`
- added `app/Notifications/Projects/ProjectEmergencyCreatedNotification.php`
- added `app/Notifications/Projects/ProjectOverBudgetNotification.php`
- added `app/Notifications/Projects/ProjectOverdueAlertNotification.php`
- added `app/Notifications/Projects/ProjectStalledAlertNotification.php`
- added `app/Notifications/Projects/ProjectUnapprovedEscalationNotification.php`
- added `app/Notifications/Projects/ProjectUnapprovedReminderNotification.php`
- added `app/Observers/ProjectObserver.php`
- updated `app/Policies/ProjectPolicy.php`
- updated `app/Providers/AppServiceProvider.php`
- added `app/Services/ProjectService.php`
- added `database/factories/CostRecordFactory.php`
- updated `database/factories/OrganizationSettingFactory.php`
- updated `database/factories/ProjectFactory.php`
- added `database/factories/ProjectUserFactory.php`
- updated `database/factories/PropertyFactory.php`
- updated `database/factories/TaskFactory.php`
- updated `database/factories/TimeEntryFactory.php`
- added `database/migrations/2026_03_28_180000_expand_projects_module_tables.php`
- added `database/migrations/2026_03_28_180100_create_cost_records_table.php`
- added `database/migrations/2026_03_28_180200_create_project_users_table.php`
- updated `database/seeders/LegacyCollaborationFoundationSeeder.php`
- updated `database/seeders/OperationalDemoDatasetSeeder.php`
- added `docs/superpowers/plans/2026-03-28-projects-module-implementation.md`
- added `docs/superpowers/specs/2026-03-28-projects-module-design.md`
- updated `lang/en/enums.php`
- updated `lang/es/enums.php`
- updated `lang/lt/enums.php`
- updated `lang/ru/enums.php`
- updated `routes/console.php`
- added `tests/Feature/Projects/ProjectCostsAndAlertsTest.php`
- added `tests/Feature/Projects/ProjectLifecycleTest.php`
- added `tests/Feature/Projects/ProjectResourceTest.php`
- updated `tests/Feature/Superadmin/RelationCrudResourcesTest.php`
- updated `tests/Feature/Superadmin/RelationResourceListContextTest.php`
<!-- changelog:auto:end:staged-20260328180833 -->

### fix: keep legacy collaboration seeds aligned with roster roles

- stopped the legacy collaboration seeder from overwriting a demo tenant membership to `manager` just to attach collaboration data
- tied the seeded legacy collaboration project to a real manager and added regression coverage for the preserved tenant roster role
- normalized `OrganizationUserFactory` defaults to supported membership roles and removed the stale default legacy permissions payload

### fix: restrict manager billing permissions on starter and basic trials

- extended the manager permission matrix availability rules so billing-side resources are marked unavailable for starter and basic trial subscriptions, not only pending organizations
- kept the restricted billing resource set aligned across billing, invoices, tariffs, providers, service configurations, and utility services
- added Livewire regression coverage to ensure restricted trial plans surface the “not available on your current plan” state in the manager permission matrix

### feat: make seeded reports usable for superadmins and current-period demos

- added a superadmin organization selector on the unified reports page so cross-organization reporting works without requiring a tenant-scoped organization context
- defaulted superadmin reports to the seeded demo organization and wired report widgets, filters, exports, and option lists through the selected organization
- refreshed the login-demo and operational dataset seeders so current-period invoices and meter readings populate revenue, outstanding balances, and meter compliance reports with realistic paid, overdue, partial, pending, rejected, and missing states
- added focused admin report page and seed-coverage Pest coverage for the new superadmin selector and seeded current-period report output

### feat: implement the projects module end to end

- added the full projects domain contract with project status, priority, type, and cost-record enums plus project-specific exceptions, observer logic, service workflows, notifications, and scheduled alert commands
- expanded project persistence with cost records and project team memberships, added automatic project reference sequencing through organization settings, and wired project cost, approval, completion, and child-rescoping behavior into the live models
- rebuilt the Filament projects resource for cross-organization superadmin visibility and organization-scoped admin visibility with richer forms, list filters, infolists, header actions, and bulk actions
- added focused Pest coverage for project lifecycle, costs and alerts, resource behavior, and updated affected superadmin/admin regression contracts
- aligned legacy and demo seeders plus locale enum dictionaries with the new project enum contract so full reseeding and translation parity continue to pass

<!-- changelog:auto:start:staged-20260328171834 -->
### Commit updates

- updated `app/Filament/Resources/OrganizationUsers/Schemas/OrganizationUserInfolist.php`
- updated `app/Filament/Support/Admin/ManagerPermissions/ManagerPermissionCatalog.php`
- updated `lang/en/admin.php`
- updated `lang/es/admin.php`
- updated `lang/lt/admin.php`
- updated `lang/ru/admin.php`
- updated `tests/Feature/Admin/OrganizationUsersResourceTest.php`
<!-- changelog:auto:end:staged-20260328171834 -->

<!-- changelog:auto:start:staged-20260328170935 -->
### Commit updates

- updated `tests/Feature/Admin/OrganizationUsersResourceTest.php`
<!-- changelog:auto:end:staged-20260328170935 -->

<!-- changelog:auto:start:staged-20260328170244 -->
### Commit updates

- updated `app/Filament/Resources/OrganizationUsers/Pages/ListOrganizationUsers.php`
- updated `app/Filament/Resources/OrganizationUsers/Tables/OrganizationUsersTable.php`
- updated `tests/Feature/Admin/OrganizationUsersResourceTest.php`
<!-- changelog:auto:end:staged-20260328170244 -->

### feat: surface manager permission summaries on organization user views

- replaced the legacy raw membership `permissions` display for manager memberships with a read-only summary sourced from the live manager permission matrix
- added a read-only fallback summary for managers who still have zero granted write permissions across all resources
- localized the inviter label on the organization-user infolist and added focused Pest coverage for both granted and default-read-only summaries

### fix: tighten organization user list affordances

- removed the dead create action from the organization-user list for org admins while preserving it for superadmins
- limited organization-user bulk deletion affordances to superadmins and made the bulk action authorization explicit
- hid the redundant organization column for org-admin manager-membership views while keeping it visible in the superadmin list
- added focused Pest coverage for the admin versus superadmin organization-user list contract

### fix: seed showcase organization memberships

- synced `organization_user` membership rows for showcase admins, managers, and tenants inside `OperationalDemoDatasetSeeder`
- added seeder regression coverage to ensure every showcase user receives a scoped membership row with an inviter
- preserved idempotent reseeding while aligning showcase data with the organization-user admin surfaces

<!-- changelog:auto:start:staged-20260328165420 -->
### Commit updates

- updated `database/seeders/OperationalDemoDatasetSeeder.php`
<!-- changelog:auto:end:staged-20260328165420 -->

<!-- changelog:auto:start:staged-20260328165017 -->
### Commit updates

- updated `app/Filament/Resources/OrganizationUsers/Pages/EditOrganizationUser.php`
- updated `app/Filament/Resources/OrganizationUsers/Pages/ViewOrganizationUser.php`
- updated `app/Filament/Resources/OrganizationUsers/Schemas/OrganizationUserInfolist.php`
- updated `app/Filament/Resources/Pages/Concerns/HasContainedSuperadminSurface.php`
- updated `tests/Feature/Admin/OperationalDemoDatasetSeederTest.php`
- updated `tests/Feature/Admin/OrganizationUsersResourceTest.php`
<!-- changelog:auto:end:staged-20260328165017 -->

<!-- changelog:auto:start:staged-20260328163744 -->
### Commit updates

- updated `.githooks/post-commit`
<!-- changelog:auto:end:staged-20260328163744 -->

<!-- changelog:auto:start:staged-20260328163633 -->
### Commit updates

- updated `.agent/skills/update-changelog-before-commit/SKILL.md`
- updated `.ai/skills/update-changelog-before-commit/SKILL.md`
- updated `.claude/skills/update-changelog-before-commit/SKILL.md`
- updated `.cursor/skills/update-changelog-before-commit/SKILL.md`
- updated `.gemini/skills/update-changelog-before-commit/SKILL.md`
- added `.githooks/post-commit`
- updated `.githooks/pre-commit`
- updated `scripts/update_changelog.php`
- added `tests/Feature/Console/UpdateChangelogScriptTest.php`
<!-- changelog:auto:end:staged-20260328163633 -->

### test: avoid admin nav label substring collisions

- tightened the admin unified-panel regression to assert the superadmin users route is absent instead of matching the raw "Users" label text
- preserved the admin Organization Users navigation entry without letting substring collisions create false failures

### feat: let admins manage org manager memberships

- opened the Organization Users resource to org admins and owners only for manager memberships inside their current organization
- scoped the resource query and policy checks so non-manager memberships and outside-organization records stay inaccessible
- locked admin-side membership fields while keeping the manager permission matrix available on the edit surface
- added focused admin, shell, matrix, and coverage-inventory Pest regression tests for the new access path

### fix: keep changelog updates inside the current commit

- documented the hook lifecycle change that moved changelog mutation away from `commit-msg`
- aligned the shared changelog-update skill copies with the repository hook behavior

<!-- changelog:auto:start:commit-20260328160318 -->
### fix: align changelog updater support namespace

- renamed `app/Support/Changelog/GitChangelogUpdater.php` to `app/Filament/Support/Changelog/GitChangelogUpdater.php`
- updated `scripts/update_changelog.php`
- renamed `tests/Unit/Support/Changelog/GitChangelogUpdaterTest.php` to `tests/Unit/Filament/Support/Changelog/GitChangelogUpdaterTest.php`
<!-- changelog:auto:end:commit-20260328160318 -->

<!-- changelog:auto:start:commit-20260328155531 -->
### feat: automate changelog updates before commit

- added `.agent/skills/update-changelog-before-commit/SKILL.md`
- added `.ai/skills/update-changelog-before-commit/SKILL.md`
- added `.claude/skills/update-changelog-before-commit/SKILL.md`
- added `.cursor/skills/update-changelog-before-commit/SKILL.md`
- added `.gemini/skills/update-changelog-before-commit/SKILL.md`
- added `.githooks/commit-msg`
- added `.githooks/pre-commit`
- added `app/Filament/Support/Changelog/GitChangelogUpdater.php`
- added `scripts/install-git-hooks.sh`
- added `scripts/update_changelog.php`
- added `tests/Unit/Filament/Support/Changelog/GitChangelogUpdaterTest.php`
<!-- changelog:auto:end:commit-20260328155531 -->

### Hidden generated slugs in admin UI

- removed organization slugs from the superadmin organizations list, organization detail overview, record subheading, and organization summary exports
- removed organization slugs from superadmin global search results and stopped matching organizations by slug in the visible search UI
- removed tag slugs from the tag list and tag detail pages while keeping slug auto-generation at the model layer
- removed slug display from the recently created organizations widget and added regression coverage for the hidden-slug contract

### Deferred relation tab count badges

- enabled deferred relation-tab badge counts across building, property, tenant, meter, and organization record views so relation tabs consistently show right-aligned object counts
- added fallback badge counting for relation managers that are rendered without preloaded `*_count` attributes, keeping badges correct on direct record views and after tab switches
- added focused Pest coverage for deferred relation-tab badges across the superadmin organizations view and the admin building, property, tenant, and meter resources

### Organization single-subscription relation

- changed the organization `Subscriptions` relation tab to manage only the current subscription record
- added relation-scoped create when an organization has no subscription yet
- added relation-scoped edit for the current subscription while preserving history access through the existing modal
- added request and action classes for creating and updating organization subscriptions from the relation manager
- added focused Pest coverage for the new single-subscription relation behavior

### Manager permission matrix

- added a manager permission matrix system with dedicated model, factory, migration, exceptions, catalog, service, notification, and Livewire-backed superadmin editor
- gated manager write access through new policies, resource middleware, and navigation filtering so manager mutations are explicitly permissioned per resource
- synchronized manager membership state through observers and seeded the login demo workspace with organization memberships and a default property-manager preset
- added focused manager permission regression coverage across admin resources, the superadmin organization-user editor, and manager workspace parity
- aligned legacy admin resource and tenant UI coverage with explicit manager permission fixtures so managers stay read-only by default unless their matrix grants write access
- flushed the in-memory manager permission cache in Pest bootstrap so request-scoped permission checks stay isolated across feature tests
- keyed the in-memory manager permission cache by organization and user identity so equivalent model instances reuse the same per-request permission matrix
- scoped the “changes take effect immediately” banner to superadmin-only matrix contexts instead of showing it for every manager-permission editor

### Demo manager presets

- added a second seeded demo manager account with the billing-manager preset so both property and billing permission profiles are visible in demo data
- updated the curated login demo account presenter and Pest coverage to include the new billing manager example

### Showcase manager permission presets

- seeded the five Baltic showcase organizations with deterministic manager permission profiles so the operational demo dataset now exercises read-only, property, billing, full-access, and custom utility-manager matrices
- added Pest coverage to keep those showcase manager permission rows idempotent across repeated database seeding runs

### Subscription request validation coverage

- added request-structure and validation scenario coverage for superadmin organization subscription create and update requests

### Superadmin organization roster management

- added create, edit, and delete roster actions to the superadmin organization users relation manager with shared roster form components
- added organization roster store and update requests plus validation scenario coverage for the new superadmin user-management flow
- added translated action and notification strings for the superadmin organization user roster in English, Lithuanian, Russian, and Spanish
