# daisyUI Integration Guide

## Overview

This guide walks through integrating daisyUI 4.x with our Laravel 12 + Filament 4.x + Tailwind CSS 4.x stack for the Vilnius Utilities Billing Platform.

## Prerequisites

- Node.js 18+ and npm/yarn installed
- Laravel 12.x project with Vite configured
- Tailwind CSS 4.x installed
- Filament 4.x admin panel configured

## Installation Steps

### 1. Install daisyUI

```bash
npm install -D daisyui@latest
```

### 2. Configure Tailwind CSS

Update `tailwind.config.js`:

```javascript
/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./app/Filament/**/*.php",
    "./vendor/filament/**/*.blade.php",
  ],
  theme: {
    extend: {
      // Custom theme extensions
    },
  },
  plugins: [
    require('daisyui'),
  ],
  daisyui: {
    themes: [
      {
        light: {
          "primary": "#3b82f6",
          "secondary": "#8b5cf6",
          "accent": "#10b981",
          "neutral": "#1f2937",
          "base-100": "#ffffff",
          "info": "#3abff8",
          "success": "#36d399",
          "warning": "#fbbd23",
          "error": "#f87272",
        },
        dark: {
          "primary": "#60a5fa",
          "secondary": "#a78bfa",
          "accent": "#34d399",
          "neutral": "#374151",
          "base-100": "#1f2937",
          "info": "#3abff8",
          "success": "#36d399",
          "warning": "#fbbd23",
          "error": "#f87272",
        },
      },
    ],
    darkTheme: "dark",
    base: true,
    styled: true,
    utils: true,
    prefix: "",
    logs: true,
    themeRoot: ":root",
  },
}
```

### 3. Update CSS Entry Point

In `resources/css/app.css`:

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

/* Custom component styles */
@layer components {
  .btn-primary-custom {
    @apply btn btn-primary;
  }
  
  .card-custom {
    @apply card bg-base-100 shadow-xl;
  }
}
```

### 4. Configure Vite

Ensure `vite.config.js` includes CSS processing:

```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
});
```

### 5. Build Assets

```bash
npm run build
```

For development:

```bash
npm run dev
```

## Filament Integration

### Custom Filament Theme

Create `resources/css/filament/admin/theme.css`:

```css
@import '../../../css/app.css';

/* Filament-specific overrides */
.fi-sidebar {
    @apply bg-base-200;
}

.fi-topbar {
    @apply bg-base-100 border-b border-base-300;
}

.fi-btn-primary {
    @apply btn btn-primary;
}
```

Register in `app/Providers/Filament/AdminPanelProvider.php`:

```php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('admin')
        ->colors([
            'primary' => '#3b82f6',
        ])
        ->viteTheme('resources/css/filament/admin/theme.css')
        ->darkMode(true)
        ->brandName('Vilnius Utilities')
        // ... other configuration
}
```

## Blade Integration

### Layout Template

Update `resources/views/layouts/app.blade.php`:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'Vilnius Utilities') }}</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Alpine.js from CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-base-100 text-base-content">
    <div class="min-h-screen">
        @include('components.navigation')
        
        <main class="container mx-auto px-4 py-8">
            @yield('content')
        </main>
        
        @include('components.footer')
    </div>
    
    @stack('scripts')
</body>
</html>
```

### Theme Switcher Component

Create `resources/views/components/theme-switcher.blade.php`:

```blade
<div x-data="{ theme: localStorage.getItem('theme') || 'light' }" x-init="$watch('theme', val => {
    localStorage.setItem('theme', val);
    document.documentElement.setAttribute('data-theme', val);
})">
    <label class="swap swap-rotate">
        <input type="checkbox" x-model="theme" value="dark" :checked="theme === 'dark'" />
        
        <!-- Sun icon -->
        <svg class="swap-on fill-current w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path d="M5.64,17l-.71.71a1,1,0,0,0,0,1.41,1,1,0,0,0,1.41,0l.71-.71A1,1,0,0,0,5.64,17ZM5,12a1,1,0,0,0-1-1H3a1,1,0,0,0,0,2H4A1,1,0,0,0,5,12Zm7-7a1,1,0,0,0,1-1V3a1,1,0,0,0-2,0V4A1,1,0,0,0,12,5ZM5.64,7.05a1,1,0,0,0,.7.29,1,1,0,0,0,.71-.29,1,1,0,0,0,0-1.41l-.71-.71A1,1,0,0,0,4.93,6.34Zm12,.29a1,1,0,0,0,.7-.29l.71-.71a1,1,0,1,0-1.41-1.41L17,5.64a1,1,0,0,0,0,1.41A1,1,0,0,0,17.66,7.34ZM21,11H20a1,1,0,0,0,0,2h1a1,1,0,0,0,0-2Zm-9,8a1,1,0,0,0-1,1v1a1,1,0,0,0,2,0V20A1,1,0,0,0,12,19ZM18.36,17A1,1,0,0,0,17,18.36l.71.71a1,1,0,0,0,1.41,0,1,1,0,0,0,0-1.41ZM12,6.5A5.5,5.5,0,1,0,17.5,12,5.51,5.51,0,0,0,12,6.5Zm0,9A3.5,3.5,0,1,1,15.5,12,3.5,3.5,0,0,1,12,15.5Z"/>
        </svg>
        
        <!-- Moon icon -->
        <svg class="swap-off fill-current w-6 h-6" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path d="M21.64,13a1,1,0,0,0-1.05-.14,8.05,8.05,0,0,1-3.37.73A8.15,8.15,0,0,1,9.08,5.49a8.59,8.59,0,0,1,.25-2A1,1,0,0,0,8,2.36,10.14,10.14,0,1,0,22,14.05,1,1,0,0,0,21.64,13Zm-9.5,6.69A8.14,8.14,0,0,1,7.08,5.22v.27A10.15,10.15,0,0,0,17.22,15.63a9.79,9.79,0,0,0,2.1-.22A8.11,8.11,0,0,1,12.14,19.73Z"/>
        </svg>
    </label>
</div>
```

## Component Usage Examples

### Button Component

```blade
<!-- Primary button -->
<button class="btn btn-primary">
    {{ __('Save Changes') }}
</button>

<!-- Secondary button with icon -->
<button class="btn btn-secondary gap-2">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
    </svg>
    {{ __('Add New') }}
</button>

<!-- Loading button -->
<button class="btn btn-primary" :class="{ 'loading': isLoading }" :disabled="isLoading">
    {{ __('Submit') }}
</button>
```

### Card Component

```blade
<div class="card bg-base-100 shadow-xl">
    <div class="card-body">
        <h2 class="card-title">{{ __('Property Details') }}</h2>
        <p>{{ $property->address }}</p>
        <div class="card-actions justify-end">
            <button class="btn btn-primary">{{ __('View Details') }}</button>
        </div>
    </div>
</div>
```

### Alert Component

```blade
@if (session('success'))
    <div class="alert alert-success shadow-lg">
        <div>
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    </div>
@endif
```

### Modal Component

```blade
<div x-data="{ open: false }">
    <!-- Trigger button -->
    <button @click="open = true" class="btn btn-primary">
        {{ __('Open Modal') }}
    </button>
    
    <!-- Modal -->
    <div x-show="open" class="modal modal-open" x-cloak>
        <div class="modal-box">
            <h3 class="font-bold text-lg">{{ __('Confirm Action') }}</h3>
            <p class="py-4">{{ __('Are you sure you want to proceed?') }}</p>
            <div class="modal-action">
                <button @click="open = false" class="btn">{{ __('Cancel') }}</button>
                <button class="btn btn-primary">{{ __('Confirm') }}</button>
            </div>
        </div>
    </div>
</div>
```

## Multi-Tenancy Considerations

### Tenant-Aware Components

When building components that display tenant-specific data:

```blade
{{-- Ensure tenant context is set --}}
@php
    // This should be handled by middleware/view composers
    // Never use @php in production - this is for documentation only
@endphp

<div class="stats shadow">
    <div class="stat">
        <div class="stat-title">{{ __('Total Properties') }}</div>
        <div class="stat-value">{{ $tenantProperties->count() }}</div>
        <div class="stat-desc">{{ __('In your portfolio') }}</div>
    </div>
</div>
```

### Role-Based UI

Use view composers to determine user roles:

```blade
{{-- Navigation based on user role --}}
<ul class="menu bg-base-200 w-56 rounded-box">
    @if ($userRole === 'superadmin')
        <li><a href="{{ route('admin.organizations.index') }}">{{ __('Organizations') }}</a></li>
    @endif
    
    @if (in_array($userRole, ['admin', 'manager']))
        <li><a href="{{ route('admin.properties.index') }}">{{ __('Properties') }}</a></li>
        <li><a href="{{ route('admin.meters.index') }}">{{ __('Meters') }}</a></li>
    @endif
    
    <li><a href="{{ route('tenant.dashboard') }}">{{ __('Dashboard') }}</a></li>
</ul>
```

## Accessibility Guidelines

### Keyboard Navigation

Ensure all interactive elements are keyboard accessible:

```blade
<button class="btn btn-primary" 
        tabindex="0"
        @keydown.enter="handleAction"
        @keydown.space.prevent="handleAction">
    {{ __('Submit') }}
</button>
```

### ARIA Labels

Add appropriate ARIA labels for screen readers:

```blade
<button class="btn btn-circle btn-ghost" 
        aria-label="{{ __('Close menu') }}"
        @click="menuOpen = false">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
    </svg>
</button>
```

### Focus Indicators

Maintain visible focus indicators:

```css
/* In resources/css/app.css */
@layer components {
  .btn:focus-visible {
    @apply outline outline-2 outline-offset-2 outline-primary;
  }
}
```

## Performance Optimization

### Lazy Loading

For components with heavy JavaScript:

```blade
<div x-data="{ loaded: false }" 
     x-intersect="loaded = true">
    <template x-if="loaded">
        <div class="carousel w-full">
            {{-- Heavy carousel content --}}
        </div>
    </template>
</div>
```

### CSS Purging

Ensure Tailwind purges unused styles in production:

```javascript
// tailwind.config.js
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./app/Filament/**/*.php",
    "./vendor/filament/**/*.blade.php",
  ],
  // ... rest of config
}
```

## Testing

### Component Testing

Test daisyUI components with Pest:

```php
it('renders button with correct classes', function () {
    $view = $this->blade('<button class="btn btn-primary">Test</button>');
    
    $view->assertSee('Test');
    $view->assertSee('btn btn-primary', false);
});

it('applies theme correctly', function () {
    $this->get(route('dashboard'))
        ->assertSee('data-theme="light"', false);
});
```

## Troubleshooting

### Styles Not Applying

1. Clear Vite cache: `rm -rf node_modules/.vite`
2. Rebuild assets: `npm run build`
3. Clear Laravel cache: `php artisan cache:clear`

### Theme Not Switching

1. Check browser console for JavaScript errors
2. Verify Alpine.js is loaded
3. Check localStorage in browser DevTools

### Filament Conflicts

If Filament styles conflict with daisyUI:

1. Use CSS specificity to override
2. Create custom Filament theme
3. Use `@layer` directives to control cascade

## Next Steps

1. Review `COMPONENT_AUDIT.md` for available components
2. Check `MIGRATION_PLAN.md` for migration strategy
3. Explore `examples/` directory for usage patterns
4. Consult `tokens/` directory for design tokens

## Support

For issues or questions:
- Check [daisyUI documentation](https://daisyui.com/)
- Review project-specific patterns in `design/components/`
- Consult team lead for architectural decisions
# daisyUI Integration Guide

## Step 1: Install daisyUI

```bash
npm install -D daisyui@latest
```

## Step 2: Update Tailwind Configuration

Update `tailwind.config.js`:

```javascript
/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./app/Filament/**/*.php",
  ],
  theme: {
    extend: {
      fontFamily: {
        display: ['Inter', 'system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [
    require('daisyui'),
  ],
  daisyui: {
    themes: [
      {
        light: {
          "primary": "#4f46e5",           // indigo-600
          "primary-content": "#ffffff",
          "secondary": "#0ea5e9",         // sky-500
          "secondary-content": "#ffffff",
          "accent": "#8b5cf6",            // violet-500
          "accent-content": "#ffffff",
          "neutral": "#1e293b",           // slate-800
          "neutral-content": "#f1f5f9",   // slate-100
          "base-100": "#ffffff",
          "base-200": "#f8fafc",          // slate-50
          "base-300": "#e2e8f0",          // slate-200
          "base-content": "#0f172a",      // slate-900
          "info": "#0ea5e9",              // sky-500
          "info-content": "#ffffff",
          "success": "#10b981",           // emerald-500
          "success-content": "#ffffff",
          "warning": "#f59e0b",           // amber-500
          "warning-content": "#ffffff",
          "error": "#ef4444",             // red-500
          "error-content": "#ffffff",
        },
        dark: {
          "primary": "#6366f1",           // indigo-500
          "primary-content": "#ffffff",
          "secondary": "#38bdf8",         // sky-400
          "secondary-content": "#0f172a",
          "accent": "#a78bfa",            // violet-400
          "accent-content": "#1e1b4b",
          "neutral": "#1e293b",           // slate-800
          "neutral-content": "#f1f5f9",
          "base-100": "#0f172a",          // slate-900
          "base-200": "#1e293b",          // slate-800
          "base-300": "#334155",          // slate-700
          "base-content": "#f1f5f9",      // slate-100
          "info": "#38bdf8",
          "info-content": "#0f172a",
          "success": "#34d399",           // emerald-400
          "success-content": "#064e3b",
          "warning": "#fbbf24",           // amber-400
          "warning-content": "#78350f",
          "error": "#f87171",             // red-400
          "error-content": "#7f1d1d",
        },
      },
    ],
    darkTheme: "dark",
    base: true,
    styled: true,
    utils: true,
    prefix: "",
    logs: true,
    themeRoot: ":root",
  },
}
```

## Step 3: Update Layout File

Update `resources/views/layouts/app.blade.php` to remove CDN Tailwind and use compiled CSS:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('app.meta.default_title'))</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @stack('styles')
</head>
<body class="antialiased">
    <!-- Content -->
</body>
</html>
```

## Step 4: Update CSS Entry Point

Update `resources/css/app.css`:

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

/* Custom component styles */
@layer components {
  /* Navigation enhancements */
  .nav-link {
    @apply px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition;
  }
  
  .nav-link-active {
    @apply bg-primary text-primary-content;
  }
  
  .nav-link-inactive {
    @apply text-base-content/70 hover:bg-base-200 hover:text-base-content;
  }

  /* Card enhancements */
  .card-elevated {
    @apply card bg-base-100 shadow-xl;
  }

  /* Button enhancements */
  .btn-gradient {
    @apply btn bg-gradient-to-r from-primary to-secondary text-primary-content;
  }

  /* Form enhancements */
  .form-control-enhanced {
    @apply form-control w-full;
  }

  /* Alert enhancements */
  .alert-floating {
    @apply alert shadow-lg;
  }
}

/* Custom utility classes */
@layer utilities {
  .shadow-glow {
    box-shadow: 0 0 20px rgba(99, 102, 241, 0.3);
  }

  .backdrop-blur-glass {
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
  }
}
```

## Step 5: Build Assets

```bash
npm run build
```

For development:
```bash
npm run dev
```

## Step 6: Theme Switching (Optional)

Add theme switcher component:

```blade
<!-- resources/views/components/theme-switcher.blade.php -->
<div x-data="{ theme: localStorage.getItem('theme') || 'light' }" x-init="$watch('theme', val => { localStorage.setItem('theme', val); document.documentElement.setAttribute('data-theme', val); })">
    <button @click="theme = theme === 'light' ? 'dark' : 'light'" class="btn btn-ghost btn-circle">
        <svg x-show="theme === 'light'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
        </svg>
        <svg x-show="theme === 'dark'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
    </button>
</div>
```

## Step 7: Filament Integration

Update Filament theme configuration in `config/filament.php`:

```php
'theme' => [
    'colors' => [
        'primary' => '#4f46e5', // indigo-600
        'secondary' => '#0ea5e9', // sky-500
        'success' => '#10b981', // emerald-500
        'warning' => '#f59e0b', // amber-500
        'danger' => '#ef4444', // red-500
        'info' => '#0ea5e9', // sky-500
    ],
],
```

## Step 8: Verify Installation

Create a test page to verify daisyUI components work:

```blade
<!-- resources/views/test-daisy.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container mx-auto p-8 space-y-8">
    <h1 class="text-4xl font-bold">daisyUI Component Test</h1>
    
    <!-- Buttons -->
    <div class="space-x-2">
        <button class="btn">Default</button>
        <button class="btn btn-primary">Primary</button>
        <button class="btn btn-secondary">Secondary</button>
        <button class="btn btn-accent">Accent</button>
    </div>
    
    <!-- Card -->
    <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
            <h2 class="card-title">Card Title</h2>
            <p>This is a daisyUI card component.</p>
            <div class="card-actions justify-end">
                <button class="btn btn-primary">Action</button>
            </div>
        </div>
    </div>
    
    <!-- Alert -->
    <div class="alert alert-success">
        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>Success! daisyUI is working correctly.</span>
    </div>
</div>
@endsection
```

## Troubleshooting

### Issue: Styles not applying
**Solution**: Run `npm run build` and clear browser cache

### Issue: Theme not switching
**Solution**: Ensure `data-theme` attribute is on `<html>` tag

### Issue: Conflicts with existing styles
**Solution**: Use daisyUI's prefix option or adjust specificity

### Issue: Filament styles conflicting
**Solution**: Filament uses its own styling system; apply daisyUI only to non-Filament views

## Next Steps

1. Review component documentation in `design/components/`
2. Check migration plan in `MIGRATION_PLAN.md`
3. Start migrating existing components
4. Test thoroughly across all user roles
5. Update documentation as needed

## Performance Considerations

- daisyUI adds ~30KB to your CSS bundle (gzipped)
- Use PurgeCSS (built into Tailwind) to remove unused styles
- Consider code splitting for large applications
- Monitor bundle size with `npm run build -- --analyze`

## Accessibility Notes

- All daisyUI components are keyboard accessible
- Proper ARIA attributes are included
- Color contrast meets WCAG 2.1 AA standards
- Test with screen readers before deployment
