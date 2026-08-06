<?php

namespace App\Actions\Backups;

use App\Enums\BackupRunStatus;
use App\Exceptions\Backups\RestoreRunFailed;
use App\Models\BackupRun;
use App\Support\Backups\BackupArchive;
use App\Support\Backups\BackupArchiveContents;
use App\Support\Backups\BackupArchives;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\Backup\Tasks\Backup\DbDumperFactory;
use Throwable;
use ZipArchive;

final readonly class RestoreBackup
{
    /** The restore died somewhere that could not name a better reason. */
    public const string REASON_FAILED = 'restore_failed';

    /** The archive named by the run is no longer on any destination. */
    public const string REASON_MISSING_ARCHIVE = 'restore_missing_archive';

    /** The file is not an archive of this application, or cannot be unpacked. */
    public const string REASON_UNREADABLE_ARCHIVE = 'restore_unreadable_archive';

    /** The dumps are compressed, and this restore only replays plain SQL. */
    public const string REASON_UNSUPPORTED_DUMP = 'restore_unsupported_dump';

    /** The current database could not be copied, so nothing was replaced. */
    public const string REASON_SNAPSHOT_FAILED = 'restore_snapshot_failed';

    /** The dumps failed to replay; the database is now partially restored. */
    public const string REASON_IMPORT_FAILED = 'restore_import_failed';

    /** Where the pre-restore copy is written, alongside the archive folder. */
    private const string SNAPSHOT_SUFFIX = '-pre-restore';

    /**
     * How many pre-restore copies are kept.
     *
     * They sit outside Spatie's listing and retention on purpose, which also
     * means nothing else would ever delete them: uncompressed dumps would pile
     * up on the destination one per restore, forever. Keeping the last few
     * covers the case the copy exists for - a restore that went wrong and a
     * second one that went wrong on top of it - without that.
     */
    private const int SNAPSHOT_RETENTION = 3;

    public function __construct(
        private BackupArchives $archives,
        private SettleRestoredDatabase $settleRestoredDatabase,
    ) {}

    /**
     * Replay one archive over the current database and media.
     *
     * The run's state machine is the same one `RunBackup` owns, for the same
     * reasons: a conditional claim so a redelivered job cannot restart a
     * finished restore, and a terminal transition that always releases the lock.
     *
     * Every failure is recorded as a translatable `error_code` before it is
     * rethrown. That matters more here than for a backup: a restore that reports
     * nothing looks exactly like a restore that worked, and the operator's next
     * move is to trust a database that was never replaced.
     *
     * @throws RestoreRunFailed
     */
    public function handle(BackupRun $run): void
    {
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

        $archive = is_string($run->filename) ? $this->archives->find($run->filename) : null;

        if (! $archive instanceof BackupArchive) {
            $this->abort($run, self::REASON_MISSING_ARCHIVE, 'The archive to restore is no longer on any destination.');
        }

        /*
         * Under `storage/app/private`, which is outside every backed-up prefix,
         * so an in-flight restore can never end up inside the archive it is
         * restoring. Keyed by run id rather than a timestamp: two directories
         * must not collide even at the same second.
         */
        $workspace = storage_path('app/private/restore/'.$run->getKey());
        File::ensureDirectoryExists($workspace);

        try {
            $this->restore($run, $archive, $workspace);
        } finally {
            File::deleteDirectory($workspace);
        }
    }

    /**
     * @throws RestoreRunFailed
     */
    private function restore(BackupRun $run, BackupArchive $archive, string $workspace): void
    {
        $localArchive = $this->copyToWorkspace($archive, $workspace);

        if ($localArchive === null) {
            $this->abort($run, self::REASON_MISSING_ARCHIVE, 'The archive could not be read from its destination.');
        }

        $contents = BackupArchiveContents::tryRead($localArchive);

        if (! $contents instanceof BackupArchiveContents) {
            $this->abort($run, self::REASON_UNREADABLE_ARCHIVE, 'The archive is not a backup of this application.');
        }

        /*
         * A compressed dump would have to be decompressed with whatever wrote
         * it. Refusing is the honest answer; replaying the compressed bytes as
         * SQL would wipe the database and then fail on the first statement.
         */
        if ($contents->dumpEntries !== [] && config('backup.backup.database_dump_compressor') !== null) {
            $this->abort($run, self::REASON_UNSUPPORTED_DUMP, 'The archive holds compressed dumps, which cannot be replayed.');
        }

        $extractedTo = $workspace.'/archive';

        if (! $this->extract($localArchive, $extractedTo, $contents->extractableEntries())) {
            $this->abort($run, self::REASON_UNREADABLE_ARCHIVE, 'The archive could not be unpacked.');
        }

        if ($contents->dumpEntries !== []) {
            $snapshot = $this->snapshot($run, $workspace);

            $this->import($run, $extractedTo, $contents->dumpEntries, $snapshot);

            /*
             * The archive holds rows describing work that was in flight when it
             * was taken - a backup run still holding the single-flight lock,
             * queued jobs, sessions. They are cleared before this run records
             * itself, so it is written into a database nothing else claims.
             */
            $this->settleRestoredDatabase->handle($this->connectionName(), $run);
        }

        $this->restoreFiles($extractedTo, $contents->fileEntries);

        $this->recordCompletion($run, $archive);
    }

    /**
     * Write the outcome back into the database that now exists.
     *
     * A restore replaces the table this row lives in, and the archive was taken
     * before the run started - so by now the row is usually gone. Updating would
     * silently affect nothing and the restore would vanish from its own history,
     * which is the failure this whole action exists to avoid. Re-inserting keeps
     * the account of what happened attached to the data it happened to.
     *
     * @throws RestoreRunFailed
     */
    private function recordCompletion(BackupRun $run, BackupArchive $archive): void
    {
        $recorded = $this->writeRunState($run, [
            'status' => BackupRunStatus::Completed,
            'lock_key' => null,
            'size_in_bytes' => $archive->sizeInBytes,
            'error_code' => null,
            'completed_at' => now(),
        ]);

        /*
         * The archive restored a database this application cannot run on -
         * there is nowhere left to record anything, including a failure.
         */
        throw_unless($recorded, RestoreRunFailed::class, "The restored database does not carry this application's schema.");
    }

    /**
     * Persist a terminal state, whatever the restore did to the row underneath.
     *
     * A restore replaces the table this row lives in, so by the time anything is
     * written the row is usually gone: an update would silently affect nothing
     * and the run would vanish from its own history, which is the outcome this
     * whole action exists to avoid. Re-inserting keeps the account of what
     * happened attached to the data it happened to.
     *
     * This is as true of a failure as of a success - more so, since a restore
     * that failed after wiping is precisely when the table may not be back yet.
     * Returning false rather than throwing lets each caller decide, because the
     * failure path has already thrown something more informative.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function writeRunState(BackupRun $run, array $attributes): bool
    {
        try {
            if (! BackupRun::query()->whereKey($run->getKey())->exists()) {
                $run->exists = false;
            }

            $run->forceFill($attributes)->save();

            return true;
        } catch (Throwable $throwable) {
            Log::error("A restore could not record its own outcome; the database it left behind is missing this application's tables.", [
                'run' => $run->getKey(),
                'attempted_status' => $attributes['status'] ?? null,
                'exception' => $throwable->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Bring the archive local, whatever disk it lives on.
     *
     * A destination may be off-site, where there is no filesystem path to hand
     * to `ZipArchive` at all, so the bytes are streamed rather than addressed.
     */
    private function copyToWorkspace(BackupArchive $archive, string $workspace): ?string
    {
        $source = Storage::disk($archive->diskName)->readStream($archive->path);

        if ($source === null) {
            return null;
        }

        $target = $workspace.'/archive.zip';
        $handle = fopen($target, 'wb');

        if ($handle === false) {
            fclose($source);

            return null;
        }

        stream_copy_to_stream($source, $handle);
        fclose($handle);
        fclose($source);

        return $target;
    }

    /**
     * Unpack only the entries the inspector allowed.
     *
     * `extractTo` is given an explicit list rather than the whole archive: the
     * allowlist is the security boundary, and an archive is free to hold entries
     * that were added between the check and this call only if we let it.
     *
     * @param  list<string>  $entries
     */
    private function extract(string $localArchive, string $destination, array $entries): bool
    {
        $zip = new ZipArchive;

        if ($zip->open($localArchive, ZipArchive::RDONLY) !== true) {
            return false;
        }

        try {
            return $entries === [] || $zip->extractTo($destination, $entries);
        } finally {
            $zip->close();
        }
    }

    /**
     * Copy the current database aside before anything replaces it.
     *
     * This is the difference between a restore and a one-way door. It runs
     * before `db:wipe`, and a failure here aborts with the database untouched -
     * refusing to restore is recoverable, wiping without a copy is not.
     *
     * The copy goes next to the archive folder on the same destination rather
     * than inside it, so it survives a storage wipe, is where the operator
     * already looks, and is invisible to Spatie's listing and retention.
     *
     * @throws RestoreRunFailed
     */
    private function snapshot(BackupRun $run, string $workspace): string
    {
        $localDump = $workspace.'/pre-restore.sql';
        $remotePath = config('backup.backup.name').self::SNAPSHOT_SUFFIX.'/'.now()->format('Y-m-d-H-i-s').'.sql';

        try {
            DbDumperFactory::createFromConnection($this->connectionName())
                ->dumpToFile($localDump);

            $stream = fopen($localDump, 'rb');

            throw_if($stream === false, RuntimeException::class, 'The pre-restore copy could not be read back.');

            Storage::disk($this->archives->primaryDiskName())->writeStream($remotePath, $stream);
            fclose($stream);
        } catch (Throwable $throwable) {
            $this->abort(
                $run,
                self::REASON_SNAPSHOT_FAILED,
                'The database could not be copied before restoring: '.$throwable->getMessage(),
                $throwable,
            );
        }

        $this->pruneSnapshots();

        return $remotePath;
    }

    /**
     * Keep the newest few pre-restore copies and delete the rest.
     *
     * The names are timestamps in a sortable format, so oldest-first is plain
     * ordering. Pruning happens after the new copy is written rather than
     * before: a failure here must never be what stops a restore that has
     * already been made safe to attempt.
     */
    private function pruneSnapshots(): void
    {
        $disk = Storage::disk($this->archives->primaryDiskName());
        $files = $disk->files(config('backup.backup.name').self::SNAPSHOT_SUFFIX);

        sort($files);

        foreach (array_slice($files, 0, max(0, count($files) - self::SNAPSHOT_RETENTION)) as $stale) {
            $disk->delete($stale);
        }
    }

    /**
     * Replay the dumps over a wiped schema.
     *
     * Whole files, not statements: splitting SQL on semicolons corrupts any
     * dump containing one inside a string, and the drivers accept a multi
     * statement string. The cost is that a dump is held in memory once.
     *
     * @param  list<string>  $dumpEntries
     *
     * @throws RestoreRunFailed
     */
    private function import(BackupRun $run, string $extractedTo, array $dumpEntries, string $snapshot): void
    {
        $connection = $this->connectionName();

        try {
            /*
             * Named explicitly rather than left to the default: this restores
             * the database the archive was taken from, which is the one the
             * backup configuration points at.
             */
            Artisan::call('db:wipe', ['--database' => $connection, '--force' => true]);

            foreach ($dumpEntries as $entry) {
                $path = $extractedTo.'/'.$entry;

                if (! File::isFile($path)) {
                    continue;
                }

                $sql = File::get($path);

                if (mb_trim($sql) === '') {
                    continue;
                }

                /*
                 * `unprepared` is typed for a literal SQL string, which a dump
                 * read from a file can never be; `exec` is the primitive it
                 * wraps and accepts the runtime string, while still replaying
                 * the whole multi-statement dump in one call. The wipe above
                 * disconnected the connection, so it is reconnected first.
                 */
                $connectionHandle = DB::connection($connection);

                $connectionHandle->reconnectIfMissingConnection();

                $connectionHandle->getPdo()->exec($sql);
            }
        } catch (Throwable $throwable) {
            /*
             * The schema is now neither the old one nor the new one. The error
             * code cannot carry a path - the page renders it - so the way back
             * is written to the log instead.
             */
            Log::error('Restoring a backup failed part way through; the database is incomplete.', [
                'run' => $run->getKey(),
                'connection' => $connection,
                'pre_restore_copy' => $snapshot,
                'disk' => $this->archives->primaryDiskName(),
            ]);

            $this->abort($run, self::REASON_IMPORT_FAILED, 'The database could not be replaced from the archive.', $throwable);
        }
    }

    /**
     * The connection a restore replaces.
     *
     * The backup configuration decides which database is archived, so it decides
     * which one is put back. Falling back to the framework default keeps a
     * misconfigured `source.databases` from silently restoring nothing.
     */
    private function connectionName(): string
    {
        /** @var array<int, string> $databases */
        $databases = (array) config('backup.backup.source.databases', []);

        return (string) ($databases[0] ?? config('database.default'));
    }

    /**
     * Put the archived media back.
     *
     * A merge, not a mirror: files written since the archive was taken stay
     * where they are. Deleting them would make a restore destroy media that no
     * archive has ever held, which is the opposite of what it is for.
     *
     * @param  list<string>  $fileEntries
     */
    private function restoreFiles(string $extractedTo, array $fileEntries): void
    {
        foreach ($fileEntries as $entry) {
            $source = $extractedTo.'/'.$entry;

            if (! File::isFile($source)) {
                continue;
            }

            /* Safe to join: every entry passed the inspector's allowlist. */
            $destination = base_path($entry);

            File::ensureDirectoryExists(dirname($destination));
            File::copy($source, $destination);
        }
    }

    /**
     * Record a terminal failure, release the lock, and stop the restore.
     *
     * The reason is a translation key suffix, never an exception message: the
     * page renders it, and a raw message can carry paths or credentials.
     *
     * @throws RestoreRunFailed
     */
    private function abort(BackupRun $run, string $reason, string $message, ?Throwable $previous = null): never
    {
        $this->writeRunState($run, [
            'status' => BackupRunStatus::Failed,
            'lock_key' => null,
            'error_code' => $reason,
            'completed_at' => now(),
        ]);

        throw new RestoreRunFailed($message, previous: $previous);
    }
}
