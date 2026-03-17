# Shared Interface Elements Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the authenticated Tenanto shell so every signed-in experience gains a role-aware top bar, navigation chrome, locale switcher, notifications center, pluggable global search, impersonation banner, and branded error pages.

**Architecture:** Keep Filament 5 as the authenticated shell for superadmin, admin, and manager users, but replace its topbar and sidebar with custom Livewire components so the shared interface matches the product spec instead of fighting Filament defaults. Use a shared Blade shell component for tenant pages and error views, backed by small support classes for navigation, avatar colors, locale persistence, notification presentation, search-provider registration, and impersonation session state. Because the repo does not yet contain buildings, properties, meters, readings, or invoices, ship global search as an extensible registry with route-safe providers and empty-state handling rather than inventing unfinished CRUD pages.

**Tech Stack:** Laravel 12, Filament 5, Livewire 4, Blade, Tailwind CSS v4, Alpine.js, Laravel database notifications, SQLite, Pest 4, Laravel Pint.

---

## Spec Reference

- Spec: `docs/superpowers/specs/2026-03-17-shared-interface-elements-design.md`
- Requirements source: user-provided “Shared Interface Elements” specification from the 2026-03-17 conversation.
- Supporting baseline: `docs/superpowers/specs/2026-03-17-foundation-auth-onboarding-design.md`
- Skills to use during execution: `@laravel-11-12-app-guidelines`, `@tailwindcss-development`, `@pest-testing`, `@livewire-development`

## Scope Notes

- Keep Filament in place for superadmin/admin/manager routes. Do not replace the admin area with a custom router or a second dashboard framework.
- Keep tenant pages as Blade routes, but move them under the same branded shell language and Livewire-powered topbar.
- Only these domain models currently exist: `User`, `Organization`, `Subscription`, `OrganizationInvitation`. Search and navigation must therefore be extensible and route-safe.
- Notifications can ship now using Laravel’s database notifications table even if most notification producers arrive later.
- This plan includes impersonation banner rendering plus stop-impersonation flow and session contract. The “start impersonating from organization view” trigger belongs to the later platform-management slice.
- This plan includes a lightweight profile destination so the avatar menu has a real target. The full profile/settings spec should replace that placeholder in a later slice.

## File Map

### Create

- `config/tenanto.php` — supported locales, shell polling intervals, search debounce, role-aware search groups, route-name placeholders.
- `app/Support/Shell/DashboardUrlResolver.php` — resolves the correct “back to dashboard” URL for the current user or guest fallback.
- `app/Support/Shell/UserAvatarColor.php` — deterministic avatar color token selection from user name.
- `app/Support/Shell/Navigation/NavigationItemData.php` — immutable navigation item payload.
- `app/Support/Shell/Navigation/NavigationGroupData.php` — immutable navigation group payload.
- `app/Support/Shell/Navigation/NavigationBuilder.php` — builds role-aware groups, active states, and route-safe items.
- `app/Support/Shell/Search/Contracts/GlobalSearchProvider.php` — provider interface for search sources.
- `app/Support/Shell/Search/Data/GlobalSearchResultData.php` — immutable search-result payload.
- `app/Support/Shell/Search/GlobalSearchRegistry.php` — coordinates provider lookup by role and route availability.
- `app/Support/Shell/Search/Providers/OrganizationSearchProvider.php` — first provider for routable organization results when routes exist.
- `app/Support/Shell/Search/Providers/UserSearchProvider.php` — first provider for routable user results when routes exist.
- `app/Support/Shell/Notifications/DatabaseNotificationPresenter.php` — formats unread/read notifications into UI-friendly labels and relative time copy.
- `app/Support/Auth/ImpersonationManager.php` — owns impersonation session keys, current state, and stop flow.
- `app/Actions/Preferences/UpdateUserLocaleAction.php` — persists locale changes and updates app locale for current request.
- `app/Http/Controllers/Auth/StopImpersonationController.php` — POST endpoint to stop impersonation cleanly.
- `app/Http/Controllers/Profile/EditProfileController.php` — lightweight authenticated profile landing page for all roles.
- `app/View/Components/Shell/AppFrame.php` — shared authenticated Blade shell wrapper for tenant pages and error pages.
- `app/Livewire/Shell/Topbar.php` — shared topbar Livewire component used by Filament and tenant shell.
- `app/Livewire/Shell/Sidebar.php` — custom Filament sidebar component for superadmin/admin/manager.
- `app/Livewire/Shell/TenantBottomNavigation.php` — bottom navigation component for tenant routes.
- `app/Livewire/Shell/LanguageSwitcher.php` — locale switcher dropdown with instant persistence.
- `app/Livewire/Shell/NotificationCenter.php` — notifications bell, panel, mark-read, and mark-all-read behavior.
- `app/Livewire/Shell/GlobalSearch.php` — search trigger, overlay/panel, debounce, keyboard dismissal, grouped results.
- `resources/views/components/shell/app-frame.blade.php` — shell wrapper markup with slots for content and tenant bottom nav.
- `resources/views/components/shell/brand.blade.php` — Tenanto logo/wordmark partial shared by topbar and auth pages when needed.
- `resources/views/components/shell/user-avatar.blade.php` — deterministic initials avatar partial.
- `resources/views/components/shell/impersonation-banner.blade.php` — persistent impersonation banner markup.
- `resources/views/livewire/shell/topbar.blade.php`
- `resources/views/livewire/shell/sidebar.blade.php`
- `resources/views/livewire/shell/tenant-bottom-navigation.blade.php`
- `resources/views/livewire/shell/language-switcher.blade.php`
- `resources/views/livewire/shell/notification-center.blade.php`
- `resources/views/livewire/shell/global-search.blade.php`
- `resources/views/profile/edit.blade.php` — temporary but real “My Profile” destination.
- `resources/views/errors/403.blade.php`
- `resources/views/errors/404.blade.php`
- `resources/views/errors/500.blade.php`
- `database/migrations/2026_03_17_100000_create_notifications_table.php`
- `lang/en/shell.php`
- `lang/lt/shell.php`
- `lang/ru/shell.php`
- `lang/es/shell.php`
- `tests/Feature/Shell/AuthenticatedShellTest.php`
- `tests/Feature/Shell/LocaleSwitcherTest.php`
- `tests/Feature/Shell/NotificationCenterTest.php`
- `tests/Feature/Shell/GlobalSearchTest.php`
- `tests/Feature/Shell/ImpersonationBannerTest.php`
- `tests/Feature/Shell/ErrorPagesTest.php`

### Modify

- `app/Providers/AppServiceProvider.php` — register search providers and shell support singletons in the container.
- `app/Providers/Filament/AdminPanelProvider.php` — replace Filament topbar/sidebar components and register render hooks for impersonation/banner islands.
- `app/Enums/UserRole.php` — add translated labels and any shell-facing helpers instead of hardcoding role names in views.
- `app/Support/Auth/LoginRedirector.php` — expose dashboard-route behavior for shell/error use without duplicating role logic.
- `app/Models/User.php` — add any minimal helper needed for shell display only if it keeps Blade logic slim.
- `routes/web.php` — profile page, stop-impersonation route, and any tenant shell destinations required by the new chrome.
- `resources/views/layouts/app.blade.php` — convert the existing authenticated layout into the shared tenant/error shell entry point.
- `resources/views/tenant/home.blade.php` — move content into the shared shell and add the topbar/bottom-nav frame.
- `resources/views/filament/pages/platform-dashboard.blade.php` — keep content inside the new Filament chrome assumptions.
- `resources/views/filament/pages/organization-dashboard.blade.php` — same as above.
- `resources/views/layouts/guest.blade.php` — optionally reuse the new shared brand partial.
- `resources/css/app.css` — shell variables, layout utilities, search overlay states, bell badge styles, banner styles.
- `resources/js/app.js` — mobile search expansion, escape handling, and any tiny shell-only progressive enhancement that does not belong in Livewire.

### Deliberately Out of Scope

- CRUD pages for organizations, users, buildings, properties, tenants, meters, readings, invoices, tariffs, providers, or reports.
- Full profile/settings editing behavior.
- The UI action that starts impersonation from platform organization screens.
- Real provider implementations for entities that do not exist in the current repo.

## Chunk 1: Shared Shell Foundation

### Task 1: Build the shared shell frame and smoke-test the chrome

**Files:**
- Create: `config/tenanto.php`
- Create: `app/View/Components/Shell/AppFrame.php`
- Create: `resources/views/components/shell/app-frame.blade.php`
- Create: `resources/views/components/shell/brand.blade.php`
- Create: `resources/views/components/shell/user-avatar.blade.php`
- Create: `app/Support/Shell/DashboardUrlResolver.php`
- Create: `app/Support/Shell/UserAvatarColor.php`
- Create: `tests/Feature/Shell/AuthenticatedShellTest.php`
- Create: `lang/en/shell.php`
- Create: `lang/lt/shell.php`
- Create: `lang/ru/shell.php`
- Create: `lang/es/shell.php`
- Modify: `resources/views/layouts/app.blade.php`
- Modify: `resources/views/tenant/home.blade.php`
- Modify: `resources/views/layouts/guest.blade.php`

- [ ] **Step 1: Write the failing shell smoke tests**

Create `tests/Feature/Shell/AuthenticatedShellTest.php` with two core checks:

```php
it('renders tenant pages inside the authenticated shell', function () {
    $tenant = User::factory()->tenant()->create();

    $this->actingAs($tenant)
        ->get(route('tenant.home'))
        ->assertOk()
        ->assertSeeText('Search anything')
        ->assertSeeText('Home')
        ->assertSeeText('Profile');
});

it('renders a shared chrome shell around admin pages', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.organization-dashboard'))
        ->assertOk()
        ->assertSeeText('Search anything');
});
```

- [ ] **Step 2: Run the smoke tests to verify the shell does not exist yet**

Run:

```bash
php artisan test --compact tests/Feature/Shell/AuthenticatedShellTest.php
```

Expected: FAIL because no shared shell landmarks or bottom navigation are rendered.

- [ ] **Step 3: Add the shell configuration and shared frame primitives**

Create `config/tenanto.php` with:

- supported locales (`en`, `lt`, `ru`, `es`)
- locale labels in native language
- shell polling intervals (`notifications`, `tenant_home`)
- role-to-search-group mappings
- route placeholders for later modules

Create `AppFrame`, `brand`, `user-avatar`, `DashboardUrlResolver`, and `UserAvatarColor` so later components have a stable home instead of putting logic into Blade.

- [ ] **Step 4: Move tenant home into the authenticated shell**

Refactor `resources/views/layouts/app.blade.php` to become the shared authenticated layout for non-Filament pages, then update `resources/views/tenant/home.blade.php` so its content lives inside the shell frame rather than a standalone document.

- [ ] **Step 5: Re-run the smoke tests**

Run:

```bash
php artisan test --compact tests/Feature/Shell/AuthenticatedShellTest.php
```

Expected: PASS for tenant shell visibility, with the admin assertion still allowed to fail until the custom Filament topbar/sidebar lands in the next task if you kept that assertion in place. If so, narrow the test file to the tenant shell first, then expand it in Task 2.

- [ ] **Step 6: Commit the shell foundation**

Run:

```bash
git add config/tenanto.php app/View/Components/Shell app/Support/Shell resources/views/components/shell resources/views/layouts/app.blade.php resources/views/tenant/home.blade.php resources/views/layouts/guest.blade.php lang tests/Feature/Shell/AuthenticatedShellTest.php
git commit -m "feat: add shared authenticated shell foundation"
```

### Task 2: Add role-aware navigation and replace Filament chrome

**Files:**
- Create: `app/Support/Shell/Navigation/NavigationItemData.php`
- Create: `app/Support/Shell/Navigation/NavigationGroupData.php`
- Create: `app/Support/Shell/Navigation/NavigationBuilder.php`
- Create: `app/Livewire/Shell/Topbar.php`
- Create: `app/Livewire/Shell/Sidebar.php`
- Create: `app/Livewire/Shell/TenantBottomNavigation.php`
- Create: `app/Http/Controllers/Profile/EditProfileController.php`
- Create: `resources/views/livewire/shell/topbar.blade.php`
- Create: `resources/views/livewire/shell/sidebar.blade.php`
- Create: `resources/views/livewire/shell/tenant-bottom-navigation.blade.php`
- Create: `resources/views/profile/edit.blade.php`
- Modify: `app/Providers/Filament/AdminPanelProvider.php`
- Modify: `app/Enums/UserRole.php`
- Modify: `routes/web.php`
- Modify: `resources/views/layouts/app.blade.php`
- Modify: `resources/views/tenant/home.blade.php`
- Test: `tests/Feature/Shell/AuthenticatedShellTest.php`

- [ ] **Step 1: Expand the failing tests to cover role-aware navigation**

Add assertions that:

- superadmin/admin/manager pages render the topbar and grouped sidebar
- tenant pages render the topbar plus bottom navigation instead of a sidebar
- the current route is highlighted
- only route-safe items are rendered today

Use route fixtures rather than inventing full CRUD pages.

- [ ] **Step 2: Run the expanded shell tests**

Run:

```bash
php artisan test --compact tests/Feature/Shell/AuthenticatedShellTest.php
```

Expected: FAIL because Filament still uses its default chrome and navigation grouping is missing.

- [ ] **Step 3: Implement `NavigationBuilder` and custom Livewire chrome**

Important implementation rules:

- `NavigationBuilder` must build grouped data by role.
- It must skip links whose route names do not yet exist so the shell never renders dead links.
- Tenant bottom navigation should use `wire:navigate`.
- Tenant shell should wrap persistent chrome with `@persist(...)` where it improves navigation continuity.
- Use `topbarLivewireComponent()` and `sidebarLivewireComponent()` in `AdminPanelProvider` instead of brittle vendor-view overrides.

- [ ] **Step 4: Keep the user avatar dropdown route-safe**

Add a lightweight profile destination if none exists yet:

- create `app/Http/Controllers/Profile/EditProfileController.php`
- create `resources/views/profile/edit.blade.php`
- register a named route such as `profile.edit`

Do not implement full profile editing here; just make the menu’s “My Profile” destination real and translated.

- [ ] **Step 5: Re-run the shell tests**

Run:

```bash
php artisan test --compact tests/Feature/Shell/AuthenticatedShellTest.php
```

Expected: PASS.

- [ ] **Step 6: Commit the navigation shell**

Run:

```bash
git add app/Providers/Filament/AdminPanelProvider.php app/Enums/UserRole.php app/Support/Shell/Navigation app/Livewire/Shell resources/views/livewire/shell resources/views/layouts/app.blade.php resources/views/tenant/home.blade.php routes/web.php app/Http/Controllers/Profile resources/views/profile/edit.blade.php tests/Feature/Shell/AuthenticatedShellTest.php
git commit -m "feat: add role-aware shared navigation shell"
```

## Chunk 2: Interactive Shell Behavior

### Task 3: Add the locale switcher with immediate persistence

**Files:**
- Create: `app/Actions/Preferences/UpdateUserLocaleAction.php`
- Create: `app/Livewire/Shell/LanguageSwitcher.php`
- Create: `resources/views/livewire/shell/language-switcher.blade.php`
- Create: `tests/Feature/Shell/LocaleSwitcherTest.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `resources/views/livewire/shell/topbar.blade.php`
- Modify: `app/Models/User.php` (only if a tiny helper makes the view cleaner)

- [ ] **Step 1: Write the failing locale-switcher tests**

Cover:

- the switcher shows the current locale abbreviation
- the dropdown shows locale names in their own language
- switching locale updates `users.locale`
- the next rendered response uses the new locale without a manual reload

Prefer a Livewire test for the component plus one HTTP assertion that the next page request renders translated strings.

- [ ] **Step 2: Run the locale tests**

Run:

```bash
php artisan test --compact tests/Feature/Shell/LocaleSwitcherTest.php
```

Expected: FAIL because no shell locale switcher exists.

- [ ] **Step 3: Implement the locale action and Livewire component**

Implementation notes:

- validate locale against `config('tenanto.locales')`
- persist via `UpdateUserLocaleAction`
- update `app()->setLocale(...)` during the same Livewire request
- emit a browser/Livewire event if sibling shell components need to refresh displayed labels

- [ ] **Step 4: Re-run the locale tests**

Run:

```bash
php artisan test --compact tests/Feature/Shell/LocaleSwitcherTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the locale switcher**

Run:

```bash
git add app/Actions/Preferences/UpdateUserLocaleAction.php app/Livewire/Shell/LanguageSwitcher.php resources/views/livewire/shell/language-switcher.blade.php app/Providers/AppServiceProvider.php resources/views/livewire/shell/topbar.blade.php tests/Feature/Shell/LocaleSwitcherTest.php
git commit -m "feat: add live locale switcher"
```

### Task 4: Add the notifications bell and panel using database notifications

**Files:**
- Create: `database/migrations/2026_03_17_100000_create_notifications_table.php`
- Create: `app/Support/Shell/Notifications/DatabaseNotificationPresenter.php`
- Create: `app/Livewire/Shell/NotificationCenter.php`
- Create: `resources/views/livewire/shell/notification-center.blade.php`
- Create: `tests/Feature/Shell/NotificationCenterTest.php`
- Modify: `resources/views/livewire/shell/topbar.blade.php`
- Modify: `config/tenanto.php`

- [ ] **Step 1: Write the failing notifications tests**

Cover:

- unread badge count renders
- the panel lists notification title, preview, and relative time
- clicking a notification marks it read
- “Mark all as read” updates all unread notifications
- tenant/admin users only ever see their own notifications

Use Laravel’s database notification model through the `Notifiable` trait rather than a custom table/query layer.

- [ ] **Step 2: Run the notifications tests**

Run:

```bash
php artisan test --compact tests/Feature/Shell/NotificationCenterTest.php
```

Expected: FAIL because the notifications table and panel component do not exist.

- [ ] **Step 3: Add the notifications migration and presenter**

Write the migration manually at `database/migrations/2026_03_17_100000_create_notifications_table.php` so the file path is stable, then create the presenter that trims preview text and formats relative timestamps centrally.

- [ ] **Step 4: Implement the Livewire bell/panel component**

Implementation notes:

- poll with `wire:poll` using the interval from `config/tenanto.php`
- keep unread highlighting in the view, not controller logic
- if a notification contains a target URL, redirect after marking it read
- mark-all should be a single relationship update, not a loop over records

- [ ] **Step 5: Run the migration and the notifications tests**

Run:

```bash
php artisan migrate --force
php artisan test --compact tests/Feature/Shell/NotificationCenterTest.php
```

Expected: migration succeeds, then tests PASS.

- [ ] **Step 6: Commit the notifications center**

Run:

```bash
git add database/migrations/2026_03_17_100000_create_notifications_table.php app/Support/Shell/Notifications/DatabaseNotificationPresenter.php app/Livewire/Shell/NotificationCenter.php resources/views/livewire/shell/notification-center.blade.php resources/views/livewire/shell/topbar.blade.php config/tenanto.php tests/Feature/Shell/NotificationCenterTest.php
git commit -m "feat: add shared notification center"
```

### Task 5: Build the global search overlay as a pluggable registry

**Files:**
- Create: `app/Support/Shell/Search/Contracts/GlobalSearchProvider.php`
- Create: `app/Support/Shell/Search/Data/GlobalSearchResultData.php`
- Create: `app/Support/Shell/Search/GlobalSearchRegistry.php`
- Create: `app/Support/Shell/Search/Providers/OrganizationSearchProvider.php`
- Create: `app/Support/Shell/Search/Providers/UserSearchProvider.php`
- Create: `app/Livewire/Shell/GlobalSearch.php`
- Create: `resources/views/livewire/shell/global-search.blade.php`
- Create: `tests/Feature/Shell/GlobalSearchTest.php`
- Modify: `config/tenanto.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `resources/views/livewire/shell/topbar.blade.php`
- Modify: `resources/js/app.js`

- [ ] **Step 1: Write the failing global-search tests**

Cover:

- the search field appears in the topbar
- typing shows role-appropriate group labels from config
- pressing Escape clears and closes the overlay
- results never include another organization’s records
- missing destination routes cause providers to return no clickable result instead of a broken link

Do not write tests that assume buildings/properties/meters/invoices already exist. Test the provider contract and route-safe behavior instead.

- [ ] **Step 2: Run the search tests**

Run:

```bash
php artisan test --compact tests/Feature/Shell/GlobalSearchTest.php
```

Expected: FAIL because the registry and overlay do not exist.

- [ ] **Step 3: Implement the registry and current-state providers**

Implementation notes:

- register providers in `AppServiceProvider`
- use explicit Eloquent `select([...])`
- guard each provider behind `Route::has(...)` for its destination
- return empty results when a route or owning module does not exist yet
- expose group labels from `config/tenanto.php` so the UI already matches the product vocabulary

- [ ] **Step 4: Implement the Livewire search UI**

Implementation notes:

- debounce typing before querying
- keep keyboard-dismiss logic tiny in `resources/js/app.js`
- use Alpine only for focus/open state, not for fetching/search logic
- show empty-state copy when a permitted group has no results yet

- [ ] **Step 5: Re-run the search tests**

Run:

```bash
php artisan test --compact tests/Feature/Shell/GlobalSearchTest.php
```

Expected: PASS.

- [ ] **Step 6: Commit the global search foundation**

Run:

```bash
git add app/Support/Shell/Search app/Livewire/Shell/GlobalSearch.php resources/views/livewire/shell/global-search.blade.php app/Providers/AppServiceProvider.php config/tenanto.php resources/views/livewire/shell/topbar.blade.php resources/js/app.js tests/Feature/Shell/GlobalSearchTest.php
git commit -m "feat: add pluggable global search shell"
```

## Chunk 3: Access Context and Fallback States

### Task 6: Add impersonation banner state and stop flow

**Files:**
- Create: `app/Support/Auth/ImpersonationManager.php`
- Create: `app/Http/Controllers/Auth/StopImpersonationController.php`
- Create: `resources/views/components/shell/impersonation-banner.blade.php`
- Create: `tests/Feature/Shell/ImpersonationBannerTest.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `app/Providers/Filament/AdminPanelProvider.php`
- Modify: `routes/web.php`
- Modify: `resources/views/components/shell/app-frame.blade.php`
- Modify: `resources/views/livewire/shell/topbar.blade.php`

- [ ] **Step 1: Write the failing impersonation tests**

Cover:

- the banner renders when the session contains impersonation metadata
- the banner includes name and email
- the stop action clears impersonation state and redirects to the real user’s dashboard
- no banner renders outside impersonation

- [ ] **Step 2: Run the impersonation tests**

Run:

```bash
php artisan test --compact tests/Feature/Shell/ImpersonationBannerTest.php
```

Expected: FAIL because no impersonation contract or banner exists.

- [ ] **Step 3: Implement the impersonation manager and stop route**

Session contract recommendation:

- `impersonator_id`
- `impersonator_email`
- `impersonator_name`

Keep all reads/writes inside `ImpersonationManager` so future “start impersonation” work reuses one contract.

- [ ] **Step 4: Render the banner in both shells**

Use Filament render hooks for admin-like pages and the shared `AppFrame` for tenant/error pages. The banner must be non-dismissible and remain visible until stop impersonation succeeds.

- [ ] **Step 5: Re-run the impersonation tests**

Run:

```bash
php artisan test --compact tests/Feature/Shell/ImpersonationBannerTest.php
```

Expected: PASS.

- [ ] **Step 6: Commit the impersonation banner**

Run:

```bash
git add app/Support/Auth/ImpersonationManager.php app/Http/Controllers/Auth/StopImpersonationController.php resources/views/components/shell/impersonation-banner.blade.php app/Providers/AppServiceProvider.php app/Providers/Filament/AdminPanelProvider.php routes/web.php resources/views/components/shell/app-frame.blade.php resources/views/livewire/shell/topbar.blade.php tests/Feature/Shell/ImpersonationBannerTest.php
git commit -m "feat: add impersonation banner and stop flow"
```

### Task 7: Add branded 403, 404, and 500 pages

**Files:**
- Create: `resources/views/errors/403.blade.php`
- Create: `resources/views/errors/404.blade.php`
- Create: `resources/views/errors/500.blade.php`
- Create: `tests/Feature/Shell/ErrorPagesTest.php`
- Modify: `resources/views/layouts/app.blade.php`
- Modify: `config/tenanto.php` (if you centralize error-page labels there)

- [ ] **Step 1: Write the failing error-page tests**

Cover:

- 403 shows “You do not have permission to view this page”
- 404 shows “The page you are looking for does not exist”
- 500 shows the support-safe message when `app.debug` is false
- each page includes a role-aware “go back to dashboard” action

- [ ] **Step 2: Run the error-page tests**

Run:

```bash
php artisan test --compact tests/Feature/Shell/ErrorPagesTest.php
```

Expected: FAIL because no branded error views exist.

- [ ] **Step 3: Implement the error pages using the shared shell language**

Use the same brand component and dashboard resolver so the error pages feel native to the authenticated shell. Keep them query-free and translation-driven.

- [ ] **Step 4: Re-run the error-page tests**

Run:

```bash
php artisan test --compact tests/Feature/Shell/ErrorPagesTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the error pages**

Run:

```bash
git add resources/views/errors resources/views/layouts/app.blade.php config/tenanto.php tests/Feature/Shell/ErrorPagesTest.php
git commit -m "feat: add branded shared error pages"
```

### Task 8: Full verification and cleanup

**Files:**
- Review only: all files touched in Tasks 1-7

- [ ] **Step 1: Run the focused shell suite**

Run:

```bash
php artisan test --compact tests/Feature/Shell
```

Expected: PASS.

- [ ] **Step 2: Run the auth regression suite**

Run:

```bash
php artisan test --compact tests/Feature/Auth
```

Expected: PASS so the new shell does not break login/onboarding flows.

- [ ] **Step 3: Run the full test suite**

Run:

```bash
php artisan test --compact
```

Expected: PASS.

- [ ] **Step 4: Format the touched PHP files**

Run:

```bash
vendor/bin/pint --dirty
```

Expected: PASS with no remaining style changes after review.

- [ ] **Step 5: Verify the frontend build**

Run:

```bash
npm run build
```

Expected: Vite production build succeeds.

- [ ] **Step 6: Commit the verified shell slice**

Run:

```bash
git add app config database lang resources routes tests
git commit -m "feat: deliver shared interface elements shell"
```

## Execution Notes

- Prefer Livewire islands for shell interactions and polling; avoid ad-hoc fetch code unless a behavior truly does not fit Livewire.
- Keep all data access in support classes/components/actions, never in Blade templates.
- Use explicit Eloquent `select([...])` in search providers and presenters.
- Use route-name guards (`Route::has(...)`) everywhere the shell references later feature destinations.
- Keep admin-like chrome inside Filament’s extension points and tenant chrome inside the shared Blade frame so each layer uses its native strengths.
