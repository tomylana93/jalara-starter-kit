<?php

use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Artisan;

/**
 * @return array<string, mixed>
 */
function composerManifest(): array
{
    /** @var array<string, mixed> $manifest */
    $manifest = json_decode((string) file_get_contents(base_path('composer.json')), true, 512, JSON_THROW_ON_ERROR);

    return $manifest;
}

it('registers the development runner and the diagnostics command', function () {
    expect(array_keys(Artisan::all()))
        ->toContain('dev')
        ->toContain('dev:list')
        ->toContain('doctor');
});

it('runs Reverb, the server, the queue, the logs and Vite together', function () {
    $names = array_column(DevCommands::commands(), 'name');

    expect($names)
        ->toContain('reverb')
        ->toContain('server')
        ->toContain('queue')
        ->toContain('logs')
        ->toContain('vite');
});

it('drives development through the framework dev command', function () {
    expect(composerManifest()['scripts']['dev'])->toBe([
        'Composer\\Config::disableProcessTimeout',
        '@php artisan dev',
    ]);
});

it('exposes diagnostics as a non-mutating check and a separate manual fix', function () {
    $scripts = composerManifest()['scripts'];

    expect($scripts['doctor:check'])->toBe(['@php artisan doctor --no-interaction --format=agent'])
        ->and($scripts['doctor:fix'])->toBe(['@php artisan doctor --fix']);
});

it('never repairs the application from an automated script or workflow', function () {
    $scripts = composerManifest()['scripts'];

    $automated = ['setup', 'ci:setup', 'ci:check', 'test', 'post-autoload-dump', 'post-update-cmd', 'post-root-package-install', 'post-create-project-cmd'];

    foreach ($automated as $script) {
        expect(json_encode($scripts[$script], JSON_THROW_ON_ERROR))
            ->not->toContain('doctor:fix')
            ->not->toContain('doctor --fix');
    }

    expect((string) file_get_contents(base_path('.github/workflows/tests.yml')))
        ->not->toContain('doctor:fix')
        ->not->toContain('--fix');
});
