# Meter Reading Form Component - Usage Guide

**Date**: 2025-11-25  
**Component**: `x-meter-reading-form`  
**Status**: ✅ **PRODUCTION READY**

---

## Quick Start

### Basic Usage

```blade
{{-- In your controller --}}
public function create(): View
{
    $meters = Meter::with('property')->orderBy('serial_number')->get();
    $providers = Provider::all();
    
    return view('manager.meter-readings.create', compact('meters', 'providers'));
}

{{-- In your view --}}
@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ __('meter_readings.headings.create') }}</h1>
    
    <x-meter-reading-form 
        :meters="$meters" 
        :providers="$providers"
    />
</div>
@endsection
```

That's it! The component handles all the complexity internally.

---

## Component Props

### Required Props

| Prop | Type | Description |
|------|------|-------------|
| `meters` | `Collection<Meter>` | Collection of meters available for selection |
| `providers` | `Collection<Provider>` | Collection of providers for tariff selection |

### Prop Details

**meters** - Must include:
- `id` - Meter ID
- `serial_number` - Meter serial number
- `type` - MeterType enum (electricity, water, heating, gas)
- `supports_zones` - Boolean indicating multi-zone support
- `property` - Related property with `address` field

**providers** - Must include:
- `id` - Provider ID
- `name` - Provider name

---

## Features

### 1. Dynamic Meter Selection

The component automatically:
- Displays all meters with serial number, type, and property address
- Detects multi-zone support (electricity day/night)
- Loads previous reading when meter is selected
- Resets form state when meter changes

**User Experience**:
```
Select Meter: [LT-2024-001 - Electricity (Vilnius, Gedimino 10)]
              [LT-2024-002 - Water (Vilnius, Gedimino 10)]
              [LT-2024-003 - Heating (Vilnius, Gedimino 10)]
```

### 2. Provider/Tariff Cascading Dropdowns

**Flow**:
1. User selects meter
2. Provider dropdown becomes available
3. User selects provider
4. Tariff dropdown loads via AJAX
5. User selects tariff
6. Charge preview calculates automatically

**API Calls**:
- `GET /api/providers/{id}/tariffs` - Loads tariffs for selected provider

### 3. Previous Reading Display

Shows the last recorded reading for the selected meter:

**Single-zone meter**:
```
┌─────────────────────────────┐
│ Previous Reading            │
├─────────────────────────────┤
│ Date: 2024-01-15            │
│ Value: 1234.56              │
└─────────────────────────────┘
```

**Multi-zone meter**:
```
┌─────────────────────────────┐
│ Previous Reading            │
├─────────────────────────────┤
│ Date: 2024-01-15            │
│ Day Value: 800.00           │
│ Night Value: 400.00         │
└─────────────────────────────┘
```

**API Call**:
- `GET /api/meters/{id}/last-reading` - Fetches previous reading

### 4. Multi-Zone Support

For electricity meters with `supports_zones = true`:

**Single Input** (standard meters):
```
Reading Value: [1234.56]
```

**Dual Inputs** (multi-zone meters):
```
Day Zone Reading:   [800.00]
Night Zone Reading: [400.00]
```

The component automatically:
- Shows/hides zone inputs based on meter capabilities
- Validates each zone independently
- Calculates combined consumption

### 5. Real-Time Validation

**Monotonicity Check**:
```javascript
// Reading cannot be lower than previous
if (current < previous) {
    error = "Reading cannot be lower than previous reading (1200.00)"
}
```

**Future Date Check**:
```javascript
// Reading date cannot be in the future
if (reading_date > today) {
    error = "Reading date cannot be in the future"
}
```

**Negative Value Check**:
```javascript
// All readings must be positive
if (value < 0) {
    error = "Reading must be a positive number"
}
```

### 6. Consumption Calculation

**Single-zone**:
```
Consumption = Current Reading - Previous Reading
            = 1234.56 - 1200.00
            = 34.56 units
```

**Multi-zone**:
```
Day Consumption   = 800.00 - 750.00 = 50.00
Night Consumption = 400.00 - 380.00 = 20.00
Total Consumption = 50.00 + 20.00   = 70.00 units
```

### 7. Charge Preview

**Flat-rate tariff**:
```
Charge = Consumption × Rate
       = 34.56 × €0.1234
       = €4.26
```

**Time-of-use tariff** (uses average rate for preview):
```
Average Rate = (Day Rate + Night Rate) / 2
             = (€0.1500 + €0.0800) / 2
             = €0.1150

Charge = Consumption × Average Rate
       = 70.00 × €0.1150
       = €8.05
```

---

## Customization

### Styling

The component uses Tailwind CSS classes. To customize:

```blade
{{-- Override component styles --}}
<div class="my-custom-container">
    <x-meter-reading-form 
        :meters="$meters" 
        :providers="$providers"
        class="custom-form-class"
    />
</div>

<style>
.my-custom-container .bg-blue-50 {
    background-color: #your-color;
}
</style>
```

### Translations

All text is translatable via `lang/en/meter_readings.php`:

```php
'form_component' => [
    'title' => 'Enter Meter Reading',
    'select_meter' => 'Select Meter',
    'previous' => 'Previous Reading',
    'consumption' => 'Consumption',
    'estimated_charge' => 'Estimated Charge',
    // ... more keys
],
```

**Localization**:
```bash
# Copy to other languages
cp lang/en/meter_readings.php lang/lt/meter_readings.php
cp lang/en/meter_readings.php lang/ru/meter_readings.php

# Translate values
# EN: 'title' => 'Enter Meter Reading'
# LT: 'title' => 'Įveskite skaitiklio rodmenį'
# RU: 'title' => 'Введите показания счетчика'
```

### Validation Messages

Customize validation messages in the component:

```javascript
validateReading() {
    this.errors = {};
    
    // Custom monotonicity message
    if (this.formData.value < this.previousReading.value) {
        this.errors.value = 'Your custom error message here';
    }
    
    // Custom future date message
    if (this.formData.reading_date > this.maxDate) {
        this.errors.reading_date = 'Your custom error message here';
    }
}
```

---

## Advanced Usage

### Pre-selecting a Meter

```blade
{{-- Pass meter_id via query parameter --}}
<x-meter-reading-form 
    :meters="$meters" 
    :providers="$providers"
/>

{{-- In your route --}}
Route::get('/meter-readings/create', function () {
    $meterId = request('meter_id');
    // Component will auto-select if meter_id is in query string
});
```

### Custom Success Redirect

```javascript
// In the component's submitReading() method
if (response.ok) {
    // Custom redirect with message
    window.location.href = '/custom/path?success=Custom message';
}
```

### Handling Errors

```javascript
// In the component's submitReading() method
if (!response.ok) {
    const data = await response.json();
    
    // Display errors in the form
    this.errors = data.errors || {};
    
    // Or show a toast notification
    showToast('Error submitting reading', 'error');
}
```

---

## Integration Examples

### With Filament Admin Panel

```php
// In your Filament Resource
public static function getPages(): array
{
    return [
        'index' => Pages\ListMeterReadings::route('/'),
        'create' => Pages\CreateMeterReading::route('/create'),
        // Use custom page with component
        'create-form' => Pages\CreateMeterReadingForm::route('/create-form'),
    ];
}

// In CreateMeterReadingForm.php
class CreateMeterReadingForm extends Page
{
    protected static string $view = 'filament.pages.create-meter-reading-form';
    
    public function mount(): void
    {
        $this->meters = Meter::with('property')->get();
        $this->providers = Provider::all();
    }
}
```

### With Livewire

```php
// In your Livewire component
class MeterReadingForm extends Component
{
    public $meters;
    public $providers;
    
    public function mount()
    {
        $this->meters = Meter::with('property')->get();
        $this->providers = Provider::all();
    }
    
    public function render()
    {
        return view('livewire.meter-reading-form');
    }
}

// In your view
<div>
    <x-meter-reading-form 
        :meters="$meters" 
        :providers="$providers"
    />
</div>
```

### With Inertia.js

```php
// In your Inertia controller
public function create()
{
    return Inertia::render('MeterReadings/Create', [
        'meters' => Meter::with('property')->get(),
        'providers' => Provider::all(),
    ]);
}

// In your Vue/React component
// Note: You'll need to adapt the Alpine.js logic to Vue/React
```

---

## Accessibility

The component follows WCAG 2.1 AA standards:

### Keyboard Navigation
- Tab through all form fields
- Enter to submit form
- Escape to reset form (optional)

### Screen Readers
- All inputs have descriptive labels
- Error messages are announced
- Loading states are announced
- Success/failure feedback is announced

### Focus Management
- Focus moves to first error on validation failure
- Focus returns to trigger element after modal close
- Focus is trapped within modal dialogs

### Color Contrast
- All text meets WCAG AA contrast ratios
- Error states use both color and icons
- Success states use both color and icons

---

## Performance

### Optimization Tips

1. **Lazy Load Providers**:
```php
// Only load providers when needed
$providers = Cache::remember('providers', 3600, function () {
    return Provider::all();
});
```

2. **Eager Load Relationships**:
```php
// Prevent N+1 queries
$meters = Meter::with('property')->get();
```

3. **Debounce API Calls**:
```javascript
// Add debouncing to validation
validateReading: debounce(function() {
    // Validation logic
}, 300)
```

4. **Cache Previous Readings**:
```javascript
// Cache previous reading after first load
if (this.previousReadingCache[meterId]) {
    this.previousReading = this.previousReadingCache[meterId];
} else {
    await this.loadPreviousReading();
    this.previousReadingCache[meterId] = this.previousReading;
}
```

---

## Testing

### Component Testing

```php
// Test component renders
test('meter reading form component renders correctly', function () {
    $manager = User::factory()->manager()->create();
    $meters = Meter::factory()->count(3)->create();
    $providers = Provider::factory()->count(2)->create();

    $response = $this->actingAs($manager)
        ->get(route('manager.meter-readings.create'));

    $response->assertStatus(200);
    $response->assertSee(__('meter_readings.form_component.title'));
    $response->assertSee($meters->first()->serial_number);
});
```

### API Testing

```php
// Test API endpoint
test('can submit meter reading via API', function () {
    $manager = User::factory()->manager()->create();
    $meter = Meter::factory()->create(['tenant_id' => $manager->tenant_id]);

    $response = $this->actingAs($manager)
        ->postJson('/api/meter-readings', [
            'meter_id' => $meter->id,
            'reading_date' => now()->format('Y-m-d'),
            'value' => 1234.56,
        ]);

    $response->assertStatus(201);
    $response->assertJsonStructure(['id', 'meter_id', 'value']);
});
```

### Browser Testing (Dusk)

```php
// Test full user flow
test('user can submit meter reading', function () {
    $this->browse(function (Browser $browser) {
        $browser->loginAs(User::factory()->manager()->create())
                ->visit('/manager/meter-readings/create')
                ->select('meter_id', 1)
                ->waitForText('Previous Reading')
                ->select('provider_id', 1)
                ->waitForText('Select Tariff')
                ->select('tariff_id', 1)
                ->type('value', '1234.56')
                ->press('Submit Reading')
                ->assertPathIs('/manager/meter-readings')
                ->assertSee('Reading submitted successfully');
    });
});
```

---

## Troubleshooting

### Component Not Rendering

**Issue**: Component shows blank or errors
**Solution**: 
1. Verify Alpine.js is loaded in layout
2. Check `@push('scripts')` is in layout
3. Ensure CSRF token meta tag exists

### API Calls Failing

**Issue**: 404 or 401 errors on API calls
**Solution**:
1. Verify routes are registered in `routes/api.php`
2. Check authentication middleware is applied
3. Verify CSRF token is included in headers

### Previous Reading Not Loading

**Issue**: Previous reading shows "N/A"
**Solution**:
1. Verify meter has previous readings in database
2. Check API endpoint returns 200 status
3. Verify tenant scoping is correct

### Validation Not Working

**Issue**: Form submits invalid data
**Solution**:
1. Check FormRequest validation rules
2. Verify client-side validation logic
3. Test API endpoint directly with Postman

---

## Best Practices

### 1. Always Eager Load Relationships
```php
// Good
$meters = Meter::with('property')->get();

// Bad (N+1 queries)
$meters = Meter::all();
```

### 2. Cache Static Data
```php
// Cache providers for 1 hour
$providers = Cache::remember('providers', 3600, fn() => Provider::all());
```

### 3. Use Translation Keys
```blade
{{-- Good --}}
{{ __('meter_readings.form_component.title') }}

{{-- Bad --}}
Enter Meter Reading
```

### 4. Handle Errors Gracefully
```javascript
// Good
try {
    await this.submitReading();
} catch (error) {
    console.error('Error:', error);
    this.errors.general = 'An unexpected error occurred';
}

// Bad
await this.submitReading(); // Unhandled errors
```

### 5. Validate on Both Client and Server
```javascript
// Client-side (immediate feedback)
validateReading() {
    if (this.formData.value < this.previousReading.value) {
        this.errors.value = 'Reading too low';
    }
}

// Server-side (security)
// StoreMeterReadingRequest validates again
```

---

## Related Documentation

- **API Reference**: `docs/api/METER_READING_FORM_API.md`
- **Implementation Guide**: `docs/refactoring/METER_READING_FORM_COMPLETE.md`
- **Refactoring Summary**: `docs/refactoring/METER_READING_FORM_REFACTORING_SUMMARY.md`
- **Component Source**: `resources/views/components/meter-reading-form.blade.php`
- **Controller**: `app/Http/Controllers/Manager/MeterReadingController.php`
- **Tests**: `tests/Feature/MeterReadingFormComponentTest.php`

---

**Status**: ✅ PRODUCTION READY  
**Last Updated**: 2025-11-25  
**Maintainer**: Development Team
