---
created: 2026-03-19T14:51:06.534Z
title: Finalize shared design system migration
area: ui
files:
  - resources/views/components/shared
  - resources/views/filament/pages
  - tests/Feature/Filament/UnifiedPanelTest.php
  - tests/Feature/Tenant/TenantPortalNavigationTest.php
---

## Problem

The repository already completed most of the design-system unification described in the brief: the old role-specific layouts and loose root components are gone, `resources/views/components/shared` already contains the shared component library, and the old `x-backoffice`, `x-manager-*`, `x-tenant-*`, and `x-ui-*` namespaces are no longer referenced. The remaining gap is that the shared library does not yet include the tenant-specific bottom navigation component, even though the shell and tenant portal feature set now depend on a unified component system.

## Solution

Finish the migration by:

1. Adding `resources/views/components/shared/tenant-bottom-nav.blade.php`.
2. Rendering it from the tenant-facing Filament page views and the shared profile page, with the component itself hiding for non-tenant users.
3. Extending the existing panel and tenant navigation feature tests to cover the shared tenant bottom navigation.
4. Re-running namespace grep checks, view cache clearing, and browser console verification so the shared system is proven clean.
