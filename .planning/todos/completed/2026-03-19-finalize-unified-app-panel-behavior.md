---
created: 2026-03-19T14:44:38.227Z
title: Finalize unified app panel behavior
area: general
files:
  - app/Providers/Filament/AppPanelProvider.php
  - app/Filament/Support/Auth/LoginRedirector.php
  - tests/Feature/Filament/UnifiedPanelTest.php
  - tests/Feature/Auth/AccessIsolationTest.php
---

## Problem

The live repository already uses a single `AppPanelProvider` at `/app`, but it does not fully match the requested unified-panel behavior. The panel still applies `CheckSubscriptionStatus` in its auth middleware stack even though subscription enforcement is supposed to happen at the resource and action level. The login redirector also still sends tenants to the legacy tenant dashboard route instead of using the unified `/app` landing route backed by the shared dashboard page.

The brief also assumes provider and test files that already exist in the live tree, so this work needs to align the existing implementation with the intended behavior rather than recreate the whole panel from scratch.

## Solution

Complete the unified-panel rollout by:

1. Keeping the existing `/app` Filament panel and role-aware dashboard composition.
2. Removing `CheckSubscriptionStatus` from the panel auth middleware.
3. Updating the login/dashboard redirector so all authenticated roles land on the shared `/app` dashboard entrypoint, with onboarding exceptions preserved.
4. Refreshing the unified panel and auth redirect tests so they assert the current shared-panel behavior.
5. Verifying Filament cache generation and the relevant Filament/auth test coverage after the changes.
