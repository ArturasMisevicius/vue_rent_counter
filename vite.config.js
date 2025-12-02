import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

/**
 * Vite configuration for Vilnius Utilities Billing System
 * 
 * Asset Strategy:
 * - Blade views use CDN-based Alpine.js and Tailwind CSS 4.x
 * - Filament handles its own asset compilation
 * - Custom assets (app.css, app.js) available for future needs if required
 * 
 * To activate compiled assets:
 * 1. Add @vite(['resources/css/app.css', 'resources/js/app.js']) to layouts
 * 2. Run `npm run dev` for development or `npm run build` for production
 * 3. Remove CDN script/link tags from layout files
 */
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
