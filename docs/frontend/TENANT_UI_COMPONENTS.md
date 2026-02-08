# Tenant UI Components

## Overview

The tenant interface uses a consistent, fixed-width layout with reusable Blade components for a cohesive user experience across all tenant pages.

## Layout Structure

### Tenant Layout (`layouts/tenant.blade.php`)

All tenant pages extend the `layouts.tenant` layout, which provides:
- Fixed width container (`max-w-5xl`)
- Centered content
- Consistent spacing
- Extends the main `layouts.app` layout

**Usage:**
```blade
@extends('layouts.tenant')

@section('tenant-content')
    <!-- Your tenant page content -->
@endsection
```

## Reusable Components

### 1. Quick Actions (`components/tenant/quick-actions.blade.php`)

Displays prominent action cards for common tenant tasks.

**Features:**
- Grid layout (1 column mobile, 2 on tablet, 3 on desktop)
- Hover effects with border color change
- Icon + text layout
- Links to: Invoices, Meters, Property

**Usage:**
```blade
<x-tenant.quick-actions />
```

### 2. Stat Card (`components/tenant/stat-card.blade.php`)

Displays key metrics in a card format.

**Props:**
- `label` (required): The metric label
- `value` (required): The metric value
- `value-color` (optional): Tailwind color class for the value (default: `text-gray-900`)

**Usage:**
```blade
<x-tenant.stat-card label="Total Invoices" :value="$count" />
<x-tenant.stat-card label="Unpaid" :value="$unpaid" value-color="text-orange-600" />
```

### 3. Alert (`components/tenant/alert.blade.php`)

Displays contextual alerts with icons and optional actions.

**Props:**
- `type` (optional): `warning`, `error`, `success`, `info` (default: `info`)
- `title` (optional): Alert title
- `action` (slot, optional): Action button/link

**Usage:**
```blade
<x-tenant.alert type="warning" title="No Property Assigned">
    You do not have a property assigned yet.
</x-tenant.alert>

<x-tenant.alert type="error">
    <p>Error message here</p>
    <x-slot name="action">
        <a href="#" class="btn">Fix Now</a>
    </x-slot>
</x-tenant.alert>
```

### 4. Section Card (`components/tenant/section-card.blade.php`)

White card container for content sections.

**Props:**
- `title` (optional): Section heading
- `class` (optional): Additional CSS classes

**Usage:**
```blade
<x-tenant.section-card title="My Property">
    <!-- Content here -->
</x-tenant.section-card>

<x-tenant.section-card class="mt-8">
    <!-- Content without title -->
</x-tenant.section-card>
```

## Dashboard Layout

The tenant dashboard follows this structure:

1. **Page Header** - Title and description
2. **Quick Actions** (top of page) - Prominent action cards
3. **Property Information** - Details about assigned property
4. **Unpaid Balance Alert** (conditional) - Shows if balance > 0
5. **Stats Grid** - 3-column metrics (Total/Unpaid Invoices, Active Meters)
6. **Recent Meter Readings** (conditional) - Table of latest readings

## Design Principles

### Fixed Width
All tenant pages use `max-w-5xl` for consistent reading width and visual hierarchy.

### Component Reusability
Components are designed to be reused across tenant pages:
- Alerts for warnings/errors
- Stat cards for metrics
- Section cards for content blocks

### Responsive Design
- Mobile-first approach
- Grid layouts adapt: 1 column → 2 columns → 3 columns
- Tables scroll horizontally on mobile

### Visual Consistency
- Consistent spacing (mb-6, mt-8, gap-4/5)
- Unified color scheme (indigo-600 for primary actions)
- Shadow and border styles match across components

## Updated Pages

All tenant pages now use the new layout and components:

- `tenant/dashboard.blade.php` - Main dashboard with Quick Actions at top
- `tenant/invoices/index.blade.php` - Invoice listing
- `tenant/invoices/show.blade.php` - Invoice details
- `tenant/meters/index.blade.php` - Meter listing
- `tenant/property/show.blade.php` - Property details
- `tenant/meter-readings/index.blade.php` - Consumption history
- `tenant/profile/show.blade.php` - Profile management

## Future Enhancements

Consider adding:
- `<x-tenant.data-table>` - Reusable table component
- `<x-tenant.empty-state>` - Consistent empty state messaging
- `<x-tenant.action-button>` - Standardized button styles
- `<x-tenant.badge>` - Status badges (paid, unpaid, draft, etc.)
