# Frontend Architecture

## Overview

This application uses a **CDN-based frontend architecture** with minimal build requirements. The approach prioritizes simplicity and fast development cycles.

## Technology Stack

### Alpine.js (CDN)
- **Version**: 3.x (latest)
- **Source**: `https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js`
- **Usage**: Reactive UI components in Blade templates
- **No build step required**

### Tailwind CSS (CDN)
- **Source**: `https://cdn.tailwindcss.com`
- **Usage**: Utility-first CSS styling
- **No build step required**

### Axios
- **Version**: ^1.6.4
- **Usage**: HTTP client for AJAX requests
- **Loaded**: Via `resources/js/bootstrap.js`

## Filament Admin Panel

Filament compiles and serves its own assets independently. No additional configuration is required.

## Build System

### Vite Configuration
The `vite.config.js` is kept minimal for potential future custom asset compilation needs. Currently, no assets are compiled through Vite.

### Package.json
Contains only essential dependencies:
- `axios`: HTTP client
- `vite`: Build tool (for future use)
- `laravel-vite-plugin`: Laravel integration (for future use)

### No Build Scripts
Since all assets are loaded via CDN, there are no npm build scripts. The application runs without any frontend build step.

## Development Workflow

1. **No build step required** - Changes to Blade templates are immediately visible
2. **Alpine.js** - Add reactive behavior directly in Blade templates using `x-data`, `x-show`, etc.
3. **Tailwind CSS** - Use utility classes directly in HTML
4. **Axios** - Available globally as `window.axios` for AJAX requests

## File Structure

```
resources/
├── js/
│   ├── app.js          # Entry point (imports bootstrap)
│   └── bootstrap.js    # Axios configuration
└── views/
    ├── layouts/
    │   └── app.blade.php  # Main layout with CDN scripts
    └── ...
```

## Why CDN-Based?

1. **Simplicity**: No build step means faster development
2. **Performance**: CDN assets are cached and served from edge locations
3. **Maintenance**: No need to manage npm dependencies for frontend frameworks
4. **Compatibility**: Works seamlessly with Filament's asset compilation
5. **Development Speed**: Changes are immediately visible without rebuilding

## Future Considerations

If custom asset compilation becomes necessary:
1. Add input files to `vite.config.js`
2. Add build scripts to `package.json`
3. Use `@vite` directive in Blade templates
4. Run `npm run build` for production

For now, the CDN-based approach meets all requirements.
