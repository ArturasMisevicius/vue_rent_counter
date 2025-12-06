# Alpine.js Bundling Migration

**Date**: 2024-12-06  
**Type**: Asset Strategy Change  
**Impact**: Required for all deployments

## Summary

Alpine.js has been migrated from CDN delivery to Vite bundling for improved performance, better caching, and version control.

## What Changed

### Before
```html
<!-- Alpine.js loaded from CDN -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
```

### After
```javascript
// Alpine.js bundled via Vite (resources/js/app.js)
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();
```

## Benefits

1. **Performance**: Reduced external HTTP requests, better caching
2. **Reliability**: No dependency on external CDN availability
3. **Version Control**: Locked to specific Alpine.js version (3.14.0)
4. **Bundle Optimization**: Tree-shaking and minification
5. **Offline Capability**: Works without internet connection

## Required Actions

### For Development

```bash
# Install dependencies
npm install

# Run development server (with hot reload)
npm run dev
```

Keep `npm run dev` running in a separate terminal while developing.

### For Production

```bash
# Install dependencies
npm ci --production

# Build optimized assets (REQUIRED)
npm run build
```

**Critical**: Production deployments will fail without running `npm run build`.

## Affected Components

The following components rely on Alpine.js and require compiled assets:

- **Mobile Navigation**: Toggle menu (`x-data="{ mobileMenuOpen: false }"`)
- **Flash Messages**: Auto-dismiss notifications (`x-data="{ show: true }"`)
- **Tenant Meter Readings**: Consumption history charts
- **Interactive Forms**: Dynamic field visibility
- **Dropdown Menus**: Locale switcher and user menus

## Verification

### Check if Assets are Built

```bash
# Check for compiled assets
ls -la public/build/

# Should see files like:
# - manifest.json
# - assets/app-[hash].js
# - assets/app-[hash].css
```

### Test in Browser

1. Open browser developer tools (F12)
2. Check Console for errors
3. Verify Alpine.js is loaded: `window.Alpine` should be defined
4. Test mobile menu toggle functionality

## Troubleshooting

### Issue: "Alpine is not defined"

**Cause**: Assets not compiled or not loaded correctly

**Solution**:
```bash
# Clear caches
php artisan optimize:clear

# Rebuild assets
npm run build

# Verify Vite manifest exists
cat public/build/manifest.json
```

### Issue: Mobile menu not working

**Cause**: Alpine.js not initialized

**Solution**:
1. Check browser console for JavaScript errors
2. Verify `@vite(['resources/css/app.css', 'resources/js/app.js'])` is in layout
3. Ensure `npm run build` completed successfully

### Issue: Hot reload not working in development

**Cause**: Vite dev server not running

**Solution**:
```bash
# Start Vite dev server
npm run dev

# Should see:
# VITE v5.x.x  ready in XXX ms
# ➜  Local:   http://localhost:5173/
```

## Deployment Checklist

- [ ] Run `npm ci --production` on production server
- [ ] Run `npm run build` to compile assets
- [ ] Verify `public/build/` directory exists and contains assets
- [ ] Clear Laravel caches: `php artisan optimize:clear`
- [ ] Test mobile navigation functionality
- [ ] Test flash message auto-dismiss
- [ ] Verify no console errors in browser

## Rollback Plan

If issues occur, you can temporarily rollback by:

1. Restore CDN script tag in `resources/views/layouts/app.blade.php`:
```html
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
```

2. Remove Alpine import from `resources/js/app.js`

**Note**: This is a temporary measure. The bundled approach is the recommended long-term solution.

## Related Files

- `resources/views/layouts/app.blade.php` - Layout file using Alpine directives
- `resources/js/app.js` - Alpine.js initialization
- `package.json` - Alpine.js dependency (v3.14.0)
- `vite.config.js` - Vite configuration
- `docs/guides/SETUP.md` - Updated setup instructions

## References

- [Alpine.js Documentation](https://alpinejs.dev/)
- [Laravel Vite Documentation](https://laravel.com/docs/12.x/vite)
- [Vite Documentation](https://vitejs.dev/)
