# Blade Guardrails

## View templating rules

- Never use `@php` (or raw `<?php ?>`) inside Blade templates; rely on Blade directives, components, and includes for all presentation logic.
- Keep PHP logic out of Blade files—push it into Filament v4 resources (forms, tables, pages, actions), dedicated view components, or services so views stay declarative.
- When you need data shaping or formatting, prefer Blade components/view models over inline expressions; Filament columns/fields should expose formatted values instead of embedding helpers in Blade.
- Favor reusable Blade components for shared UI (cards, badges, modals, table rows) so tenant-facing pages and Filament panels stay consistent without inline PHP.
