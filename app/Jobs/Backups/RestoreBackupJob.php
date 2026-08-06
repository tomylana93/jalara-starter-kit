<?php

namespace App\Jobs\Backups;

use App\Actions\Backups\RestoreBackup;
use App\Enums\BackupRunStatus;
use App\Models\BackupRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Restores one archive on the queue.
 *
 * On `database-long` for the same reason as `RunBackupJob`, and more urgently: a
 * restore unpacks an archive, replaces the database and copies media back, which
 * outlasts the default connection's 90 second `retry_after` on any real dataset.
 * A second worker claiming it mid-flight would wipe the database underneath the
 * import that is still running.
 */
class RestoreBackupJob implements ShouldQueue
{
    use Queueable;

    /**
     * Never retried automatically. A restore that died part way has already
     * replaced some of the data, so repeating it unattended reapplies a
     * half-known state; the run is recorded failed and left to a person.
     */
    public int $tries = 1;

    /**
     * Well under the connection's 3600 second `retry_after`, matching the backup
     * job it is the mirror of.
     */
    public int $timeout = 1800;

    public function __construct(public BackupRun $run)
    {
        $this->onConnection('database-long');
    }

    public function handle(RestoreBackup $action): void
    {
        $action->handle($this->run);
    }

    /**
     * Give up for good: a run left mid-flight would hold the lock forever and
     * block every later backup, so the row is closed even when the failure
     * happened somewhere the action could not record it.
     */
    public function failed(?Throwable $throwable): void
    {
        try {
            $run = $this->run->fresh();

            if (! $run instanceof BackupRun || $run->status->isTerminal()) {
                return;
            }

            $run->forceFill([
                'status' => BackupRunStatus::Failed,
                'lock_key' => null,
                'error_code' => RestoreBackup::REASON_FAILED,
                'completed_at' => now(),
            ])->save();
        } catch (Throwable $unrecordable) {
            /*
             * A restore that died between wiping the database and replaying the
             * archive leaves no table to write this to. There is nothing left to
             * do about it here, and throwing from `failed` only buries the
             * original cause under a second one.
             */
            Log::error("A failed restore could not be closed; the database it left behind is missing this application's tables.", [
                'run' => $this->run->getKey(),
                'exception' => $unrecordable->getMessage(),
            ]);
        }
    }
}
