<?php

namespace App\Http\Presenters;

use App\Models\BackupRun;
use App\Support\Backups\BackupArchive;
use Illuminate\Support\Collection;

/**
 * Maps archives and runs to the shape the backup page consumes.
 *
 * Only the basename of an archive ever leaves the server. The full path is an
 * implementation detail of the destination disk, and handing it to the client
 * would invite the client to hand one back.
 */
final class BackupPresenter
{
    /**
     * @param  Collection<int, BackupArchive>  $archives
     * @return list<array{filename: string, disk: string, size_in_bytes: int, created_at: string}>
     */
    public static function presentArchives(Collection $archives): array
    {
        return array_values($archives
            ->map(fn (BackupArchive $archive): array => [
                'filename' => $archive->filename,
                'disk' => $archive->diskName,
                'size_in_bytes' => $archive->sizeInBytes,
                /* UTC ISO 8601; the browser decides how to render it. */
                'created_at' => $archive->createdAt->toIso8601String(),
            ])
            ->all());
    }

    /**
     * @return array{
     *     id: string,
     *     status: string,
     *     filename: string|null,
     *     size_in_bytes: int|null,
     *     error_code: string|null,
     *     started_by: string|null,
     *     started_at: string|null,
     *     completed_at: string|null,
     *     created_at: string|null,
     * }
     */
    public static function presentRun(BackupRun $run): array
    {
        return [
            'id' => $run->id,
            'status' => $run->status->value,
            'filename' => $run->filename,
            'size_in_bytes' => $run->size_in_bytes,
            /* A translation key suffix, never an exception message. */
            'error_code' => $run->error_code,
            /* Null means the schedule started it, not an administrator. */
            'started_by' => $run->user?->name,
            'started_at' => $run->started_at?->toIso8601String(),
            'completed_at' => $run->completed_at?->toIso8601String(),
            'created_at' => $run->created_at?->toIso8601String(),
        ];
    }

    /**
     * @param  Collection<int, BackupRun>  $runs
     * @return list<array<string, mixed>>
     */
    public static function presentRuns(Collection $runs): array
    {
        return array_values($runs
            ->map(fn (BackupRun $run): array => self::presentRun($run))
            ->all());
    }
}
