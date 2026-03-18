---
name: tenanto-laravel-stack
description: Use for Tenanto Laravel, Filament, Livewire, Blade, route, request, or test work when you need the current stack, validation, scoping, naming, localization, and money-handling conventions.
---

# Tenanto Laravel Stack

Mirror entry for the canonical skill at `.agent/skills/tenanto-laravel-stack/SKILL.md`.

- Stack: PHP `8.5.4` runtime, Composer `^8.2`, Laravel `12`, Filament `5.3`, Livewire `4`, Tailwind `4`, Alpine `3`, Sanctum `4`, Pest `4`, PHPUnit `12`
- New and touched PHP files use `declare(strict_types=1);`
- Validation lives in Form Requests, never inline
- Livewire validation must come from Form Requests via `(new XxxRequest())->rules()` or request-backed helpers like `validatePayload(...)`
- Navigation labels and titles use translation keys
- Tenant scoping is explicit with organization/property/self scopes because the historical `HierarchicalScope` is not active in the live repo
- Livewire classes mirror kebab-case Blade view paths
- Monetary calculations use BCMath-backed services only

Read the canonical `SKILL.md` for the full workflow and checklist.
