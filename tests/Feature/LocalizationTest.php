<?php

use Illuminate\Support\Arr;
use Inertia\Testing\AssertableInertia as Assert;

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
        ->and(array_keys(Arr::dot($indonesian)))
        ->toBe(array_keys(Arr::dot($english)))
        ->and(translationPlaceholders($indonesian))
        ->toEqualCanonicalizing(translationPlaceholders($english));
})->with([
    'account' => 'account',
    'authentication' => 'auth',
    'common' => 'common',
    'console' => 'console',
    'dashboard' => 'dashboard',
    'navigation' => 'navigation',
    'pagination' => 'pagination',
    'password reset' => 'passwords',
    'setting' => 'setting',
    'system' => 'system',
    'user' => 'user',
    'validation' => 'validation',
]);

it('loads Indonesian language lines through the translator', function (): void {
    app()->setLocale('id');

    expect(__('auth.failed'))
        ->toBe('Email atau password tidak sesuai.')
        ->and(__('validation.required', ['attribute' => 'email']))
        ->toBe('email wajib diisi.');
});

it('shares the active and fallback locales with Inertia', function (): void {
    app()->setLocale('id');

    $this->get(route('login'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('locale', 'id')
            ->where('fallbackLocale', 'en'),
        );
});

it('keeps user-facing translations free from direct address', function (string $locale, string $directAddress): void {
    $translations = collect([
        'account',
        'auth',
        'common',
        'console',
        'dashboard',
        'navigation',
        'pagination',
        'passwords',
        'setting',
        'system',
        'user',
        'validation',
    ])
        ->mapWithKeys(fn (string $file): array => [$file => require lang_path("{$locale}/{$file}.php")])
        ->toJson();

    expect($translations)->not->toMatch($directAddress);
})->with([
    'English' => ['en', '/\b(?:you|your|we|our|us)\b/i'],
    'Indonesian' => ['id', '/\b(?:kamu|anda|kami|kita)\b/i'],
]);
