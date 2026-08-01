<?php

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Vite as FoundationVite;
use Illuminate\Support\Facades\Vite;

/*
 * A unique filename keeps the fixture manifest from colliding with a real
 * manifest written by `pnpm run build` or by the Playwright runner, so the test
 * can never disturb the developer's bundle.
 */
$manifestFilename = 'vite-asset-isolation-fixture.json';

/** @var list<string> $fixturePaths */
$fixturePaths = [];

/**
 * Apply the application's own Vite configuration to the current container.
 */
$bootViteConfiguration = function (): void {
    new AppServiceProvider(app())->boot();
};

/**
 * Read the build directory Vite resolves assets from.
 */
$buildDirectory = function (): string {
    $property = new ReflectionProperty(FoundationVite::class, 'buildDirectory');

    return (string) $property->getValue(app(FoundationVite::class));
};

/**
 * Write a fixture manifest into the given build directory.
 */
$writeFixtureManifest = function (string $buildDirectory) use ($manifestFilename, &$fixturePaths): void {
    $directory = public_path($buildDirectory);

    if (! is_dir($directory)) {
        mkdir($directory, recursive: true);
        $fixturePaths[] = $directory;
    }

    $path = $directory.'/'.$manifestFilename;

    file_put_contents($path, json_encode([
        'resources/js/app.ts' => ['file' => 'assets/app-fixture.js'],
    ]));

    array_unshift($fixturePaths, $path);
};

/*
 * The base test case stubs Vite out for every feature test; this suite is about
 * the real resolver, so it opts back in.
 */
beforeEach(function () use ($manifestFilename): void {
    $this->withVite();

    Vite::useManifestFilename($manifestFilename);
});

afterEach(function () use (&$fixturePaths): void {
    foreach ($fixturePaths as $path) {
        is_dir($path) ? rmdir($path) : unlink($path);
    }

    $fixturePaths = [];
});

it('resolves the isolated bundle when asset isolation is enabled', function () use ($bootViteConfiguration, $writeFixtureManifest) {
    config(['app.vite.isolated_assets' => true]);

    $bootViteConfiguration();
    $writeFixtureManifest('build-e2e');

    expect(Vite::hotFile())->toBe(public_path('hot-e2e'))
        ->and(Vite::hotFile())->not->toBe(public_path('hot'))
        ->and(Vite::asset('resources/js/app.ts'))->toBe(asset('build-e2e/assets/app-fixture.js'));
});

/*
 * The development bundle is read through the configured paths rather than
 * through a fixture, because writing into "public/build" or reading through
 * "public/hot" is exactly what a live development session must be spared.
 */
it('keeps the development bundle when asset isolation is disabled', function () use ($bootViteConfiguration, $buildDirectory) {
    config(['app.vite.isolated_assets' => false]);

    $bootViteConfiguration();

    expect(Vite::hotFile())->toBe(public_path('/hot'))
        ->and($buildDirectory())->toBe('build');
});
