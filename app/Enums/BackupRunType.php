<?php

namespace App\Enums;

/**
 * What a run does to the destination.
 *
 * Backups and restores share one table and one lock because they must never
 * overlap: a dump taken while a restore is half-applied archives a database that
 * never existed, and a restore landing mid-dump does the same in reverse. The
 * type is what keeps the shared history honest about which one a row describes.
 */
enum BackupRunType: string
{
    case Backup = 'backup';
    case Restore = 'restore';
}
