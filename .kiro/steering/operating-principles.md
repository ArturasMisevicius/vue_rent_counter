# Operating Principles

## Engineering

- Prefer Volt single-file Livewire components with shared traits; keep controllers thin and service-aware.
- Enforce type safety (`declare(strict_types=1);`) and immutability where practical; favor value objects/enums.
- Keep UI consistent through design tokens and Blade/Flux primitives; avoid ad-hoc utility sprawl.
- Default to optimistic UI with graceful rollback and explicit error states.
- Logically separate concerns: validation in FormRequests, authorization in Policies, data access via scopes/services.

## Delivery & Review

- Every PR ships with tests: Pest for PHP, Playwright for critical UI, property tests for invariant-heavy logic.
- Run Pint + PHPStan + eslint/prettier locally before pushing; treat warnings as blockers.
- Update relevant docs (`docs/`, `.kiro/specs`, `.kiro/steering`) alongside code changes.
- Backwards compatibility by default: additive migrations, feature flags/config toggles when behavior changes.
- Small, cohesive changesets; keep rollout notes in PR descriptions.

## Accessibility & UX

- Keyboard-first navigation and focus management for all admin flows; avoid hidden focus traps in modals.
- Respect prefers-reduced-motion and color-contrast targets; validate against WCAG 2.1 AA.
- Persist filter/search state in URLs for shareable admin/public views.
- Keep UI copy localized; never hardcode strings in Blade/JS.

## Security

- Policies for every admin action; never bypass authorization in Livewire actions.
- Keep CSP/security headers intact; whitelist only necessary origins.
- Validate user input via FormRequests; avoid trusting client-provided IDs without ownership checks.
- Treat demo mode carefully: prevent destructive operations from leaking outside seeded scope.

## Operations

- Cache config/routes/views in production; monitor queue health when adding async workloads.
- Avoid N+1 queries; eager load relationships in controllers/services and Livewire components.
- Keep seeds deterministic and idempotent; use factories for tests.
- Document migrations and deployment steps in PR descriptions when altering schema.
