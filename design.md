# Tenanto Design Contract

> AI agent usage: read this file with `AGENTS.md`, `README.md`, and `docs/PROJECT-CONTEXT.md` before changing UI. Treat this as the product design contract, then verify the current implementation in code and browser before editing.

## Purpose

Tenanto is a work-focused property, utility billing, and tenant self-service system. The interface must feel clear, operational, and repeatable. Users should be able to scan records, complete forms, and understand billing or meter-reading state without decorative distractions.

## Product Surfaces

- `SUPERADMIN`: platform control plane for organizations, subscriptions, translations, audit, security, and global resources.
- `ADMIN`: organization workspace for property operations, billing, meters, providers, tariffs, KYC, and reports.
- `MANAGER`: organization-scoped workspace with limited write access based on manager permissions.
- `TENANT`: self-service portal for home summary, readings, invoices, property details, profile, and KYC.

## Visual Principles

- Prefer restrained, dense, readable interfaces over marketing-style layouts.
- Use consistent containers, spacing, headers, and controls across pages within the same role.
- Avoid nested cards. Use cards only for repeated items, modals, and genuinely framed tools.
- Avoid decorative orbs, abstract gradients, and visual noise.
- Use icons for common actions and controls when a familiar icon exists.
- Text must never overlap, truncate important meaning, or escape its container on mobile, tablet, or desktop.

## Layout Standards

- Authenticated pages should use a consistent max-width container per role.
- Tenant pages should share one layout standard across dashboard, property details, readings, invoices, and profile.
- Admin, manager, and superadmin pages should follow Filament resource conventions unless a custom page is justified.
- Page sections should be full-width bands or unframed layouts with constrained inner content.
- Tables and repeated records should be optimized for scanning: clear columns, visible status, and predictable actions.
- Avoid grid-heavy custom tenant layouts when responsive flex or stacked sections are clearer.

## Tenant UX Contract

- Tenant navigation is top-oriented and mobile-first.
- Tenant mobile menu must include all primary destinations: Home, Readings, Property, Invoices, Profile, and Logout.
- Tenant menu buttons must be large enough for touch interaction.
- Tenant profile email should not be duplicated in the top menu when name is already visible.
- Tenant language switcher should live on profile, not in the top tenant menu.
- Tenant avatar must appear consistently in desktop and mobile navigation when available.
- Tenant reading entry should minimize clicks and keep related meter, date, value, and validation feedback in one flow.

## Filament UX Contract

- Every user-facing resource label, page title, table heading, form label, infolist label, action label, placeholder, modal title, and empty state must use translation files.
- Do not rely on Filament automatic field labels for production UI.
- Fixed database codes such as statuses, roles, channels, methods, periods, and system-owned slugs must be displayed through a translated label helper or enum label.
- Relationship columns should show human-readable related records, not raw foreign keys, unless the ID is the product value.
- Resource table queries must eager load relationships used by columns, filters, badges, and actions.
- Destructive and bulk actions need clear translated labels, confirmation copy, and authorization.

## Localization Standard

- Supported locales are `en`, `lt`, `ru`, and `es`.
- English is the fallback source, but selected locale must fully drive visible UI.
- Add new app strings to existing domain files before creating new translation files.
- Keep translation key parity across supported locales.
- Use vendor translation overrides under `lang/vendor/*` when package chrome leaks raw translation keys.
- Database values must be translated only when they are system-owned stable values. User-created names, emails, addresses, file names, and free text should remain as entered.

## Components

- Reuse existing Blade, Livewire, and Filament components before creating new ones.
- Shared reusable support code belongs under `app/Filament/Support`.
- Shared mutation logic belongs under `app/Filament/Actions`.
- Request validation belongs under `app/Http/Requests`.
- Do not create new shared classes in `app/Actions` or `app/Support`.

## Responsive Rules

- Verify tenant and custom Livewire pages at mobile, tablet, and desktop widths.
- Touch targets should be comfortable on mobile and not depend on tiny icons.
- Menus must open as visible, usable panels on mobile.
- Fixed-format controls need stable dimensions so hover, loading, labels, and validation messages do not shift layout.
- Do not scale font size with viewport width. Use responsive layout, wrapping, and sensible text sizes instead.

## Accessibility

- Every icon-only action needs a label or tooltip.
- Form fields need explicit labels.
- Validation errors must be clear, translated, and tied to the field.
- Status badges should use color plus text, not color alone.
- Keyboard focus and modal focus behavior should remain native Filament or Livewire behavior unless there is a tested reason to customize it.

## Browser Verification Checklist

Before finishing UI work:

- Open the changed pages in the browser.
- Confirm the selected locale is reflected in every label, title, table heading, action, validation message, and empty state.
- Confirm no raw translation keys like `filament-*::...` are visible or exposed through accessible labels.
- Confirm create, edit, view, and list pages render without HTTP errors.
- Confirm tenant mobile navigation opens and all buttons are tappable.
- Confirm browser console has no new UI-breaking errors.

## Test Expectations

- Add focused Pest tests for translated labels when changing Filament resources.
- Add browser or Livewire coverage when changing custom tenant or shell flows.
- Run targeted tests for changed resources, then run translation parity tests when adding translation keys.
- Run `vendor/bin/pint --dirty` before finishing.
