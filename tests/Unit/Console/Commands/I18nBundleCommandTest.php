<?php

declare(strict_types=1);

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

pest()->extend(TestCase::class);

beforeEach(function (): void {
    Config::set('i18n.source_locale', 'en');
    Config::set('i18n.locales', ['en', 'id']);
    Config::set('i18n.domains', ['messages']);
    Config::set('i18n.scopes', ['label']);
});

it('writes the bundles built by the action', function (): void {
    Lang::shouldReceive('get')->once()->with('messages', [], 'en', false)->andReturn([
        'label' => ['name' => 'Name'],
    ]);
    Lang::shouldReceive('get')->once()->with('messages', [], 'id', false)->andReturn([
        'label' => ['name' => 'Nama'],
    ]);

    File::shouldReceive('ensureDirectoryExists')->once()->with(resource_path('js/i18n'));
    File::shouldReceive('ensureDirectoryExists')->once()->with(resource_path('js/types'));
    File::shouldReceive('put')->once()->with(
        resource_path('js/i18n/en.json'),
        "{\n    \"messages.label.name\": \"Name\"\n}\n",
    );
    File::shouldReceive('put')->once()->with(
        resource_path('js/i18n/id.json'),
        "{\n    \"messages.label.name\": \"Nama\"\n}\n",
    );
    File::shouldReceive('put')->once()->with(
        resource_path('js/types/i18n.d.ts'),
        "export type SupportedLocale =\n    | \"en\"\n    | \"id\";\n\nexport type TranslationKey =\n    | \"messages.label.name\";\n",
    );

    expect(Artisan::call('i18n:bundle'))->toBe(Command::SUCCESS);
});
