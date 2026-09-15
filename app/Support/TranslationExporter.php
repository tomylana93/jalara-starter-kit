<?php

namespace App\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;

class TranslationExporter
{
    public function __construct(private readonly Filesystem $files) {}

    /**
     * @return array{locales: list<string>, messages: int}
     */
    public function export(string $sourcePath, string $outputPath, string $fallbackLocale): array
    {
        $locales = $this->locales($sourcePath);

        throw_if($locales === [], InvalidArgumentException::class, "No translations were found in [{$sourcePath}].");

        throw_unless(in_array($fallbackLocale, $locales, true), InvalidArgumentException::class, "Fallback locale [{$fallbackLocale}] was not found in [{$sourcePath}].");

        $messages = [];
        $parameters = [];

        foreach ($locales as $locale) {
            $messages[$locale] = $this->messagesForLocale($sourcePath, $locale);
            $parameters[$locale] = $this->parameterMap($messages[$locale]);
        }

        $this->assertMatchingKeys($messages, $fallbackLocale);
        $this->assertMatchingParameters($parameters, $fallbackLocale);
        $this->files->ensureDirectoryExists($outputPath);
        $this->deleteStaleLocaleFiles($outputPath, $locales);

        foreach ($messages as $locale => $localeMessages) {
            $this->writeJson("{$outputPath}/{$locale}.json", $localeMessages);
        }

        $this->files->replace(
            "{$outputPath}/messages.ts",
            $this->typescript($locales, $messages[$fallbackLocale], $parameters[$fallbackLocale], $fallbackLocale),
        );

        return [
            'locales' => $locales,
            'messages' => count(Arr::dot($messages[$fallbackLocale])),
        ];
    }

    /** @return list<string> */
    private function locales(string $sourcePath): array
    {
        if (! $this->files->isDirectory($sourcePath)) {
            return [];
        }

        $locales = collect($this->files->directories($sourcePath))
            ->map(fn (string $path): string => basename($path))
            ->merge(collect($this->files->glob("{$sourcePath}/*.json"))
                ->map(fn (string $path): string => pathinfo($path, PATHINFO_FILENAME)))
            ->unique()
            ->sort()
            ->values()
            ->all();

        return array_values($locales);
    }

    /** @return array<string, mixed> */
    private function messagesForLocale(string $sourcePath, string $locale): array
    {
        $messages = [];
        $localePath = "{$sourcePath}/{$locale}";

        if ($this->files->isDirectory($localePath)) {
            foreach ($this->files->files($localePath) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $group = $file->getFilenameWithoutExtension();
                $translations = require $file->getPathname();

                if (! is_array($translations)) {
                    throw new InvalidArgumentException("Translation file [{$file->getPathname()}] must return an array.");
                }

                $messages[$group] = $this->normalize($translations, "{$locale}.{$group}");
            }
        }

        $jsonPath = "{$sourcePath}/{$locale}.json";

        if ($this->files->exists($jsonPath)) {
            try {
                $jsonMessages = json_decode($this->files->get($jsonPath), true, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new InvalidArgumentException("Translation file [{$jsonPath}] contains invalid JSON: {$exception->getMessage()}", $exception->getCode(), previous: $exception);
            }

            throw_unless(is_array($jsonMessages), InvalidArgumentException::class, "Translation file [{$jsonPath}] must contain a JSON object.");

            foreach ($jsonMessages as $key => $value) {
                throw_if(array_key_exists($key, $messages), InvalidArgumentException::class, "Translation key [{$locale}.{$key}] is defined by both PHP and JSON sources.");

                $messages[$key] = $this->normalize($value, "{$locale}.{$key}");
            }
        }

        ksort($messages);

        return $messages;
    }

    private function normalize(mixed $value, string $key): mixed
    {
        if (is_string($value)) {
            return preg_replace('/(?<!:):([A-Za-z_][A-Za-z0-9_]*)/u', '{$1}', $value);
        }

        throw_if(! is_array($value) || ($value !== [] && array_is_list($value)), InvalidArgumentException::class, "Translation [{$key}] must be a string or an associative array.");

        $normalized = [];

        foreach ($value as $childKey => $childValue) {
            $normalizedChild = $this->normalize($childValue, "{$key}.{$childKey}");

            if ($normalizedChild !== []) {
                $normalized[$childKey] = $normalizedChild;
            }
        }

        ksort($normalized);

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $messages
     * @return array<string, list<string>>
     */
    private function parameterMap(array $messages): array
    {
        return collect(Arr::dot($messages))
            ->mapWithKeys(function (mixed $message, string $key): array {
                preg_match_all('/\{([A-Za-z_][A-Za-z0-9_]*)\}/u', (string) $message, $matches);

                $parameters = collect($matches[1])->unique()->sort()->values()->all();

                return [$key => array_values($parameters)];
            })
            ->sortKeys()
            ->all();
    }

    /** @param list<string> $locales */
    private function deleteStaleLocaleFiles(string $outputPath, array $locales): void
    {
        foreach ($this->files->glob("{$outputPath}/*.json") as $path) {
            if (! in_array(pathinfo($path, PATHINFO_FILENAME), $locales, true)) {
                $this->files->delete($path);
            }
        }
    }

    /** @param array<string, array<string, mixed>> $messages */
    private function assertMatchingKeys(array $messages, string $fallbackLocale): void
    {
        $fallbackKeys = array_keys(Arr::dot($messages[$fallbackLocale]));
        sort($fallbackKeys);

        foreach ($messages as $locale => $localeMessages) {
            $keys = array_keys(Arr::dot($localeMessages));
            sort($keys);

            if ($keys === $fallbackKeys) {
                continue;
            }

            $missing = array_values(array_diff($fallbackKeys, $keys));
            $extra = array_values(array_diff($keys, $fallbackKeys));

            throw new InvalidArgumentException($this->mismatchMessage($locale, $missing, $extra));
        }
    }

    /** @param array<string, array<string, list<string>>> $parameters */
    private function assertMatchingParameters(array $parameters, string $fallbackLocale): void
    {
        foreach ($parameters as $locale => $localeParameters) {
            foreach ($parameters[$fallbackLocale] as $key => $expected) {
                $actual = $localeParameters[$key];

                if ($actual !== $expected) {
                    throw new InvalidArgumentException(
                        "Translation placeholders for [{$key}] in locale [{$locale}] must match [{$fallbackLocale}]. Expected [".
                        implode(', ', $expected).'], found ['.implode(', ', $actual).'].'
                    );
                }
            }
        }
    }

    /**
     * @param  list<string>  $missing
     * @param  list<string>  $extra
     */
    private function mismatchMessage(string $locale, array $missing, array $extra): string
    {
        $details = [];

        if ($missing !== []) {
            $details[] = 'missing ['.implode(', ', $missing).']';
        }

        if ($extra !== []) {
            $details[] = 'extra ['.implode(', ', $extra).']';
        }

        return "Translation keys for locale [{$locale}] do not match the fallback locale: ".implode('; ', $details).'.';
    }

    /** @param array<string, mixed> $messages */
    private function writeJson(string $path, array $messages): void
    {
        $json = json_encode(
            $messages,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );

        $this->files->replace($path, $json.PHP_EOL);
    }

    /**
     * @param  list<string>  $locales
     * @param  array<string, mixed>  $fallbackMessages
     * @param  array<string, list<string>>  $parameters
     */
    private function typescript(array $locales, array $fallbackMessages, array $parameters, string $fallbackLocale): string
    {
        $imports = collect($locales)
            ->map(fn (string $locale): string => "import {$this->localeIdentifier($locale)} from './{$locale}.json';")
            ->implode(PHP_EOL);
        $messageEntries = collect($locales)
            ->map(fn (string $locale): string => '    '.$this->typescriptString($locale).": {$this->localeIdentifier($locale)},")
            ->implode(PHP_EOL);
        $parameterEntries = collect($parameters)
            ->map(function (array $names, string $key): string {
                $type = $names === []
                    ? 'Record<string, never>'
                    : '{ '.collect($names)->map(fn (string $name): string => "'{$name}': string | number")->implode('; ').' }';

                return '    '.$this->typescriptString($key).": {$type};";
            })
            ->implode(PHP_EOL);
        $fallbackIdentifier = $this->localeIdentifier($fallbackLocale);
        $messageCount = count(Arr::dot($fallbackMessages));
        $fallbackLocaleLiteral = $this->typescriptString($fallbackLocale);

        return <<<TYPESCRIPT
        {$imports}

        export const messages = {
        {$messageEntries}
        } as const;

        export type AppLocale = keyof typeof messages;
        export type MessageSchema = typeof {$fallbackIdentifier};
        export type MessageKey = keyof MessageParameters;

        export type MessageParameters = {
        {$parameterEntries}
        };

        export const translationMetadata = {
            fallbackLocale: {$fallbackLocaleLiteral},
            messageCount: {$messageCount},
        } as const;
        TYPESCRIPT;
    }

    private function localeIdentifier(string $locale): string
    {
        return 'locale'.Str::studly(str_replace('-', '_', $locale));
    }

    private function typescriptString(string $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
