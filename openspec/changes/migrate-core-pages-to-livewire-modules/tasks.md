## 1. Baseline and Safety Tests
- [x] 1.1 Add feature tests for role-based access and successful rendering of `profile`, `dashboard`, and `settings` routes.
- [x] 1.2 Add regression tests that assert each migrated route renders exactly one canonical page shell (no duplicate full-page blocks).
- [ ] 1.3 Add/adjust tests for profile and settings update validation/messages to preserve current behavior.

## 2. Profile Module Migration
- [ ] 2.1 Make `ProfilePage` the canonical renderer for profile pages across roles.
- [ ] 2.2 Move profile update actions to full Livewire flow with explicit authorization and validation parity.
- [ ] 2.3 Remove controller-based profile page rendering paths after route swaps.

## 3. Dashboard Module Migration
- [ ] 3.1 Make `DashboardPage` the canonical renderer for dashboard pages across roles.
- [ ] 3.2 Remove duplicate role wrapper rendering and controller-based dashboard page rendering paths.
- [ ] 3.3 Verify widget blocks, links, and translations still render correctly per role.

## 4. Settings Module Migration
- [ ] 4.1 Make `SettingsPage` the canonical renderer for admin settings page.
- [ ] 4.2 Move settings actions (update/cache/backup) to Livewire actions or documented Livewire-driven handlers while preserving authorization.
- [ ] 4.3 Remove controller-based settings page rendering path after route swap.

## 5. Cleanup and Validation
- [ ] 5.1 Remove dead code and unused imports related to migrated controller render methods.
- [ ] 5.2 Run formatting and focused tests for changed modules.
- [ ] 5.3 Run full test suite and resolve regressions before marking complete.
