import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/login.js',
                'resources/js/admin.js',
                'resources/js/home.js',
                'resources/js/homeusuarios.js',
                'resources/js/HomeEstadisticas.js',
                'resources/js/GestionCaballosModal.js',
                'resources/js/GestionCarrerasModal.js',
                'resources/js/GestionFormulariosModal.js',
                'resources/js/GestionFormulariosUsuariosModal.js',
            ],
            refresh: true,
        }),
        vue(),
    ],
});
