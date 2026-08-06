<?php

namespace App\Support\Backups;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\BackupDestination\Backup;
use Spatie\Backup\BackupDestination\BackupDestination;

/**
 * Read and delete access to the archives Spatie has written.
 *
 * Everything a client may name goes through `find()`, which resolves a filename
 * against the archives that actually exist rather than building a path from the
 * submitted value. That distinction is the whole point of this class: no client
 * input ever reaches the filesystem, so `../../.env` is not a traversal attempt
 * to be sanitised, it is simply a name that matches nothing and yields null.
 */
final class BackupArchives
{
    /**
     * Every archive across every configured destination, newest first.
     *
     * @return Collection<int, BackupArchive>
     */
    public function all(): Collection
    {
        return $this->destinations()
            ->flatMap(fn (BackupDestination $destination): Collection => collect($destination->backups()->all())
                ->filter(fn (Backup $backup): bool => $backup->exists())
                ->map(fn (Backup $backup): BackupArchive => new BackupArchive(
                    filename: basename($backup->path()),
                    diskName: $destination->diskName(),
                    path: $backup->path(),
                    sizeInBytes: (int) $backup->sizeInBytes(),
                    createdAt: $backup->date(),
                )))
            ->sortByDesc(fn (BackupArchive $archive): int => $archive->createdAt->getTimestamp())
            ->values();
    }

    /**
     * The archive with this basename, or null when no such archive exists.
     *
     * The comparison is against the listing, never against a constructed path.
     */
    public function find(string $filename): ?BackupArchive
    {
        return $this->all()
            ->first(fn (BackupArchive $archive): bool => $archive->filename === $filename);
    }

    /**
     * The most recent archive, used to record what a finished run produced.
     */
    public function newest(): ?BackupArchive
    {
        return $this->all()->first();
    }

    /**
     * The destination an archive is written to when the application chooses.
     *
     * Spatie writes every backup to every configured disk; the first is the one
     * that always exists, so an upload lands there and the rest of the ladder
     * stays Spatie's business.
     */
    public function primaryDiskName(): string
    {
        /** @var array<int, string> $disks */
        $disks = (array) config('backup.backup.destination.disks', []);

        return $disks[0] ?? 'backups';
    }

    public function delete(BackupArchive $archive): void
    {
        Storage::disk($archive->diskName)->delete($archive->path);
    }

    /**
     * @return Collection<int, BackupDestination>
     */
    private function destinations(): Collection
    {
        $backupName = (string) config('backup.backup.name');

        /** @var array<int, string> $disks */
        $disks = (array) config('backup.backup.destination.disks', []);

        return collect($disks)
            ->map(fn (string $diskName): BackupDestination => BackupDestination::create($diskName, $backupName))
            /*
             * An unreachable destination (missing credentials on an off-site
             * disk, say) must not take the whole listing down with it: the
             * archives on the reachable disks are still worth showing.
             */
            ->filter(fn (BackupDestination $destination): bool => $destination->isReachable())
            ->values();
    }
}
