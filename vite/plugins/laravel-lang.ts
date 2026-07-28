import { execFile } from 'node:child_process';
import { mkdir, readdir, rename, rm, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { promisify } from 'node:util';
import type { Plugin, ResolvedConfig, ViteDevServer } from 'vite';

const execFileAsync = promisify(execFile);

const phpExporter = String.raw`
$sourcePath = $argv[1];
$translations = [];

$localePaths = glob($sourcePath.'/*', GLOB_ONLYDIR) ?: [];
sort($localePaths);

foreach ($localePaths as $localePath) {
    $files = glob($localePath.'/*.php') ?: [];
    sort($files);

    if ($files === []) {
        continue;
    }

    $locale = basename($localePath);
    $translations[$locale] = [];

    foreach ($files as $file) {
        $domain = pathinfo($file, PATHINFO_FILENAME);
        $lines = require $file;

        if (! is_array($lines)) {
            throw new UnexpectedValueException($file.' must return an array.');
        }

        $translations[$locale][$domain] = $lines;
    }
}

if ($translations === []) {
    throw new UnexpectedValueException('No PHP language files were found.');
}

echo json_encode(
    $translations,
    JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
);
`;

export type LaravelLangPluginOptions = {
    langPath?: string;
    outputPath?: string;
    phpBinary?: string;
};

type ResolvedLaravelLangOptions = {
    langPath: string;
    outputPath: string;
    phpBinary: string;
};

type TranslationValue =
    | string
    | number
    | boolean
    | null
    | TranslationValue[]
    | { [key: string]: TranslationValue };

type TranslationLocales = Record<string, Record<string, TranslationValue>>;

const resolveOptions = (
    root: string,
    options: LaravelLangPluginOptions,
): ResolvedLaravelLangOptions => ({
    langPath: path.resolve(root, options.langPath ?? 'lang'),
    outputPath: path.resolve(
        root,
        options.outputPath ?? 'resources/js/generated/lang',
    ),
    phpBinary: options.phpBinary ?? 'php',
});

const readTranslations = async (
    options: ResolvedLaravelLangOptions,
): Promise<TranslationLocales> => {
    const { stdout } = await execFileAsync(
        options.phpBinary,
        ['-r', phpExporter, options.langPath],
        {
            maxBuffer: 10 * 1024 * 1024,
        },
    );

    return JSON.parse(stdout) as TranslationLocales;
};

export const generateLaravelLangJson = async (
    options: ResolvedLaravelLangOptions,
): Promise<string[]> => {
    const translations = await readTranslations(options);
    const generatedFiles = Object.entries(translations).map(
        ([locale, domains]) => ({
            filename: `${locale}.json`,
            contents: `${JSON.stringify(domains, null, 4)}\n`,
        }),
    );

    await mkdir(options.outputPath, { recursive: true });

    for (const file of generatedFiles) {
        const targetPath = path.join(options.outputPath, file.filename);
        const temporaryPath = `${targetPath}.${process.pid}.tmp`;

        await writeFile(temporaryPath, file.contents, 'utf8');
        await rename(temporaryPath, targetPath);
    }

    const expectedFiles = new Set(generatedFiles.map((file) => file.filename));
    const outputEntries = await readdir(options.outputPath, {
        withFileTypes: true,
    });

    await Promise.all(
        outputEntries
            .filter(
                (entry) =>
                    entry.isFile() &&
                    entry.name.endsWith('.json') &&
                    !expectedFiles.has(entry.name),
            )
            .map((entry) => rm(path.join(options.outputPath, entry.name))),
    );

    return generatedFiles.map((file) =>
        path.join(options.outputPath, file.filename),
    );
};

const isPhpLanguageFile = (langPath: string, file: string): boolean => {
    const relativePath = path.relative(langPath, file);

    return (
        relativePath !== '' &&
        !relativePath.startsWith('..') &&
        !path.isAbsolute(relativePath) &&
        path.extname(file) === '.php'
    );
};

export const laravelLang = (options: LaravelLangPluginOptions = {}): Plugin => {
    let resolvedOptions: ResolvedLaravelLangOptions;
    let resolvedConfig: ResolvedConfig;
    let devServer: ViteDevServer | undefined;
    let isReady = false;
    let regenerationTimer: ReturnType<typeof setTimeout> | undefined;

    const regenerate = async (): Promise<void> => {
        try {
            await generateLaravelLangJson(resolvedOptions);
            resolvedConfig.logger.info('Laravel language JSON regenerated.');
            devServer?.ws.send({ type: 'full-reload' });
        } catch (error) {
            const generationError =
                error instanceof Error ? error : new Error(String(error));

            resolvedConfig.logger.error(
                `Laravel language JSON generation failed: ${generationError.message}`,
                { error: generationError },
            );
        }
    };

    const scheduleRegeneration = (file: string): void => {
        if (!isReady || !isPhpLanguageFile(resolvedOptions.langPath, file)) {
            return;
        }

        clearTimeout(regenerationTimer);
        regenerationTimer = setTimeout(() => {
            void regenerate();
        }, 50);
    };

    return {
        name: 'laravel-lang-json',
        enforce: 'pre',
        configResolved(config) {
            resolvedConfig = config;
            resolvedOptions = resolveOptions(config.root, options);
        },
        async buildStart() {
            try {
                await generateLaravelLangJson(resolvedOptions);

                this.addWatchFile(resolvedOptions.langPath);

                isReady = true;
            } catch (error) {
                const message =
                    error instanceof Error ? error.message : String(error);

                this.error(
                    `Laravel language JSON generation failed: ${message}`,
                );
            }
        },
        configureServer(server) {
            devServer = server;
            server.watcher.add(resolvedOptions.langPath);
            server.watcher.on('add', scheduleRegeneration);
            server.watcher.on('change', scheduleRegeneration);
            server.watcher.on('unlink', scheduleRegeneration);

            server.httpServer?.once('close', () => {
                clearTimeout(regenerationTimer);
                server.watcher.off('add', scheduleRegeneration);
                server.watcher.off('change', scheduleRegeneration);
                server.watcher.off('unlink', scheduleRegeneration);
            });
        },
    };
};
