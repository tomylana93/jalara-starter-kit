<?php

namespace App\Jobs\Backups;

use App\Actions\Backups\RunBackup;
use App\Enums\BackupRunStatus;
use App\Models\BackupRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Runs one backup on the queue.
 *
 * Deliberately on `database-long`: `retry_after` is a per-connection setting,
 * and the default connection releases a reservation after 90 seconds. A backup
 * reliably takes longer than that, so on the default connection a worker would
 * start a second dump while the first was still writing.
 */
class RunBackupJob implements ShouldQueue
{
    use Queueable;

    /**
     * Never retried automatically. A backup that died part way leaves a partial
     * archive behind, and repeating it unattended only makes more of them; the
     * failure is mailed and repeated deliberately instead.
     */
    public int $tries = 1;

    /**
     * Well under the connection's 3600 second `retry_after`, so a slow backup is
     * never claimed twice, while still allowing a genuinely large archive to
     * finish.
     */
    public int $timeout = 1800;

    public function __construct(public BackupRun $run)
    {
        $this->onConnection('database-long');
    }

    public function handle(RunBackup $action): void
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
        $run = $this->run->fresh();

        if (! $run instanceof BackupRun || $run->status->isTerminal()) {
            return;
        }

        $run->forceFill([
            'status' => BackupRunStatus::Failed,
            'lock_key' => null,
            'error_code' => RunBackup::REASON_FAILED,
            'completed_at' => now(),
        ])->save();
    }
}
