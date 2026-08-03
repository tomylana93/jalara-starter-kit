<?php

use App\Models\ImageUpload;
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

Schedule::command('model:prune', ['--model='.ImageUpload::class])
    ->daily()
    ->withoutOverlapping();
