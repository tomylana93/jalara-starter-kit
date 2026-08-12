import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vitest/config';
import { resolveAssetOutput } from './vite/asset-output.ts';
import { laravelLang } from './vite/plugins/laravel-lang.ts';

const assetOutput = resolveAssetOutput(process.env);

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
        /*
         * No timezone is configured here on purpose. A globally pinned zone
         * hides the dependency from the tests that have one, and only applies
         * under the `forks` pool, so it silently stopped working when the pool
         * changed. Tests that render browser-local instants pin the zone
         * themselves through `withTimeZone` in `resources/js/test/timeZone.ts`.
         */
    },
    plugins: [
        laravelLang(),
        laravel({
            input: [
                'resources/css/app.css',
                /* Print-only, inlined into the PDF blade views at render time
                   rather than linked, so Chromium never has to fetch it. */
                'resources/css/pdf.css',
                'resources/js/app.ts',
            ],
            refresh: true,
            buildDirectory: assetOutput.buildDirectory,
            hotFile: assetOutput.hotFile,
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
