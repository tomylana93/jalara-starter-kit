<?php

namespace App\Actions\Backups;

use App\Enums\BackupRunStatus;
use App\Jobs\Backups\RunBackupJob;
use App\Models\BackupRun;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Opens a backup run and hands it to the queue.
 *
 * The unique `lock_key` is the gate: the insert either wins and owns the only
 * active run, or it violates the constraint and loses. That makes "one backup at
 * a time" atomic rather than a read-then-write race, which matters because both
 * administrators and the schedule enter through here.
 */
final class StartBackupRun
{
    /**
     * Returns null when a run is already in flight; the caller decides how to
     * report that, since a person clicking a button and the schedule finding one
     * already running deserve different answers.
     */
    public function handle(?User $user = null): ?BackupRun
    {
        $run = new BackupRun;
        $run->forceFill([
            'user_id' => $user?->getKey(),
            'lock_key' => BackupRun::ACTIVE_LOCK_KEY,
            'status' => BackupRunStatus::Pending,
        ]);

        try {
            $run->save();
        } catch (UniqueConstraintViolationException) {
            return null;
        }

        dispatch(new RunBackupJob($run));

        return $run;
    }
}
