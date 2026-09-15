import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig, lazyPlugins } from 'vite-plus';

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
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
    ]),
    server: {
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/vendor/**',
            ],
        },
    },
    lint: {
        ignorePatterns: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'bootstrap/ssr/**',
            'tailwind.config.js',
            'resources/js/actions/**',
            'resources/js/components/ui/**',
            'resources/js/routes/**',
            'resources/js/wayfinder/**',
        ],
        options: {
            denyWarnings: true,
            typeAware: true,
            typeCheck: true,
        },
    },
    fmt: {
        printWidth: 80,
        tabWidth: 4,
        singleQuote: true,
        semi: true,
        singleAttributePerLine: true,
        htmlWhitespaceSensitivity: 'ignore',
        sortImports: {
            customGroups: [
                {
                    groupName: 'ui',
                    elementNamePattern: ['@/components/ui/**'],
                },
                {
                    groupName: 'components',
                    elementNamePattern: ['@/components/**'],
                },
                {
                    groupName: 'layouts',
                    elementNamePattern: ['@/layouts/**'],
                },
                {
                    groupName: 'actions',
                    elementNamePattern: ['@/actions/**'],
                },
                {
                    groupName: 'routes',
                    elementNamePattern: ['@/routes', '@/routes/**'],
                },
                {
                    groupName: 'composables',
                    elementNamePattern: ['@/composables/**'],
                },
                {
                    groupName: 'lib',
                    elementNamePattern: ['@/lib/**'],
                },
                {
                    groupName: 'types',
                    elementNamePattern: ['@/types', '@/types/**'],
                },
                {
                    groupName: 'wayfinder',
                    elementNamePattern: ['@/wayfinder', '@/wayfinder/**'],
                },
            ],
            groups: [
                'builtin',
                'external',
                'ui',
                'components',
                'layouts',
                'actions',
                'routes',
                'composables',
                'lib',
                'types',
                'wayfinder',
                ['internal', 'subpath'],
                ['parent', 'sibling', 'index'],
                'style',
                'unknown',
            ],
        },
        ignorePatterns: [
            '.github/**',
            '.serena/**',
            'composer.json',
            'resources/js/actions/**',
            'resources/js/components/ui/**',
            'resources/js/routes/**',
            'resources/js/wayfinder/**',
            'resources/views/mail/*',
        ],
        sortTailwindcss: {
            functions: ['clsx', 'cn', 'cva'],
            entryPoint: 'resources/css/app.css',
        },
    },
});
