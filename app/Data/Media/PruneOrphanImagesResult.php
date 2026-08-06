<?php

namespace App\Data\Media;

/**
 * What one orphan sweep found, and what it did about it.
 *
 * Reported per disk so a caller can tell a public sweep from a private one
 * without knowing which prefixes belong to which disk.
 */
final readonly class PruneOrphanImagesResult
{
    /**
     * @param  array<string, array{candidates: int, deleted: int, skipped: int}>  $disks
     */
    public function __construct(
        public bool $dryRun,
        public int $hours,
        public array $disks,
    ) {}
}
