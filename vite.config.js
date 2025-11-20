import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

/**
 * Vite configuration for Vilnius Utilities Billing System
 * 
 * Note: This project uses CDN-based assets (Alpine.js, Tailwind CSS) for the Blade views
 * and Filament handles its own asset compilation. This configuration is kept minimal
 * for potential future custom asset compilation needs.
 */
export default defineConfig({
    plugins: [
        laravel({
            input: [],
            refresh: true,
        }),
    ],
});
