<?php

function translationPlaceholders(mixed $translation): array
{
    if (is_array($translation)) {
        return collect($translation)
            ->flatMap(fn (mixed $line): array => translationPlaceholders($line))
            ->values()
            ->all();
    }

    preg_match_all('/:[a-z_]+/', $translation, $matches);

    return $matches[0];
}

it('provides complete Indonesian translations with matching placeholders', function (string $file): void {
    $english = require lang_path("en/{$file}.php");
    $indonesian = require lang_path("id/{$file}.php");

    expect(array_keys($indonesian))
        ->toBe(array_keys($english))
        ->and(translationPlaceholders($indonesian))
        ->toEqualCanonicalizing(translationPlaceholders($english));
})->with([
    'authentication' => 'auth',
    'pagination' => 'pagination',
    'password reset' => 'passwords',
    'validation' => 'validation',
]);

it('loads Indonesian language lines through the translator', function (): void {
    app()->setLocale('id');

    expect(__('auth.failed'))
        ->toBe('Email atau password tidak sesuai.')
        ->and(__('validation.required', ['attribute' => 'email']))
        ->toBe('email wajib diisi.');
});

it('keeps user-facing translations free from direct address', function (string $locale, string $directAddress): void {
    $translations = collect(['auth', 'pagination', 'passwords', 'validation'])
        ->mapWithKeys(fn (string $file): array => [$file => require lang_path("{$locale}/{$file}.php")])
        ->toJson();

    expect($translations)->not->toMatch($directAddress);
})->with([
    'English' => ['en', '/\b(?:you|your|we|our|us)\b/i'],
    'Indonesian' => ['id', '/\b(?:kamu|anda|kami|kita)\b/i'],
]);
