import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/css/components.css', 'resources/css/component-motion.css', 'resources/css/admin-components.css', 'resources/js/app.js', 'resources/js/admin-scan.js', 'resources/js/admin.js', 'resources/js/interactions.js'],
            refresh: true,
        }),
    ],
});
