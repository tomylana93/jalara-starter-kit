import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vitest/config';
import { laravelLang } from './vite/plugins/laravel-lang';

export default defineConfig({
    resolve: {
        alias: {
            '@': new URL('./resources/js', import.meta.url).pathname,
        },
    },
    test: {
        environment: 'jsdom',
        setupFiles: ['./resources/js/test/setup.ts'],
        include: ['resources/js/**/*.test.ts', 'vite/**/*.test.ts'],
        exclude: ['e2e/**'],
    },
    plugins: [
        laravelLang(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    optimizedFallbacks: false,
                    weights: [400, 500, 600, 700],
                }),
                bunny('Inter', {
                    optimizedFallbacks: false,
                    preload: false,
                    weights: [400, 500, 600],
                }),
                bunny('Space Grotesk', {
                    optimizedFallbacks: false,
                    preload: false,
                    weights: [500, 600, 700],
                }),
                bunny('Poppins', {
                    optimizedFallbacks: false,
                    preload: false,
                    weights: [500, 600, 700],
                }),
                bunny('Montserrat', {
                    optimizedFallbacks: false,
                    preload: false,
                    weights: [500, 600, 700],
                }),
                bunny('Open Sans', {
                    optimizedFallbacks: false,
                    preload: false,
                    weights: [400, 500, 600],
                }),
                bunny('Playfair Display', {
                    optimizedFallbacks: false,
                    preload: false,
                    weights: [500, 600, 700],
                }),
                bunny('Source Sans 3', {
                    optimizedFallbacks: false,
                    preload: false,
                    weights: [400, 500, 600],
                }),
            ],
        }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
    ],
});
