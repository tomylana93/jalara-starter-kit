<?php

declare(strict_types=1);

use App\Actions\I18n\BuildTranslationBundles;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

pest()->extend(TestCase::class);

beforeEach(function (): void {
    Config::set('i18n.source_locale', 'en');
    Config::set('i18n.locales', ['en', 'id']);
    Config::set('i18n.domains', ['messages']);
    Config::set('i18n.scopes', ['label']);
});

it('bundles only translations in configured frontend scopes', function (): void {
    Lang::shouldReceive('get')
        ->once()
        ->with('user', [], 'en', false)
        ->andReturn([
            'auth' => [
                'status' => [
                    'disabled' => 'Disabled account message',
                ],
            ],
            'enum' => [
                'status' => [
                    'active' => 'Active',
                    'disabled' => 'Disabled',
                    'suspended' => 'Suspended',
                ],
            ],
        ]);

    $translations = (new BuildTranslationBundles)->bundleForLocale('en', ['user'], ['enum']);

    expect($translations)
        ->toHaveKey('user.enum.status.active', 'Active')
        ->not->toHaveKey('user.auth.status.disabled')
        ->and(array_keys($translations))->toBe([
            'user.enum.status.active',
            'user.enum.status.disabled',
            'user.enum.status.suspended',
        ]);
});

it('sorts bundled keys alphabetically', function (): void {
    Lang::shouldReceive('get')
        ->once()
        ->with('messages', [], 'en', false)
        ->andReturn([
            'label' => [
                'zebra' => 'Zebra',
                'mango' => 'Mango',
                'apple' => 'Apple',
            ],
        ]);

    $translations = (new BuildTranslationBundles)->bundleForLocale('en', ['messages'], ['label']);

    expect(array_keys($translations))->toBe([
        'messages.label.apple',
        'messages.label.mango',
        'messages.label.zebra',
    ]);
});

it('skips non-string translation values', function (): void {
    Lang::shouldReceive('get')
        ->once()
        ->with('messages', [], 'en', false)
        ->andReturn([
            'label' => [
                'name' => 'Name',
                'retry_after' => 5,
                'hint' => null,
            ],
        ]);

    $translations = (new BuildTranslationBundles)->bundleForLocale('en', ['messages'], ['label']);

    expect($translations)->toBe(['messages.label.name' => 'Name']);
});

it('merges translations from multiple domains', function (): void {
    Lang::shouldReceive('get')
        ->once()
        ->with('alpha', [], 'en', false)
        ->andReturn(['label' => ['zeta' => 'Zeta']]);
    Lang::shouldReceive('get')
        ->once()
        ->with('beta', [], 'en', false)
        ->andReturn(['label' => ['omega' => 'Omega']]);

    $translations = (new BuildTranslationBundles)->bundleForLocale('en', ['alpha', 'beta'], ['label']);

    expect($translations)->toBe([
        'alpha.label.zeta' => 'Zeta',
        'beta.label.omega' => 'Omega',
    ]);
});

it('rejects pluralized frontend translations', function (): void {
    Lang::shouldReceive('get')
        ->once()
        ->with('profile', [], 'en', false)
        ->andReturn([
            'description' => [
                'results' => 'One result|Many results',
            ],
        ]);

    expect(fn (): array => (new BuildTranslationBundles)->bundleForLocale('en', ['profile'], ['description']))
        ->toThrow(LogicException::class, 'Frontend translation [profile.description.results] cannot use pluralization.');
});

it('rejects configured domains that do not exist for a locale', function (mixed $lines): void {
    Lang::shouldReceive('get')
        ->once()
        ->with('messages', [], 'en', false)
        ->andReturn($lines);

    expect(fn (): array => (new BuildTranslationBundles)->bundleForLocale('en', ['messages'], ['label']))
        ->toThrow(LogicException::class, 'Frontend translation domain [messages] does not exist for locale [en].');
})->with([
    'unknown group' => ['missing'],
    'null group' => [null],
]);

it('returns locale bundles and the source locale type declaration', function (): void {
    Lang::shouldReceive('get')->once()->with('messages', [], 'en', false)->andReturn([
        'label' => ['name' => 'Name'],
    ]);
    Lang::shouldReceive('get')->once()->with('messages', [], 'id', false)->andReturn([
        'label' => ['name' => 'Nama'],
    ]);

    $files = (new BuildTranslationBundles)->files();

    expect($files)->toBe([
        resource_path('js/i18n/en.json') => "{\n    \"messages.label.name\": \"Name\"\n}\n",
        resource_path('js/i18n/id.json') => "{\n    \"messages.label.name\": \"Nama\"\n}\n",
        resource_path('js/types/i18n.d.ts') => "export type SupportedLocale =\n    | \"en\"\n    | \"id\";\n\nexport type TranslationKey =\n    | \"messages.label.name\";\n",
    ]);
});

it('returns empty object bundles when no translations match the scopes', function (): void {
    Lang::shouldReceive('get')->once()->with('messages', [], 'en', false)->andReturn([
        'auth' => ['failed' => 'These credentials do not match our records.'],
    ]);
    Lang::shouldReceive('get')->once()->with('messages', [], 'id', false)->andReturn([
        'auth' => ['failed' => 'Kredensial ini tidak cocok dengan catatan kami.'],
    ]);

    $files = (new BuildTranslationBundles)->files();

    expect($files)->toBe([
        resource_path('js/i18n/en.json') => "{}\n",
        resource_path('js/i18n/id.json') => "{}\n",
        resource_path('js/types/i18n.d.ts') => "export type SupportedLocale =\n    | \"en\"\n    | \"id\";\n\nexport type TranslationKey = never;\n",
    ]);
});

it('returns bundles from the configured language files', function (): void {
    Config::set('i18n.domains', ['authentication']);
    Config::set('i18n.scopes', ['heading']);

    $files = (new BuildTranslationBundles)->files();

    expect($files)->toBe([
        resource_path('js/i18n/en.json') => "{\n    \"authentication.heading.login\": \"Log into {app_name}\"\n}\n",
        resource_path('js/i18n/id.json') => "{\n    \"authentication.heading.login\": \"Login ke {app_name}\"\n}\n",
        resource_path('js/types/i18n.d.ts') => "export type SupportedLocale =\n    | \"en\"\n    | \"id\";\n\nexport type TranslationKey =\n    | \"authentication.heading.login\";\n",
    ]);
});

it('rejects locale bundles whose keys differ from the source locale', function (array $idLines, string $missing, string $unexpected): void {
    Lang::shouldReceive('get')->once()->with('messages', [], 'en', false)->andReturn([
        'label' => ['name' => 'Name'],
    ]);
    Lang::shouldReceive('get')->once()->with('messages', [], 'id', false)->andReturn($idLines);

    expect(fn (): array => (new BuildTranslationBundles)->files())->toThrow(
        LogicException::class,
        "Frontend translation keys for locale [id] do not match source locale [en]. Missing: {$missing}. Unexpected: {$unexpected}.",
    );
})->with([
    'missing key' => [[], 'messages.label.name', 'none'],
    'unexpected key' => [
        ['label' => ['name' => 'Nama', 'nickname' => 'Nama panggilan']],
        'none',
        'messages.label.nickname',
    ],
    'missing and unexpected keys' => [
        ['label' => ['nickname' => 'Nama panggilan']],
        'messages.label.name',
        'messages.label.nickname',
    ],
]);

it('rejects a source locale missing from the locales', function (): void {
    Config::set('i18n.source_locale', 'fr');

    Lang::shouldReceive('get')->once()->with('messages', [], 'en', false)->andReturn([
        'label' => ['name' => 'Name'],
    ]);
    Lang::shouldReceive('get')->once()->with('messages', [], 'id', false)->andReturn([
        'label' => ['name' => 'Nama'],
    ]);

    expect(fn (): array => (new BuildTranslationBundles)->files())->toThrow(
        LogicException::class,
        'Source locale [fr] must be included in i18n.locales.',
    );
});

it('rejects empty locales', function (): void {
    Config::set('i18n.locales', []);

    expect(fn (): array => (new BuildTranslationBundles)->files())->toThrow(
        LogicException::class,
        'Source locale [en] must be included in i18n.locales.',
    );
});

it('rejects invalid i18n configuration', function (string $key, mixed $value, string $message): void {
    Config::set("i18n.{$key}", $value);

    expect(fn (): array => (new BuildTranslationBundles)->files())->toThrow(LogicException::class, $message);
})->with([
    'locales as string' => ['locales', 'en', 'Configuration [i18n.locales] must be a list of strings.'],
    'locales as map' => ['locales', ['en' => 'English'], 'Configuration [i18n.locales] must be a list of strings.'],
    'locales with non-string' => ['locales', ['en', 42], 'Configuration [i18n.locales] must be a list of strings.'],
    'domains as string' => ['domains', 'messages', 'Configuration [i18n.domains] must be a list of strings.'],
    'domains with non-string' => ['domains', ['messages', 42], 'Configuration [i18n.domains] must be a list of strings.'],
    'scopes as string' => ['scopes', 'label', 'Configuration [i18n.scopes] must be a list of strings.'],
    'source locale as non-string' => ['source_locale', ['en'], 'Configuration [i18n.source_locale] must be a string.'],
]);
