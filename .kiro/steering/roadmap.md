# Roadmap Snapshot

## Now (in-flight)

- **Filament admin panel** (`.kiro/specs/filament-admin-panel`): Finish Filament resources for properties, buildings, meters, meter readings, invoices, tariffs, providers, users, and subscriptions plus the navigation/breadcrumb experience, validation consistency, and accessibility coverage already documented in the spec.
- **Vilnius utilities billing** (`.kiro/specs/vilnius-utilities-billing`): Deliver gyvatukas calculations, multi-zone tariff selection, audited meter readings, invoice snapshotting, and WAL/backup requirements for Lithuanian billing rules.
- **Hierarchical user management** (`.kiro/specs/hierarchical-user-management`): Keep validating the three-tier roles (superadmin, admin, tenant), subscription lifecycle, tenant assignments, and audit logging around account actions.

## Next (queued)

- **Authentication testing** (`.kiro/specs/authentication-testing`): Wrap up optional property tests for gyvatukas formulas (Properties 24-27), complete remaining property tests around tariff overlaps/time-of-use coverage, and extend API/filament authorization coverage already outlined in `tasks.md`.
- **Operational observability & backup sweeps**: Verify Spatie backup + WAL logs, improve `php artisan pail` guidance in docs/reviews, and review `SubscriptionService` reporting for superadmin dashboards so renewal notices stay accurate.
- **User-group frontends cleanup** (`.kiro/specs/user-group-frontends`): Revisit tenant/admin Blade components, breadcrumb helpers, and documentation for the shared components (cards, data tables, modals) referenced in the spec.

## Later (backlog)

- **Property-based test backlog**: Finish the remaining optional property tests (invoice immutability, tariff snapshots, gyvatukas distributions) before shipping major billing changes.
- **Localization parity**: Expand `lang/{en,lt,ru}` coverage, vet translation keys inside Filament/Blade, and add tooling for catching untranslated copy referenced in docs.
- **API docs & automation**: Formalize meter/invoice API endpoints, add more `docs/api` guidance, and document `TESTING_GUIDE.md` workflows.

## Delivery Notes

- Keep every change tied to the goals/quality guards in this folder (especially `goals.md` and `quality.md`), and update the relevant `.kiro/specs/*` files when behavior shifts.
- Run `php artisan test:setup --fresh` plus `php artisan test` before merging billing or Filament work; call out any skipped property suites.
- Document customer-facing changes in `docs/overview/readme.md` and the broader `docs/frontend/` area, then link the updates inside the spec referenced above.
