<?php

declare(strict_types=1);

namespace App\Actions\I18n;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use LogicException;

class BuildTranslationBundles
{
    /**
     * Build all bundles from the `i18n` configuration.
     *
     * Requires `i18n.locales`, `i18n.domains` and `i18n.scopes` to be lists of
     * strings, and `i18n.source_locale` to be a string included in the locales.
     *
     * @return array<string, string> file path => contents
     */
    public function files(): array
    {
        $locales = $this->stringListConfig('locales');
        $domains = $this->stringListConfig('domains');
        $scopes = $this->stringListConfig('scopes');
        $sourceLocale = $this->stringConfig('source_locale');

        $bundles = [];

        foreach ($locales as $locale) {
            $bundles[$locale] = $this->bundleForLocale($locale, $domains, $scopes);
        }

        throw_unless(array_key_exists($sourceLocale, $bundles), LogicException::class, "Source locale [{$sourceLocale}] must be included in i18n.locales.");

        $this->assertTranslationParity($bundles, $sourceLocale);

        $files = [];

        foreach ($bundles as $locale => $translations) {
            $files[resource_path("js/i18n/{$locale}.json")] = json_encode(
                (object) $translations,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            ).PHP_EOL;
        }

        $files[resource_path('js/types/i18n.d.ts')] = $this->typeDeclaration(
            array_keys($bundles[$sourceLocale]),
            $locales,
        );

        return $files;
    }

    /**
     * @param  list<string>  $domains
     * @param  list<string>  $scopes
     * @return array<string, string>
     */
    public function bundleForLocale(string $locale, array $domains, array $scopes): array
    {
        $translations = [];

        foreach ($domains as $domain) {
            $lines = Lang::get($domain, [], $locale, false);

            throw_unless(
                is_array($lines),
                LogicException::class,
                "Frontend translation domain [{$domain}] does not exist for locale [{$locale}].",
            );

            foreach (Arr::dot($lines) as $key => $value) {
                if (! is_string($value) || ! in_array(Str::before($key, '.'), $scopes, true)) {
                    continue;
                }

                throw_if(Str::contains($value, '|'), LogicException::class, "Frontend translation [{$domain}.{$key}] cannot use pluralization.");

                $translations["{$domain}.{$key}"] = $value;
            }
        }

        ksort($translations);

        return $translations;
    }

    /** @param array<string, array<string, string>> $bundles */
    private function assertTranslationParity(array $bundles, string $sourceLocale): void
    {
        $sourceKeys = array_keys($bundles[$sourceLocale]);

        foreach ($bundles as $locale => $translations) {
            $localeKeys = array_keys($translations);
            $missing = array_diff($sourceKeys, $localeKeys);
            $unexpected = array_diff($localeKeys, $sourceKeys);

            if ($missing === [] && $unexpected === []) {
                continue;
            }

            $missingKeys = $missing === [] ? 'none' : implode(', ', $missing);
            $unexpectedKeys = $unexpected === [] ? 'none' : implode(', ', $unexpected);

            throw new LogicException(
                "Frontend translation keys for locale [{$locale}] do not match source locale [{$sourceLocale}]. "
                ."Missing: {$missingKeys}. Unexpected: {$unexpectedKeys}.",
            );
        }
    }

    /**
     * @param  list<string>  $keys
     * @param  list<string>  $locales
     */
    private function typeDeclaration(array $keys, array $locales): string
    {
        $localeMembers = array_map(
            fn (string $locale): string => '    | '.json_encode($locale, JSON_THROW_ON_ERROR),
            $locales,
        );
        $localeDeclaration = "export type SupportedLocale =\n".implode("\n", $localeMembers).";\n\n";

        if ($keys === []) {
            return $localeDeclaration."export type TranslationKey = never;\n";
        }

        $members = array_map(
            fn (string $key): string => '    | '.json_encode($key, JSON_THROW_ON_ERROR),
            $keys,
        );

        return $localeDeclaration."export type TranslationKey =\n".implode("\n", $members).";\n";
    }

    /** @return list<string> */
    private function stringListConfig(string $key): array
    {
        $value = config("i18n.{$key}");

        throw_if(! is_array($value) || ! array_is_list($value), LogicException::class, "Configuration [i18n.{$key}] must be a list of strings.");

        $strings = [];

        foreach ($value as $item) {
            throw_unless(is_string($item), LogicException::class, "Configuration [i18n.{$key}] must be a list of strings.");

            $strings[] = $item;
        }

        return $strings;
    }

    private function stringConfig(string $key): string
    {
        $value = config("i18n.{$key}");

        throw_unless(is_string($value), LogicException::class, "Configuration [i18n.{$key}] must be a string.");

        return $value;
    }
}
