# daisyUI Component Library

## Component Categories

This directory contains comprehensive documentation for all daisyUI components integrated into the Vilnius Utilities Billing platform.

### Actions (11 components)
- [Buttons](../misc/BUTTONS.md) - Various button styles and states
- [Dropdown](actions/DROPDOWN.md) - Dropdown menus
- [Modal](actions/MODAL.md) - Dialog overlays
- [Swap](actions/SWAP.md) - Toggle animations
- [Theme Controller](actions/THEME_CONTROLLER.md) - Theme switching
- [Drawer](actions/DRAWER.md) - Side panels
- [Menu](actions/MENU.md) - Navigation menus
- [Tooltip](actions/TOOLTIP.md) - Contextual help
- [Toast](actions/TOAST.md) - Temporary notifications
- [File Input](actions/FILE_INPUT.md) - File upload
- [Rating](actions/RATING.md) - Star ratings

### Data Display (13 components)
- [Accordion](data-display/ACCORDION.md) - Collapsible content
- [Avatar](data-display/AVATAR.md) - User profile images
- [Badge](data-display/BADGE.md) - Status indicators
- [Card](data-display/CARD.md) - Content containers
- [Carousel](data-display/CAROUSEL.md) - Image galleries
- [Chat Bubble](data-display/CHAT_BUBBLE.md) - Messaging interfaces
- [Collapse](data-display/COLLAPSE.md) - Expandable sections
- [Countdown](data-display/COUNTDOWN.md) - Time counters
- [Diff](data-display/DIFF.md) - Before/after comparisons
- [Kbd](data-display/KBD.md) - Keyboard shortcuts
- [Stat](data-display/STAT.md) - Statistics display
- [Table](data-display/TABLE.md) - Tabular data
- [Timeline](data-display/TIMELINE.md) - Chronological data

### Data Input (13 components)
- [Checkbox](data-input/CHECKBOX.md) - Boolean inputs
- [File Input](data-input/FILE_INPUT.md) - File uploads
- [Radio](data-input/RADIO.md) - Single-choice inputs
- [Range](data-input/RANGE.md) - Slider inputs
- [Rating](data-input/RATING.md) - Star ratings
- [Select](data-input/SELECT.md) - Dropdown selects
- [Text Input](data-input/TEXT_INPUT.md) - Text fields
- [Textarea](data-input/TEXTAREA.md) - Multi-line text
- [Toggle](data-input/TOGGLE.md) - On/off switches
- [Form Control](data-input/FORM_CONTROL.md) - Form wrappers
- [Label](data-input/LABEL.md) - Input labels
- [Input Group](data-input/INPUT_GROUP.md) - Grouped inputs
- [Join](data-input/JOIN.md) - Joined elements

### Feedback (6 components)
- [Alert](feedback/ALERT.md) - Notification messages
- [Loading](feedback/LOADING.md) - Loading indicators
- [Progress](feedback/PROGRESS.md) - Progress bars
- [Radial Progress](feedback/RADIAL_PROGRESS.md) - Circular progress
- [Skeleton](feedback/SKELETON.md) - Loading placeholders
- [Toast](feedback/TOAST.md) - Temporary notifications

### Layout (8 components)
- [Artboard](layout/ARTBOARD.md) - Device mockups
- [Divider](layout/DIVIDER.md) - Content separators
- [Drawer](layout/DRAWER.md) - Side panels
- [Footer](layout/FOOTER.md) - Page footers
- [Hero](layout/HERO.md) - Landing sections
- [Indicator](layout/INDICATOR.md) - Notification badges
- [Join](layout/JOIN.md) - Grouped elements
- [Stack](layout/STACK.md) - Vertical/horizontal layouts

### Navigation (9 components)
- [Breadcrumbs](navigation/BREADCRUMBS.md) - Hierarchical navigation
- [Bottom Navigation](navigation/BOTTOM_NAV.md) - Mobile navigation
- [Link](navigation/LINK.md) - Styled links
- [Menu](navigation/MENU.md) - Navigation menus
- [Navbar](navigation/NAVBAR.md) - Top navigation
- [Pagination](navigation/PAGINATION.md) - Page navigation
- [Steps](navigation/STEPS.md) - Multi-step processes
- [Tab](navigation/TAB.md) - Tabbed interfaces
- [Sidebar](navigation/SIDEBAR.md) - Side navigation

## Usage Guidelines

### Component Naming Convention

All custom Blade components use the `x-ui.*` namespace:

```blade
<x-ui.button variant="primary">Click Me</x-ui.button>
<x-ui.card title="Card Title">Content</x-ui.card>
<x-ui.alert type="success">Success message</x-ui.alert>
```

### Common Props

Most components support these common props:

- `class` - Additional CSS classes
- `id` - Element ID
- `data-*` - Data attributes for Alpine.js

### Accessibility

All components include:
- Proper ARIA attributes
- Keyboard navigation support
- Screen reader compatibility
- Focus management
- Color contrast compliance

### Performance

Components are optimized for:
- Minimal JavaScript
- CSS-first approach
- Lazy loading support
- Mobile responsiveness

## Quick Reference

### Most Used Components

1. **Button** - Primary actions
2. **Card** - Content containers
3. **Input** - Form fields
4. **Alert** - Notifications
5. **Table** - Data display
6. **Modal** - Dialogs
7. **Badge** - Status indicators
8. **Stat** - Statistics

### Component Combinations

Common component patterns:

```blade
<!-- Card with Stats -->
<x-ui.card>
    <div class="stats stats-vertical lg:stats-horizontal">
        <x-ui.stat title="Total" value="1,234" />
        <x-ui.stat title="Active" value="567" />
    </div>
</x-ui.card>

<!-- Form with Validation -->
<form>
    <x-ui.form-control>
        <x-ui.label>Email</x-ui.label>
        <x-ui.input type="email" name="email" />
        <x-ui.error field="email" />
    </x-ui.form-control>
    
    <x-ui.button type="submit" variant="primary">
        Submit
    </x-ui.button>
</form>

<!-- Alert with Action -->
<x-ui.alert type="warning">
    <span>Your subscription expires soon</span>
    <x-ui.button size="sm" variant="ghost">
        Renew Now
    </x-ui.button>
</x-ui.alert>
```

## Migration Guide

See [MIGRATION_PLAN.md](../misc/MIGRATION_PLAN.md) for detailed migration strategy.

## Examples

See the `examples/` directory for complete page examples:
- Dashboard layouts
- Form pages
- Table views
- Modal interactions
- Navigation patterns

## Support

For questions or issues:
1. Check component documentation
2. Review examples
3. Consult migration guide
4. Ask in team Slack channel
