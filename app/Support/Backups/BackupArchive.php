<?php

namespace App\Support\Backups;

use Carbon\CarbonInterface;

/**
 * One archive on one destination disk.
 *
 * Carries the disk name alongside the path because Spatie's own backup object
 * exposes a filesystem instance but not which configured disk it came from, and
 * both downloading and deleting need the name.
 */
final readonly class BackupArchive
{
    public function __construct(
        public string $filename,
        public string $diskName,
        public string $path,
        public int $sizeInBytes,
        public CarbonInterface $createdAt,
    ) {}
}
