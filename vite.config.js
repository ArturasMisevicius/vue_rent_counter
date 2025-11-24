import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

/**
 * Vite 5.x configuration for Vilnius Utilities Billing System
 * 
 * Current Status:
 * - Vite 5.x with laravel-vite-plugin 1.x
 * - Configuration follows latest Vite conventions
 * - Input files prepared but not actively used (CDN assets preferred)
 * - Filament handles its own asset compilation
 * 
 * Asset Strategy:
 * - Blade views use CDN-based Alpine.js and Tailwind CSS 4.x
 * - Filament resources use framework-provided assets
 * - Custom assets (app.css, app.js) available for future needs
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
    
    // Build configuration optimized for Laravel
    build: {
        // Generate manifest for Laravel integration
        manifest: 'manifest.json',
        // Output directory (default: public/build)
        outDir: 'public/build',
        // Clean output directory before build
        emptyOutDir: true,
        // Rollup options for optimized builds
        rollupOptions: {
            output: {
                // Manual chunk splitting for better caching
                manualChunks: undefined,
            },
        },
    },
    
    // Server configuration for development
    server: {
        // HMR configuration
        hmr: {
            host: 'localhost',
        },
        // Watch options for better performance
        watch: {
            usePolling: false,
        },
    },
});
