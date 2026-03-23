# 260319-usk Framework Modernization Livewire Filament Tailwind

## Goal

Add an in-repo framework showcase that demonstrates current Livewire 4, Filament 5, and Tailwind CSS 4 patterns without rewriting the existing Tenanto product surface.

## Delivered

- Published Livewire config with namespace support for `pages`, `layouts`, and `framework` single-file components.
- Added a routed full-page Livewire showcase at `/framework/livewire-showcase`.
- Built a Livewire single-file alert component with named-slot support, attribute forwarding, and scoped styles.
- Built a class-based multi-file command palette component with computed filtering, DOM-preserving `wire:show`, and targeted `wire:ref` dispatch support.
- Built a directory-based multi-file preview modal component under `resources/views/components/framework/⚡preview-modal/` and wired it into the showcase through targeted child events.
- Added Tailwind v4 CSS-first theme tokens, a typed custom property, custom utilities, and custom variants in `resources/css/app.css`.
- Added a `FrameworkShowcase` model, factory, and migration for a demo CRUD surface.
- Added a Filament `FrameworkStudio` page, stats/chart widgets, exporter, and `FrameworkShowcase` resource wired into the existing admin panel for superadmins only.
- Extended the Livewire showcase with validated component state, locked route-name props, lazy-island placeholders, `wire:navigate` / `wire:current` links, `wire:transition`, and `wire:replace`.
- Extended the Livewire showcase with a second targeted child flow (`openPreviewModal`) to demonstrate event-to-ref dispatch with a folder-based component.
- Added a second concrete Filament action pattern through a resource-level slide-over `sharePreview` action.
- Added focused Pest coverage for the showcase route, command palette component, framework studio page/resource, and showcase model casts.

## Verification

Passed with CLI opcache disabled:

```bash
php -d opcache.enable_cli=0 artisan test tests/Feature/Livewire/Framework tests/Feature/Filament/FrameworkStudioTest.php tests/Feature/Models/FrameworkShowcaseTest.php --compact
npm run build
```

## Caveats

- This repository is already on Laravel 13 / Filament 5 / Livewire 4 / Tailwind 4, so this task was implemented as an in-place showcase and modernization slice rather than a fresh Laravel 11 scaffold.
- `laravel-boost` MCP tools were not available in this session, so verification used the local app, Serena, Herd, Context7, and focused test runs instead.
- HTTP Artisan and test commands in this workspace should be run with `php -d opcache.enable_cli=0` because stale CLI opcache produced false missing-class and missing-file failures during verification.
