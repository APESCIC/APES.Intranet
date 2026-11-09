import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js',
                'resources/js/editor/tiptap.js',
                'resources/css/editor.css'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        // 5174, so it does not clash with other projects' Vite on 5173.
        port: 5174,
        strictPort: true,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
