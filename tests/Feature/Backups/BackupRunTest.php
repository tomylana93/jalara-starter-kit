<?php

use App\Actions\Backups\RunBackup;
use App\Actions\Backups\StartBackupRun;
use App\Enums\BackupRunStatus;
use App\Exceptions\Backups\BackupRunFailed;
use App\Jobs\Backups\RunBackupJob;
use App\Models\BackupRun;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('queues a run and records who started it', function () {
    Queue::fake();

    $user = backupManager();

    actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('settings.backups.store'))
        ->assertRedirectToRoute('settings.backups.index');

    $run = BackupRun::query()->sole();

    expect($run->status)->toBe(BackupRunStatus::Pending)
        ->and($run->user_id)->toBe($user->getKey())
        ->and($run->lock_key)->toBe(BackupRun::ACTIVE_LOCK_KEY);

    Queue::assertPushed(RunBackupJob::class);
});

it('runs the backup job on the long-running connection', function () {
    Queue::fake();

    app(StartBackupRun::class)->handle();

    Queue::assertPushed(
        RunBackupJob::class,
        fn (RunBackupJob $job): bool => $job->connection === 'database-long'
            && $job->tries === 1,
    );
});

/*
 * The single-flight gate. Two administrators clicking at the same moment - or an
 * administrator clicking while the schedule is already running - must not
 * produce two dumps writing to the same destination.
 */
it('refuses to start a second run while one is active', function () {
    Queue::fake();

    expect(app(StartBackupRun::class)->handle())->toBeInstanceOf(BackupRun::class)
        ->and(app(StartBackupRun::class)->handle())->toBeNull()
        ->and(BackupRun::query()->count())->toBe(1);

    Queue::assertPushed(RunBackupJob::class, 1);
});

it('tells the administrator when a backup is already running', function () {
    Queue::fake();

    app(StartBackupRun::class)->handle();

    actingAs(backupManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('settings.backups.store'))
        ->assertRedirectToRoute('settings.backups.index');

    expect(BackupRun::query()->count())->toBe(1);
});

it('allows a new run once the previous one has finished', function () {
    Queue::fake();

    $first = app(StartBackupRun::class)->handle();

    $first?->forceFill([
        'status' => BackupRunStatus::Completed,
        'lock_key' => null,
        'completed_at' => now(),
    ])->save();

    expect(app(StartBackupRun::class)->handle())->toBeInstanceOf(BackupRun::class)
        ->and(BackupRun::query()->count())->toBe(2);
});

it('exposes the active run so the page can poll', function () {
    Queue::fake();
    Storage::fake('backups');

    app(StartBackupRun::class)->handle();

    actingAs(backupManager())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('settings.backups.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('activeRun.status', BackupRunStatus::Pending->value)
            ->has('runs', 1));
});

it('completes a run and records the archive it produced', function () {
    Queue::fake();
    Storage::fake('backups');

    $run = app(StartBackupRun::class)->handle();

    Artisan::command('backup:run', function (): int {
        Storage::disk('backups')->put(
            config('backup.backup.name').'/2026-01-01-00-00-00.zip',
            'archive-contents',
        );

        return 0;
    });

    app(RunBackup::class)->handle($run);

    $run->refresh();

    expect($run->status)->toBe(BackupRunStatus::Completed)
        ->and($run->lock_key)->toBeNull()
        ->and($run->filename)->toBe('2026-01-01-00-00-00.zip')
        ->and($run->size_in_bytes)->toBe(strlen('archive-contents'))
        ->and($run->completed_at)->not->toBeNull();
});

it('fails a run and releases the lock when the command fails', function () {
    Queue::fake();
    Storage::fake('backups');

    $run = app(StartBackupRun::class)->handle();

    Artisan::command('backup:run', fn (): int => 1);

    expect(fn () => app(RunBackup::class)->handle($run))
        ->toThrow(BackupRunFailed::class);

    $run->refresh();

    expect($run->status)->toBe(BackupRunStatus::Failed)
        ->and($run->lock_key)->toBeNull()
        ->and($run->error_code)->toBe(RunBackup::REASON_FAILED);
});

/*
 * A command that reports success while leaving nothing behind must not produce a
 * completed row pointing at an archive nobody can download.
 */
it('fails a run when the command succeeds but no archive appears', function () {
    Queue::fake();
    Storage::fake('backups');

    $run = app(StartBackupRun::class)->handle();

    Artisan::command('backup:run', fn (): int => 0);

    expect(fn () => app(RunBackup::class)->handle($run))
        ->toThrow(BackupRunFailed::class);

    $run->refresh();

    expect($run->status)->toBe(BackupRunStatus::Failed)
        ->and($run->error_code)->toBe(RunBackup::REASON_MISSING_ARCHIVE);
});

/*
 * A redelivered job must not restart a run that already finished.
 */
it('ignores a run that is no longer pending', function () {
    Queue::fake();
    Storage::fake('backups');

    $run = app(StartBackupRun::class)->handle();

    $run?->forceFill([
        'status' => BackupRunStatus::Completed,
        'lock_key' => null,
        'completed_at' => now(),
    ])->save();

    app(RunBackup::class)->handle($run);

    expect($run->refresh()->status)->toBe(BackupRunStatus::Completed);
});

/*
 * A job that dies where the action cannot record it would otherwise hold the
 * lock forever and block every later backup.
 */
it('closes the run and releases the lock when the job fails outright', function () {
    Queue::fake();

    $run = app(StartBackupRun::class)->handle();

    new RunBackupJob($run)->failed(new RuntimeException('worker died'));

    $run->refresh();

    expect($run->status)->toBe(BackupRunStatus::Failed)
        ->and($run->lock_key)->toBeNull();
});
