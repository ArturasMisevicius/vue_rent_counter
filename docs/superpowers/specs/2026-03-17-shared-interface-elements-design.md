# Tenanto Shared Interface Elements Design

## Goal

Establish a single authenticated shell language for Tenanto so every signed-in user lands inside a consistent, branded interface with role-aware navigation, shared top-level actions, and clear fallback states.

This slice unifies the authenticated experience across Filament-backed admin areas and Blade-backed tenant pages without replacing either runtime. It adds the shared chrome, locale switching, notifications, pluggable global search, impersonation awareness, and branded error pages that later product slices can build on.

## Scope

This slice includes:

- a shared authenticated shell language for signed-in experiences
- a custom role-aware topbar for both Filament and tenant pages
- role-aware navigation with a Filament sidebar for superadmin, admin, and manager users
- tenant bottom navigation for tenant-facing routes
- a lightweight profile destination for the user avatar menu
- locale switching with immediate persistence and same-request UI updates
- a database-backed notifications center with unread state and bulk mark-read behavior
- a pluggable global search registry with route-safe providers and empty states
- impersonation banner rendering and a stop-impersonation flow
- branded 403, 404, and 500 pages that link users back to the correct dashboard
- shared branding and deterministic user-avatar presentation

This slice does not include:

- replacing Filament with a custom admin framework
- CRUD pages for organizations, users, buildings, properties, tenants, meters, readings, invoices, tariffs, providers, or reports
- a complete profile or settings area
- the UI action that starts impersonation from platform management screens
- search providers for modules that do not exist in the codebase yet
- any query logic inside Blade templates or Filament view renderers

## Approved Product Decisions

- Filament remains the authenticated shell runtime for superadmin, admin, and manager routes.
- Tenant pages remain standard Laravel web routes rendered with Blade.
- Both authenticated runtimes must present one shared product language instead of separate admin and tenant chrome systems.
- Filament chrome is customized through supported extension points rather than vendor view overrides.
- Shared shell behavior lives in small support classes, Livewire components, and Blade components rather than duplicated controller or view logic.
- Global search ships now as an extensible registry even though most future domain modules are not present yet.
- Notifications use Laravel's database notifications model so producers can be added later without redesigning the shell.
- Impersonation support in this slice is limited to visible session context plus a stop flow; start-impersonation entry points belong to a later slice.
- The avatar menu must always have a real, route-safe profile destination even before full settings are designed.

## System Architecture

The authenticated experience is split into two presentation layers that share one shell vocabulary:

1. Filament-backed admin-like pages for `superadmin`, `admin`, and `manager`
2. Blade-backed tenant pages for `tenant`

Both layers depend on the same shell support services for dashboard resolution, navigation decisions, avatar styling, locale behavior, search-provider registration, notification presentation, and impersonation state.

The result is intentionally hybrid:

- Filament continues to own panel routing, page lifecycle, and admin-page composition
- Blade continues to own tenant-page routing and server-rendered page structure
- Livewire owns interactive shell behavior such as navigation islands, locale switching, notifications, and search
- Blade components own shared frame markup, branding, avatars, and error-page presentation

This keeps each layer inside its native strengths while still making the application feel like one product.

## Shared Shell Composition

The shared shell is built from a small number of reusable primitives:

- `AppFrame` wraps authenticated Blade pages and error pages
- a shared brand partial renders the Tenanto wordmark/logo consistently
- a shared user-avatar partial renders initials with deterministic color selection
- a shared topbar component exposes search, locale switching, notifications, impersonation state, and the user menu
- role-specific navigation is rendered either as a Filament sidebar or a tenant bottom navigation bar

The shell must be query-free at render time. Any data required by the shell is prepared before it reaches Blade, or is loaded inside dedicated Livewire/support classes.

## Role-Aware Navigation Model

Navigation behavior is determined by role and current route, not by duplicated conditionals in views.

`NavigationBuilder` is responsible for:

- grouping items for admin-like users
- producing tenant bottom-navigation items
- highlighting the active destination
- suppressing items whose route names do not exist yet
- keeping today's shell route-safe even while later modules are still missing

Navigation items are represented as immutable data objects so Blade and Livewire only render prepared state. The shell may show placeholders or omit modules entirely, but it must never expose dead links.

## Dashboard Resolution

Any feature that needs a "back to dashboard" action must resolve that destination centrally rather than re-implementing role logic.

`DashboardUrlResolver` provides the canonical signed-in landing URL for the current user and a safe fallback when no authenticated dashboard is available. This resolver is shared by error pages, impersonation stop flow, and any shell control that needs to redirect users home.

## Locale and Language Behavior

Locale is part of the signed-in user preference model.

The shell must support these locales:

- English (`en`)
- Lithuanian (`lt`)
- Russian (`ru`)
- Spanish (`es`)

The locale switcher:

- shows locale names in their own language
- persists the selected locale on the current user
- updates the application locale during the same Livewire request
- allows the next rendered response to immediately use the new translations

Locale metadata and polling/debounce settings live in configuration so the shell does not hardcode labels or timing values in components.

## Notifications Model

Notifications are built on Laravel's database notifications table and the `Notifiable` relationship already aligned with the framework.

The notification center must support:

- unread badge counts
- panel rendering with title, preview text, and relative timestamps
- marking a single notification as read
- marking all unread notifications as read in one data-layer operation
- optional redirection when a notification carries a valid destination URL

Presentation formatting belongs in a dedicated presenter so relative-time copy and preview trimming stay out of Blade templates.

## Global Search Model

Global search is designed as a registry, not a one-off query.

The shell exposes one consistent search surface now, but actual result sources are supplied by pluggable providers. Each provider:

- decides whether it is available for the current role and current codebase state
- uses explicit Eloquent `select([...])` payloads
- returns immutable result data
- declines to return clickable results if the target route does not exist

This design lets the product ship search UI immediately without inventing unfinished CRUD pages. It also ensures the shell remains safe in partial product states.

Initial providers can target routable organizations and users when those routes exist. Future modules can register additional providers without changing the Livewire search component contract.

## Impersonation State

Impersonation support in this slice is session-based and intentionally narrow.

`ImpersonationManager` owns the full session contract, including:

- impersonator id
- impersonator name
- impersonator email

The shell banner appears whenever impersonation metadata exists, stays visible until impersonation ends, and exposes one stop action. Stopping impersonation clears the managed session state and returns the user to the correct dashboard via the shared resolver.

Both Filament-backed and Blade-backed shells must render the same banner language so impersonation remains obvious regardless of where the user navigates.

## Error Pages and Fallback States

Authenticated fallback states must feel like part of the same product, not framework defaults.

This slice adds branded 403, 404, and 500 pages that:

- use the shared shell language and brand assets
- provide translation-driven copy
- avoid leaking sensitive application details
- offer a role-aware action back to the appropriate dashboard

The 500 page must show the support-safe production message when debug mode is disabled.

## Data Flow and Performance Rules

This slice follows the existing project rules for Laravel, Blade, and Filament:

- no queries inside Blade templates
- no duplicated route/role logic across controllers and views
- no raw SQL or ad-hoc query strings
- eager load any relationships required by shell views
- use explicit `select([...])` payload control in search providers and presenters
- keep cache, configuration, and reusable query decisions out of templates

Interactive shell pieces should prefer Livewire islands and minimal Alpine.js only where local UI state is needed. Search fetching, notification state changes, and locale persistence belong to Livewire/actions rather than bespoke browser code.

## Testing Strategy

This slice is validated through focused feature coverage around the shell itself:

- authenticated shell rendering for tenant and admin-like pages
- role-aware navigation visibility and active-state behavior
- locale switcher rendering and persistence
- notifications badge, panel, and mark-read flows
- pluggable global search behavior and route-safe empty states
- impersonation banner rendering and stop flow
- branded 403, 404, and 500 page behavior

Regression coverage must also confirm that shared shell changes do not break the earlier authentication and onboarding slice.

## Dependencies and Future Extension Points

This slice depends on the foundation auth/onboarding design for:

- current roles
- login redirect behavior
- locale persistence groundwork
- organization-aware access boundaries

It creates stable extension points for later work:

- additional navigation groups as new modules land
- additional notification producers
- additional global search providers
- a future full profile/settings area
- impersonation entry points from platform management

## Success Criteria

This design is successful when:

- every signed-in route feels like part of one Tenanto product shell
- navigation is role-aware and never renders dead links
- locale changes are immediate and persisted
- notifications and search work inside the shared chrome without leaking future-module assumptions
- impersonation is always visible and easy to stop
- branded fallback pages guide users safely back into the product
