<?php

namespace App\Console\Commands\Ci;

use App\Support\Ci\CiPayload;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Guards the release pull request itself.
 *
 * The candidate it is built from has already been through the full gate, so
 * nothing here re-runs the test suite. What is unverified is the release
 * metadata: that the pull request touches release files and nothing else, that
 * the manifest, the runtime version file, and the changelog agree, and that the
 * tag the publisher will create is a new one.
 */
#[Signature('ci:release-manifest {--changed= : Path to a file listing the changed paths} {--tags= : Path to a file listing the existing tags} {--previous= : The version this release supersedes} {--root= : The repository root to read the release files from}')]
#[Description('Validate the release pull request metadata against the tag that would be published.')]
final class CheckReleaseManifest extends Command
{
    /**
     * @var list<string>
     */
    private const array RELEASE_FILES = [
        'CHANGELOG.md',
        '.release-please-manifest.json',
        'version.json',
    ];

    public function handle(): int
    {
        $problems = [];

        $root = (string) $this->option('root') !== '' ? (string) $this->option('root') : base_path();

        $manifest = CiPayload::readFile($root.'/.release-please-manifest.json');
        $version = CiPayload::string($manifest, '.');
        $runtimeVersion = CiPayload::string(CiPayload::readFile($root.'/version.json'), 'version');

        foreach ($this->lines((string) $this->option('changed')) as $path) {
            if (! in_array($path, self::RELEASE_FILES, true)) {
                $problems[] = "A release pull request may not change [{$path}].";
            }
        }

        if ($version === '') {
            $problems[] = 'The release manifest declares no version for the root package.';
        }

        if ($version !== $runtimeVersion) {
            $problems[] = "version.json declares [{$runtimeVersion}] but the release manifest declares [{$version}].";
        }

        $changelogVersion = $this->latestChangelogVersion($root.'/CHANGELOG.md');

        if ($version !== '' && $changelogVersion !== $version) {
            $problems[] = "The newest CHANGELOG.md entry is [{$changelogVersion}] but the release manifest declares [{$version}].";
        }

        $tag = "v{$version}";

        if ($version !== '' && in_array($tag, $this->lines((string) $this->option('tags')), true)) {
            $problems[] = "The tag [{$tag}] already exists, so this release pull request would publish nothing new.";
        }

        $previous = (string) $this->option('previous');

        if ($version !== '' && $previous !== '' && version_compare($version, $previous, '<=')) {
            $problems[] = "The release version [{$version}] does not increase on the released [{$previous}].";
        }

        foreach ($problems as $problem) {
            $this->line("::error::{$problem}");
        }

        if ($problems !== []) {
            return self::FAILURE;
        }

        $this->line("The release pull request publishes [{$tag}] consistently across the manifest, version.json, and the changelog.");

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function lines(string $path): array
    {
        if ($path === '' || ! is_file($path)) {
            return [];
        }

        $lines = [];

        foreach (Str::of((string) file_get_contents($path))->split('/\R/') as $line) {
            $line = Str::trim($line);

            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    /**
     * Release Please writes the newest entry first, as a level-two heading whose
     * version is either linked or bare.
     */
    private function latestChangelogVersion(string $path): string
    {
        if (! is_file($path)) {
            return '';
        }

        return Str::match(
            '/^##\s+\[?v?(\d+\.\d+\.\d+[^\]\s)]*)\]?/m',
            (string) file_get_contents($path),
        );
    }
}
