import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    base: '/',
    server: {
        // Tambahkan ini untuk mengizinkan CORS dari semua origin saat dev
        cors: true,
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(),
        VitePWA({
            strategies: 'generateSW',
            registerType: 'autoUpdate',
            devOptions: {
                enabled: true
            },
            // Kita kosongkan manifest di sini karena sudah pakai file fisik di public/
            manifest: false,
        })
    ],
});