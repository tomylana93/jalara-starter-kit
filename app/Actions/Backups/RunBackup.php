<?php

namespace App\Actions\Backups;

use App\Enums\BackupRunStatus;
use App\Exceptions\Backups\BackupRunFailed;
use App\Models\BackupRun;
use App\Support\Backups\BackupArchive;
use App\Support\Backups\BackupArchives;
use Illuminate\Support\Facades\Artisan;
use Throwable;

/**
 * Executes one backup run and records what happened to it.
 *
 * The archive itself is Spatie's work; this owns only the run's state machine.
 * Every transition out of an active state also clears `lock_key`, because that
 * column is what the next run has to acquire.
 */
final readonly class RunBackup
{
    /** The archive failed to build. */
    public const string REASON_FAILED = 'failed';

    /** The command finished but no archive could be located afterwards. */
    public const string REASON_MISSING_ARCHIVE = 'missing_archive';

    public function __construct(private BackupArchives $archives) {}

    /**
     * @throws BackupRunFailed
     */
    public function handle(BackupRun $run): void
    {
        /*
         * Conditional claim, not a plain save: a redelivered job must not
         * restart a run that already finished, and only one claimant may win.
         */
        $claimed = BackupRun::query()
            ->whereKey($run->getKey())
            ->where('status', BackupRunStatus::Pending)
            ->update([
                'status' => BackupRunStatus::Running,
                'started_at' => now(),
                'updated_at' => now(),
            ]);

        if ($claimed === 0) {
            return;
        }

        try {
            $exitCode = Artisan::call('backup:run');
        } catch (Throwable $throwable) {
            $this->fail($run, self::REASON_FAILED);

            throw new BackupRunFailed($throwable->getMessage(), $throwable->getCode(), previous: $throwable);
        }

        if ($exitCode !== 0) {
            $this->fail($run, self::REASON_FAILED);

            throw new BackupRunFailed('The backup command exited with a non-zero status.');
        }

        $archive = $this->archives->newest();

        if (! $archive instanceof BackupArchive) {
            /*
             * The command reported success but nothing is on the destination.
             * Recording this as completed would put a row on the page pointing
             * at an archive nobody can download.
             */
            $this->fail($run, self::REASON_MISSING_ARCHIVE);

            throw new BackupRunFailed('The backup command reported success but no archive was found.');
        }

        $run->forceFill([
            'status' => BackupRunStatus::Completed,
            'lock_key' => null,
            'filename' => $archive->filename,
            'size_in_bytes' => $archive->sizeInBytes,
            'error_code' => null,
            'completed_at' => now(),
        ])->save();
    }

    /**
     * Record a terminal failure and release the lock.
     *
     * The reason is a translation key suffix, never an exception message: the
     * page renders it, and a raw message can carry paths or credentials.
     */
    private function fail(BackupRun $run, string $reason): void
    {
        $run->forceFill([
            'status' => BackupRunStatus::Failed,
            'lock_key' => null,
            'error_code' => $reason,
            'completed_at' => now(),
        ])->save();
    }
}
