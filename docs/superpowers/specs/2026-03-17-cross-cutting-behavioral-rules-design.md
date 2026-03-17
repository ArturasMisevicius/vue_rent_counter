# Tenanto Cross-Cutting Behavioral Rules Design

## Goal

Enforce Tenanto's shared behavioral rules across the already-planned admin, manager, superadmin, and tenant experiences so the application behaves consistently no matter where a mutation originates.

This design does not introduce a new product area. It hardens the existing and planned surfaces with one set of shared rules for subscription enforcement, meter-reading validation, invoice immutability, real-time refresh behavior, table persistence, loading and feedback states, and locale fallback.

## Scope

This slice includes:

- organization-scoped subscription access evaluation
- shared limit and grace-period messaging
- one meter-reading validation engine used by admin CRUD, tenant submission, and bulk import
- one finalized-invoice mutability guard used by actions and edit screens
- event-driven partial refresh contracts with timed polling fallback
- standardized Filament table sorting and session-persisted filters
- shared skeleton loading states and severity-based toast behavior
- immediate locale switching with English fallback

This slice does not include:

- new CRUD domains, new billing plans, or new reporting surfaces
- custom JavaScript state systems for table sorting or filter persistence
- a second locale-storage mechanism outside the shared `users.locale` field
- duplicate validation or lock logic for specific roles or screens
- replacing Filament or tenant Blade shells with a new UI framework

This design assumes the prerequisite foundation, shell, admin workspace, manager parity, superadmin control plane, and tenant portal slices either already exist or are being implemented in parallel with these rules layered on top.

## Approved Product Decisions

- Cross-cutting behavior is a shared overlay, not a new standalone subsystem.
- Hard business rules live in shared support classes first, then surface-specific adapters apply them in Filament, Livewire, Blade, and Actions.
- Meter-reading validation must be identical for admin entry, tenant submission, and import flows.
- Finalized-invoice mutability must be enforced from one guard, never re-implemented inside individual pages.
- Reaching a subscription plan limit does not remove read access. It blocks the constrained create action and routes the user toward upgrade.
- Expired subscriptions inside the grace period move the organization into read-only mode without fully hiding the experience.
- After the grace period, organization-scoped write and payment-processing actions disappear or become unavailable everywhere, while view and download access continue.
- Real-time updates use small broadcast payloads plus fixed polling intervals as a freshness fallback. Polling alone is not sufficient for the intended experience.
- Filament's built-in sorting and filter persistence should be used instead of bespoke browser state.
- Transient feedback uses one shared toast severity model across Filament and non-Filament surfaces.
- English is the required fallback locale for any missing translation.

## System Architecture

The implementation is split into three layers.

### Domain Guardrails

Shared support classes own the business rules that must stay consistent across every entry point:

- `OrganizationSubscriptionAccess` computes organization write-access state
- `SubscriptionEnforcementMessage` turns access state into localized UX copy
- `ValidateReadingValue` and `ReadingValidationResult` determine whether a reading is blocked, anomalous, or allowed with review notes
- `FinalizedInvoiceGuard` decides which invoice fields remain mutable after finalization

These classes are the source of truth. No Filament Resource, Livewire component, or Blade view is allowed to create a parallel version of the same rule.

### Experience Adapters

Thin adapters translate shared rules into the user experience of each surface:

- Filament concerns intercept or hide actions based on subscription access state
- Actions and services enforce the same restrictions even when UI affordances are bypassed
- Livewire traits subscribe to refresh topics and request partial re-renders
- Blade and Livewire shell components render shared skeletons, toast stacks, and translated labels

This layer keeps the user experience coherent without moving policy decisions into UI code.

### Runtime Contract Layer

Configuration and runtime primitives make the cross-cutting rules predictable:

- `config/tenanto.php` defines grace periods, polling intervals, supported locales, and related behavior settings
- broadcast events and channel authorization define who may listen for refreshes
- translation files hold behavior-specific messaging per locale
- session-backed table persistence and locale persistence keep the experience stable between requests

## Subscription Access Model

Organization-scoped write behavior is driven by an explicit access mode rather than ad hoc booleans. The access modes are:

- `ACTIVE`
- `LIMIT_BLOCKED`
- `GRACE_READ_ONLY`
- `POST_GRACE_READ_ONLY`

The access mode is computed from:

- subscription status
- expiration timestamp
- configurable grace-period duration
- current usage against plan limits
- the organization context attached to the acting user

### Access Semantics

`ACTIVE`

- full read and write access
- create, edit, delete, and payment actions are available subject to normal policy checks

`LIMIT_BLOCKED`

- normal read access continues
- only the write actions gated by the exhausted limit are blocked
- existing records may still be viewed and edited when that edit does not create more quota-bound records
- the blocked action must explain the current limit and offer an upgrade path

`GRACE_READ_ONLY`

- organization data remains visible
- organization-scoped writes are blocked server-side
- create and edit affordances remain visible so the user understands why work is blocked and how to renew
- surfaces that expose other write actions should keep them unavailable in a way that communicates renewal is required rather than looking like a broken screen

`POST_GRACE_READ_ONLY`

- organization data remains visible
- create, edit, delete, and payment-processing actions disappear or become unavailable everywhere
- invoice view and download behavior remains available
- the subscription or billing settings destination remains reachable so renewal can still happen

### Access Matrix

| Access mode | Read existing data | Create new data | Edit existing data | Delete data | Process invoice payment | Renewal/upgrade prompts |
| --- | --- | --- | --- | --- | --- | --- |
| `ACTIVE` | Yes | Yes | Yes | Yes | Yes | Optional usage messaging only |
| `LIMIT_BLOCKED` | Yes | Blocked only for exhausted quota | Usually yes | Yes | Yes | Required for the blocked quota action |
| `GRACE_READ_ONLY` | Yes | Blocked with renewal explanation | Blocked with renewal explanation | Blocked | Blocked | Required |
| `POST_GRACE_READ_ONLY` | Yes | Hidden or unavailable | Hidden or unavailable | Hidden or unavailable | Hidden or unavailable | Required |

### Surface Rules

For admin and manager organization workspaces:

- property and tenant creation must honor subscription limits
- grace-period read-only state keeps create and edit actions visible but intercepted
- post-grace state removes or disables write actions consistently across list pages, edit pages, and settings-adjacent flows

For tenant-facing organization-scoped actions:

- actions such as reading submission inherit the same access mode checks
- tenant users continue to see their data and invoices in read-only periods

For superadmin:

- platform-level control-plane actions are not globally blocked by an organization's subscription state
- any superadmin flow that operates inside an organization's commercial context must use the same access-state service rather than inventing an exception path

## Meter Reading Validation Rules

Meter-reading validation is handled by one reusable validator that accepts the target meter, proposed reading value, reading date, and any supporting context needed for anomaly detection.

The validator returns a structured result object instead of plain strings or booleans. The result carries:

- whether the reading is blocking
- whether the reading is anomalous but still allowed
- whether a long gap note should be attached
- computed consumption deltas where applicable
- user-facing message or note text suitable for translation
- any validation status that downstream workflows need for persistence

### Blocking Rules

A reading is blocked when:

- the value is lower than the previous reading
- the reading date is in the future
- any other invariant added later must be treated as a shared rule rather than a role-specific patch

Blocked readings do not persist in any write path.

### Non-Blocking Review Rules

A reading is allowed but flagged when:

- consumption exceeds three times the average monthly usage
- more than sixty days have passed since the previous reading and the product requires a gap note

Allowed-but-flagged readings persist, but they carry review-oriented status and notes so staff can distinguish ordinary submissions from suspicious ones.

### Write-Path Requirements

The following flows all call the same validator before saving:

- admin create reading
- admin update reading
- admin bulk import
- tenant reading submission

No flow may downgrade the validator output into a weaker, role-specific interpretation.

## Finalized Invoice Immutability

Invoice finalization is treated as the point where invoice-commercial content becomes immutable.

The immutable set includes:

- line items
- totals and calculated amounts
- pricing inputs that would change the commercial meaning of the invoice
- any metadata that changes what the invoice charges for

The allowed mutable set includes only the fields necessary to complete payment and status handling after finalization, such as:

- payment timestamps
- payment references
- payment method metadata
- explicitly allowed status or settlement markers

### Guard Rules

- the allowed mutable field list lives in one guard
- backend actions must consult the guard before mutating finalized invoices
- the edit UI mirrors the guard instead of defining its own rules
- payment processing remains functional for finalized invoices
- once an invoice reaches finalized mutability rules, later transitions must not silently reopen line-item editing

This keeps accounting integrity consistent whether a change is initiated from a form, an action, or a background process.

## Runtime Refresh and Polling Model

The application needs immediate partial updates for high-value operational data, but it must stay resilient when broadcasts are delayed or unavailable.

### Event Contracts

Two shared UI events define the first refresh contract:

- `MeterReadingChanged`
- `InvoiceChanged`

Event payloads stay intentionally small. They may include:

- organization identifier
- tenant user identifier when the update targets a tenant-specific surface
- invoice or reading record identifiers
- a refresh topic or component-friendly discriminator

### Channel Authorization

Broadcast channels are private and scoped by ownership rules:

- organization channels require membership in the target organization
- tenant-specific channels require the authenticated user to be the intended tenant
- invoice-specific channels must resolve through organization or tenant ownership rules rather than exposing raw IDs

### Polling Rules

Polling remains active even when broadcasts are available:

- organization admin dashboards and integration health: `30s`
- superadmin dashboards: `60s`
- tenant home summary surfaces: `120s`

Broadcasts make the experience feel immediate. Polling protects freshness after missed events, stale tabs, reconnects, or temporary real-time outages.

### Component Integration

Livewire pages and widgets should adopt a shared listener trait so the refresh contract is declared once and reused broadly. Page-specific classes may define which topics they care about, but they should not re-implement channel subscription plumbing.

## Table Behavior and Persistence

Filament list experiences should feel identical across the main operational tables.

### Sorting Rules

- default sort is `created_at desc` unless a table truly lacks a meaningful created timestamp
- user-facing sortable columns must explicitly opt into sorting
- sort direction should cycle through ascending, descending, and reset-to-default behavior

### Filter Persistence

- filter and sort state persists for the current browser session using Filament-native persistence
- navigation away from a list and back again should preserve the user's current filter state
- choosing "Clear All Filters" resets persisted state for that table

This behavior must be provided through a shared concern or pattern rather than copied manually into each resource.

## Loading and Feedback Behavior

Cross-cutting feedback rules make the product feel deliberate instead of inconsistent.

### Loading States

- pages and panels should render lightweight skeleton placeholders instead of blank white space during loading
- buttons that trigger server work must show loading and disabled states while the request is active
- loading treatments should be reusable across tenant Blade pages and Filament-adjacent surfaces

The first shared skeleton primitives are:

- card skeleton
- list skeleton
- table skeleton

### Toast Behavior

Transient action feedback is normalized through a shared toast payload and factory.

Severity rules:

- success toasts auto-dismiss after 5 seconds
- warning toasts auto-dismiss after 8 seconds
- error toasts stay visible until manually dismissed

Additional rules:

- toast presentation must be consistent across Filament actions and custom Blade/Livewire pages
- toast behavior is separate from the persistent notifications center
- browser `alert()` usage is forbidden for this product feedback

## Localization Strategy

The shared locale model introduced in earlier slices remains the only source of truth for user language preference.

Requirements:

- supported locales remain `en`, `lt`, `ru`, and `es`
- locale changes persist immediately to the authenticated user's record
- the next rendered response in the same session must use the new locale without requiring a manual reload
- translated labels in the switcher and affected shell UI must update immediately after the change
- missing keys fall back to English
- the UI must never render a blank string or raw translation key because a locale file is incomplete

Behavior-specific copy for subscription enforcement, validation messaging, and other cross-cutting rules should live in dedicated behavior translation files so these messages are not scattered across unrelated language domains.

## Edge Cases and Failure Handling

### Subscription Limit Reached

When an organization reaches a property or tenant limit:

- the constrained create action does not open the normal form
- the user receives the limit explanation for their current plan
- the call to action points toward the subscription management destination

### Subscription Expired Within Grace Period

When a subscription is expired but still inside the configured grace period:

- organization data remains readable
- attempted writes are blocked by shared guardrails
- the UX explains renewal instead of feeling like a permissions bug

### Subscription Expired After Grace Period

When the grace period has passed:

- mutating actions are removed or made unavailable across all organization-scoped surfaces
- invoice viewing and download remain intact
- renewal entry points remain reachable

### Suspicious Meter Readings

When a reading looks anomalous but is still allowed:

- the record is stored
- the saved state captures that review is required
- downstream dashboards and workflows can react without needing to re-run bespoke heuristics

### Missed Real-Time Events

When a browser tab misses a broadcast event:

- polling still refreshes the relevant widgets within the configured interval
- no component depends on a long-lived websocket state to become correct again

### Missing Translation Keys

When a selected locale is missing a cross-cutting behavior key:

- English copy is rendered
- the user never sees an empty value or internal key identifier

## Testing Strategy

This slice should be covered by a mix of focused unit tests for shared rule engines and feature tests for visible behavior.

Required unit coverage:

- subscription access-state computation for active, limit-blocked, grace read-only, and post-grace cases
- finalized-invoice mutability decisions for allowed and disallowed fields

Required feature coverage:

- property and tenant create actions respect subscription limit and grace/post-grace behavior
- admin, tenant, and import reading flows all receive the same validation outcomes
- finalized invoice edit attempts preserve immutable fields while allowing payment updates
- meter and invoice mutations dispatch the expected refresh events
- polling intervals are exposed correctly on admin, superadmin, integration-health, and tenant surfaces
- Filament tables use the standard sort cycle and session-persisted filters
- skeletons and toast payloads match the required loading and dismissal behavior
- locale switching persists immediately and English fallback works for missing keys

## Delivery Boundary for This Slice

This cross-cutting layer is complete when:

- organization subscription state is computed in one place and consistently enforced across write paths
- property and tenant creation respect plan limits and renewal messaging
- grace and post-grace subscription behavior matches the approved product rules
- one meter-reading validator governs admin, tenant, and import writes
- finalized invoices cannot be commercially edited after finalization, while payment processing still works
- high-value dashboards and invoice or reading surfaces refresh through broadcasts plus polling fallback
- main Filament operational tables share predictable sorting and filter persistence
- shared loading and toast behavior exists across Filament and tenant-facing surfaces
- locale switching is immediate and missing translations fall back to English

## Out of Scope for the Next Planning Step

The implementation plan for this spec should not add:

- new billing products or renewal checkout flows
- new CRUD areas beyond the prerequisite slices
- a custom front-end state library for tables or toasts
- per-role duplicate versions of validation, invoice-lock, or locale logic
- translation-management tooling beyond the fallback-safe behavior required here
