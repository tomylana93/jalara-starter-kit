<?php

use App\Actions\Backups\RestoreBackup;
use App\Actions\Backups\SettleRestoredDatabase;
use App\Actions\Backups\StartBackupRun;
use App\Actions\Backups\StartRestoreRun;
use App\Enums\BackupRunStatus;
use App\Enums\BackupRunType;
use App\Exceptions\Backups\RestoreRunFailed;
use App\Jobs\Backups\RestoreBackupJob;
use App\Models\BackupRun;
use App\Support\Backups\BackupArchive;
use App\Support\Backups\BackupArchives;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

/**
 * Put a real archive on the faked destination and return its basename.
 *
 * @param  array<string, string>  $entries  archive path => contents
 */
function destinationArchive(array $entries, string $filename = '2026-01-01-00-00-00.zip'): string
{
    $path = backupArchiveFile($entries);

    Storage::disk('backups')->put(
        config('backup.backup.name').'/'.$filename,
        (string) file_get_contents($path),
    );

    @unlink($path);

    return $filename;
}

/**
 * The archive under test, resolved the way the application resolves it.
 */
function destinationArchiveObject(string $filename): BackupArchive
{
    $archive = app(BackupArchives::class)->find($filename);

    expect($archive)->not->toBeNull();

    return $archive;
}

beforeEach(function (): void {
    Storage::fake('backups');
});

it('queues a restore and records which archive it is replaying', function () {
    Queue::fake();

    $filename = destinationArchive(['db-dumps/database' => 'select 1;']);
    $user = backupManager();

    actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('settings.backups.restore', ['filename' => $filename]))
        ->assertRedirectToRoute('settings.backups.index');

    $run = BackupRun::query()->sole();

    expect($run->type)->toBe(BackupRunType::Restore)
        ->and($run->status)->toBe(BackupRunStatus::Pending)
        ->and($run->filename)->toBe($filename)
        ->and($run->user_id)->toBe($user->getKey())
        ->and($run->lock_key)->toBe(BackupRun::ACTIVE_LOCK_KEY);

    Queue::assertPushed(
        RestoreBackupJob::class,
        fn (RestoreBackupJob $job): bool => $job->connection === 'database-long' && $job->tries === 1,
    );
});

it('answers 404 for a filename that names no archive', function () {
    Queue::fake();

    actingAs(backupManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('settings.backups.restore', ['filename' => '../../.env']))
        ->assertNotFound();

    expect(BackupRun::query()->count())->toBe(0);
});

/*
 * Backups and restores share one lock precisely so this cannot happen: a dump
 * taken while a restore is half applied would archive a database that never
 * existed.
 */
it('refuses to restore while a backup is already running', function () {
    Queue::fake();

    $filename = destinationArchive(['db-dumps/database' => 'select 1;']);

    app(StartBackupRun::class)->handle();

    actingAs(backupManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('settings.backups.restore', ['filename' => $filename]))
        ->assertRedirectToRoute('settings.backups.index');

    expect(BackupRun::query()->count())->toBe(1);
    Queue::assertNotPushed(RestoreBackupJob::class);
});

it('refuses to start a second restore while one is active', function () {
    Queue::fake();

    $filename = destinationArchive(['db-dumps/database' => 'select 1;']);
    $archive = destinationArchiveObject($filename);

    expect(app(StartRestoreRun::class)->handle($archive))->toBeInstanceOf(BackupRun::class)
        ->and(app(StartRestoreRun::class)->handle($archive))->toBeNull()
        ->and(BackupRun::query()->count())->toBe(1);
});

it('fails the run when the archive has gone from the destination', function () {
    Queue::fake();

    $run = BackupRun::factory()->restore()->active()->create(['filename' => 'vanished.zip']);
    $run->forceFill(['status' => BackupRunStatus::Pending])->save();

    expect(fn () => app(RestoreBackup::class)->handle($run))
        ->toThrow(RestoreRunFailed::class);

    $run->refresh();

    expect($run->status)->toBe(BackupRunStatus::Failed)
        ->and($run->lock_key)->toBeNull()
        ->and($run->error_code)->toBe(RestoreBackup::REASON_MISSING_ARCHIVE);
});

/*
 * The archive is addressed by name from a queued job, so the contents are
 * inspected again here rather than trusted from upload time.
 */
it('fails the run when the archive is not one of ours', function () {
    Queue::fake();

    $filename = destinationArchive(['etc/passwd' => 'root:x:0:0']);

    $run = BackupRun::factory()->restore()->active()->create(['filename' => $filename]);
    $run->forceFill(['status' => BackupRunStatus::Pending])->save();

    expect(fn () => app(RestoreBackup::class)->handle($run))
        ->toThrow(RestoreRunFailed::class)
        ->and($run->refresh()->error_code)->toBe(RestoreBackup::REASON_UNREADABLE_ARCHIVE);
});

/*
 * The archive is addressed by name from a queued job, so the contents are
 * inspected again here rather than trusted from upload time.
 */
it('refuses an archive carrying compressed dumps', function () {
    Queue::fake();

    $filename = destinationArchive(['db-dumps/database' => 'gzip-bytes']);

    config()->set('backup.backup.database_dump_compressor', 'gzip');

    $run = BackupRun::factory()->restore()->active()->create(['filename' => $filename]);
    $run->forceFill(['status' => BackupRunStatus::Pending])->save();

    expect(fn () => app(RestoreBackup::class)->handle($run))
        ->toThrow(RestoreRunFailed::class)
        ->and($run->refresh()->error_code)->toBe(RestoreBackup::REASON_UNSUPPORTED_DUMP)
        /* Nothing was wiped: the refusal happens before the snapshot is taken. */
        ->and(Storage::disk('backups')->files(config('backup.backup.name').'-pre-restore'))
        ->toBeEmpty();
});

/*
 * The safety property the whole action is built around: if the current database
 * cannot be copied first, nothing is replaced. Refusing to restore is
 * recoverable; wiping without a copy is not.
 */
it('refuses to wipe anything when the pre-restore copy cannot be taken', function () {
    Queue::fake();

    $filename = destinationArchive([
        'db-dumps/database' => 'create table widgets (id integer);',
    ]);

    $run = BackupRun::factory()->restore()->active()->create(['filename' => $filename]);
    $run->forceFill(['status' => BackupRunStatus::Pending])->save();

    /* A connection no dumper can be built for, which is what the guard is for. */
    config()->set('database.connections.undumpable', ['driver' => 'not-a-driver']);
    config()->set('backup.backup.source.databases', ['undumpable']);

    expect(fn () => app(RestoreBackup::class)->handle($run))
        ->toThrow(RestoreRunFailed::class);

    $run->refresh();

    expect($run->status)->toBe(BackupRunStatus::Failed)
        ->and($run->error_code)->toBe(RestoreBackup::REASON_SNAPSHOT_FAILED)
        /* The schema is untouched, which is the whole point. */
        ->and(Schema::hasTable('users'))->toBeTrue();
});

/**
 * Point the backup configuration at a throwaway file database.
 *
 * A restore wipes and replays the connection the backup configuration names, so
 * the test gives it one of its own. The suite's own connection is left alone -
 * it is in memory and inside a transaction, where `db:wipe` cannot run at all.
 */
function useRestoreTargetDatabase(): string
{
    $path = tempnam(sys_get_temp_dir(), 'restore-target-').'.sqlite';
    touch($path);

    config()->set('database.connections.restore_target', [
        'driver' => 'sqlite',
        'database' => $path,
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);
    config()->set('backup.backup.source.databases', ['restore_target']);

    return $path;
}

/*
 * The end-to-end claim: the archived dump really does become the database, and
 * the copy taken beforehand really does land where the operator was promised it
 * would.
 */
it('replaces the database with the dump the archive carries', function () {
    Queue::fake();

    $databasePath = useRestoreTargetDatabase();

    DB::connection('restore_target')->statement('create table widgets (id integer primary key, name text)');
    DB::connection('restore_target')->table('widgets')->insert(['name' => 'before-the-restore']);

    $filename = destinationArchive([
        'db-dumps/database' => "create table widgets (id integer primary key, name text);\ninsert into widgets (name) values ('from-the-archive');\n",
    ]);

    $run = BackupRun::factory()->restore()->active()->create(['filename' => $filename]);
    $run->forceFill(['status' => BackupRunStatus::Pending])->save();

    app(RestoreBackup::class)->handle($run);

    expect(DB::connection('restore_target')->table('widgets')->pluck('name')->all())
        ->toBe(['from-the-archive'])
        ->and($run->refresh()->status)->toBe(BackupRunStatus::Completed)
        ->and($run->lock_key)->toBeNull()
        /* The way back, next to the archives rather than among them. */
        ->and(Storage::disk('backups')->files(config('backup.backup.name').'-pre-restore'))
        ->toHaveCount(1);

    DB::purge('restore_target');
    @unlink($databasePath);
});

/*
 * A dump that fails half way leaves a database that is neither state. The run
 * has to say so, because the operator's next move depends on knowing it.
 */
it('reports a dump that fails to replay', function () {
    Queue::fake();

    $databasePath = useRestoreTargetDatabase();

    $filename = destinationArchive([
        'db-dumps/database' => 'create table widgets (id integer primary key); this is not sql at all;',
    ]);

    $run = BackupRun::factory()->restore()->active()->create(['filename' => $filename]);
    $run->forceFill(['status' => BackupRunStatus::Pending])->save();

    expect(fn () => app(RestoreBackup::class)->handle($run))
        ->toThrow(RestoreRunFailed::class);

    $run->refresh();

    expect($run->status)->toBe(BackupRunStatus::Failed)
        ->and($run->lock_key)->toBeNull()
        ->and($run->error_code)->toBe(RestoreBackup::REASON_IMPORT_FAILED)
        ->and(Storage::disk('backups')->files(config('backup.backup.name').'-pre-restore'))
        ->toHaveCount(1);

    DB::purge('restore_target');
    @unlink($databasePath);
});

/*
 * A restore that touches no database at all still puts media back, and needs no
 * snapshot to do it.
 */
it('restores archived media without touching the database', function () {
    Queue::fake();

    $relativePath = 'storage/app/public/restored-'.Str::uuid7()->toString().'.txt';
    $filename = destinationArchive([$relativePath => 'restored-bytes']);

    $run = BackupRun::factory()->restore()->active()->create(['filename' => $filename]);
    $run->forceFill(['status' => BackupRunStatus::Pending])->save();

    app(RestoreBackup::class)->handle($run);

    $run->refresh();

    expect($run->status)->toBe(BackupRunStatus::Completed)
        ->and($run->lock_key)->toBeNull()
        ->and($run->error_code)->toBeNull()
        ->and(File::get(base_path($relativePath)))->toBe('restored-bytes');

    File::delete(base_path($relativePath));
});

/*
 * The workspace lives under `storage/app/private`, outside every backed-up
 * prefix, and must not survive the run either way.
 */
it('leaves no workspace behind', function () {
    Queue::fake();

    $filename = destinationArchive(['etc/passwd' => 'nope']);

    $run = BackupRun::factory()->restore()->active()->create(['filename' => $filename]);
    $run->forceFill(['status' => BackupRunStatus::Pending])->save();

    expect(fn () => app(RestoreBackup::class)->handle($run))->toThrow(RestoreRunFailed::class)
        ->and(File::isDirectory(storage_path('app/private/restore/'.$run->getKey())))->toBeFalse();
});

/*
 * A redelivered job must not restart a restore that already finished.
 */
it('ignores a run that is no longer pending', function () {
    Queue::fake();

    $run = BackupRun::factory()->restore()->create(['filename' => 'anything.zip']);

    app(RestoreBackup::class)->handle($run);

    expect($run->refresh()->status)->toBe(BackupRunStatus::Completed);
});

/*
 * A restore left mid-flight would hold the lock forever and block every later
 * backup.
 */
it('closes the run and releases the lock when the job fails outright', function () {
    Queue::fake();

    $run = BackupRun::factory()->restore()->active()->create(['filename' => 'anything.zip']);

    new RestoreBackupJob($run)->failed(new RuntimeException('worker died'));

    $run->refresh();

    expect($run->status)->toBe(BackupRunStatus::Failed)
        ->and($run->lock_key)->toBeNull()
        ->and($run->error_code)->toBe(RestoreBackup::REASON_FAILED);
});

/*
 * The trap the settling step exists for, and the one a restore cannot be shipped
 * without: every archive this application writes was dumped while a backup run
 * held the single-flight lock, so replaying it hands that lock to a row that
 * will never finish. The next backup collides with the unique index forever, the
 * page reports a run in progress that is years old, and the schedule stops
 * without a word.
 */
it('discards a run the archive brought back holding the lock', function () {
    Queue::fake();

    $resurrected = BackupRun::factory()->active()->create();
    $current = BackupRun::factory()->restore()->create(['filename' => 'anything.zip']);
    $current->forceFill(['status' => BackupRunStatus::Running])->save();

    app(SettleRestoredDatabase::class)->handle(DB::getDefaultConnection(), $current);

    expect(BackupRun::query()->whereKey($resurrected->getKey())->exists())->toBeFalse()
        /* The restore doing the restoring is the one row that must survive. */
        ->and($current->refresh()->status)->toBe(BackupRunStatus::Running)
        /* And the lock is free again, which is the whole point. */
        ->and(app(StartBackupRun::class)->handle())->toBeInstanceOf(BackupRun::class);
});

/*
 * Queued jobs and sessions describe work in flight, not data anybody restores a
 * backup to get back. Replayed, the jobs run again and the sessions replace
 * those of everyone currently signed in.
 */
it('clears the queue and session rows the archive brought back', function () {
    Queue::fake();

    $run = BackupRun::factory()->restore()->active()->create(['filename' => 'anything.zip']);

    DB::table('jobs')->insert([
        'queue' => 'long-running',
        'payload' => '{}',
        'attempts' => 0,
        'reserved_at' => null,
        'available_at' => now()->getTimestamp(),
        'created_at' => now()->getTimestamp(),
    ]);

    DB::table('sessions')->insert([
        'id' => Str::random(40),
        'user_id' => null,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'restored',
        'payload' => 'e30=',
        'last_activity' => now()->getTimestamp(),
    ]);

    app(SettleRestoredDatabase::class)->handle(DB::getDefaultConnection(), $run);

    expect(DB::table('jobs')->count())->toBe(0)
        ->and(DB::table('sessions')->count())->toBe(0);
});

/*
 * The copy is deliberately outside Spatie's listing and retention, so nothing
 * else would ever delete it either.
 */
it('keeps only the newest few pre-restore copies', function () {
    Queue::fake();

    $databasePath = useRestoreTargetDatabase();
    $directory = config('backup.backup.name').'-pre-restore';

    foreach (['2020-01-01-00-00-00', '2021-01-01-00-00-00', '2022-01-01-00-00-00'] as $stamp) {
        Storage::disk('backups')->put($directory.'/'.$stamp.'.sql', 'older copy');
    }

    $filename = destinationArchive([
        'db-dumps/database' => "create table widgets (id integer primary key);\n",
    ]);

    $run = BackupRun::factory()->restore()->active()->create(['filename' => $filename]);
    $run->forceFill(['status' => BackupRunStatus::Pending])->save();

    app(RestoreBackup::class)->handle($run);

    $kept = Storage::disk('backups')->files($directory);

    expect($kept)->toHaveCount(3)
        /* Oldest first out; the copy just taken is necessarily among the rest. */
        ->and($kept)->not->toContain($directory.'/2020-01-01-00-00-00.sql');

    DB::purge('restore_target');
    @unlink($databasePath);
});
