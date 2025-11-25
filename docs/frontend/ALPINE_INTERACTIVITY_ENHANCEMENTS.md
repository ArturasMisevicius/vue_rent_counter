# Alpine.js Interactivity Enhancements

## Overview

This document describes the Alpine.js interactive enhancements implemented across the Vilnius Utilities Billing application to improve user experience and provide reactive, client-side functionality without requiring a full JavaScript build process.

## Implementation Date

November 25, 2025

## Enhanced Views

### 1. Dashboard Views with Role-Based Content

#### Admin Dashboard (`resources/views/admin/dashboard.blade.php`)
- **Role-based content display**: Uses `@if(auth()->user()->role->value === 'admin')` to show different content for admins vs superadmins
- **Subscription status banners**: Dynamic alerts for expired, expiring, or missing subscriptions
- **Usage progress bars**: Visual representation of subscription limits (properties, tenants)
- **Quick actions**: Role-specific action cards with authorization checks using `@can` directives

#### Manager Dashboard (`resources/views/manager/dashboard.blade.php`)
- **Pending tasks display**: Highlights properties needing meter readings
- **Draft invoices section**: Shows invoices awaiting finalization
- **Recent activity**: Displays latest invoices with status badges
- **Quick shortcuts**: Contextual action cards for common manager tasks

#### Tenant Dashboard (`resources/views/tenant/dashboard.blade.php`)
- **Property information**: Displays tenant's property details
- **Unpaid balance alerts**: Prominent warnings for outstanding invoices
- **Consumption trends**: Shows meter reading changes with delta calculations
- **Latest readings**: Table of most recent meter readings

### 2. Meter Reading Forms with Alpine.js

#### Component: `x-meter-reading-form`
**Location**: `resources/views/components/meter-reading-form.blade.php`

**Features**:
- Dynamic provider/tariff selection (AJAX-powered)
- Real-time validation for reading monotonicity
- Client-side charge preview calculation
- Previous reading display with consumption calculation
- Multi-zone support for electricity meters
- Optimistic UI with loading states

**Alpine.js Data Structure**:
```javascript
{
    meterId: null,
    currentReading: 0,
    previousReading: null,
    error: false,
    errorMessage: '',
    consumption: computed
}
```

**Documentation**:
- Architecture: `docs/architecture/METER_READING_FORM_ARCHITECTURE.md`
- Usage Guide: `docs/frontend/METER_READING_FORM_USAGE.md`
- API Reference: `docs/api/METER_READING_FORM_API.md`

### 3. Tariff Configuration Editor (Admin Only)

#### Enhanced Views
- `resources/views/admin/tariffs/create.blade.php`
- `resources/views/admin/tariffs/edit.blade.php`

**Features**:
- **Dual-mode editor**: Visual editor and JSON editor with tab switching
- **Visual Editor**:
  - Tariff type selector (Flat Rate / Time of Use)
  - Currency input
  - Flat rate configuration (rate, fixed fee)
  - Time of Use zones with add/remove functionality
  - Weekend logic selector
  - Real-time JSON generation
- **JSON Editor**:
  - Syntax-highlighted textarea
  - Real-time validation
  - Error messages for invalid JSON
  - Example configurations

**Alpine.js Data Structure**:
```javascript
{
    activeTab: 'visual',
    config: {
        type: 'flat' | 'time_of_use',
        currency: 'EUR',
        rate: number,
        fixed_fee: number,
        zones: [{
            id: string,
            start: string,
            end: string,
            rate: number
        }],
        weekend_logic: string
    },
    jsonText: string,
    jsonError: string,
    validationError: string
}
```

**Methods**:
- `init()`: Initializes configuration from server data
- `updateJson()`: Converts config object to JSON string
- `parseJson()`: Parses JSON string to config object
- `addZone()`: Adds new time-of-use zone
- `removeZone(index)`: Removes zone at index

**Authorization**:
- Only accessible to users with `admin` or `superadmin` roles
- Protected by `@can('create', App\Models\Tariff::class)` and `@can('update', $tariff)` directives

### 4. Invoice Itemized Breakdown Display

#### Component: `x-invoice-summary`
**Location**: `resources/views/components/invoice-summary.blade.php`

**Features**:
- Itemized breakdown by utility type
- Consumption amount and rate display
- Chronological consumption history
- Property filter dropdown for multi-property tenants
- Status badges for invoice states
- Responsive design (desktop table, mobile cards)

**Documentation**:
- Component Guide: `docs/components/INVOICE_SUMMARY_COMPONENT.md`

### 5. Tenant Consumption History

#### View: `resources/views/tenant/meter-readings/index.blade.php`

**Features**:
- **Interactive Filters**:
  - Meter type filter (all types, electricity, water, heating)
  - Date range filter (from/to)
  - Real-time filtering without page reload
- **Grouped Display**:
  - Readings grouped by meter
  - Sorted chronologically (newest first)
  - Consumption calculation between readings
- **Responsive Design**:
  - Desktop: Full table with all columns
  - Mobile: Card-based layout
- **Empty States**: Helpful messages when no data available

**Alpine.js Data Structure**:
```javascript
{
    readings: array,
    meterTypeLabels: object,
    filters: {
        meterType: string,
        dateFrom: string,
        dateTo: string
    },
    groupedReadings: computed
}
```

**Methods**:
- `init()`: Sets default date range (last 12 months)
- `applyFilters()`: Triggers reactive recalculation
- `formatDate(dateString)`: Formats dates for display
- `formatMeterType(type)`: Converts enum to label
- `calculateConsumption(current, previous)`: Calculates usage delta

### 6. TailwindCSS Styling

All views use TailwindCSS 4.x (loaded via CDN) for consistent styling:

**Design System**:
- **Colors**: Slate (neutral), Indigo (primary), Emerald (success), Rose (error), Amber (warning)
- **Typography**: Font sizes from xs to 2xl, font weights from normal to bold
- **Spacing**: Consistent gap and padding using Tailwind's spacing scale
- **Borders**: Rounded corners (lg, xl, 2xl), border colors with opacity
- **Shadows**: Subtle shadows for depth (sm, md, lg)
- **Transitions**: Smooth hover and focus states

**Responsive Breakpoints**:
- `sm`: 640px
- `md`: 768px
- `lg`: 1024px
- `xl`: 1280px

## Authorization Patterns

### @can Directives

Used throughout dashboards and views to conditionally display content based on user permissions:

```blade
@can('viewAny', App\Models\User::class)
    <!-- Admin user management link -->
@endcan

@can('create', App\Models\MeterReading::class)
    <!-- Meter reading creation button -->
@endcan

@can('update', $tariff)
    <!-- Tariff edit link -->
@endcan
```

### Role-Based Content

```blade
@if(auth()->user()->role->value === 'admin')
    <!-- Admin-specific content -->
@elseif(auth()->user()->role->value === 'manager')
    <!-- Manager-specific content -->
@else
    <!-- Tenant-specific content -->
@endif
```

## Performance Considerations

### Alpine.js Benefits
- **No build step**: Loaded via CDN, no webpack/vite compilation
- **Small footprint**: ~15KB gzipped
- **Reactive**: Automatic DOM updates on data changes
- **Declarative**: Easy to read and maintain

### Optimization Techniques
- **Lazy initialization**: Components initialize only when needed
- **Computed properties**: Cached calculations that update only when dependencies change
- **Event delegation**: Efficient event handling for dynamic content
- **Minimal DOM manipulation**: Alpine handles updates efficiently

## Testing

### Manual Testing Checklist

#### Tariff Editor
- [ ] Visual editor displays correctly
- [ ] Tab switching works (Visual ↔ JSON)
- [ ] Flat rate configuration saves properly
- [ ] Time of Use zones can be added/removed
- [ ] JSON editor validates syntax
- [ ] Configuration persists on form submission
- [ ] Validation errors display correctly

#### Consumption History
- [ ] Filters apply without page reload
- [ ] Meter type filter works
- [ ] Date range filter works
- [ ] Readings group by meter correctly
- [ ] Consumption calculations are accurate
- [ ] Responsive design works on mobile
- [ ] Empty state displays when no data

#### Dashboards
- [ ] Role-based content displays correctly
- [ ] Subscription banners show for admins
- [ ] Quick actions respect authorization
- [ ] Stats display accurate counts
- [ ] Mobile menu works properly

## Browser Compatibility

- **Chrome/Edge**: 90+
- **Firefox**: 88+
- **Safari**: 14+
- **Mobile Safari**: 14+
- **Chrome Android**: 90+

## Future Enhancements

### Potential Improvements
1. **Tariff Editor**:
   - Visual zone timeline representation
   - Drag-and-drop zone reordering
   - Configuration templates library
   - Import/export configurations

2. **Consumption History**:
   - Chart visualization (line/bar charts)
   - Export to CSV/PDF
   - Comparison between periods
   - Anomaly detection alerts

3. **Dashboards**:
   - Customizable widget layout
   - Real-time updates via WebSockets
   - Advanced filtering and search
   - Saved dashboard preferences

## Related Documentation

- [Meter Reading Form Architecture](../architecture/METER_READING_FORM_ARCHITECTURE.md)
- [Invoice Summary Component](../components/INVOICE_SUMMARY_COMPONENT.md)
- [Frontend Overview](./FRONTEND.md)
- [Blade Guardrails](../../.kiro/steering/blade-guardrails.md)

## Requirements Validation

This implementation satisfies the following requirements from the spec:

- **Requirement 10.5**: Alpine.js interactivity without build step ✅
- **Requirement 11.5**: Role-based content with @can directives ✅
- **Requirement 2.2**: Tariff configuration JSON validation ✅
- **Requirement 6.2**: Invoice itemized breakdown display ✅
- **Requirement 6.3**: Consumption history chronological ordering ✅
- **Requirement 10.1**: Dynamic provider/tariff selection ✅
- **Requirement 10.2**: Real-time validation ✅
- **Requirement 10.3**: Client-side charge preview ✅

## Conclusion

The Alpine.js enhancements provide a modern, reactive user experience while maintaining simplicity and avoiding the complexity of a full SPA framework. The implementation follows Laravel and Filament best practices, uses TailwindCSS for consistent styling, and respects authorization boundaries through @can directives.
