<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->translationPath = storage_path('framework/testing/translations-'.Str::uuid());
    $this->translationOutputPath = storage_path('framework/testing/exported-translations-'.Str::uuid());

    File::ensureDirectoryExists($this->translationPath.'/en');
    File::ensureDirectoryExists($this->translationPath.'/id');
});

afterEach(function (): void {
    File::deleteDirectory($this->translationPath);
    File::deleteDirectory($this->translationOutputPath);
});

it('exports nested messages, Laravel placeholders, and TypeScript types', function (): void {
    File::put($this->translationPath.'/en/user.php', <<<'PHP'
    <?php

    return [
        'greeting' => 'Hello, :name',
        'label' => [
            'email' => 'Email',
            'name' => 'Name',
        ],
    ];
    PHP);
    File::put($this->translationPath.'/id/user.php', <<<'PHP'
    <?php

    return [
        'greeting' => 'Halo, :name',
        'label' => [
            'email' => 'Email',
            'name' => 'Nama',
        ],
    ];
    PHP);

    $this->artisan('lang:export', [
        '--source' => $this->translationPath,
        '--output' => $this->translationOutputPath,
        '--fallback' => 'en',
    ])->assertSuccessful();

    expect(json_decode(File::get($this->translationOutputPath.'/en.json'), true, flags: JSON_THROW_ON_ERROR))
        ->toBe([
            'user' => [
                'greeting' => 'Hello, {name}',
                'label' => [
                    'email' => 'Email',
                    'name' => 'Name',
                ],
            ],
        ])
        ->and(File::get($this->translationOutputPath.'/messages.ts'))
        ->toContain('"user.label.name": Record<string, never>')
        ->toContain('"user.greeting": { \'name\': string | number }');
});

it('rejects locales with different placeholders', function (): void {
    File::put($this->translationPath.'/en/user.php', "<?php return ['greeting' => 'Hello, :name'];");
    File::put($this->translationPath.'/id/user.php', "<?php return ['greeting' => 'Halo, :username'];");

    $this->artisan('lang:export', [
        '--source' => $this->translationPath,
        '--output' => $this->translationOutputPath,
        '--fallback' => 'en',
    ])->assertFailed();

    expect(File::isDirectory($this->translationOutputPath))->toBeFalse();
});

it('rejects numeric translation arrays', function (): void {
    File::put($this->translationPath.'/en/user.php', "<?php return ['roles' => ['admin', 'member']];");
    File::put($this->translationPath.'/id/user.php', "<?php return ['roles' => ['admin', 'anggota']];");

    $this->artisan('lang:export', [
        '--source' => $this->translationPath,
        '--output' => $this->translationOutputPath,
        '--fallback' => 'en',
    ])->assertFailed();

    expect(File::isDirectory($this->translationOutputPath))->toBeFalse();
});
