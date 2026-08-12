<?php

namespace App\Actions\Backups;

use App\Enums\BackupRunStatus;
use App\Enums\BackupRunType;
use App\Jobs\Backups\RestoreBackupJob;
use App\Models\BackupRun;
use App\Models\User;
use App\Support\Backups\BackupArchive;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Opens a restore run and hands it to the queue.
 *
 * The counterpart to `StartBackupRun`, and deliberately identical in shape: the
 * same unique `lock_key` insert is the gate, so a restore and a backup can never
 * be in flight together. Restoring reads the archive it was asked for from disk
 * again inside the job, so only the basename is carried across the queue.
 */
final class StartRestoreRun
{
    /**
     * Returns null when a run is already in flight; the caller decides how to
     * report that.
     */
    public function handle(BackupArchive $archive, ?User $user = null): ?BackupRun
    {
        $run = new BackupRun;
        $run->forceFill([
            'user_id' => $user?->getKey(),
            'lock_key' => BackupRun::ACTIVE_LOCK_KEY,
            'type' => BackupRunType::Restore,
            'status' => BackupRunStatus::Pending,
            /* Which archive is being restored, not one this run produced. */
            'filename' => $archive->filename,
        ]);

        /*
         * The insert gets its own transaction so losing the race stays
         * survivable. PostgreSQL aborts the whole transaction on a constraint
         * violation and rejects every statement after it, so a caller that had
         * already opened one would find its next query failing rather than
         * seeing the null this returns. Nested, this is a savepoint; standalone,
         * a transaction around one insert. The gate itself is unchanged.
         */
        try {
            DB::transaction(function () use ($run): void {
                $run->save();
            });
        } catch (UniqueConstraintViolationException) {
            return null;
        }

        dispatch(new RestoreBackupJob($run));

        return $run;
    }
}
