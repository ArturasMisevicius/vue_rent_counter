# Quality Playbook

## Quality Gates (must pass)

- **Static**: Pint (auto + `--test`), PHPStan (max level), ESLint/Prettier (with Tailwind plugin), TypeScript build clean.
- **Tests**: Pest suites (Unit, Feature, Browser when applicable), Playwright smoke for admin CRUD + public news filters, property tests for invariants (posts, comments, news filters).
- **Accessibility**: Lighthouse ≥ 95, axe clean for key templates, keyboard-only runs through admin modals and tables.
- **Performance**: Admin interactions median < 400ms; news page filters fast (< 350ms server processing) with indexes and eager loading.
- **Security**: Policies enforced for admin routes/actions, CSP headers intact, no direct mass assignment without guarded fillables, validated input for all Livewire actions.

## Checklists

- **Backend**: Strict types; early returns guard clauses; query scopes reused; avoid duplicated validation logic; cache heavy computations where safe.
- **Livewire**: Public properties validated; emits/responds namespaced; optimistic UI includes rollback; bulk actions respect configured limits.
- **Frontend**: Components lean on design tokens; ARIA labels set; loading/empty/error states present; avoid inline JS in Blade unless Alpine.
- **Database**: Indexes on queryable columns; migrations reversible; seed data realistic; foreign keys enforced.

## Release Confidence

- CI: composer test (aggregated quality), npm run lint, npm run build.
- Smoke: Run Playwright admin smoke (login, post CRUD, comment moderation) and public news filter walk-through before tagging release.
- Observability: Capture log noise via `php artisan pail`; ensure failure modes surface to UI with user-friendly copy.
