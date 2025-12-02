# Button Components

## Overview

daisyUI provides a comprehensive button system with multiple variants, sizes, and states.

## Basic Button

```blade
<button class="btn">Button</button>
```

## Button Variants

### Brand Colors

```blade
<button class="btn">Default</button>
<button class="btn btn-neutral">Neutral</button>
<button class="btn btn-primary">Primary</button>
<button class="btn btn-secondary">Secondary</button>
<button class="btn btn-accent">Accent</button>
<button class="btn btn-ghost">Ghost</button>
<button class="btn btn-link">Link</button>
```

### Semantic Colors

```blade
<button class="btn btn-info">Info</button>
<button class="btn btn-success">Success</button>
<button class="btn btn-warning">Warning</button>
<button class="btn btn-error">Error</button>
```

### Outline Buttons

```blade
<button class="btn btn-outline">Default</button>
<button class="btn btn-outline btn-primary">Primary</button>
<button class="btn btn-outline btn-secondary">Secondary</button>
<button class="btn btn-outline btn-accent">Accent</button>
```

## Button Sizes

```blade
<button class="btn btn-lg">Large</button>
<button class="btn">Normal</button>
<button class="btn btn-sm">Small</button>
<button class="btn btn-xs">Tiny</button>
```

## Button States

### Active State

```blade
<button class="btn btn-active">Active</button>
<button class="btn btn-active btn-primary">Active Primary</button>
```

### Disabled State

```blade
<button class="btn" disabled>Disabled</button>
<button class="btn btn-primary" disabled>Disabled Primary</button>
```

### Loading State

```blade
<button class="btn btn-primary">
    <span class="loading loading-spinner"></span>
    Loading
</button>
```

## Button Shapes

### Rounded

```blade
<button class="btn btn-circle">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
    </svg>
</button>
```

### Square

```blade
<button class="btn btn-square">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
    </svg>
</button>
```

## Button Groups

```blade
<div class="join">
    <button class="btn join-item">Button 1</button>
    <button class="btn join-item">Button 2</button>
    <button class="btn join-item">Button 3</button>
</div>
```

## Blade Component Example

Create `resources/views/components/ui/button.blade.php`:

```blade
@props([
    'variant' => 'default',
    'size' => 'md',
    'outline' => false,
    'loading' => false,
    'disabled' => false,
    'type' => 'button',
])

@php
$classes = 'btn';

// Variant classes
$variantClasses = [
    'default' => '',
    'primary' => 'btn-primary',
    'secondary' => 'btn-secondary',
    'accent' => 'btn-accent',
    'ghost' => 'btn-ghost',
    'link' => 'btn-link',
    'info' => 'btn-info',
    'success' => 'btn-success',
    'warning' => 'btn-warning',
    'error' => 'btn-error',
];

// Size classes
$sizeClasses = [
    'xs' => 'btn-xs',
    'sm' => 'btn-sm',
    'md' => '',
    'lg' => 'btn-lg',
];

$classes .= ' ' . ($variantClasses[$variant] ?? '');
$classes .= ' ' . ($sizeClasses[$size] ?? '');

if ($outline) {
    $classes .= ' btn-outline';
}
@endphp

<button 
    type="{{ $type }}"
    {{ $attributes->merge(['class' => $classes]) }}
    @if($disabled) disabled @endif
>
    @if($loading)
        <span class="loading loading-spinner"></span>
    @endif
    {{ $slot }}
</button>
```

### Usage

```blade
<x-ui.button variant="primary">
    Click Me
</x-ui.button>

<x-ui.button variant="secondary" size="lg" outline>
    Large Outline
</x-ui.button>

<x-ui.button variant="success" loading>
    Saving...
</x-ui.button>

<x-ui.button variant="error" disabled>
    Disabled
</x-ui.button>
```

## Real-World Examples

### Form Submit Button

```blade
<form method="POST" action="{{ route('meter-readings.store') }}">
    @csrf
    <!-- Form fields -->
    
    <div class="flex gap-2 justify-end">
        <x-ui.button variant="ghost" type="button" onclick="history.back()">
            Cancel
        </x-ui.button>
        <x-ui.button variant="primary" type="submit">
            Submit Reading
        </x-ui.button>
    </div>
</form>
```

### Action Buttons with Icons

```blade
<div class="flex gap-2">
    <x-ui.button variant="primary">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Add New
    </x-ui.button>
    
    <x-ui.button variant="secondary" outline>
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
        </svg>
        Export
    </x-ui.button>
</div>
```

### Loading State Example

```blade
<form x-data="{ loading: false }" @submit="loading = true">
    @csrf
    <!-- Form fields -->
    
    <x-ui.button 
        variant="primary" 
        type="submit"
        x-bind:loading="loading"
        x-bind:disabled="loading"
    >
        <span x-show="!loading">Save Changes</span>
        <span x-show="loading">Saving...</span>
    </x-ui.button>
</form>
```

## Accessibility

- Always include descriptive text or `aria-label`
- Use proper `type` attribute (button, submit, reset)
- Ensure sufficient color contrast
- Make clickable area large enough (min 44x44px)
- Provide visual feedback on hover/focus/active states
- Disable buttons during loading states

## Best Practices

1. **Use semantic colors**: Primary for main actions, secondary for alternative actions
2. **Consistent sizing**: Use the same size within a button group
3. **Loading states**: Show loading spinner for async operations
4. **Disabled state**: Disable buttons when action is not available
5. **Icon placement**: Place icons before text for better readability
6. **Button groups**: Use join component for related actions
7. **Mobile friendly**: Ensure buttons are large enough for touch targets

## Migration from Current Component

### Before (Custom)

```blade
<button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
    {{ $label }}
</button>
```

### After (daisyUI)

```blade
<x-ui.button variant="primary">
    {{ $label }}
</x-ui.button>
```

## Performance Notes

- Buttons are CSS-only (no JavaScript required)
- Minimal CSS footprint (~2KB)
- No runtime performance impact
- Works with Alpine.js for dynamic states
