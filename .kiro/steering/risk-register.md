# Risk Register

| Risk | Impact | Likelihood | Mitigation |
| --- | --- | --- | --- |
| Livewire regressions on optimistic UI | Broken admin actions / data loss | Medium | Expand Playwright + Pest coverage; include rollback paths; use per-action loading/error states. |
| Translation drift (English vs Spanish) | Mixed-language UI, accessibility gaps | Medium | Enforce localization linting; block merges with missing keys; add tests for `lang` coverage. |
| Slow news filters under load | Poor UX, higher bounce | Medium | Keep indexes on `published_at`, `slug`, foreign keys; eager load; paginate; cache filter metadata. |
| Bulk actions over-processing | Lock contention, timeouts | Low | Respect configured bulk limits; queue long-running tasks; provide progress feedback. |
| Security header misconfiguration | CSP bypass or frame injection | Low | Keep `config/security.php` source of truth; add test to assert headers; document allowed origins. |
| Demo mode misuse in prod | Privilege escalation | Low | Highlight config flag in docs; ensure policies still enforced; disable admin:create auto-login in prod. |
| Component divergence from design tokens | Visual inconsistency | Medium | Lint Tailwind usage; document variants; add property tests for token application. |
| Accessibility regressions | Non-compliance | Medium | Run Lighthouse/axe regularly; keyboard walkthroughs for new modals/forms; central ARIA patterns. |
