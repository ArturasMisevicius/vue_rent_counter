# Public Homepage Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a tester-first public homepage at `/` for `https://tenanto.test/`, add guest locale switching for public pages, and keep `Login` / `Register` as clear localized entry points into the existing auth flow.

**Architecture:** Keep the public homepage outside Filament as a standard Laravel web page driven by translation files and a thin controller. Add one guest-locale middleware layer that reads the selected locale from session for public routes, then reuse a shared Blade language-switcher component across the homepage and guest auth pages so locale behavior stays consistent without adding database queries.

**Tech Stack:** Laravel 12, Blade, Tailwind CSS v4, session middleware, Pest 4, Laravel Pint, Vite.

---

## Spec Reference

- Spec: `docs/superpowers/specs/2026-03-17-public-homepage-design.md`
- Supporting baseline: `docs/superpowers/specs/2026-03-17-foundation-auth-onboarding-design.md`
- Skills to apply during execution: `@laravel-11-12-app-guidelines`, `@frontend-design`, `@tailwindcss-development`, `@pest-testing`

## Scope Notes

- Do not introduce any database queries for rendering the homepage.
- Do not inline locale validation or session persistence directly into controllers; keep that logic in a request + action pair.
- Do not break the existing login, register, password reset, or invitation flows while adding the shared language switcher.
- Keep the current auth-page visual language as the foundation; the homepage may be broader and more editorial, but it must still feel like the same product family.
- Continue to respect the authenticated user locale once a user signs in; guest session locale is only the guest fallback.

## File Map

### Create

- `app/Actions/Preferences/StoreGuestLocaleAction.php`
- `app/Http/Controllers/Public/HomepageController.php`
- `app/Http/Controllers/Preferences/UpdateGuestLocaleController.php`
- `app/Http/Middleware/SetGuestLocale.php`
- `app/Http/Requests/Preferences/UpdateGuestLocaleRequest.php`
- `app/Support/PublicSite/HomepageContent.php`
- `resources/views/layouts/public.blade.php`
- `resources/views/components/public/language-switcher.blade.php`
- `lang/en/landing.php`
- `lang/lt/landing.php`
- `lang/es/landing.php`
- `lang/ru/landing.php`
- `tests/Feature/Public/PublicHomepageTest.php`

### Modify

- `bootstrap/app.php`
- `routes/web.php`
- `resources/views/welcome.blade.php`
- `resources/views/layouts/guest.blade.php`
- `app/Actions/Auth/RegisterAdminAction.php`
- `tests/Feature/Auth/LocalePersistenceTest.php`

### Keep Out of Scope

- pricing pages
- public marketing forms
- public documentation pages
- authenticated dashboard features
- guest locale persistence in the database
- changes to admin, manager, tenant, or superadmin navigation

## Chunk 1: Guest Locale Foundation

### Task 1: Add failing coverage for guest locale switching and locale precedence

**Files:**
- Create: `tests/Feature/Public/PublicHomepageTest.php`
- Modify: `tests/Feature/Auth/LocalePersistenceTest.php`

- [ ] **Step 1: Write the failing homepage and guest-locale assertions**

Create `tests/Feature/Public/PublicHomepageTest.php` with coverage like:

```php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the tester-first public homepage', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertSeeText('Property operations platform, presented as a guided testing lab.')
        ->assertSeeText('Login')
        ->assertSeeText('Register')
        ->assertSeeText('Superadmin')
        ->assertSeeText('Tenant');
});
```

In `tests/Feature/Auth/LocalePersistenceTest.php`, add failing tests that prove:

- posting a guest locale changes the login page language
- posting `es` before registration stores `es` on the created Admin user
- an authenticated user locale still wins over any guest-session locale on an auth-protected route

- [ ] **Step 2: Run the targeted tests and confirm they fail**

Run:

```bash
php artisan test --compact tests/Feature/Public/PublicHomepageTest.php tests/Feature/Auth/LocalePersistenceTest.php
```

Expected:

- homepage assertions fail because the current `/` view does not match the new copy
- guest-locale assertions fail because there is no public locale-switch endpoint or middleware yet

- [ ] **Step 3: Add the guest-locale plumbing**

Create:

- `app/Http/Middleware/SetGuestLocale.php`
- `app/Http/Requests/Preferences/UpdateGuestLocaleRequest.php`
- `app/Actions/Preferences/StoreGuestLocaleAction.php`
- `app/Http/Controllers/Preferences/UpdateGuestLocaleController.php`

Implementation rules:

- `SetGuestLocale` reads a locale from session only when no authenticated user is present
- supported locales are restricted to `en`, `lt`, `es`, `ru`
- `UpdateGuestLocaleRequest` validates the locale against the supported set
- `StoreGuestLocaleAction` stores the locale in session and returns control to the controller
- `UpdateGuestLocaleController` redirects back to the previous guest page after storing the locale

Code shape:

```php
final class StoreGuestLocaleAction
{
    public function handle(Request $request, string $locale): void
    {
        $request->session()->put('guest_locale', $locale);
        app()->setLocale($locale);
    }
}
```

If you prefer not to hardcode the locale list twice, centralize it in one place while keeping the change minimal.

- [ ] **Step 4: Register the middleware and route**

Modify:

- `bootstrap/app.php`
- `routes/web.php`

Implementation rules:

- apply `SetGuestLocale` to the web layer so it affects `/`, `/login`, `/register`, `/forgot-password`, `/reset-password/{token}`, and `/invite/{token}`
- keep `set.auth.locale` in place for authenticated routes
- add a public locale-switch endpoint such as:

```php
Route::post('/locale', UpdateGuestLocaleController::class)->name('locale.update');
```

- [ ] **Step 5: Re-run the targeted tests and confirm they pass**

Run:

```bash
php artisan test --compact tests/Feature/Public/PublicHomepageTest.php tests/Feature/Auth/LocalePersistenceTest.php
```

Expected: PASS.

- [ ] **Step 6: Commit the locale foundation**

Run:

```bash
git add bootstrap/app.php routes/web.php app/Actions/Preferences app/Http/Controllers/Preferences app/Http/Middleware/SetGuestLocale.php app/Http/Requests/Preferences tests/Feature/Public/PublicHomepageTest.php tests/Feature/Auth/LocalePersistenceTest.php
git commit -m "feat: add guest locale switching foundation"
```

## Chunk 2: Public Homepage Content and Shared Guest UI

### Task 2: Add failing homepage-content coverage

**Files:**
- Create: `tests/Feature/Public/PublicHomepageTest.php`
- Create: `lang/en/landing.php`
- Create: `lang/lt/landing.php`
- Create: `lang/es/landing.php`
- Create: `lang/ru/landing.php`

- [ ] **Step 1: Expand the homepage test to cover the final page sections**

Add assertions proving the homepage shows:

- a tester-first hero
- the four role cards
- the system-tester checklist section
- the roadmap/future-value section
- the closing CTA with both `Login` and `Register`

Add one locale-specific assertion, for example:

```php
it('renders the homepage in lithuanian when the guest locale is lt', function () {
    $this->withSession(['guest_locale' => 'lt'])
        ->get('/')
        ->assertSuccessful()
        ->assertSeeText('Tiesioginis testavimo įėjimas');
});
```

Use the exact copy you commit to the language files.

- [ ] **Step 2: Run the homepage test and confirm it fails**

Run:

```bash
php artisan test --compact tests/Feature/Public/PublicHomepageTest.php
```

Expected: FAIL because the current homepage view and translations do not exist yet.

### Task 3: Build the translation-driven homepage and shared language switcher

**Files:**
- Create: `app/Http/Controllers/Public/HomepageController.php`
- Create: `app/Support/PublicSite/HomepageContent.php`
- Create: `resources/views/layouts/public.blade.php`
- Create: `resources/views/components/public/language-switcher.blade.php`
- Create: `lang/en/landing.php`
- Create: `lang/lt/landing.php`
- Create: `lang/es/landing.php`
- Create: `lang/ru/landing.php`
- Modify: `routes/web.php`
- Modify: `resources/views/welcome.blade.php`
- Modify: `resources/views/layouts/guest.blade.php`
- Modify: `app/Actions/Auth/RegisterAdminAction.php`

- [ ] **Step 1: Replace the root closure with a thin homepage controller**

Change `routes/web.php` so `/` uses a controller:

```php
Route::get('/', HomepageController::class)->name('home');
```

`HomepageController` should delegate data shaping to `HomepageContent` and return:

```php
return view('welcome', [
    'page' => $homepageContent->toArray(),
]);
```

- [ ] **Step 2: Add translation-backed homepage content**

Create `lang/*/landing.php` with structured keys for:

- brand
- hero
- chips
- roles
- tester checklist
- roadmap
- closing CTA

Avoid hardcoded copy inside the Blade view beyond route labels already localized elsewhere.

- [ ] **Step 3: Build `HomepageContent` as the presentation data shaper**

`app/Support/PublicSite/HomepageContent.php` should gather translated arrays into a simple view payload, for example:

```php
final class HomepageContent
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'hero' => trans('landing.hero'),
            'roles' => trans('landing.roles'),
            'tester' => trans('landing.tester'),
            'roadmap' => trans('landing.roadmap'),
            'cta' => trans('landing.cta'),
        ];
    }
}
```

Keep it static and query-free.

- [ ] **Step 4: Create the shared language-switcher component**

Create `resources/views/components/public/language-switcher.blade.php`.

Implementation rules:

- render `EN`, `LT`, `ES`, `RU`
- submit to `route('locale.update')`
- visually indicate the active locale
- work in both the homepage header and the guest auth shell
- keep markup accessible with visible labels and `aria-current` or equivalent active-state semantics

- [ ] **Step 5: Introduce a dedicated public layout and rebuild `welcome.blade.php`**

Create `resources/views/layouts/public.blade.php` for the broader homepage shell.

Then rewrite `resources/views/welcome.blade.php` so it renders:

- branded public header
- hero with tester-first messaging
- role overview cards
- tester checklist block
- roadmap/future-value section
- closing CTA band

Design rules:

- reuse the brand ink / warm / mint palette already present in auth
- keep mobile and desktop layouts intentional
- avoid generic boilerplate hero patterns
- do not query or call model methods from the view

- [ ] **Step 6: Update the guest auth layout to include the same switcher**

Modify `resources/views/layouts/guest.blade.php` so the auth screens share the same locale switcher experience.

Keep the existing card layout and auth-brand identity intact; this is a shared enhancement, not a redesign of the auth forms.

- [ ] **Step 7: Keep registration locale-aware**

Confirm `app/Actions/Auth/RegisterAdminAction.php` continues to use `app()->getLocale()` and only adjust it if the new middleware/action flow makes that behavior ambiguous.

The goal is:

- guest selects `es`
- registers
- created user record stores `es`

- [ ] **Step 8: Re-run the homepage and locale tests**

Run:

```bash
php artisan test --compact tests/Feature/Public/PublicHomepageTest.php tests/Feature/Auth/LocalePersistenceTest.php
```

Expected: PASS.

- [ ] **Step 9: Commit the homepage slice**

Run:

```bash
git add app/Http/Controllers/Public app/Support/PublicSite resources/views/welcome.blade.php resources/views/layouts/public.blade.php resources/views/layouts/guest.blade.php resources/views/components/public lang app/Actions/Auth/RegisterAdminAction.php routes/web.php
git commit -m "feat: add public homepage and shared guest language switcher"
```

## Chunk 3: Regression, Styling, and Verification

### Task 4: Run focused regression and formatting

**Files:**
- Modify: any files changed in previous tasks

- [ ] **Step 1: Run the most relevant auth and homepage regression tests**

Run:

```bash
php artisan test --compact tests/Feature/Public/PublicHomepageTest.php tests/Feature/Auth/LoginFlowTest.php tests/Feature/Auth/RegistrationAndOnboardingTest.php tests/Feature/Auth/LocalePersistenceTest.php
```

Expected: PASS.

- [ ] **Step 2: Run Pint on dirty files**

Run:

```bash
vendor/bin/pint --dirty
```

Expected: PASS with formatting fixes applied if needed.

- [ ] **Step 3: Re-run the focused regression suite after formatting**

Run:

```bash
php artisan test --compact tests/Feature/Public/PublicHomepageTest.php tests/Feature/Auth/LoginFlowTest.php tests/Feature/Auth/RegistrationAndOnboardingTest.php tests/Feature/Auth/LocalePersistenceTest.php
```

Expected: PASS.

- [ ] **Step 4: Commit the verification pass**

Run:

```bash
git add app resources views lang tests
git commit -m "test: verify public homepage guest experience"
```

## Implementation Notes

- If there is no existing config file for product-level settings, keep the supported locale list close to this slice and avoid introducing a broad new configuration surface unless it clearly reduces duplication.
- If you need a single source of truth for the locales in the request, action, and component, prefer a small shared helper or config entry over copying the same array three times.
- Keep the homepage copy honest about current product maturity; do not market unfinished roadmap items as already-live features.
- Avoid adding JavaScript for the locale switcher; regular Blade forms are sufficient here.

Plan complete and saved to `docs/superpowers/plans/2026-03-17-public-homepage.md`. Ready to execute?
