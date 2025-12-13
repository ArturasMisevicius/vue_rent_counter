# Frontend Development Overview

## Technology Stack

CFlow uses a modern frontend stack focused on performance and developer experience.

### Core Technologies
- **Alpine.js** - Minimal JavaScript framework for interactivity
- **Tailwind CSS v4** - Utility-first CSS framework with OKLCH colors
- **Livewire v3** - Server-side rendering with reactive components
- **Vite** - Fast build tool and development server

### Build Tools
- **Vite** - Module bundler and dev server
- **PostCSS** - CSS processing
- **Autoprefixer** - CSS vendor prefixes
- **PurgeCSS** - Unused CSS removal

## Alpine.js Guidelines

### Component Structure
```html
<!-- ✅ GOOD: Declarative Alpine component -->
<div x-data="userForm()" x-init="init()">
    <form @submit.prevent="submit">
        <input 
            x-model="form.name" 
            :class="{ 'border-red-500': errors.name }"
            type="text" 
            placeholder="Name"
        >
        <span x-show="errors.name" x-text="errors.name" class="text-red-500"></span>
        
        <button 
            type="submit" 
            :disabled="loading"
            x-text="loading ? 'Saving...' : 'Save'"
        ></button>
    </form>
</div>

<script>
function userForm() {
    return {
        form: {
            name: '',
            email: ''
        },
        errors: {},
        loading: false,
        
        init() {
            // Component initialization
        },
        
        async submit() {
            this.loading = true;
            this.errors = {};
            
            try {
                const response = await fetch('/api/users', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.form)
                });
                
                if (!response.ok) {
                    const data = await response.json();
                    this.errors = data.errors || {};
                    return;
                }
                
                // Success handling
                window.location.href = '/users';
            } catch (error) {
                console.error('Form submission error:', error);
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
```

### Best Practices
- Keep components small and focused
- Use `x-data` for component state
- Use `x-init` for initialization logic
- Use `@` for event listeners
- Use `:` for dynamic attributes
- Use `x-show`/`x-if` for conditional rendering

### Performance Optimization
```html
<!-- Lazy load heavy components -->
<div x-data x-intersect="$el.innerHTML = heavyComponentHtml()">
    <!-- Placeholder content -->
</div>

<!-- Debounce expensive operations -->
<input x-model.debounce.500ms="searchQuery" @input="search()">

<!-- Use x-show for frequently toggled elements -->
<div x-show="isVisible" x-transition>
    <!-- Content that toggles frequently -->
</div>

<!-- Use x-if for elements that rarely change -->
<template x-if="shouldRender">
    <div><!-- Heavy content --></div>
</template>
```

## Tailwind CSS Guidelines

### Utility-First Approach
```html
<!-- ✅ GOOD: Utility classes -->
<div class="bg-white rounded-lg shadow-md p-6 max-w-md mx-auto">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">Card Title</h2>
    <p class="text-gray-600 leading-relaxed">Card content goes here.</p>
</div>

<!-- ❌ AVOID: Custom CSS when utilities exist -->
<div class="custom-card">
    <h2 class="custom-title">Card Title</h2>
    <p class="custom-content">Card content goes here.</p>
</div>
```

### Component Extraction
```css
/* Extract repeated patterns to components */
@layer components {
    .btn-primary {
        @apply bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors;
    }
    
    .form-input {
        @apply block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500;
    }
    
    .card {
        @apply bg-white rounded-lg shadow-md p-6;
    }
}
```

### Responsive Design
```html
<!-- Mobile-first responsive design -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <div class="p-4 text-sm md:text-base lg:text-lg">
        Responsive content
    </div>
</div>

<!-- Container queries (Tailwind v4) -->
<div class="@container">
    <div class="@md:grid-cols-2 @lg:grid-cols-3">
        Container-based responsive grid
    </div>
</div>
```

### Color System (OKLCH)
```css
/* Use OKLCH colors for better color accuracy */
:root {
    --primary: oklch(0.7 0.15 250);
    --secondary: oklch(0.6 0.1 280);
    --accent: oklch(0.8 0.2 120);
    --neutral: oklch(0.5 0.02 270);
}

/* Semantic color usage */
.btn-primary {
    background-color: var(--primary);
}

.text-accent {
    color: var(--accent);
}
```

## Livewire Integration

### Component Structure
```php
<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Validate;

final class UserForm extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';
    
    #[Validate('required|email|unique:users,email')]
    public string $email = '';
    
    public function save(): void
    {
        $this->validate();
        
        User::create([
            'name' => $this->name,
            'email' => $this->email,
        ]);
        
        $this->redirect('/users');
    }
    
    public function render()
    {
        return view('livewire.user-form');
    }
}
```

### Blade Template
```html
<div>
    <form wire:submit="save">
        <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
            <input 
                wire:model="name" 
                type="text" 
                id="name"
                class="form-input @error('name') border-red-500 @enderror"
            >
            @error('name')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>
        
        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input 
                wire:model="email" 
                type="email" 
                id="email"
                class="form-input @error('email') border-red-500 @enderror"
            >
            @error('email')
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>
        
        <button type="submit" class="btn-primary" wire:loading.attr="disabled">
            <span wire:loading.remove>Save User</span>
            <span wire:loading>Saving...</span>
        </button>
    </form>
</div>
```

## Asset Management

### Vite Configuration
```javascript
// vite.config.js
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
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['alpinejs'],
                    utils: ['axios'],
                }
            }
        }
    }
});
```

### CSS Organization
```css
/* resources/css/app.css */
@import 'tailwindcss/base';
@import 'tailwindcss/components';
@import 'tailwindcss/utilities';

/* Custom base styles */
@layer base {
    html {
        font-family: 'Inter', system-ui, sans-serif;
    }
    
    body {
        @apply bg-gray-50 text-gray-900;
    }
}

/* Custom components */
@layer components {
    .btn {
        @apply inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors;
    }
    
    .btn-primary {
        @apply btn bg-blue-600 hover:bg-blue-700 text-white focus:ring-blue-500;
    }
    
    .btn-secondary {
        @apply btn bg-gray-600 hover:bg-gray-700 text-white focus:ring-gray-500;
    }
}

/* Custom utilities */
@layer utilities {
    .text-balance {
        text-wrap: balance;
    }
    
    .text-pretty {
        text-wrap: pretty;
    }
}
```

### JavaScript Organization
```javascript
// resources/js/app.js
import Alpine from 'alpinejs';
import axios from 'axios';

// Configure axios
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['X-CSRF-TOKEN'] = 
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

// Alpine.js global components
Alpine.data('dropdown', () => ({
    open: false,
    toggle() {
        this.open = !this.open;
    },
    close() {
        this.open = false;
    }
}));

Alpine.data('modal', () => ({
    show: false,
    open() {
        this.show = true;
        document.body.style.overflow = 'hidden';
    },
    close() {
        this.show = false;
        document.body.style.overflow = '';
    }
}));

// Start Alpine
Alpine.start();

// Make Alpine available globally
window.Alpine = Alpine;
```

## Performance Optimization

### Code Splitting
```javascript
// Dynamic imports for heavy components
const loadChart = () => import('./components/chart.js');
const loadEditor = () => import('./components/editor.js');

// Use with Alpine
Alpine.data('dashboard', () => ({
    chartLoaded: false,
    
    async loadChart() {
        if (!this.chartLoaded) {
            const { Chart } = await loadChart();
            this.initChart(Chart);
            this.chartLoaded = true;
        }
    }
}));
```

### Image Optimization
```html
<!-- Responsive images -->
<img 
    src="/images/hero-mobile.jpg"
    srcset="/images/hero-mobile.jpg 480w,
            /images/hero-tablet.jpg 768w,
            /images/hero-desktop.jpg 1200w"
    sizes="(max-width: 480px) 100vw,
           (max-width: 768px) 100vw,
           1200px"
    alt="Hero image"
    loading="lazy"
    class="w-full h-auto"
>

<!-- WebP with fallback -->
<picture>
    <source srcset="/images/hero.webp" type="image/webp">
    <img src="/images/hero.jpg" alt="Hero image" class="w-full h-auto">
</picture>
```

### CSS Optimization
```css
/* Use CSS custom properties for theming */
:root {
    --color-primary: theme('colors.blue.600');
    --color-secondary: theme('colors.gray.600');
    --spacing-unit: 1rem;
    --border-radius: 0.375rem;
}

/* Optimize for critical rendering path */
@layer base {
    /* Critical styles only */
    body {
        font-family: system-ui, sans-serif;
        line-height: 1.6;
    }
}

/* Non-critical styles in components layer */
@layer components {
    .complex-component {
        /* Complex styles here */
    }
}
```

## Accessibility

### Semantic HTML
```html
<!-- Use proper semantic elements -->
<main>
    <article>
        <header>
            <h1>Article Title</h1>
            <time datetime="2024-12-13">December 13, 2024</time>
        </header>
        
        <section>
            <h2>Section Title</h2>
            <p>Content goes here.</p>
        </section>
    </article>
    
    <aside>
        <nav aria-label="Related articles">
            <ul>
                <li><a href="/article-1">Related Article 1</a></li>
                <li><a href="/article-2">Related Article 2</a></li>
            </ul>
        </nav>
    </aside>
</main>
```

### ARIA Attributes
```html
<!-- Form accessibility -->
<form>
    <div class="form-group">
        <label for="email" class="sr-only">Email Address</label>
        <input 
            type="email" 
            id="email"
            aria-describedby="email-help"
            aria-invalid="false"
            placeholder="Email address"
        >
        <div id="email-help" class="text-sm text-gray-600">
            We'll never share your email with anyone else.
        </div>
    </div>
</form>

<!-- Interactive elements -->
<button 
    aria-expanded="false"
    aria-controls="dropdown-menu"
    @click="toggle()"
>
    Menu
</button>

<div 
    id="dropdown-menu"
    x-show="open"
    role="menu"
    aria-labelledby="menu-button"
>
    <a href="/profile" role="menuitem">Profile</a>
    <a href="/settings" role="menuitem">Settings</a>
</div>
```

### Focus Management
```javascript
// Alpine.js focus management
Alpine.data('modal', () => ({
    show: false,
    
    open() {
        this.show = true;
        this.$nextTick(() => {
            this.$refs.firstInput?.focus();
        });
    },
    
    close() {
        this.show = false;
        this.$refs.trigger?.focus();
    },
    
    handleEscape(event) {
        if (event.key === 'Escape') {
            this.close();
        }
    }
}));
```

## Testing

### Component Testing
```javascript
// Test Alpine.js components
describe('UserForm Component', () => {
    let component;
    
    beforeEach(() => {
        document.body.innerHTML = `
            <div x-data="userForm()" id="test-component">
                <input x-model="form.name" data-testid="name-input">
                <button @click="submit()" data-testid="submit-button">Submit</button>
            </div>
        `;
        
        Alpine.start();
        component = document.getElementById('test-component');
    });
    
    test('updates form data on input', () => {
        const input = component.querySelector('[data-testid="name-input"]');
        input.value = 'John Doe';
        input.dispatchEvent(new Event('input'));
        
        expect(Alpine.$data(component).form.name).toBe('John Doe');
    });
});
```

### Visual Regression Testing
```javascript
// Playwright visual testing
test('homepage visual regression', async ({ page }) => {
    await page.goto('/');
    await page.waitForLoadState('networkidle');
    
    await expect(page).toHaveScreenshot('homepage.png');
});
```

## Build and Deployment

### Production Build
```bash
# Build for production
npm run build

# Analyze bundle size
npm run build -- --analyze

# Build with source maps
npm run build -- --sourcemap
```

### Environment Configuration
```javascript
// Different configs for environments
const config = {
    development: {
        apiUrl: 'http://localhost:8000/api',
        debug: true,
    },
    production: {
        apiUrl: '/api',
        debug: false,
    }
};

export default config[import.meta.env.MODE] || config.development;
```

## Related Documentation

- [Tailwind CSS Configuration](./tailwind.md)
- [Alpine.js Components](./alpine-components.md)
- [Livewire Integration](./livewire.md)
- [Performance Optimization](../performance/frontend.md)