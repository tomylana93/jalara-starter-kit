<?php

use Illuminate\Support\Facades\File;

it('satisfies the documentation and licensing contract', function () {
    expect(base_path('README.md'))->toBeFile()
        ->and(base_path('LICENSE'))->toBeFile();

    $readme = File::get(base_path('README.md'));
    $license = File::get(base_path('LICENSE'));

    expect($readme)
        ->toContain('<picture>')
        ->toContain('<source media="(prefers-color-scheme: dark)" srcset="public/assets/images/branding/logo-dark.png">')
        ->toContain('<source media="(prefers-color-scheme: light)" srcset="public/assets/images/branding/logo.png">')
        ->toContain('PHP-8.5')
        ->toContain('Laravel-13')
        ->toContain('Inertia-3')
        ->toContain('Vue-3')
        ->toContain('Tailwind_CSS-4')
        ->toContain('Tests-568-blue')
        ->toContain('[MIT License](LICENSE)')
        ->and($license)->toContain('MIT License')
        ->toContain('Copyright (c) 2026 Tomy Maulana')
        ->and($readme)->not->toMatch('/^#+\s+(installation|install|setup|getting\s+started)/mi');

    $forbiddenCommands = [
        'composer install',
        'pnpm install',
        'npm install',
        'yarn install',
        'php artisan migrate',
        'php artisan key:generate',
    ];

    foreach ($forbiddenCommands as $command) {
        expect(strtolower($readme))->not->toContain($command);
    }
});
