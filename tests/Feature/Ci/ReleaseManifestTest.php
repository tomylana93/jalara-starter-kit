<?php

use Illuminate\Support\Facades\File;

/*
 * The release pull request is the one pull request whose diff nobody writes by
 * hand, and the one whose contents decide what a deployment will fetch. These
 * cases pin what the gate refuses to publish.
 */

afterEach(function (): void {
    File::deleteDirectory(sys_get_temp_dir().'/jalara-ci-manifest');
});

/**
 * Writes a candidate release tree and returns the options that describe it.
 *
 * @param  list<string>  $changed
 * @param  list<string>  $tags
 * @return array<string, string>
 */
function ciReleaseCandidate(
    string $manifestVersion = '2.2.0',
    ?string $runtimeVersion = null,
    ?string $changelogVersion = null,
    array $changed = ['CHANGELOG.md', '.release-please-manifest.json', 'version.json'],
    array $tags = ['v2.1.0', 'v2.1.1'],
    string $previous = '2.1.1',
): array {
    $root = sys_get_temp_dir().'/jalara-ci-manifest';
    File::ensureDirectoryExists($root);

    File::put($root.'/.release-please-manifest.json', (string) json_encode(['.' => $manifestVersion]));
    File::put($root.'/version.json', (string) json_encode(['version' => $runtimeVersion ?? $manifestVersion]));
    File::put($root.'/CHANGELOG.md', implode("\n", [
        '# Changelog',
        '',
        '## ['.($changelogVersion ?? $manifestVersion).'](https://example.test/compare) (2026-08-12)',
        '',
        '### Features',
        '',
        '* add a trunk based gate',
        '',
        '## [2.1.1](https://example.test/compare) (2026-08-01)',
        '',
    ]));

    File::put($root.'/changed.txt', implode("\n", $changed));
    File::put($root.'/tags.txt', implode("\n", $tags));

    return [
        '--root' => $root,
        '--changed' => $root.'/changed.txt',
        '--tags' => $root.'/tags.txt',
        '--previous' => $previous,
    ];
}

it('accepts a release pull request whose metadata agrees on one new version', function () {
    pendingCommand($this->artisan('ci:release-manifest', ciReleaseCandidate()))->assertSuccessful();
});

it('rejects a release pull request that touches a file outside the release set', function () {
    pendingCommand($this->artisan('ci:release-manifest', ciReleaseCandidate(
        changed: ['CHANGELOG.md', 'version.json', 'app/Models/User.php'],
    )))->assertFailed();
});

it('rejects a release whose runtime version file disagrees with the manifest', function () {
    pendingCommand($this->artisan('ci:release-manifest', ciReleaseCandidate(
        runtimeVersion: '2.1.1',
    )))->assertFailed();
});

it('rejects a release whose newest changelog entry is not the released version', function () {
    pendingCommand($this->artisan('ci:release-manifest', ciReleaseCandidate(
        changelogVersion: '2.1.1',
    )))->assertFailed();
});

it('rejects a release whose tag already exists', function () {
    pendingCommand($this->artisan('ci:release-manifest', ciReleaseCandidate(
        tags: ['v2.1.1', 'v2.2.0'],
    )))->assertFailed();
});

it('rejects a release that does not increase the version', function () {
    pendingCommand($this->artisan('ci:release-manifest', ciReleaseCandidate(
        manifestVersion: '2.1.0',
        tags: ['v2.1.0'],
        previous: '2.1.1',
    )))->assertFailed();
});

/*
 * The repository ships its own release metadata, and a hand edit that leaves
 * `version.json` behind the manifest would only surface at deployment time.
 */
it('keeps the committed release metadata internally consistent', function () {
    $manifest = json_decode((string) file_get_contents(base_path('.release-please-manifest.json')), true);
    $version = json_decode((string) file_get_contents(base_path('version.json')), true);

    expect($manifest)->toHaveKey('.')
        ->and($version)->toHaveKey('version')
        ->and($version['version'])->toBe($manifest['.']);
});
