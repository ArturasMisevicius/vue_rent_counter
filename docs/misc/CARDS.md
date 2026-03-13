# Card Components

## Overview

Cards are versatile containers for grouping related content. daisyUI provides a flexible card system with multiple layouts and styles.

## Basic Card

```blade
<div class="card bg-base-100 shadow-xl">
    <div class="card-body">
        <h2 class="card-title">Card Title</h2>
        <p>Card content goes here</p>
    </div>
</div>
```

## Card with Image

```blade
<div class="card bg-base-100 shadow-xl">
    <figure>
        <img src="/images/property.jpg" alt="Property" />
    </figure>
    <div class="card-body">
        <h2 class="card-title">Property Name</h2>
        <p>Property description</p>
        <div class="card-actions justify-end">
            <button class="btn btn-primary">View Details</button>
        </div>
    </div>
</div>
```

## Card Variants

### Compact Card

```blade
<div class="card card-compact bg-base-100 shadow-xl">
    <div class="card-body">
        <h2 class="card-title">Compact Card</h2>
        <p>Less padding for tighter layouts</p>
    </div>
</div>
```

### Card with Side Image

```blade
<div class="card card-side bg-base-100 shadow-xl">
    <figure>
        <img src="/images/meter.jpg" alt="Meter" class="w-48" />
    </figure>
    <div class="card-body">
        <h2 class="card-title">Meter Reading</h2>
        <p>Current reading: 1,234 kWh</p>
        <div class="card-actions justify-end">
            <button class="btn btn-primary">Update</button>
        </div>
    </div>
</div>
```

### Glass Card

```blade
<div class="card glass">
    <div class="card-body">
        <h2 class="card-title">Glass Effect</h2>
        <p>Transparent background with blur</p>
    </div>
</div>
```

## Card with Badge

```blade
<div class="card bg-base-100 shadow-xl">
    <div class="card-body">
        <h2 class="card-title">
            Invoice #12345
            <div class="badge badge-secondary">NEW</div>
        </h2>
        <p>Amount: €123.45</p>
    </div>
</div>
```

## Blade Component

Create `resources/views/components/ui/card.blade.php`:

```blade
@props([
    'title' => null,
    'subtitle' => null,
    'image' => null,
    'imageAlt' => '',
    'compact' => false,
    'side' => false,
    'glass' => false,
    'actions' => null,
])

@php
$classes = 'card bg-base-100 shadow-xl';

if ($compact) {
    $classes .= ' card-compact';
}

if ($side) {
    $classes .= ' card-side';
}

if ($glass) {
    $classes = 'card glass';
}
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    @if($image)
    <figure>
        <img src="{{ $image }}" alt="{{ $imageAlt }}" />
    </figure>
    @endif
    
    <div class="card-body">
        @if($title)
        <h2 class="card-title">
            {{ $title }}
            @if(isset($badge))
                {{ $badge }}
            @endif
        </h2>
        @endif
        
        @if($subtitle)
        <p class="text-base-content/70">{{ $subtitle }}</p>
        @endif
        
        {{ $slot }}
        
        @if($actions || isset($actions))
        <div class="card-actions justify-end">
            {{ $actions }}
        </div>
        @endif
    </div>
</div>
```

### Usage

```blade
<x-ui.card title="Property Details" subtitle="123 Main Street">
    <p>3 bedrooms, 2 bathrooms</p>
    <p>Monthly rent: €1,200</p>
    
    <x-slot:actions>
        <button class="btn btn-ghost">Cancel</button>
        <button class="btn btn-primary">Edit</button>
    </x-slot:actions>
</x-ui.card>
```

## Real-World Examples

### Property Card

```blade
<x-ui.card 
    image="/images/properties/{{ $property->id }}.jpg"
    imageAlt="{{ $property->address }}"
>
    <h2 class="card-title">
        {{ $property->address }}
        @if($property->is_occupied)
            <div class="badge badge-success">Occupied</div>
        @else
            <div class="badge badge-warning">Vacant</div>
        @endif
    </h2>
    
    <div class="space-y-2">
        <div class="flex justify-between">
            <span class="text-base-content/70">Building:</span>
            <span class="font-semibold">{{ $property->building->name }}</span>
        </div>
        <div class="flex justify-between">
            <span class="text-base-content/70">Area:</span>
            <span class="font-semibold">{{ $property->area }} m²</span>
        </div>
        <div class="flex justify-between">
            <span class="text-base-content/70">Rent:</span>
            <span class="font-semibold">€{{ number_format($property->rent, 2) }}</span>
        </div>
    </div>
    
    <x-slot:actions>
        <a href="{{ route('manager.properties.show', $property) }}" class="btn btn-primary btn-sm">
            View Details
        </a>
    </x-slot:actions>
</x-ui.card>
```

### Invoice Summary Card

```blade
<x-ui.card title="Invoice #{{ $invoice->number }}">
    <x-slot:badge>
        <div class="badge badge-{{ $invoice->status_color }}">
            {{ $invoice->status }}
        </div>
    </x-slot:badge>
    
    <div class="space-y-3">
        <div class="flex justify-between">
            <span>Period:</span>
            <span class="font-semibold">{{ $invoice->period }}</span>
        </div>
        
        <div class="divider my-2"></div>
        
        @foreach($invoice->items as $item)
        <div class="flex justify-between text-sm">
            <span>{{ $item->description }}</span>
            <span>€{{ number_format($item->amount, 2) }}</span>
        </div>
        @endforeach
        
        <div class="divider my-2"></div>
        
        <div class="flex justify-between text-lg font-bold">
            <span>Total:</span>
            <span>€{{ number_format($invoice->total, 2) }}</span>
        </div>
    </div>
    
    <x-slot:actions>
        <a href="{{ route('invoices.download', $invoice) }}" class="btn btn-ghost btn-sm">
            Download PDF
        </a>
        @if($invoice->canBePaid())
        <button class="btn btn-primary btn-sm">
            Pay Now
        </button>
        @endif
    </x-slot:actions>
</x-ui.card>
```

### Meter Reading Card

```blade
<x-ui.card compact>
    <div class="flex items-center gap-4">
        <div class="avatar placeholder">
            <div class="bg-primary text-primary-content rounded-full w-12">
                <span class="text-xl">{{ $meter->type_icon }}</span>
            </div>
        </div>
        
        <div class="flex-1">
            <h3 class="font-bold">{{ $meter->type_label }}</h3>
            <p class="text-sm text-base-content/70">{{ $meter->serial_number }}</p>
        </div>
        
        <div class="text-right">
            <div class="text-2xl font-bold">{{ $meter->latest_reading }}</div>
            <div class="text-xs text-base-content/70">{{ $meter->unit }}</div>
        </div>
    </div>
    
    <div class="mt-4">
        <a href="{{ route('meter-readings.create', $meter) }}" class="btn btn-primary btn-block btn-sm">
            Add Reading
        </a>
    </div>
</x-ui.card>
```

### Stats Card

```blade
<x-ui.card>
    <div class="stats stats-vertical">
        <div class="stat">
            <div class="stat-figure text-primary">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
            </div>
            <div class="stat-title">Total Consumption</div>
            <div class="stat-value text-primary">{{ $stats->total_consumption }}</div>
            <div class="stat-desc">{{ $stats->period }}</div>
        </div>
        
        <div class="stat">
            <div class="stat-figure text-secondary">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="stat-title">Total Cost</div>
            <div class="stat-value text-secondary">€{{ number_format($stats->total_cost, 2) }}</div>
            <div class="stat-desc">↗︎ {{ $stats->change }}% from last month</div>
        </div>
    </div>
</x-ui.card>
```

## Card Grid Layouts

### 2-Column Grid

```blade
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <x-ui.card title="Card 1">Content 1</x-ui.card>
    <x-ui.card title="Card 2">Content 2</x-ui.card>
</div>
```

### 3-Column Grid

```blade
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <x-ui.card title="Card 1">Content 1</x-ui.card>
    <x-ui.card title="Card 2">Content 2</x-ui.card>
    <x-ui.card title="Card 3">Content 3</x-ui.card>
</div>
```

### Masonry Layout

```blade
<div class="columns-1 md:columns-2 lg:columns-3 gap-6 space-y-6">
    <x-ui.card title="Card 1" class="break-inside-avoid">
        Short content
    </x-ui.card>
    <x-ui.card title="Card 2" class="break-inside-avoid">
        Longer content that takes more space...
    </x-ui.card>
    <x-ui.card title="Card 3" class="break-inside-avoid">
        Medium content
    </x-ui.card>
</div>
```

## Accessibility

- Use semantic HTML (`<article>` for card containers)
- Include descriptive headings
- Ensure sufficient color contrast
- Make interactive elements keyboard accessible
- Provide alt text for images

## Best Practices

1. **Consistent spacing**: Use card-body for proper padding
2. **Clear hierarchy**: Use card-title for headings
3. **Action placement**: Place primary actions in card-actions
4. **Image optimization**: Compress images for faster loading
5. **Responsive design**: Test on mobile devices
6. **Loading states**: Show skeleton cards while loading
7. **Empty states**: Handle empty content gracefully

## Migration from Current Component

### Before (Custom)

```blade
<div class="bg-white shadow-md rounded-lg p-6">
    <h3 class="text-lg font-semibold">{{ $title }}</h3>
    <div class="mt-4">{{ $content }}</div>
</div>
```

### After (daisyUI)

```blade
<x-ui.card title="{{ $title }}">
    {{ $content }}
</x-ui.card>
```
