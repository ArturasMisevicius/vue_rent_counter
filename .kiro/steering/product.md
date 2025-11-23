# Product Overview

## Laravel Blog News

Modern, accessible Laravel blog application with a powerful admin interface. Enhanced fork of Laravel BlogKit with upgraded admin UX, accessibility, and developer experience.

## Core Purpose

Provide a complete, production-ready blogging solution with:
- Public-facing blog (posts, categories, tags, comments, markdown, Torchlight)
- Comprehensive admin panel with Volt/Flux UI
- Multi-language support (English, Spanish)
- Accessibility-first experience (WCAG 2.1)
- SEO-friendly metadata and semantic HTML

## Value Proposition

- **Time-to-publish:** Ship a polished blog/newsroom quickly with sensible defaults.
- **Operational visibility:** Admin dashboards with sorting, search, bulk actions, and inline edit to keep content current.
- **Accessibility & localization:** Inclusive by default; easy to extend to new locales.
- **Secure by design:** Policies, FormRequests, CSP headers, and audit-friendly structure.

## Key Features

### Public Features
- Blog posts with Markdown + syntax highlighting and OG/schema metadata
- Category and tag organization with localized breadcrumbs
- Verified-user comments with moderation workflow
- Responsive design with dark mode and semantic HTML
- Localization-ready UI copy and routes

### Admin Features
- Livewire Volt + Flux UI admin panel with optimistic UI
- Real-time search, sorting, bulk actions, and inline editing
- Keyboard shortcuts, loading indicators, and guarded bulk limits
- CRUD for posts, categories, comments, users, and news feed
- Modal-heavy workflows to avoid context switching

### Developer Features
- Property-based testing for critical flows
- TypeScript-first frontend with Vite 7 pipeline
- Extensive docs in `docs/` plus spec/task files in `.kiro/`
- Code quality tools: Pint, PHPStan (max), Rector, Playwright

## Success Metrics

- P0: Page-to-first-byte < 200ms on cached pages; Core CRUD actions complete < 400ms median.
- P1: Admin task completion without full-page reloads for 95% of flows.
- P1: Accessibility score ≥ 95 in Lighthouse and keyboard-only usability for all primary flows.
- P1: 0 critical security findings in quarterly review (policies, CSP, audit logs).
- P2: < 1% error rate across admin Livewire interactions (handled exceptions with UX feedback).

## Target Users

- Content creators and bloggers shipping quickly
- Editorial teams running small/mid-size newsrooms
- Engineering teams building content platforms
- Developers learning modern Laravel (Volt/Flux patterns)

## Non-Goals

- Multi-tenant SaaS controls (billing, tenant isolation)
- Heavy WYSIWYG page builder functionality
- Real-time collaborative editing
- Headless CMS delivery APIs beyond existing controllers
