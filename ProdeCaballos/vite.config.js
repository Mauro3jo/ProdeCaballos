import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/login.js',     // ← entrada del login
                'resources/js/admin.js',     // ← entrada del admin (si lo usás)
                'resources/js/home.js',      // ← entrada para Home.vue si aplica
            ],
            refresh: true,
        }),
        vue(),
    ],
});
