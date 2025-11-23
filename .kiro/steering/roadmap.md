# Roadmap Snapshot

## Now (in-flight)

- **Admin Livewire CRUD** (`.kiro/specs/admin-livewire-crud`): Complete Volt/Flux admin surface with inline edit, modals, bulk actions, and real-time validation across posts/categories/comments/users.
- **Design System Upgrade** (`.kiro/specs/design-system-upgrade`): Finalize design tokens + UI primitives; harmonize admin/public components, introduce glass/animation options, and property-test variants.
- **News Page Enhancements** (`.kiro/specs/news-page`): Dedicated `/news` with robust filters (categories, authors, date), URL-synced state, and eager-loading/index optimizations.

## Next (queued)

- **Accessibility hardening**: Expand keyboard-only coverage for all modals/tables, add automated axe runs to CI, and document screen reader flows.
- **Observability & Ops**: Add request/queue metrics, slow query logging, and per-action audit logs for admin bulk operations.
- **Localization sweep**: Ensure 100% string coverage, add tooling to detect untranslated copy, and backfill Spanish parity for new components.

## Later (backlog)

- **Content workflows**: Draft support, scheduled publishing, and revision history with audit trails.
- **Media pipeline**: Image optimization presets, responsive sources, and alt-text enforcement in forms.
- **API surface**: Read-only REST endpoints for posts/news with signed preview tokens.
- **Collaboration**: Notifications for comment moderation queues and optional Slack/webhook hooks.

## Delivery Notes

- Keep scope aligned with success metrics in `goals.md`.
- Favor incremental rollout behind config flags; capture rollout notes in PR descriptions.
- Each roadmap item should update `.kiro/specs/*` as implementation details evolve.
