import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    server:{
        port: 5174
    },
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/filament.css',
                'resources/js/livewire.js',
                'resources/sass/app.scss',
                'public/css/filament/filament/app.css',
                'resources/js/Filament/filament-chart-plugins.js',
                'resources/js/Filament/activity-map.js',
                'resources/css/Filament/activity-map.css',
                'resources/css/filament/admin/theme.css'
            ],
            refresh: true,
        }),
    ],
});
