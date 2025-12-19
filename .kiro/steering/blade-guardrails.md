# Blade Guardrails

## View templating rules

- Never use `@php` (or raw `<?php ?>`) inside Blade templates; rely on Blade directives, components, and includes for all presentation logic.
- Keep PHP logic out of Blade files—push it into Filament v4 resources (forms, tables, pages, actions), dedicated view composers, or services so views stay declarative.
- When you need data shaping or formatting, prefer Blade components/view composers over inline expressions; Filament columns/fields should expose formatted values instead of embedding helpers in Blade.
- Favor reusable Blade components for shared UI (cards, badges, modals, table rows) so tenant-facing pages and Filament panels stay consistent without inline PHP.
- Use View Composers (registered in AppServiceProvider) to prepare data for layouts and shared views.
- always use MCP servers, use mcp services

## Implementation

All `@php` blocks have been removed from Blade templates. Navigation logic is now handled by `App\View\Composers\NavigationComposer`, which provides:
- User role detection
- Current route tracking
- Active/inactive CSS classes
- Locale switcher configuration
- Language collection for dropdowns