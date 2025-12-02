# Quick Start Guide - daisyUI Integration

## 5-Minute Setup

### Step 1: Install daisyUI

```bash
npm install
```

The `package.json` has been updated with daisyUI 4.x. Just run npm install to get it.

### Step 2: Update Tailwind Configuration

Copy the example configuration:

```bash
cp design/tailwind.config.example.js tailwind.config.js
```

Or manually update your `tailwind.config.js`:

```javascript
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./app/Filament/**/*.php",
  ],
  plugins: [
    require('daisyui'),
  ],
  daisyui: {
    themes: ["light", "dark"],
  },
}
```

### Step 3: Update CSS

Add to `resources/css/app.css`:

```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

### Step 4: Build Assets

```bash
npm run build
```

For development with hot reload:

```bash
npm run dev
```

### Step 5: Test It

Create a test route in `routes/web.php`:

```php
Route::get('/test-daisy', function () {
    return view('test-daisy');
});
```

Create `resources/views/test-daisy.blade.php`:

```blade
@extends('layouts.app')

@section('content')
<div class="container mx-auto p-8">
    <h1 class="text-4xl font-bold mb-8">daisyUI Test</h1>
    
    <!-- Test Buttons -->
    <div class="space-x-2 mb-8">
        <button class="btn btn-primary">Primary</button>
        <button class="btn btn-secondary">Secondary</button>
        <button class="btn btn-accent">Accent</button>
    </div>
    
    <!-- Test Card -->
    <div class="card bg-base-100 shadow-xl max-w-md">
        <div class="card-body">
            <h2 class="card-title">Success!</h2>
            <p>daisyUI is working correctly.</p>
            <div class="card-actions justify-end">
                <button class="btn btn-primary">Action</button>
            </div>
        </div>
    </div>
</div>
@endsection
```

Visit `/test-daisy` to see daisyUI in action!

## Next Steps

1. **Read the Documentation**:
   - `design/README.md` - Overview
   - `design/INTEGRATION_GUIDE.md` - Detailed setup
   - `design/components/` - Component docs

2. **Review Examples**:
   - `design/examples/dashboard-example.blade.php`
   - See real-world usage patterns

3. **Start Migrating**:
   - Follow `design/MIGRATION_PLAN.md`
   - Start with high-priority components
   - Test thoroughly

4. **Create Components**:
   - Use examples in `design/components/`
   - Follow blade-guardrails.md
   - Test with all user roles

## Common Commands

```bash
# Install dependencies
npm install

# Development mode (hot reload)
npm run dev

# Production build
npm run build

# Preview production build
npm run preview
```

## Troubleshooting

### Styles not applying?

1. Clear cache: `php artisan cache:clear`
2. Rebuild assets: `npm run build`
3. Hard refresh browser: Ctrl+Shift+R

### Theme not working?

Ensure `data-theme="light"` is on the `<html>` tag in your layout.

### Conflicts with existing styles?

daisyUI uses Tailwind classes. Check for conflicting custom CSS.

## Quick Reference

### Most Used Components

```blade
<!-- Button -->
<button class="btn btn-primary">Click Me</button>

<!-- Card -->
<div class="card bg-base-100 shadow-xl">
    <div class="card-body">
        <h2 class="card-title">Title</h2>
        <p>Content</p>
    </div>
</div>

<!-- Input -->
<input type="text" class="input input-bordered w-full" />

<!-- Alert -->
<div class="alert alert-success">
    <span>Success message</span>
</div>

<!-- Badge -->
<div class="badge badge-primary">New</div>

<!-- Modal -->
<dialog class="modal">
    <div class="modal-box">
        <h3 class="font-bold text-lg">Modal Title</h3>
        <p>Modal content</p>
    </div>
</dialog>
```

## Resources

- [daisyUI Docs](https://daisyui.com/)
- [Tailwind Docs](https://tailwindcss.com/)
- [Alpine.js Docs](https://alpinejs.dev/)
- Internal: `/design/` directory

## Support

Need help? Check:
1. `design/INTEGRATION_GUIDE.md`
2. `design/COMPONENT_AUDIT.md`
3. `design/MIGRATION_PLAN.md`
4. Component documentation in `design/components/`
