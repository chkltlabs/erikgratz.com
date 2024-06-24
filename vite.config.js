import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
// import react from '@vitejs/plugin-react';
import vue from '@vitejs/plugin-vue';
import tailwindcss from 'tailwindcss'

export default defineConfig({
    css: {
        postcss: {
            map: true,
            plugins: [tailwindcss()]
        }
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/sass/app.scss',
                'public/css/filament/filament/app.css',
                'resources/js/Filament/filament-chart-plugins.js'
            ],
            refresh: true,
        }),
        // react(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js'
        }
    }
});
