# Form Components

## Overview

daisyUI provides comprehensive form components with built-in validation states, labels, and helper text.

## Form Control

The `form-control` wrapper provides consistent spacing and layout for form fields.

```blade
<div class="form-control w-full">
    <label class="label">
        <span class="label-text">Email</span>
    </label>
    <input type="email" placeholder="email@example.com" class="input input-bordered w-full" />
    <label class="label">
        <span class="label-text-alt">We'll never share your email</span>
    </label>
</div>
```

## Input Variants

### Text Input

```blade
<input type="text" placeholder="Type here" class="input input-bordered w-full" />
```

### Input Sizes

```blade
<input type="text" placeholder="Large" class="input input-bordered input-lg w-full" />
<input type="text" placeholder="Normal" class="input input-bordered w-full" />
<input type="text" placeholder="Small" class="input input-bordered input-sm w-full" />
<input type="text" placeholder="Tiny" class="input input-bordered input-xs w-full" />
```

### Input Colors

```blade
<input type="text" placeholder="Primary" class="input input-bordered input-primary w-full" />
<input type="text" placeholder="Secondary" class="input input-bordered input-secondary w-full" />
<input type="text" placeholder="Accent" class="input input-bordered input-accent w-full" />
<input type="text" placeholder="Info" class="input input-bordered input-info w-full" />
<input type="text" placeholder="Success" class="input input-bordered input-success w-full" />
<input type="text" placeholder="Warning" class="input input-bordered input-warning w-full" />
<input type="text" placeholder="Error" class="input input-bordered input-error w-full" />
```

### Input States

```blade
<!-- Disabled -->
<input type="text" placeholder="Disabled" class="input input-bordered w-full" disabled />

<!-- Ghost (no border) -->
<input type="text" placeholder="Ghost" class="input input-ghost w-full" />
```

## Select

```blade
<select class="select select-bordered w-full">
    <option disabled selected>Pick one</option>
    <option>Option 1</option>
    <option>Option 2</option>
    <option>Option 3</option>
</select>
```

## Textarea

```blade
<textarea class="textarea textarea-bordered w-full" placeholder="Bio"></textarea>
```

## Checkbox

```blade
<div class="form-control">
    <label class="label cursor-pointer">
        <span class="label-text">Remember me</span>
        <input type="checkbox" class="checkbox" />
    </label>
</div>
```

### Checkbox Colors

```blade
<input type="checkbox" class="checkbox checkbox-primary" checked />
<input type="checkbox" class="checkbox checkbox-secondary" checked />
<input type="checkbox" class="checkbox checkbox-accent" checked />
```

## Radio

```blade
<div class="form-control">
    <label class="label cursor-pointer">
        <span class="label-text">Option 1</span>
        <input type="radio" name="radio-1" class="radio" checked />
    </label>
</div>
<div class="form-control">
    <label class="label cursor-pointer">
        <span class="label-text">Option 2</span>
        <input type="radio" name="radio-1" class="radio" />
    </label>
</div>
```

## Toggle

```blade
<input type="checkbox" class="toggle" checked />
<input type="checkbox" class="toggle toggle-primary" checked />
<input type="checkbox" class="toggle toggle-secondary" checked />
<input type="checkbox" class="toggle toggle-accent" checked />
```

## Range

```blade
<input type="range" min="0" max="100" value="40" class="range" />
```

## File Input

```blade
<input type="file" class="file-input file-input-bordered w-full" />
```

## Blade Components

### Input Component

Create `resources/views/components/ui/input.blade.php`:

```blade
@props([
    'label' => null,
    'name' => null,
    'type' => 'text',
    'placeholder' => '',
    'value' => null,
    'required' => false,
    'disabled' => false,
    'error' => null,
    'hint' => null,
    'size' => 'md',
    'color' => null,
])

@php
$classes = 'input input-bordered w-full';

$sizeClasses = [
    'xs' => 'input-xs',
    'sm' => 'input-sm',
    'md' => '',
    'lg' => 'input-lg',
];

$classes .= ' ' . ($sizeClasses[$size] ?? '');

if ($color) {
    $classes .= ' input-' . $color;
}

if ($error || $errors->has($name)) {
    $classes .= ' input-error';
}

$value = old($name, $value);
@endphp

<div class="form-control w-full">
    @if($label)
    <label class="label">
        <span class="label-text">
            {{ $label }}
            @if($required)
                <span class="text-error">*</span>
            @endif
        </span>
    </label>
    @endif
    
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        placeholder="{{ $placeholder }}"
        value="{{ $value }}"
        {{ $attributes->merge(['class' => $classes]) }}
        @if($required) required @endif
        @if($disabled) disabled @endif
    />
    
    @if($hint || $error || $errors->has($name))
    <label class="label">
        @if($error || $errors->has($name))
            <span class="label-text-alt text-error">
                {{ $error ?? $errors->first($name) }}
            </span>
        @elseif($hint)
            <span class="label-text-alt">{{ $hint }}</span>
        @endif
    </label>
    @endif
</div>
```

### Select Component

Create `resources/views/components/ui/select.blade.php`:

```blade
@props([
    'label' => null,
    'name' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => 'Select an option',
    'required' => false,
    'disabled' => false,
    'error' => null,
    'hint' => null,
])

@php
$classes = 'select select-bordered w-full';

if ($error || $errors->has($name)) {
    $classes .= ' select-error';
}

$selected = old($name, $selected);
@endphp

<div class="form-control w-full">
    @if($label)
    <label class="label">
        <span class="label-text">
            {{ $label }}
            @if($required)
                <span class="text-error">*</span>
            @endif
        </span>
    </label>
    @endif
    
    <select
        name="{{ $name }}"
        {{ $attributes->merge(['class' => $classes]) }}
        @if($required) required @endif
        @if($disabled) disabled @endif
    >
        @if($placeholder)
        <option value="" disabled {{ !$selected ? 'selected' : '' }}>
            {{ $placeholder }}
        </option>
        @endif
        
        @foreach($options as $value => $label)
        <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }}>
            {{ $label }}
        </option>
        @endforeach
    </select>
    
    @if($hint || $error || $errors->has($name))
    <label class="label">
        @if($error || $errors->has($name))
            <span class="label-text-alt text-error">
                {{ $error ?? $errors->first($name) }}
            </span>
        @elseif($hint)
            <span class="label-text-alt">{{ $hint }}</span>
        @endif
    </label>
    @endif
</div>
```

### Textarea Component

Create `resources/views/components/ui/textarea.blade.php`:

```blade
@props([
    'label' => null,
    'name' => null,
    'placeholder' => '',
    'value' => null,
    'rows' => 3,
    'required' => false,
    'disabled' => false,
    'error' => null,
    'hint' => null,
])

@php
$classes = 'textarea textarea-bordered w-full';

if ($error || $errors->has($name)) {
    $classes .= ' textarea-error';
}

$value = old($name, $value);
@endphp

<div class="form-control w-full">
    @if($label)
    <label class="label">
        <span class="label-text">
            {{ $label }}
            @if($required)
                <span class="text-error">*</span>
            @endif
        </span>
    </label>
    @endif
    
    <textarea
        name="{{ $name }}"
        placeholder="{{ $placeholder }}"
        rows="{{ $rows }}"
        {{ $attributes->merge(['class' => $classes]) }}
        @if($required) required @endif
        @if($disabled) disabled @endif
    >{{ $value }}</textarea>
    
    @if($hint || $error || $errors->has($name))
    <label class="label">
        @if($error || $errors->has($name))
            <span class="label-text-alt text-error">
                {{ $error ?? $errors->first($name) }}
            </span>
        @elseif($hint)
            <span class="label-text-alt">{{ $hint }}</span>
        @endif
    </label>
    @endif
</div>
```

## Real-World Examples

### Meter Reading Form

```blade
<form method="POST" action="{{ route('meter-readings.store') }}">
    @csrf
    
    <x-ui.select
        name="meter_id"
        label="Select Meter"
        :options="$meters"
        required
    />
    
    <x-ui.input
        name="reading"
        label="Current Reading"
        type="number"
        step="0.01"
        placeholder="Enter reading"
        hint="Enter the current meter reading"
        required
    />
    
    <x-ui.input
        name="reading_date"
        label="Reading Date"
        type="date"
        :value="now()->format('Y-m-d')"
        required
    />
    
    <x-ui.textarea
        name="notes"
        label="Notes (Optional)"
        placeholder="Add any additional notes"
        rows="3"
    />
    
    <div class="flex gap-2 justify-end mt-6">
        <button type="button" onclick="history.back()" class="btn btn-ghost">
            Cancel
        </button>
        <button type="submit" class="btn btn-primary">
            Submit Reading
        </button>
    </div>
</form>
```

### Property Form

```blade
<form method="POST" action="{{ route('properties.store') }}">
    @csrf
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-ui.input
            name="address"
            label="Address"
            placeholder="123 Main Street"
            required
        />
        
        <x-ui.select
            name="building_id"
            label="Building"
            :options="$buildings"
            required
        />
        
        <x-ui.input
            name="area"
            label="Area (m²)"
            type="number"
            step="0.01"
            required
        />
        
        <x-ui.input
            name="rent"
            label="Monthly Rent (€)"
            type="number"
            step="0.01"
            required
        />
        
        <x-ui.select
            name="status"
            label="Status"
            :options="['occupied' => 'Occupied', 'vacant' => 'Vacant']"
            required
        />
        
        <x-ui.input
            name="rooms"
            label="Number of Rooms"
            type="number"
            min="1"
        />
    </div>
    
    <x-ui.textarea
        name="description"
        label="Description"
        placeholder="Property description"
        rows="4"
        class="mt-4"
    />
    
    <div class="flex gap-2 justify-end mt-6">
        <a href="{{ route('properties.index') }}" class="btn btn-ghost">
            Cancel
        </a>
        <button type="submit" class="btn btn-primary">
            Create Property
        </button>
    </div>
</form>
```

### Search Form

```blade
<form method="GET" action="{{ route('properties.index') }}" class="flex gap-2">
    <x-ui.input
        name="search"
        placeholder="Search properties..."
        :value="request('search')"
        class="flex-1"
    />
    
    <x-ui.select
        name="status"
        :options="['all' => 'All Status', 'occupied' => 'Occupied', 'vacant' => 'Vacant']"
        :selected="request('status', 'all')"
    />
    
    <button type="submit" class="btn btn-primary">
        Search
    </button>
</form>
```

## Form Validation

### Laravel Validation

```blade
<form method="POST" action="{{ route('meter-readings.store') }}" x-data="{ loading: false }" @submit="loading = true">
    @csrf
    
    <x-ui.input
        name="reading"
        label="Meter Reading"
        type="number"
        step="0.01"
        :value="old('reading')"
        :error="$errors->first('reading')"
        required
    />
    
    <button type="submit" class="btn btn-primary" :disabled="loading">
        <span x-show="!loading">Submit</span>
        <span x-show="loading" class="loading loading-spinner"></span>
    </button>
</form>
```

## Accessibility

- Always include labels for inputs
- Use proper input types (email, tel, number, etc.)
- Provide helpful error messages
- Ensure keyboard navigation works
- Use ARIA attributes where needed
- Test with screen readers

## Best Practices

1. **Use form-control**: Wrap inputs in form-control for consistent spacing
2. **Provide labels**: Always include descriptive labels
3. **Show validation**: Display errors clearly
4. **Helpful hints**: Add hint text for complex fields
5. **Required indicators**: Mark required fields with asterisk
6. **Disable on submit**: Prevent double submissions
7. **Preserve values**: Use old() to preserve form values on error
