import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

/**
 * Vite configuration for Vilnius Utilities Billing System
 * 
 * Asset Strategy:
 * - Alpine.js (v3.14.0) bundled via Vite for optimal performance and reliability
 * - Tailwind CSS 4.x loaded via CDN for rapid prototyping
 * - Filament handles its own asset compilation
 * - Chart.js bundled for dashboard visualizations
 * 
 * Build Commands:
 * - Development: `npm run dev` (with hot reload)
 * - Production: `npm run build` (optimized bundle)
 * 
 * Alpine.js Bundling Benefits:
 * - Reduced external HTTP requests (no CDN dependency)
 * - Better caching with versioned filenames
 * - Tree-shaking removes unused code
 * - Works offline without internet connection
 * - Version control prevents unexpected breaking changes
 * 
 * Required for Interactive Components:
 * - Mobile navigation menu toggle
 * - Flash message auto-dismiss
 * - Locale switcher interactions
 * - Dynamic form fields
 * - Dropdown menus
 * 
 * @see docs/updates/ALPINE_BUNDLING_MIGRATION.md for migration details
 * @see docs/refactoring/LAYOUT_ALPINE_REFACTORING_SUMMARY.md for implementation notes
 */
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
