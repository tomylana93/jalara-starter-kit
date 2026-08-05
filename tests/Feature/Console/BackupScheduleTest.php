<?php

use App\Models\BackupRun;
use App\Models\ImageUpload;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

/**
 * @return array<int, Event>
 */
function scheduledEvents(): array
{
    return app(Schedule::class)->events();
}

function scheduledEventMatching(string $needle): ?Event
{
    foreach (scheduledEvents() as $event) {
        if (str_contains((string) $event->getSummaryForDisplay(), $needle)) {
            return $event;
        }
    }

    return null;
}

/*
 * The times come from configuration and the timezone is explicit, because
 * `app.timezone` is UTC and a bare time would silently mean midday in the zone
 * the operator had in mind.
 */
it('schedules the backup commands at the configured local time', function (string $needle, string $expression) {
    $event = scheduledEventMatching($needle);

    expect($event)->not->toBeNull()
        ->and($event?->expression)->toBe($expression)
        ->and($event?->timezone)->toBe(config('backup.schedule.timezone'));
})->with([
    /* Cleanup first: peak disk usage happens before the new archive lands. */
    'cleanup' => ['backup:clean', '30 4 * * *'],
    'dispatch' => ['backup:dispatch', '0 5 * * *'],
    'monitor' => ['backup:monitor', '30 5 * * *'],
]);

/*
 * `model:prune` only prunes the models it is given, so a prunable model that is
 * not listed is never actually pruned.
 */
it('prunes backup runs alongside image uploads', function () {
    $event = scheduledEventMatching('model:prune');

    expect($event)->not->toBeNull()
        ->and($event?->getSummaryForDisplay())->toContain(ImageUpload::class)
        ->and($event?->getSummaryForDisplay())->toContain(BackupRun::class);
});
