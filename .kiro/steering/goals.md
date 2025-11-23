# Product Goals

## North Star

Frictionless publishing and moderation for news/blog teams: publish and curate content in minutes with zero manual page refreshes, high accessibility, and predictable performance.

## Objectives (12-week horizon)

- Deliver a fully Livewire-powered admin CRUD that keeps 95% of operations modal/inline without full reloads.
- Ship a cohesive design system (tokens + UI primitives) that stabilizes component variants across public/admin surfaces.
- Harden news/archive experience with fast, bookmarkable filters and localized navigation.
- Preserve accessibility and security guarantees through every change (WCAG 2.1 AA, CSP intact, policies enforced).

## Success Metrics

- Admin flows: < 400ms median interaction latency; < 1% error rate across Livewire actions.
- Content accuracy: 0 failed policy violations in audit for create/update/delete across posts/categories/comments/users.
- A11y: Lighthouse accessibility ≥ 95; keyboard-only completion of CRUD and filters.
- Localization: 100% strings in `lang/{locale}.json` and PHP files; no hardcoded UI text in views/components.
- Quality: CI green with Pint + PHPStan max + Pest + Playwright smoke on every main branch merge.

## Guardrails / Anti-Goals

- No custom multi-tenant controls or billing flows in this cycle.
- Avoid bespoke JS SPA patterns; stay within Laravel + Livewire + Flux primitives.
- Keep migrations backward-compatible and idempotent; no destructive schema without explicit migration path.
- Maintain demo-mode safety: never log sensitive data; ensure seeders remain deterministic.
