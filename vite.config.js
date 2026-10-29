import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/chat.js',
                'resources/js/pdf-viewer.js',
                'resources/js/charts.js',
                'resources/js/kanban-vendor.js',
            ],
            refresh: true,
        }),
    ],
});
