# Foundation Auth Onboarding Implementation Plan

> **Workflow note:** Implement this plan directly on `main`. Do not create or switch to feature branches or worktrees for this rollout.

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the first Tenanto vertical slice so users can authenticate through one shared login flow, Admins can self-register and complete organization onboarding, invited Managers and Tenants can activate accounts, and role- plus organization-aware redirects and locale behavior work end to end.

**Architecture:** Keep public auth and onboarding outside Filament as standard Laravel web pages, while retaining Filament as the authenticated admin/superadmin shell and reserving a separate tenant home route for tenant-facing entry. Model organization ownership, invitation lifecycle, and locale preference explicitly in the database, then enforce access through middleware, route guards, and centralized redirect logic so later CRUD and dashboard slices inherit safe defaults instead of re-implementing them.

**Tech Stack:** Laravel 12, Filament 5, Blade, Tailwind CSS v4, SQLite, session auth, Laravel password broker, notifications/mail, Pest 4, PHPUnit compatibility, Laravel Pint.

---

## Spec Reference

- Spec: `docs/superpowers/specs/2026-03-17-foundation-auth-onboarding-design.md`
- Skills to apply during execution: `@laravel-11-12-app-guidelines`, `@tailwindcss-development`, `@pest-testing`

## File Map

### Create

- `app/Enums/UserRole.php`
- `app/Enums/UserStatus.php`
- `app/Enums/OrganizationStatus.php`
- `app/Enums/SubscriptionPlan.php`
- `app/Enums/SubscriptionStatus.php`
- `app/Models/Organization.php`
- `app/Models/Subscription.php`
- `app/Models/OrganizationInvitation.php`
- `database/factories/OrganizationFactory.php`
- `database/factories/SubscriptionFactory.php`
- `database/factories/OrganizationInvitationFactory.php`
- `database/migrations/2026_03_17_000100_create_organizations_table.php`
- `database/migrations/2026_03_17_000200_create_subscriptions_table.php`
- `database/migrations/2026_03_17_000300_create_organization_invitations_table.php`
- `database/migrations/2026_03_17_000400_add_foundation_fields_to_users_table.php`
- `app/Actions/Auth/RegisterAdminAction.php`
- `app/Actions/Auth/CompleteOnboardingAction.php`
- `app/Actions/Auth/CreateOrganizationInvitationAction.php`
- `app/Actions/Auth/AcceptOrganizationInvitationAction.php`
- `app/Support/Auth/LoginRedirector.php`
- `app/Http/Middleware/SetAuthenticatedUserLocale.php`
- `app/Http/Middleware/EnsureAccountIsAccessible.php`
- `app/Http/Middleware/EnsureOnboardingIsComplete.php`
- `app/Http/Requests/Auth/LoginRequest.php`
- `app/Http/Requests/Auth/RegisterRequest.php`
- `app/Http/Requests/Auth/ForgotPasswordRequest.php`
- `app/Http/Requests/Auth/ResetPasswordRequest.php`
- `app/Http/Requests/Auth/CompleteOnboardingRequest.php`
- `app/Http/Requests/Auth/AcceptInvitationRequest.php`
- `app/Http/Controllers/Auth/LoginController.php`
- `app/Http/Controllers/Auth/RegisterController.php`
- `app/Http/Controllers/Auth/ForgotPasswordController.php`
- `app/Http/Controllers/Auth/ResetPasswordController.php`
- `app/Http/Controllers/Auth/LogoutController.php`
- `app/Http/Controllers/Auth/AcceptInvitationController.php`
- `app/Http/Controllers/Onboarding/WelcomeController.php`
- `app/Http/Controllers/Filament/RedirectToPublicLoginController.php`
- `app/Notifications/Auth/OrganizationInvitationNotification.php`
- `resources/views/layouts/guest.blade.php`
- `resources/views/auth/login.blade.php`
- `resources/views/auth/register.blade.php`
- `resources/views/auth/forgot-password.blade.php`
- `resources/views/auth/reset-password.blade.php`
- `resources/views/auth/accept-invitation.blade.php`
- `resources/views/onboarding/welcome.blade.php`
- `resources/views/tenant/home.blade.php`
- `app/Filament/Pages/PlatformDashboard.php`
- `app/Filament/Pages/OrganizationDashboard.php`
- `resources/views/filament/pages/platform-dashboard.blade.php`
- `resources/views/filament/pages/organization-dashboard.blade.php`
- `lang/en/auth.php`
- `lang/en/onboarding.php`
- `lang/en/roles.php`
- `lang/en/passwords.php`
- `lang/lt/auth.php`
- `lang/lt/onboarding.php`
- `lang/lt/roles.php`
- `lang/lt/passwords.php`
- `lang/ru/auth.php`
- `lang/ru/onboarding.php`
- `lang/ru/roles.php`
- `lang/ru/passwords.php`
- `tests/Pest.php`
- `tests/Feature/Auth/LoginFlowTest.php`
- `tests/Feature/Auth/RegistrationAndOnboardingTest.php`
- `tests/Feature/Auth/PasswordResetTest.php`
- `tests/Feature/Auth/InvitationAcceptanceTest.php`
- `tests/Feature/Auth/AccessIsolationTest.php`
- `tests/Feature/Auth/LocalePersistenceTest.php`

### Modify

- `app/Models/User.php`
- `database/factories/UserFactory.php`
- `bootstrap/app.php`
- `app/Providers/Filament/AdminPanelProvider.php`
- `routes/web.php`
- `resources/css/app.css`
- `resources/js/app.js`
- `composer.json`
- `phpunit.xml`

### Keep Out of Scope

- tenant CRUD
- full admin/team management UI
- dashboard metrics and widgets beyond lightweight landing pages
- translation management screens
- billing/report modules

## Chunk 1: Project Foundation and Domain Shape

### Task 1: Install Pest and publish language scaffolding

**Files:**
- Modify: `composer.json`
- Modify: `phpunit.xml`
- Create: `tests/Pest.php`
- Modify: `tests/TestCase.php`
- Modify or keep: `tests/Feature/ExampleTest.php`
- Modify or keep: `tests/Unit/ExampleTest.php`
- Create: `lang/en/passwords.php`
- Create: `lang/lt/passwords.php`
- Create: `lang/ru/passwords.php`

- [ ] **Step 1: Add Pest to the dev toolchain**

Run:
```bash
composer require --dev pestphp/pest pestphp/pest-plugin-laravel
```

Expected: Composer installs Pest packages and updates `composer.json` / `composer.lock`.

- [ ] **Step 2: Install Pest scaffolding**

Run:
```bash
php artisan pest:install --no-interaction
```

Expected: `tests/Pest.php` exists and Laravel/Pest integration is enabled.

- [ ] **Step 3: Publish language files for password-broker copy**

Run:
```bash
php artisan lang:publish --no-interaction
```

Expected: a `lang/` tree exists so the slice can own translated auth strings.

- [ ] **Step 4: Add a tiny smoke test proving the new harness works**

Use a single Pest smoke test, for example:
```php
it('renders the welcome page', function () {
    $this->get('/')->assertSuccessful();
});
```

Place it in `tests/Feature/ExampleTest.php` or a dedicated smoke file. Do not delete starter tests unless the replacement is already green.

- [ ] **Step 5: Run the smoke test**

Run:
```bash
php artisan test --compact tests/Feature/ExampleTest.php
```

Expected: PASS.

- [ ] **Step 6: Commit the toolchain baseline**

Run:
```bash
git add composer.json composer.lock phpunit.xml tests lang
git commit -m "chore: install pest and publish language scaffolding"
```

### Task 2: Add auth domain schema, enums, and model relationships

**Files:**
- Create: `app/Enums/UserRole.php`
- Create: `app/Enums/UserStatus.php`
- Create: `app/Enums/OrganizationStatus.php`
- Create: `app/Enums/SubscriptionPlan.php`
- Create: `app/Enums/SubscriptionStatus.php`
- Create: `app/Models/Organization.php`
- Create: `app/Models/Subscription.php`
- Create: `app/Models/OrganizationInvitation.php`
- Create: `database/factories/OrganizationFactory.php`
- Create: `database/factories/SubscriptionFactory.php`
- Create: `database/factories/OrganizationInvitationFactory.php`
- Create: `database/migrations/2026_03_17_000100_create_organizations_table.php`
- Create: `database/migrations/2026_03_17_000200_create_subscriptions_table.php`
- Create: `database/migrations/2026_03_17_000300_create_organization_invitations_table.php`
- Create: `database/migrations/2026_03_17_000400_add_foundation_fields_to_users_table.php`
- Modify: `app/Models/User.php`
- Modify: `database/factories/UserFactory.php`
- Test: `tests/Feature/Auth/RegistrationAndOnboardingTest.php`

- [ ] **Step 1: Scaffold the new models and factories**

Run:
```bash
php artisan make:model Organization -mf --no-interaction
php artisan make:model Subscription -mf --no-interaction
php artisan make:model OrganizationInvitation -mf --no-interaction
```

Expected: model, migration, and factory stubs exist for all three records.

- [ ] **Step 2: Write the failing schema test**

Create a focused test that proves:
- Admin registration can exist without `organization_id`
- Manager/Tenant invitation acceptance requires an organization
- organization slug is unique
- invitation email is unique while still pending

Example:
```php
it('creates an admin without an organization until onboarding completes', function () {
    $user = User::factory()->create([
        'role' => UserRole::Admin,
        'organization_id' => null,
    ]);

    expect($user->organization)->toBeNull();
});
```

- [ ] **Step 3: Implement the schema and enum layer**

Use backed enums and explicit casts. Keep model responsibilities narrow:
```php
enum UserRole: string
{
    case Superadmin = 'superadmin';
    case Admin = 'admin';
    case Manager = 'manager';
    case Tenant = 'tenant';
}
```

`User` should gain:
- `role`, `status`, `locale`, `organization_id`, `last_login_at`
- `organization()` relation
- role helpers such as `isSuperadmin()` / `isAdminLike()`
- `canAccessPanel()` implementation for Filament access

`Organization`, `Subscription`, and `OrganizationInvitation` should expose only the relations this slice needs.

- [ ] **Step 4: Run migrations and the targeted schema test**

Run:
```bash
php artisan test --compact tests/Feature/Auth/RegistrationAndOnboardingTest.php --filter=schema
```

Expected: PASS.

- [ ] **Step 5: Commit the domain foundation**

Run:
```bash
git add app/Enums app/Models database/migrations database/factories tests/Feature/Auth/RegistrationAndOnboardingTest.php
git commit -m "feat: add auth foundation domain models"
```

### Task 3: Add middleware, route aliases, and redirect primitives

**Files:**
- Create: `app/Http/Middleware/SetAuthenticatedUserLocale.php`
- Create: `app/Http/Middleware/EnsureAccountIsAccessible.php`
- Create: `app/Http/Middleware/EnsureOnboardingIsComplete.php`
- Create: `app/Support/Auth/LoginRedirector.php`
- Modify: `bootstrap/app.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Auth/AccessIsolationTest.php`
- Test: `tests/Feature/Auth/LocalePersistenceTest.php`

- [ ] **Step 1: Write failing middleware/redirect tests**

Add tests for:
- partially onboarded Admins being forced to `/welcome`
- suspended users being denied
- suspended organizations being denied
- locale being pulled from the authenticated user

Example:
```php
it('redirects a partially onboarded admin to welcome', function () {
    $user = User::factory()->admin()->create(['organization_id' => null]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertRedirect(route('welcome.show'));
});
```

- [ ] **Step 2: Implement the middleware aliases in `bootstrap/app.php`**

Add aliases for:
- `set.auth.locale`
- `ensure.account.accessible`
- `ensure.onboarding.complete`

Do not register them globally until the route groups are ready.

- [ ] **Step 3: Implement the redirect and locale primitives**

`LoginRedirector` should centralize:
```php
public function for(User $user): string
{
    if ($user->isAdmin() && blank($user->organization_id)) {
        return route('welcome.show');
    }

    return match ($user->role) {
        UserRole::Superadmin => route('filament.admin.pages.platform-dashboard'),
        UserRole::Admin, UserRole::Manager => route('filament.admin.pages.organization-dashboard'),
        UserRole::Tenant => route('tenant.home'),
    };
}
```

The middleware layer should:
- set locale from `user->locale`
- block suspended accounts and suspended organizations
- keep incomplete Admins in onboarding until completion

- [ ] **Step 4: Run targeted auth guard tests**

Run:
```bash
php artisan test --compact tests/Feature/Auth/AccessIsolationTest.php tests/Feature/Auth/LocalePersistenceTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the request guardrails**

Run:
```bash
git add app/Http/Middleware app/Support/Auth bootstrap/app.php routes/web.php tests/Feature/Auth
git commit -m "feat: add auth middleware and redirect foundation"
```

## Chunk 2: Public Auth and Onboarding UI

### Task 4: Build login and registration flows with translated guest pages

**Files:**
- Create: `app/Http/Requests/Auth/LoginRequest.php`
- Create: `app/Http/Requests/Auth/RegisterRequest.php`
- Create: `app/Http/Controllers/Auth/LoginController.php`
- Create: `app/Http/Controllers/Auth/RegisterController.php`
- Create: `app/Actions/Auth/RegisterAdminAction.php`
- Create: `resources/views/layouts/guest.blade.php`
- Create: `resources/views/auth/login.blade.php`
- Create: `resources/views/auth/register.blade.php`
- Create: `lang/{en,lt,ru,es}/auth.php`
- Create: `lang/{en,lt,ru,es}/roles.php`
- Modify: `routes/web.php`
- Modify: `resources/css/app.css`
- Modify: `resources/js/app.js`
- Test: `tests/Feature/Auth/LoginFlowTest.php`
- Test: `tests/Feature/Auth/RegistrationAndOnboardingTest.php`

- [ ] **Step 1: Write the failing feature tests**

Cover:
- login form renders
- failed login preserves email and shows the generic error
- successful login redirects by role or intended URL
- registration creates an Admin and redirects to onboarding

Example:
```php
it('registers an admin and redirects to welcome', function () {
    $this->post(route('register.store'), [
        'name' => 'Asta Admin',
        'email' => 'asta@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('welcome.show'));

    $this->assertAuthenticated();
});
```

- [ ] **Step 2: Implement the register and login actions**

Keep controllers thin:
- `RegisterController@store` delegates to `RegisterAdminAction`
- `LoginController@store` authenticates and uses `LoginRedirector`

Validation rules:
- required email/password
- password min 8 on registration
- confirmation match on registration

Auth rules:
- preserve email on failure
- use translated error copy
- update `last_login_at` on success

- [ ] **Step 3: Build the guest layout and views**

Use a single reusable guest shell with:
- Tenanto logo slot
- heading/subheading slot
- inline error region
- loading-aware submit buttons
- translated footer links

Keep the JS minimal:
- button loading state hooks
- confirm-password mismatch helper for registration

- [ ] **Step 4: Run the login and registration tests**

Run:
```bash
php artisan test --compact tests/Feature/Auth/LoginFlowTest.php tests/Feature/Auth/RegistrationAndOnboardingTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the guest auth flow**

Run:
```bash
git add app/Http/Controllers/Auth app/Http/Requests/Auth app/Actions/Auth/RegisterAdminAction.php resources/views/layouts/guest.blade.php resources/views/auth resources/css/app.css resources/js/app.js lang routes/web.php tests/Feature/Auth
git commit -m "feat: add public login and registration flows"
```

### Task 5: Build forgot-password and reset-password flows

**Files:**
- Create: `app/Http/Requests/Auth/ForgotPasswordRequest.php`
- Create: `app/Http/Requests/Auth/ResetPasswordRequest.php`
- Create: `app/Http/Controllers/Auth/ForgotPasswordController.php`
- Create: `app/Http/Controllers/Auth/ResetPasswordController.php`
- Create: `resources/views/auth/forgot-password.blade.php`
- Create: `resources/views/auth/reset-password.blade.php`
- Modify: `routes/web.php`
- Modify: `lang/{en,lt,ru,es}/auth.php`
- Modify: `lang/{en,lt,ru,es}/passwords.php`
- Test: `tests/Feature/Auth/PasswordResetTest.php`

- [ ] **Step 1: Write the failing password-reset tests**

Cover:
- forgot-password page renders
- request endpoint always returns the generic success copy
- reset token works
- expired token is rejected

- [ ] **Step 2: Implement the broker-backed controllers**

Use Laravel's password broker directly. Keep the copy generic on the request screen:
```php
Password::sendResetLink($request->only('email'));

return back()->with('status', __('auth.reset_link_generic'));
```

Reset should:
- validate token, email, password, confirmation
- update password
- rotate remember token
- redirect back to login with translated success message

- [ ] **Step 3: Build the two guest views**

Requirements:
- one email field on forgot-password
- password and confirmation on reset
- translated headings and helper copy
- inline field errors

- [ ] **Step 4: Run the password-reset tests**

Run:
```bash
php artisan test --compact tests/Feature/Auth/PasswordResetTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit the password recovery flow**

Run:
```bash
git add app/Http/Controllers/Auth/ForgotPasswordController.php app/Http/Controllers/Auth/ResetPasswordController.php app/Http/Requests/Auth/ForgotPasswordRequest.php app/Http/Requests/Auth/ResetPasswordRequest.php resources/views/auth/forgot-password.blade.php resources/views/auth/reset-password.blade.php lang routes/web.php tests/Feature/Auth/PasswordResetTest.php
git commit -m "feat: add password reset flow"
```

### Task 6: Build Admin welcome/onboarding and free-trial activation

**Files:**
- Create: `app/Http/Requests/Auth/CompleteOnboardingRequest.php`
- Create: `app/Http/Controllers/Onboarding/WelcomeController.php`
- Create: `app/Actions/Auth/CompleteOnboardingAction.php`
- Create: `resources/views/onboarding/welcome.blade.php`
- Create: `lang/{en,lt,ru,es}/onboarding.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Auth/RegistrationAndOnboardingTest.php`

- [ ] **Step 1: Extend the onboarding feature test to fail first**

Cover:
- authenticated Admin with no organization can view onboarding
- onboarding creates organization
- slug is unique and immutable
- onboarding creates a trial subscription
- onboarding blocks repeat access after completion

- [ ] **Step 2: Implement the onboarding action**

`CompleteOnboardingAction` should atomically:
- create organization
- assign owner user
- create trial subscription
- attach `organization_id` to the current Admin

Keep it transactional:
```php
DB::transaction(function () use ($user, $data): void {
    // create organization, subscription, and attach user
});
```

Do not introduce raw SQL.

- [ ] **Step 3: Build the onboarding page**

Keep the view focused:
- free-trial message
- organization name and slug form
- translated inline validation
- save button with loading state

- [ ] **Step 4: Run the onboarding test file**

Run:
```bash
php artisan test --compact tests/Feature/Auth/RegistrationAndOnboardingTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit onboarding**

Run:
```bash
git add app/Http/Controllers/Onboarding app/Http/Requests/Auth/CompleteOnboardingRequest.php app/Actions/Auth/CompleteOnboardingAction.php resources/views/onboarding lang routes/web.php tests/Feature/Auth/RegistrationAndOnboardingTest.php
git commit -m "feat: add admin onboarding and trial activation"
```

## Chunk 3: Invitations, Shell Integration, and Verification

### Task 7: Implement invitation issuance infrastructure and acceptance flow

**Files:**
- Create: `app/Http/Requests/Auth/AcceptInvitationRequest.php`
- Create: `app/Http/Controllers/Auth/AcceptInvitationController.php`
- Create: `app/Actions/Auth/CreateOrganizationInvitationAction.php`
- Create: `app/Actions/Auth/AcceptOrganizationInvitationAction.php`
- Create: `app/Notifications/Auth/OrganizationInvitationNotification.php`
- Create: `resources/views/auth/accept-invitation.blade.php`
- Modify: `lang/{en,lt,ru,es}/auth.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Auth/InvitationAcceptanceTest.php`

- [ ] **Step 1: Write the failing invitation tests**

Cover:
- invitation acceptance page renders with organization name
- accepting a valid invitation creates the user and logs them in
- expired invitation is rejected
- already accepted invitation is rejected
- invitation creation action rejects an email that already exists

Use notification assertions for the issuance side even though the admin-facing invitation UI is deferred to a later slice.

- [ ] **Step 2: Implement invitation issuance and acceptance actions**

`CreateOrganizationInvitationAction` should:
- verify inviter is an Admin for the organization
- reject existing-user emails
- generate a single-use token
- set `expires_at` to 7 days ahead
- dispatch `OrganizationInvitationNotification`

`AcceptOrganizationInvitationAction` should:
- verify token is pending and unexpired
- create the `users` row only at acceptance time
- mark the invitation accepted
- authenticate the user

- [ ] **Step 3: Build the acceptance view**

Requirements:
- translated greeting with organization name
- prefilled full name when present
- password and confirmation fields
- inline errors
- explicit expired state

- [ ] **Step 4: Run invitation tests**

Run:
```bash
php artisan test --compact tests/Feature/Auth/InvitationAcceptanceTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit invitation lifecycle support**

Run:
```bash
git add app/Http/Controllers/Auth/AcceptInvitationController.php app/Http/Requests/Auth/AcceptInvitationRequest.php app/Actions/Auth/CreateOrganizationInvitationAction.php app/Actions/Auth/AcceptOrganizationInvitationAction.php app/Notifications/Auth/OrganizationInvitationNotification.php resources/views/auth/accept-invitation.blade.php routes/web.php lang tests/Feature/Auth/InvitationAcceptanceTest.php
git commit -m "feat: add invitation acceptance flow"
```

### Task 8: Connect Filament and tenant entry points to the shared auth foundation

**Files:**
- Create: `app/Http/Controllers/Filament/RedirectToPublicLoginController.php`
- Create: `app/Filament/Pages/PlatformDashboard.php`
- Create: `app/Filament/Pages/OrganizationDashboard.php`
- Create: `resources/views/filament/pages/platform-dashboard.blade.php`
- Create: `resources/views/filament/pages/organization-dashboard.blade.php`
- Create: `resources/views/tenant/home.blade.php`
- Create: `app/Http/Controllers/Auth/LogoutController.php`
- Modify: `app/Providers/Filament/AdminPanelProvider.php`
- Modify: `app/Models/User.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Auth/LoginFlowTest.php`
- Test: `tests/Feature/Auth/AccessIsolationTest.php`

- [ ] **Step 1: Write the failing redirect and access tests**

Cover:
- `/admin` while logged out ends up at public `/login`
- superadmin login lands on platform dashboard route
- admin and manager land on organization dashboard route
- tenant lands on `tenant.home`
- tenant cannot access the Filament panel

- [ ] **Step 2: Add the Filament redirect shim and role-aware pages**

Use the panel’s login hook as a redirect shim:
```php
$panel->login(\App\Http\Controllers\Filament\RedirectToPublicLoginController::class);
```

Create two lightweight landing pages:
- `PlatformDashboard` with `canAccess()` for superadmins only
- `OrganizationDashboard` with `canAccess()` for admins and managers only

Tenant home stays a standard Blade route for now so the tenant slice can grow independently from Filament.

- [ ] **Step 3: Add logout and route naming consistency**

Expose a standard `POST /logout` route for the public/authenticated foundation and make sure redirects land on named routes used by `LoginRedirector`.

- [ ] **Step 4: Run redirect/access tests**

Run:
```bash
php artisan test --compact tests/Feature/Auth/LoginFlowTest.php tests/Feature/Auth/AccessIsolationTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit shell integration**

Run:
```bash
git add app/Providers/Filament/AdminPanelProvider.php app/Filament/Pages resources/views/filament/pages resources/views/tenant/home.blade.php app/Http/Controllers/Filament/RedirectToPublicLoginController.php app/Http/Controllers/Auth/LogoutController.php app/Models/User.php routes/web.php tests/Feature/Auth
git commit -m "feat: connect shared auth to role entry points"
```

### Task 9: Final verification, cleanup, and manual QA notes

**Files:**
- Modify: `tests/Feature/ExampleTest.php` if still needed
- Modify: `tests/Unit/ExampleTest.php` if still needed
- Review: all files touched in previous tasks

- [ ] **Step 1: Run Pint on the final PHP diff**

Run:
```bash
vendor/bin/pint --dirty --format agent
```

Expected: formatting completes with no errors.

- [ ] **Step 2: Run the full auth slice test set**

Run:
```bash
php artisan test --compact tests/Feature/Auth
```

Expected: all auth feature tests PASS.

- [ ] **Step 3: Run the full project suite**

Run:
```bash
php artisan test --compact
```

Expected: full suite PASS, including legacy smoke tests.

- [ ] **Step 4: Manual QA checklist**

Verify in the browser:
- `/login`, `/register`, `/forgot-password`, and invitation pages render with translated copy
- failed login preserves email
- Admin registration logs in and forces onboarding
- onboarding creates the org and free trial
- suspended users or suspended organizations are blocked
- `/admin` while logged out lands on the shared public login
- tenant login reaches the tenant home route

- [ ] **Step 5: Commit the verified slice**

Run:
```bash
git add app bootstrap config database lang resources routes tests
git commit -m "feat: implement foundation auth onboarding slice"
```
