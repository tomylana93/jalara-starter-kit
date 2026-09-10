import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { execFile } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { promisify } from 'node:util';
import type { Plugin } from 'vite';
import { defineConfig, lazyPlugins } from 'vite-plus';

const execFileAsync = promisify(execFile);
const artisan = fileURLToPath(new URL('./artisan', import.meta.url));

/**
 * Runs `php artisan i18n:bundle` once before every build and dev-server
 * start, and re-runs it while serving whenever a language file or the
 * i18n config changes. A failing bundle fails the build instead of
 * shipping stale translations.
 */
function i18nBundle(): Plugin {
    let started = false;
    let debounce: ReturnType<typeof setTimeout> | undefined;

    const bundle = async (): Promise<void> => {
        await execFileAsync('php', [
            artisan,
            'i18n:bundle',
            '--no-interaction',
        ]);
    };

    // Chokidar reports project files either as absolute paths or as paths
    // relative to the project root, so match both shapes here.
    const isI18nSource = (file: string): boolean => {
        const path = file.replace(/\\/g, '/');

        return (
            path.endsWith('config/i18n.php') ||
            (/(^|\/)lang\//.test(path) && path.endsWith('.php'))
        );
    };

    return {
        name: 'i18n-bundle',
        async buildStart(): Promise<void> {
            if (started) {
                return;
            }

            started = true;
            await bundle();
        },
        configureServer(server): void {
            server.watcher.add(['config/i18n.php', 'lang/**/*.php']);
            server.watcher.on('all', (event: string, file: string) => {
                if (
                    !['add', 'change', 'unlink'].includes(event) ||
                    !isI18nSource(file)
                ) {
                    return;
                }

                if (debounce !== undefined) {
                    clearTimeout(debounce);
                }

                debounce = setTimeout(() => {
                    void bundle()
                        .then(() => {
                            server.ws.send({ type: 'full-reload' });
                        })
                        .catch((error: unknown) => {
                            const message =
                                error instanceof Error
                                    ? error.message
                                    : String(error);

                            server.config.logger.error(
                                `[i18n-bundle] ${message}`,
                            );
                        });
                }, 150);
            });
        },
    };
}

export default defineConfig({
    plugins: lazyPlugins(() => [
        i18nBundle(),
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
            'resources/js/components/ui/*',
            'resources/js/routes/**',
            'resources/js/wayfinder/**',
        ],
        options: {
            denyWarnings: true,
            typeAware: true,
        },
    },
    fmt: {
        printWidth: 80,
        tabWidth: 4,
        singleQuote: true,
        semi: true,
        singleAttributePerLine: false,
        htmlWhitespaceSensitivity: 'css',
        ignorePatterns: [
            '.github/**',
            'skills-lock.json',
            'composer.json',
            'resources/js/components/ui/*',
            'resources/views/mail/*',
            'AGENTS.md',
            'CLAUDE.md',
            'boost.json',
            'opencode.json',
            'opencode.jsonc',
            '.mcp.json',
            '.agents/**',
            '.claude/**',
            '.codex/**',
            '.ai/**',
            '.serena/**',
        ],
        sortTailwindcss: {
            functions: ['clsx', 'cn', 'cva'],
            entryPoint: 'resources/css/app.css',
        },
    },
});
