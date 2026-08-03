<?php

namespace App\Console\Commands;

use App\Enums\BrandingAsset;
use App\Models\Chat\Message;
use App\Models\ImageUpload;
use App\Models\User;
use App\Settings\BrandingSettings;
use FilesystemIterator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Deletes stored images that nothing points at any more.
 *
 * Orphans are a normal consequence of interrupted work — a worker killed
 * between storing a result and saving the path, a record removed by a cascade —
 * and without a sweep they accumulate forever.
 *
 * The command is built to be boring and safe rather than thorough. It refuses
 * to look outside the four prefixes the application itself writes to, it never
 * touches anything younger than the grace period, and it reports without
 * deleting unless explicitly told otherwise.
 */
#[Signature('images:prune-orphans {--delete : Delete the orphans instead of only reporting them} {--older-than=24 : Ignore files modified within this many hours}')]
#[Description('Report or delete stored images that nothing references.')]
class PruneOrphanImages extends Command
{
    /**
     * The only locations this command may ever read or delete from.
     *
     * @var array<string, array<int, string>>
     */
    private const array MANAGED_PREFIXES = [
        'public' => ['avatars', 'branding'],
        'local' => ['chat', ImageUpload::SOURCE_DIRECTORY],
    ];

    public function handle(): int
    {
        $hours = max(0, (int) $this->option('older-than'));
        $shouldDelete = (bool) $this->option('delete');
        $cutoff = now()->subHours($hours)->getTimestamp();
        $referenced = $this->referencedPaths();

        $this->line(__('console.prune_orphan_images.mode.'.($shouldDelete ? 'delete' : 'dry_run'), [
            'hours' => $hours,
        ]));

        foreach (self::MANAGED_PREFIXES as $diskName => $prefixes) {
            $disk = Storage::disk($diskName);
            $candidates = 0;
            $deleted = 0;
            $skipped = 0;

            foreach ($prefixes as $prefix) {
                foreach ($this->managedFiles($disk, $prefix) as $path) {
                    if ($this->isProtected($disk, $diskName, $path, $referenced, $cutoff)) {
                        $skipped++;

                        continue;
                    }

                    $candidates++;

                    if ($shouldDelete && $disk->delete($path)) {
                        $deleted++;
                    }
                }
            }

            $this->line(__('console.prune_orphan_images.disk', [
                'disk' => $diskName,
                'candidates' => $candidates,
                'deleted' => $deleted,
                'skipped' => $skipped,
            ]));
        }

        return self::SUCCESS;
    }

    /**
     * Every regular file under one managed prefix, as disk-relative paths.
     *
     * The walk is deliberate rather than a Flysystem listing. Flysystem refuses
     * to list a directory containing a symbolic link at all, which would abort
     * the whole sweep over a single link; here a link is simply not yielded,
     * and directories are never candidates in the first place.
     *
     * @return array<int, string>
     */
    private function managedFiles(Filesystem $disk, string $prefix): array
    {
        $root = rtrim($disk->path($prefix), '/');

        if (! is_dir($root)) {
            return [];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        $paths = [];

        foreach ($iterator as $entry) {
            /* Links and directories are never candidates for deletion. */
            if ($entry instanceof SplFileInfo && ! $entry->isLink() && $entry->isFile()) {
                $paths[] = $prefix.'/'.ltrim(
                    str_replace($root, '', $entry->getPathname()),
                    '/',
                );
            }
        }

        return $paths;
    }

    /**
     * Whether this file must be left alone.
     *
     * @param  array<string, true>  $referenced
     */
    private function isProtected(
        Filesystem $disk,
        string $diskName,
        string $path,
        array $referenced,
        int $cutoff,
    ): bool {
        if (isset($referenced[$diskName.':'.$path])) {
            return true;
        }

        /*
         * A symlink is someone else's file wearing our directory's name, and
         * deleting through one would reach outside the managed prefix entirely.
         */
        if (is_link($disk->path($path))) {
            return true;
        }

        /*
         * The grace period is the real safety net: a file written moments ago
         * may belong to a request that has not saved its path yet, and would
         * look exactly like an orphan.
         */
        return $disk->lastModified($path) > $cutoff;
    }

    /**
     * Every stored image path the application still points at.
     *
     * Keyed by disk so a chat path can never accidentally protect a public file
     * that happens to share its name.
     *
     * @return array<string, true>
     */
    private function referencedPaths(): array
    {
        $referenced = [];

        User::query()
            ->whereNotNull('avatar_path')
            ->pluck('avatar_path')
            ->each(function (string $path) use (&$referenced): void {
                $referenced['public:'.$path] = true;
            });

        $settings = app(BrandingSettings::class);

        foreach (BrandingAsset::cases() as $asset) {
            $path = $settings->{$asset->property()};

            if (is_string($path) && $path !== '') {
                $referenced['public:'.$path] = true;
            }
        }

        Message::query()
            ->whereNotNull('image_path')
            ->pluck('image_path')
            ->each(function (string $path) use (&$referenced): void {
                $referenced['local:'.$path] = true;
            });

        /*
         * An upload that has not finished still owns its staged bytes, and any
         * upload may own a result the sweep would otherwise not recognise.
         */
        ImageUpload::query()
            ->get()
            ->each(function (ImageUpload $upload) use (&$referenced): void {
                if (! $upload->status->isTerminal()) {
                    $referenced['local:'.$upload->source_path] = true;
                }

                if ($upload->result_path !== null) {
                    $referenced[$upload->target->disk().':'.$upload->result_path] = true;
                }
            });

        return $referenced;
    }
}
