<?php

namespace App\Actions\Backups;

use App\Enums\BackupRunStatus;
use App\Models\BackupRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Make a freshly restored database safe to keep running on.
 *
 * A dump is a photograph of a database that was being used at the moment it was
 * taken, and some of what it holds describes work in flight rather than data.
 * Replaying it puts that work back too, in a process that has long since moved
 * on. Two kinds of row matter here, and both of them are actively harmful:
 *
 * - A backup run that was still going when the dump was taken. Every archive
 *   this application writes contains one, because the run marks itself running
 *   and takes the single-flight lock before the dump begins. Restored, it holds
 *   that lock forever: the next backup or restore collides with the unique index
 *   and is told something is already running, the page shows a run that will
 *   never finish, and scheduled backups stop without a word.
 * - Queue, session and cache rows. Restored jobs are handed back to the workers
 *   and re-run whatever they were doing; restored sessions replace the sessions
 *   of everyone currently signed in, including the operator watching the restore
 *   they started; restored cache entries answer for state that no longer exists.
 *
 * None of it is data an operator restores a backup to get back, so all of it is
 * cleared. This runs against the connection the restore replaced, which is not
 * necessarily the application's default one.
 */
final readonly class SettleRestoredDatabase
{
    /**
     * Discard every run the archive resurrected and clear the operational tables.
     */
    public function handle(string $connection, BackupRun $currentRun): void
    {
        $this->discardResurrectedRuns($connection, $currentRun);

        foreach ($this->operationalTables() as $table) {
            if (Schema::connection($connection)->hasTable($table)) {
                /* Not `truncate`: it is DDL on some drivers, and a delete is enough. */
                DB::connection($connection)->table($table)->delete();
            }
        }
    }

    /**
     * Deleted rather than marked failed.
     *
     * The row is always the backup that produced the archive being restored: it
     * marks itself running, takes the lock, and only writes `completed` after
     * the dump it appears in was taken. Recording it as failed would tell the
     * operator that a backup failed while they are looking at the archive it
     * successfully produced. Its true outcome is not in this database and never
     * will be, and a row that says so is noise on a page about what happened.
     *
     * The run doing the restoring is excluded by key. Its own row is normally
     * gone by now - the restore replaced the table it lived in - and
     * `RestoreBackup` re-inserts it afterwards; but an archive from this same
     * installation can carry a row with the same key, and deleting that would
     * take the running restore out of its own history.
     */
    private function discardResurrectedRuns(string $connection, BackupRun $currentRun): void
    {
        $table = new BackupRun()->getTable();

        if (! Schema::connection($connection)->hasTable($table)) {
            return;
        }

        DB::connection($connection)
            ->table($table)
            ->whereIn('status', array_map(fn (BackupRunStatus $status): string => $status->value, BackupRunStatus::active()))
            ->where($currentRun->getKeyName(), '!=', $currentRun->getKey())
            ->delete();
    }

    /**
     * Tables that describe work rather than data, read from the configuration
     * that owns each one so a renamed table is still cleared.
     *
     * @return list<string>
     */
    private function operationalTables(): array
    {
        $tables = [
            (string) config('session.table', 'sessions'),
            (string) config('queue.failed.table', 'failed_jobs'),
            (string) config('cache.stores.database.table', 'cache'),
            (string) config('cache.stores.database.lock_table', 'cache_locks'),
            'job_batches',
        ];

        /** @var array<string, array<string, mixed>> $connections */
        $connections = (array) config('queue.connections', []);

        foreach ($connections as $definition) {
            if (($definition['driver'] ?? null) === 'database') {
                $tables[] = (string) ($definition['table'] ?? 'jobs');
            }
        }

        return array_values(array_unique(array_filter($tables)));
    }
}
