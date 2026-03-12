---
name: filament-development
description: "Implements and upgrades Filament v5 resources, pages, forms, tables, actions, and policies in this Laravel app. Activate for admin panel navigation, resources, relation managers, and panel routing work."
license: MIT
metadata:
  author: tenanto
---

# Filament Development

## When to Apply

- Creating or modifying Filament resources/pages/widgets.
- Updating Filament panel navigation, route paths, and access rules.
- Fixing Filament + policy/role visibility mismatches.

## Version Context

- Filament: `^5.3`
- Livewire: `^4`
- Laravel: `^12.54`

## Guardrails

- Keep Filament route paths isolated from legacy web routes.
- Align `shouldRegisterNavigation()` with policy permissions.
- Use explicit role checks where tests depend on role-specific menus.
- Validate changes with focused Filament feature tests.

## Useful Commands

- `php artisan filament:upgrade`
- `php artisan test --compact tests/Feature/Filament`