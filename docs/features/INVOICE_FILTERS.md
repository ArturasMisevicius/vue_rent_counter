# Invoice Filters Enhancement

## Overview

Enhanced the manager invoice views (`/manager/invoices`, `/manager/invoices/drafts`, `/manager/invoices/finalized`) with advanced Filament-powered filtering capabilities using Livewire 3 components.

## Implementation

### Livewire Component

**File**: `app/Livewire/Manager/InvoiceFilters.php`

A Livewire component that integrates Filament Form components for advanced filtering:

- **Status Filter**: Select from all invoice statuses (draft, finalized, paid, overdue, cancelled)
- **Property Filter**: Searchable dropdown of all properties
- **Billing Period Range**: Date pickers for filtering by billing period start/end dates
- **Amount Range**: Min/max amount filters with currency prefix
- **Sorting**: Sort by created date, billing period, amount, or due date
- **Sort Direction**: Ascending or descending order

### Features

1. **Live Updates**: Uses `live(onBlur: true)` for optimal performance - updates only when user leaves the field
2. **Searchable Dropdowns**: Property filter includes search functionality
3. **Responsive Design**: Works on desktop and mobile with Filament's responsive components
4. **Pagination**: Maintains filter state across pages
5. **Reset Functionality**: One-click reset to clear all filters
6. **Multi-language Support**: Full translation support for EN, LT, RU

### Views Updated

- `resources/views/manager/invoices/index.blade.php` - All invoices view
- `resources/views/manager/invoices/drafts.blade.php` - Draft invoices view
- `resources/views/manager/invoices/finalized.blade.php` - Finalized invoices view
- `resources/views/livewire/manager/invoice-filters.blade.php` - Filter component template

### Translation Keys

Added to `lang/{en,lt,ru}/invoices.php`:

```php
'filters' => [
    'title' => 'Advanced Filters',
    'reset' => 'Reset Filters',
    'status' => 'Status',
    'all_statuses' => 'All Statuses',
    'property' => 'Property',
    'all_properties' => 'All Properties',
    'billing_period_from' => 'Billing Period From',
    'billing_period_to' => 'Billing Period To',
    'min_amount' => 'Minimum Amount',
    'max_amount' => 'Maximum Amount',
    'sort_by' => 'Sort By',
    'sort_direction' => 'Sort Direction',
    'sort' => [
        'created_at' => 'Created Date',
        'billing_period' => 'Billing Period',
        'amount' => 'Amount',
        'due_date' => 'Due Date',
        'desc' => 'Descending',
        'asc' => 'Ascending',
    ],
],
```

## Usage

The component is used via Livewire directive with a view parameter:

```blade
@livewire('manager.invoice-filters', ['view' => 'all'])
@livewire('manager.invoice-filters', ['view' => 'drafts'])
@livewire('manager.invoice-filters', ['view' => 'finalized'])
```

## Performance Considerations

- Uses `->live(onBlur: true)` instead of `->reactive()` to minimize server round-trips
- Eager-loads relationships (`tenant.property`, `items`) to prevent N+1 queries
- Validates sort columns against an allowlist for security
- Paginates results (20 per page) to maintain performance with large datasets

## Blade Guardrails Compliance

- No `@php` blocks in Blade templates
- All logic contained in Livewire component
- Filament form components handle all interactivity
- Declarative template structure

## Future Enhancements

- Export filtered results to CSV/Excel
- Save filter presets for quick access
- Bulk actions on filtered invoices
- Advanced date range presets (This Month, Last Quarter, etc.)
