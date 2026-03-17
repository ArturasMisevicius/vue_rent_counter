# Tenanto Public Homepage Design

## Goal

Establish a production-ready public homepage at `/` for `https://tenanto.test/` that introduces Tenanto as a property-operations platform while explicitly framing the current environment as a guided system-testing entry point.

The page must help two audiences at the same time:

- future users who need to understand what Tenanto will do for property operators, organizations, and tenants
- system testers who need a clear public starting point for validating multilingual copy, authentication entry points, and role-aware product direction

This slice intentionally stops at the public guest experience boundary. It does not implement authenticated dashboards, CRUD modules, billing workflows, tenant actions, or superadmin operations. Those behaviors remain defined in the existing `docs/superpowers/plans/` and related design documents.

## Scope

This slice includes:

- a redesigned public homepage at `/`
- a shared guest-facing language switcher for public auth pages
- prominent `Login` and `Register` entry points
- localized homepage descriptions in:
  - English
  - Lithuanian
  - Spanish
  - Russian
- a public explanation of Tenanto’s role model:
  - Superadmin
  - Admin
  - Manager
  - Tenant
- a roadmap-oriented project description derived from the current superpowers plans
- explicit “for system testers” guidance on what can be validated from the public entry

This slice does not include:

- new database tables
- any homepage database queries
- public marketing forms, newsletter capture, or support tickets
- a documentation portal
- a public pricing page
- tenant or admin dashboard implementation
- a full translation-management interface

## Approved Product Decisions

- The homepage uses a tester-first public narrative rather than a generic marketing-only landing page.
- The existing guest/auth visual language remains the foundation: deep ink background, warm gold highlights, mint accents, and the same polished brand tone used by the login and registration pages.
- The public homepage and guest auth pages share one language-switching pattern so users do not experience locale changes only after authentication.
- The homepage keeps `Login` and `Register` above the fold and visible on mobile and desktop.
- The homepage explains the current system as a staged rollout, not as a fully complete product.
- The public copy stays honest about the current state of the platform while still communicating the future operational value of Tenanto.

## Experience Strategy

The homepage is the public front door of the product and must solve three jobs quickly:

1. orient visitors to what Tenanto is
2. give testers a structured place to begin validating the product
3. route guests into `Login` or `Register` without friction

The page should feel like a live platform preview rather than a generic SaaS template. The emotional tone is:

- credible
- polished
- operational
- forward-looking
- explicit about testing readiness

The core positioning statement is:

> Tenanto is a property-operations platform presented as a guided testing lab.

That line balances the future product promise with the current implementation reality.

## Information Architecture

The homepage is structured as one scrollable guest page with six primary sections.

### 1. Header

The header sits at the top of the public page and remains lightweight rather than sticky-heavy.

It contains:

- Tenanto brand mark and short tagline
- language switcher with `EN`, `LT`, `ES`, and `RU`
- `Login` link to `route('login')`
- `Register` link to `route('register')`

Behavior:

- `Login` is secondary in visual weight
- `Register` is primary in visual weight
- language switching updates the page immediately and preserves the user on the current guest page

### 2. Hero

The hero establishes Tenanto as both a real platform and a current testing environment.

It contains:

- one concise tester-first headline
- one supporting paragraph describing Tenanto’s future value
- trust chips or badges for:
  - live testing environment
  - four interface roles
  - multilingual public entry
- direct call-to-action pair:
  - `Register`
  - `Login`

The hero should also include a compact role overview so visitors understand the platform scope without needing to scroll.

### 3. Role Overview

This section presents the four product roles as short cards or tiles:

- Superadmin: platform control, governance, monitoring
- Admin: organization setup, operations, onboarding
- Manager: day-to-day execution inside the organization workspace
- Tenant: self-service access to readings, invoices, and profile

The role section is descriptive rather than interactive. It exists to explain the shape of the system to testers and future users.

### 4. System Testers Section

This section explicitly answers:

> What can be tested from this environment today?

It contains a short checklist-style block that points testers toward:

- public entry validation
- multilingual copy switching
- registration and login entry points
- alignment between public messaging and the current role-based roadmap

This section should feel operational and clear, not promotional.

### 5. Product Roadmap / Future User Value

This section translates the current `docs/superpowers/plans/` into user-facing language.

It explains that Tenanto is growing across these product surfaces:

- shared authenticated shell
- superadmin control plane
- organization operations
- tenant self-service portal
- cross-cutting behavioral rules such as subscription enforcement and validation

This section should not expose internal file names or implementation terminology to end users. It should communicate the roadmap in plain product language.

### 6. Closing CTA

The page ends with one closing call-to-action band or card.

It reinforces:

- `Login` for existing users and testers
- `Register` for new admin accounts

It may also include one concise sentence clarifying that Manager and Tenant accounts arrive through organization-controlled flows rather than public registration.

## Content Model

The homepage content should be translation-driven instead of hardcoded inline in the Blade template.

Recommended structure:

- `lang/en/landing.php`
- `lang/lt/landing.php`
- `lang/es/landing.php`
- `lang/ru/landing.php`

Each locale file should provide structured keys for:

- brand tagline
- hero headline
- hero supporting copy
- CTA labels
- role labels and descriptions
- tester section heading and checklist lines
- roadmap heading and descriptive items
- closing CTA copy

This keeps the page consistent with the rest of the localized auth experience and avoids mixing content decisions into the view layer.

## Language Switching Behavior

The current repository already supports translated auth copy and authenticated user locale persistence, but it does not yet provide a guest-facing locale switch flow.

This homepage slice adds that missing guest locale layer.

### Supported Locales

The public guest experience supports:

- `en`
- `lt`
- `es`
- `ru`

### Guest Locale Rule

For guests:

- the selected locale is stored in session
- the active locale applies immediately to the homepage and guest auth pages
- English remains the fallback locale

For authenticated users:

- the saved `users.locale` remains authoritative after login
- guest session locale does not override an already-saved user locale

### Registration Rule

Public registration should continue to create Admin users with their locale set from the currently active application locale. This matches the existing registration behavior and makes the homepage language switcher relevant to the registration flow.

### Guest Auth Consistency

The same language-switching UI pattern should appear on:

- homepage
- login
- register
- forgot password
- reset password
- invitation acceptance

This avoids a mismatch where the user can change language on `/` but loses that control on the next guest screen.

## Route and Application Flow

The current root route is a closure that returns `view('welcome')`.

This should be refactored into a thin controller or invokable action so the public experience follows the same architectural discipline as the rest of the application.

Recommended flow:

- `GET /` resolves through a dedicated controller or action
- the controller returns the homepage view with pre-shaped translated section data
- the view renders only presentation logic
- `Login` and `Register` buttons route to the existing named auth routes
- locale-switch actions redirect back to the current guest route

The homepage must not load any database records. All content is static or translation-driven for this slice.

## Blade and View Composition

The public homepage should reuse the guest-shell direction established by `resources/views/layouts/guest.blade.php`, but it needs a broader layout than the auth card pages.

Recommended composition:

- keep one shared guest layout or introduce a sibling guest-marketing layout
- render the homepage as a dedicated Blade view
- extract repeated public UI elements into Blade components where appropriate:
  - language switcher
  - role card
  - tester checklist panel
  - closing CTA

The homepage view should remain data-first and presentation-only:

- no queries
- no loops that trigger model methods
- no business logic in Blade

## Visual Design Direction

The approved visual direction is:

- tester-first
- editorial product polish
- consistent with existing auth styling
- responsive on desktop and mobile

Visual characteristics:

- dark-to-cream atmospheric background using the current brand ink, warm, and mint palette
- expressive but controlled typography that stays consistent with the current guest/auth font choices
- bold hero composition with clear contrast
- card-based role overview
- lighter operational panels for tester guidance and roadmap content

The homepage should feel more expansive than the login/register cards but still unmistakably part of the same product family.

## Copy Strategy

The copy should communicate two truths at once:

1. Tenanto is becoming a serious multi-role property operations platform.
2. This public entry currently exists to help system testers validate that platform as it is being assembled.

The copy should avoid:

- pretending all roadmap features are already complete
- sounding like placeholder lorem-ipsum marketing
- exposing internal implementation language such as “chunk 1,” “spec,” or “superpowers plan”

The copy should favor:

- concise operational phrasing
- explicit role descriptions
- honest statements about current testability
- future-user benefits in plain language

## Error Handling and Fallbacks

Locale handling must be safe and predictable.

Rules:

- unsupported locale requests fall back to English or the previous valid locale rather than producing a broken page
- missing translation keys must resolve through Laravel’s English fallback behavior
- language switching redirects back safely to the prior guest page
- public auth links remain valid regardless of the active locale

The homepage should not become inaccessible if a locale file is incomplete.

## Testing Strategy

This slice should be covered by focused feature tests.

Recommended coverage:

- homepage returns `200`
- homepage shows `Login` and `Register`
- homepage displays localized copy in each supported language
- guest locale switching persists across homepage and auth routes
- unsupported locale input falls back safely
- registration still stores the active locale on the created Admin user
- authenticated user locale continues to win after login

Browser-level smoke coverage is optional, but server-side feature tests are required.

## Query and Performance Expectations

This homepage should execute without introducing application queries for rendering the public page itself.

Expected characteristics:

- zero database reads for homepage content
- no N+1 risk
- translation and view rendering only

This makes the public entry inexpensive to render and safe to expand with additional localized copy later.

## Out of Scope Follow-Ons

The following can be addressed later if needed:

- public screenshot or feature-gallery components fed from real product data
- richer animation or motion polish
- public release notes or changelog entry from the homepage
- public tester credentials display
- a separate public documentation or help center

## Recommended Implementation Boundary

Once this design is approved for execution, implementation should cover:

- homepage route/controller cleanup
- guest locale switching mechanism
- localized landing-page copy files
- homepage Blade template and supporting components
- guest-auth layout updates so language switching is shared across all public auth pages
- feature tests for homepage and guest locale behavior

That gives Tenanto a coherent public front door without coupling the landing page to unfinished dashboard internals.
