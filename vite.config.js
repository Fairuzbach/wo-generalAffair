import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    server:{
        host:'0.0.0.0',
        port:5173,
        hmr: {
            host: '192.168.24.88',
        }
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 
                'resources/js/app.js',
                'resources/css/general-affair.css',
                'resources/js/general-affair.js',
                'resources/js/dashboard.js',
                'resources/css/dashboard.css'
            ],
            refresh: true,
        }),
    ],
});
