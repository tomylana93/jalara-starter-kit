<?php

use App\Actions\Backups\StartBackupRun;
use App\Models\BackupRun;
use App\Models\ImageUpload;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose(__('console.inspire.description'));

/*
 * Storage housekeeping. Both run daily and both respect the same 24 hour
 * window: long enough that nothing still in flight is ever in scope, short
 * enough that abandoned files and finished records do not pile up.
 *
 * `withoutOverlapping` matters here because a large storage sweep can outlive
 * its own schedule slot.
 */
Schedule::command('images:prune-orphans', ['--delete', '--older-than='.ImageUpload::RETENTION_HOURS])
    ->daily()
    ->withoutOverlapping();

Schedule::command('model:prune', [
    '--model='.ImageUpload::class,
    '--model='.BackupRun::class,
])
    ->daily()
    ->withoutOverlapping();

/*
 * Backups.
 *
 * The times come from configuration rather than literals so an operator can move
 * them without a deploy, and they are read through `config()` because `env()`
 * outside a config file returns null once `config:cache` has run - which would
 * silently unschedule every backup in production.
 *
 * The timezone is explicit for the same reason: `app.timezone` is UTC, so a bare
 * `dailyAt('05:00')` would mean midday in the zone the operator has in mind.
 */
$backupTimezone = (string) config('backup.schedule.timezone');
$backupAt = CarbonImmutable::createFromFormat('H:i', (string) config('backup.schedule.time'));

/*
 * Cleanup runs first. Deleting before writing means peak disk usage happens
 * before the new archive lands, not after - on a tight disk the reverse order is
 * what makes a backup fail on precisely the night retention should have relieved
 * it.
 */
Schedule::command('backup:clean')
    ->timezone($backupTimezone)
    ->dailyAt($backupAt->subMinutes(30)->format('H:i'))
    ->withoutOverlapping();

/*
 * The scheduled backup goes through the same action as the button in the UI, so
 * both share one lock and one history. A separate `Schedule::command('backup:run')`
 * would be simpler, but it could collide with a manual run and would leave the
 * backup page describing only half of what actually happened.
 *
 * Dispatching is instantaneous; the single-flight guarantee lives in the unique
 * `lock_key`, not in `withoutOverlapping`.
 */
Schedule::call(fn (StartBackupRun $startBackupRun) => $startBackupRun->handle())
    ->name('backup:dispatch')
    ->timezone($backupTimezone)
    ->dailyAt($backupAt->format('H:i'));

/*
 * Catches the one failure mode a failure notification cannot: a backup that
 * quietly stopped happening at all. It cannot see a dead cron, which would stop
 * this check too.
 */
Schedule::command('backup:monitor')
    ->timezone($backupTimezone)
    ->dailyAt($backupAt->addMinutes(30)->format('H:i'))
    ->withoutOverlapping();
