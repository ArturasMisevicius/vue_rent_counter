# Vilnius Utilities Billing - Design System

## Overview

This design system integrates **daisyUI** components with our Laravel + Filament + Tailwind CSS stack to provide a consistent, accessible, and maintainable UI across the entire application.

## Architecture

- **Base**: Tailwind CSS 4.x (via Vite compilation)
- **Component Library**: daisyUI 4.x
- **JavaScript**: Alpine.js 3.x (CDN)
- **Admin Interface**: Filament 4.x (with custom theming)
- **Template Engine**: Blade (following blade-guardrails.md)

## Directory Structure

```
design/
├── README.md                          # This file
├── INTEGRATION_GUIDE.md               # Step-by-step integration guide
├── COMPONENT_AUDIT.md                 # Current component inventory
├── MIGRATION_PLAN.md                  # Migration strategy
├── components/                        # Component documentation
│   ├── actions/                       # Buttons, dropdowns, modals, swaps
│   ├── data-display/                  # Cards, badges, tables, stats
│   ├── data-input/                    # Forms, inputs, selects, toggles
│   ├── feedback/                      # Alerts, toasts, progress, loading
│   ├── layout/                        # Containers, dividers, grids
│   └── navigation/                    # Navbar, breadcrumbs, tabs, menus
├── themes/                            # Custom theme configurations
│   ├── default.json                   # Default theme
│   ├── dark.json                      # Dark mode theme
│   └── custom.json                    # Custom brand theme
├── examples/                          # Usage examples
│   ├── dashboard.blade.php            # Dashboard example
│   ├── forms.blade.php                # Form examples
│   ├── tables.blade.php               # Table examples
│   └── modals.blade.php               # Modal examples
└── tokens/                            # Design tokens
    ├── colors.md                      # Color palette
    ├── typography.md                  # Typography scale
    ├── spacing.md                     # Spacing system
    └── shadows.md                     # Shadow system
```

## Quick Start

1. **Install daisyUI**:
   ```bash
   npm install -D daisyui@latest
   ```

2. **Configure Tailwind** (see `INTEGRATION_GUIDE.md`)

3. **Browse Components** in the `components/` directory

4. **Use Examples** from the `examples/` directory

## Key Principles

1. **Accessibility First**: All components meet WCAG 2.1 AA standards
2. **Multi-tenancy Aware**: Components respect tenant context
3. **Blade Compliant**: No `@php` blocks, use view composers
4. **Filament Compatible**: Works seamlessly with Filament 4.x
5. **Performance Optimized**: Minimal JavaScript, CSS-first approach

## Component Categories

### Actions (11 components)
- Button, Dropdown, Modal, Swap, Theme Controller, Drawer, Menu, Tooltip, Toast, File Input, Rating

### Data Display (13 components)
- Accordion, Avatar, Badge, Card, Carousel, Chat Bubble, Collapse, Countdown, Diff, Kbd, Stat, Table, Timeline

### Data Input (13 components)
- Checkbox, File Input, Radio, Range, Rating, Select, Text Input, Textarea, Toggle, Form Control, Label, Input Group, Join

### Feedback (6 components)
- Alert, Loading, Progress, Radial Progress, Skeleton, Toast

### Layout (8 components)
- Artboard, Divider, Drawer, Footer, Hero, Indicator, Join, Stack

### Navigation (9 components)
- Breadcrumbs, Bottom Navigation, Link, Menu, Navbar, Pagination, Steps, Tab, Sidebar

## Integration Status

- ✅ daisyUI installed and configured
- ✅ Component documentation created
- ✅ Theme system configured
- ✅ Examples provided
- 🔄 Migration in progress
- ⏳ Testing pending

## Resources

- [daisyUI Documentation](https://daisyui.com/)
- [Tailwind CSS Documentation](https://tailwindcss.com/)
- [Alpine.js Documentation](https://alpinejs.dev/)
- [Filament Documentation](https://filamentphp.com/)

## Support

For questions or issues, refer to:
- `INTEGRATION_GUIDE.md` for setup help
- `COMPONENT_AUDIT.md` for current state
- `MIGRATION_PLAN.md` for migration strategy
