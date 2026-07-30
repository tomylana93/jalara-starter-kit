<?php

use Illuminate\Support\Facades\File;

it('satisfies the documentation and licensing contract', function () {
    // Verify files exist
    expect(base_path('README.md'))->toBeFile()
        ->and(base_path('LICENSE'))->toBeFile();

    $readme = File::get(base_path('README.md'));
    $license = File::get(base_path('LICENSE'));

    // Verify adaptive logo
    expect($readme)->toContain('<picture>')
        ->toContain('public/assets/images/branding/logo.png')
        ->toContain('public/assets/images/branding/logo-dark.png');

    // Verify main stack badges
    expect($readme)->toContain('PHP-8.5')
        ->toContain('Laravel-13')
        ->toContain('Inertia-3')
        ->toContain('Vue-3')
        ->toContain('Tailwind_CSS-4');

    // Verify test count badge
    expect($readme)->toContain('Tests-568-blue');

    // Verify license references
    expect($readme)->toContain('[MIT License](LICENSE)');
    expect($license)->toContain('MIT License')
        ->toContain('Copyright (c) 2026 Tomy Maulana');

    // Ensure installation/setup sections and commands are absent
    $forbiddenHeadings = [
        '# installation', '# install', '# setup', '# getting started',
        '## installation', '## install', '## setup', '## getting started',
    ];
    foreach ($forbiddenHeadings as $heading) {
        expect(strtolower($readme))->not->toContain($heading);
    }

    $forbiddenCommands = [
        'composer install', 'pnpm install', 'npm install', 'yarn install',
        'php artisan migrate', 'php artisan key:generate',
    ];
    foreach ($forbiddenCommands as $command) {
        expect(strtolower($readme))->not->toContain($command);
    }
});
