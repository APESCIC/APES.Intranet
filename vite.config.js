import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

// Set by compose.yaml when Vite runs in the node container.
const devHost = process.env.VITE_DEV_HOST;

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
        watch: {
            ignored: ['**/storage/framework/views/**'],
            // File events do not cross a Windows bind mount into the container.
            usePolling: Boolean(devHost),
            interval: 300,
        },
        ...(devHost ? {
            host: '0.0.0.0',
            port: Number(process.env.VITE_PORT ?? 5173),
            strictPort: true,
            allowedHosts: [devHost],
            hmr: { host: devHost },
        } : {}),
    },
});
