import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                `resources/css/filament/sms/theme.css`,
                `resources/css/filament/agent/theme.css`
            ],
            refresh: true,
        }),
    ],
});
