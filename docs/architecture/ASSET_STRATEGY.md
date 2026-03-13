# Asset Strategy Documentation

**Date**: 2024-12-06  
**Type**: Architecture Documentation  
**Status**: Current

## Overview

The Vilnius Utilities Billing System uses a hybrid asset strategy that balances performance, maintainability, and development velocity. This document outlines the asset loading strategy, build process, and architectural decisions.

## Asset Loading Strategy

### Bundled Assets (via Vite)

**Alpine.js (v3.14.0)**:
- **Status**: Bundled via Vite
- **Rationale**: Performance, reliability, version control
- **Location**: `resources/js/app.js`
- **Build**: Compiled into `public/build/assets/app-[hash].js`

**Chart.js (v4.4.4)**:
- **Status**: Bundled via Vite
- **Rationale**: Dashboard visualizations, tree-shaking
- **Location**: `resources/js/app.js`
- **Build**: Compiled into `public/build/assets/app-[hash].js`

**Custom JavaScript**:
- **Status**: Bundled via Vite
- **Location**: `resources/js/app.js`, `resources/js/bootstrap.js`
- **Build**: Compiled into `public/build/assets/app-[hash].js`

**Custom CSS**:
- **Status**: Bundled via Vite
- **Location**: `resources/css/app.css`
- **Build**: Compiled into `public/build/assets/app-[hash].css`

### CDN Assets

**Tailwind CSS (v4.x)**:
- **Status**: Loaded via CDN
- **Rationale**: Rapid prototyping, no build step for CSS changes
- **URL**: `https://cdn.tailwindcss.com`
- **Future**: May migrate to bundled approach

**Filament Assets**:
- **Status**: Self-managed by Filament
- **Location**: `public/js/filament/`, `public/css/filament/`
- **Build**: Handled by Filament's own asset pipeline

## Architecture Diagram

```mermaid
graph TD
    subgraph "Browser"
        HTML[HTML Document]
        JS[JavaScript Runtime]
        CSS[CSS Engine]
    end
    
    subgraph "Vite Build Process"
        ViteEntry[Vite Entry Points]
        ViteBundle[Vite Bundler]
        ViteOutput[Build Artifacts]
        
        ViteEntry -->|resources/js/app.js| ViteBundle
        ViteEntry -->|resources/css/app.css| ViteBundle
        ViteBundle -->|Tree-shake & Minify| ViteOutput
    end
    
    subgraph "Asset Sources"
        Alpine[Alpine.js npm]
        Chart[Chart.js npm]
        CustomJS[Custom JavaScript]
        CustomCSS[Custom CSS]
        TailwindCDN[Tailwind CDN]
        FilamentAssets[Filament Assets]
    end
    
    Alpine --> ViteBundle
    Chart --> ViteBundle
    CustomJS --> ViteBundle
    CustomCSS --> ViteBundle
    
    ViteOutput -->|app-[hash].js| HTML
    ViteOutput -->|app-[hash].css| HTML
    TailwindCDN -->|CDN Link| HTML
    FilamentAssets -->|Self-managed| HTML
    
    HTML --> JS
    HTML --> CSS
    
    style ViteBundle fill:#646cff,stroke:#535bf2,color:white
    style ViteOutput fill:#4dbb5f,stroke:#36873f,color:white
    style Alpine fill:#8bc0d0,stroke:#77a6b6,color:white
    style Chart fill:#ff6384,stroke:#cc4f69,color:white
```

## Build Process

### Development Workflow

```bash
# Install dependencies
npm install

# Start Vite dev server (hot reload)
npm run dev

# In another terminal, start Laravel
php artisan serve
```

**Development Features**:
- Hot Module Replacement (HMR)
- Source maps for debugging
- Fast refresh
- Error overlay

### Production Build

```bash
# Install production dependencies
npm ci --production

# Build optimized assets
npm run build

# Output: public/build/
# - manifest.json
# - assets/app-[hash].js
# - assets/app-[hash].css
```

**Production Optimizations**:
- Tree-shaking (removes unused code)
- Minification (reduces file size)
- Code splitting (separates vendor code)
- Asset versioning (cache busting)

## File Structure

```
resources/
├── css/
│   └── app.css                 # Custom CSS entry point
├── js/
│   ├── app.js                  # JavaScript entry point
│   └── bootstrap.js            # Axios configuration
└── views/
    └── layouts/
        └── app.blade.php       # Main layout with @vite directive

public/
├── build/                      # Vite build output (generated)
│   ├── manifest.json          # Asset manifest
│   └── assets/
│       ├── app-[hash].js      # Bundled JavaScript
│       └── app-[hash].css     # Bundled CSS
└── js/
    └── filament/              # Filament self-managed assets

vite.config.js                  # Vite configuration
package.json                    # npm dependencies
```

## Asset Loading in Blade

### Main Layout (`resources/views/layouts/app.blade.php`)

```html
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('app.meta.default_title'))</title>

    {{-- Vite bundled assets (Alpine.js, Chart.js, custom JS/CSS) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Additional page-specific styles --}}
    @stack('styles')
</head>
<body>
    {{-- Content --}}
    @yield('content')

    {{-- Additional page-specific scripts --}}
    @stack('scripts')
</body>
</html>
```

### Asset Loading Order

1. **Vite Manifest**: Laravel reads `public/build/manifest.json`
2. **CSS Loading**: `<link>` tag for `app-[hash].css`
3. **JavaScript Loading**: `<script type="module">` for `app-[hash].js`
4. **Alpine.js Initialization**: Automatic via `Alpine.start()` in `app.js`
5. **Page-specific Assets**: Via `@stack('styles')` and `@stack('scripts')`

## Performance Characteristics

### Bundle Sizes

| Asset | Size (Development) | Size (Production) | Gzipped |
|-------|-------------------|-------------------|---------|
| Alpine.js | ~50KB | ~45KB | ~15KB |
| Chart.js | ~250KB | ~200KB | ~60KB |
| Custom JS | ~10KB | ~5KB | ~2KB |
| Custom CSS | ~5KB | ~3KB | ~1KB |
| **Total** | **~315KB** | **~253KB** | **~78KB** |

### Load Times

**Development** (with HMR):
- Initial load: ~500ms
- Hot reload: ~50ms

**Production** (optimized):
- Initial load: ~200ms (with caching)
- Subsequent loads: ~50ms (cached)

### Caching Strategy

**Vite Versioned Assets**:
- Filename includes content hash: `app-[hash].js`
- Cache-Control: `public, max-age=31536000, immutable`
- Cache invalidation: Automatic on content change

**CDN Assets**:
- Tailwind CSS: Browser cache + CDN cache
- Cache-Control: Varies by CDN

## Integration Points

### Laravel Integration

**Vite Helper**:
```php
// In Blade templates
@vite(['resources/css/app.css', 'resources/js/app.js'])

// Generates:
// <link rel="stylesheet" href="/build/assets/app-[hash].css">
// <script type="module" src="/build/assets/app-[hash].js"></script>
```

**Asset Manifest**:
```json
{
  "resources/js/app.js": {
    "file": "assets/app-abc123.js",
    "src": "resources/js/app.js",
    "isEntry": true
  },
  "resources/css/app.css": {
    "file": "assets/app-def456.css",
    "src": "resources/css/app.css",
    "isEntry": true
  }
}
```

### Alpine.js Integration

**Initialization** (`resources/js/app.js`):
```javascript
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();
```

**Usage in Blade**:
```html
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open">Content</div>
</div>
```

### Chart.js Integration

**Initialization** (`resources/js/app.js`):
```javascript
import Chart from 'chart.js/auto';
window.Chart = Chart;
```

**Usage in Blade**:
```html
<canvas id="myChart"></canvas>
@push('scripts')
<script>
    const ctx = document.getElementById('myChart');
    new Chart(ctx, {
        type: 'bar',
        data: { /* ... */ }
    });
</script>
@endpush
```

## Deployment Considerations

### Production Deployment Checklist

- [ ] Run `npm ci --production` to install dependencies
- [ ] Run `npm run build` to compile assets
- [ ] Verify `public/build/` directory exists
- [ ] Clear Laravel caches: `php artisan optimize:clear`
- [ ] Cache Laravel config: `php artisan config:cache`
- [ ] Cache Laravel routes: `php artisan route:cache`
- [ ] Cache Laravel views: `php artisan view:cache`
- [ ] Test asset loading in production environment

### CI/CD Pipeline

```yaml
# Example GitHub Actions workflow
- name: Install Node dependencies
  run: npm ci --production

- name: Build assets
  run: npm run build

- name: Deploy assets
  run: |
    rsync -avz public/build/ user@server:/path/to/public/build/
```

### Rollback Strategy

If asset deployment fails:

1. **Revert to previous build**:
   ```bash
   git checkout HEAD~1 public/build/
   ```

2. **Rebuild assets**:
   ```bash
   npm run build
   ```

3. **Clear caches**:
   ```bash
   php artisan optimize:clear
   ```

## Security Considerations

### Content Security Policy (CSP)

**Current CSP** (with CDN Tailwind):
```
Content-Security-Policy: 
  default-src 'self';
  script-src 'self' https://cdn.tailwindcss.com;
  style-src 'self' https://cdn.tailwindcss.com 'unsafe-inline';
```

**Future CSP** (if Tailwind bundled):
```
Content-Security-Policy: 
  default-src 'self';
  script-src 'self';
  style-src 'self';
```

### Subresource Integrity (SRI)

**Bundled Assets**: Not needed (served from same origin)

**CDN Assets**: Consider adding SRI hashes:
```html
<script src="https://cdn.example.com/lib.js" 
        integrity="sha384-..." 
        crossorigin="anonymous"></script>
```

### Asset Verification

**Build-time Verification**:
- Vite validates all imports
- TypeScript/ESLint checks (if enabled)
- Dependency vulnerability scanning

**Runtime Verification**:
- Browser console checks
- Automated testing
- Performance monitoring

## Future Enhancements

### Planned Improvements

1. **Tailwind CSS Bundling**:
   - Migrate from CDN to Vite bundling
   - Enable PurgeCSS for smaller bundle
   - Improve CSP compliance

2. **Code Splitting**:
   - Split vendor code into separate chunk
   - Lazy load Chart.js on dashboard pages
   - Reduce initial bundle size

3. **Service Worker**:
   - Add offline capability
   - Cache assets for faster loads
   - Background sync for forms

4. **Bundle Analysis**:
   - Use Vite bundle analyzer
   - Identify optimization opportunities
   - Monitor bundle size over time

5. **Asset Preloading**:
   - Preload critical assets
   - Prefetch next-page assets
   - Optimize resource hints

### Migration Roadmap

**Phase 1** (Current):
- ✅ Alpine.js bundled via Vite
- ✅ Chart.js bundled via Vite
- ⏳ Tailwind CSS via CDN

**Phase 2** (Q1 2025):
- 🔄 Migrate Tailwind CSS to Vite
- 🔄 Implement code splitting
- 🔄 Add bundle analysis

**Phase 3** (Q2 2025):
- 📋 Add service worker
- 📋 Implement asset preloading
- 📋 Optimize for Core Web Vitals

## Troubleshooting

### Common Issues

**Issue**: Assets not loading in production

**Solution**:
```bash
# Rebuild assets
npm run build

# Clear caches
php artisan optimize:clear

# Verify manifest
cat public/build/manifest.json
```

**Issue**: Hot reload not working in development

**Solution**:
```bash
# Restart Vite dev server
npm run dev

# Check Vite is running on http://localhost:5173
```

**Issue**: Alpine.js not working

**Solution**:
```bash
# Verify Alpine.js is installed
npm list alpinejs

# Rebuild assets
npm run build

# Check browser console for errors
```

## Related Documentation

- [Alpine.js Bundling](../frontend/ALPINE_BUNDLING.md)
- [Migration Guide](../updates/ALPINE_BUNDLING_MIGRATION.md)
- [Setup Guide](../guides/SETUP.md)
- [Vite Configuration](../../vite.config.js)

## References

- [Laravel Vite Documentation](https://laravel.com/docs/12.x/vite)
- [Vite Documentation](https://vitejs.dev/)
- [Alpine.js Documentation](https://alpinejs.dev/)
- [Chart.js Documentation](https://www.chartjs.org/)
- [Tailwind CSS Documentation](https://tailwindcss.com/)
