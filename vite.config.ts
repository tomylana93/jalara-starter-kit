import { execFileSync } from 'node:child_process';
import { isAbsolute, relative, resolve, sep } from 'node:path';

import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig, lazyPlugins, type Plugin } from 'vite-plus';

function laravelTranslations(): Plugin {
    let root = process.cwd();

    const exportTranslations = () => {
        execFileSync('php', ['artisan', 'lang:export', '--no-interaction'], {
            cwd: root,
            stdio: 'inherit',
        });
    };

    return {
        name: 'laravel-translations',
        configResolved(config) {
            root = config.root;
        },
        buildStart() {
            exportTranslations();
        },
        configureServer(server) {
            const sourcePath = resolve(root, 'lang');
            let exportTimer: ReturnType<typeof setTimeout> | undefined;

            server.watcher.add(resolve(sourcePath, '**/*.{php,json}'));

            const queueExport = (changedPath: string) => {
                const absolutePath = isAbsolute(changedPath)
                    ? changedPath
                    : resolve(root, changedPath);
                const sourceRelativePath = relative(sourcePath, absolutePath);

                if (
                    sourceRelativePath.startsWith(`..${sep}`) ||
                    !/\.(php|json)$/.test(sourceRelativePath)
                ) {
                    return;
                }

                clearTimeout(exportTimer);
                exportTimer = setTimeout(() => {
                    try {
                        exportTranslations();
                        server.ws.send({ type: 'full-reload' });
                    } catch {
                        server.config.logger.error(
                            'Failed to export Laravel translations.',
                        );
                    }
                }, 75);
            };

            server.watcher.on('add', queueExport);
            server.watcher.on('change', queueExport);
            server.watcher.on('unlink', queueExport);
        },
    };
}

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
        laravelTranslations(),
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
