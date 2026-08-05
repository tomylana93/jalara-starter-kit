<?php

use Illuminate\Foundation\DevCommands;

/*
 * `DevCommands::registerDefaults()` starts `queue:listen` with no connection
 * argument, so it serves the default connection only. Backups run on
 * `database-long` because `retry_after` is per-connection, and without a worker
 * for it their jobs sit in the queue unclaimed: the run stays pending, nothing
 * fails, and nothing explains why. This asserts the developer runner covers it.
 */
it('runs a worker for the long-running queue connection alongside the defaults', function () {
    $commands = collect(DevCommands::commands());

    expect($commands->pluck('command'))
        ->toContain('php artisan queue:listen database-long --tries=1 --timeout=0')
        ->and($commands->pluck('name'))->toContain('queue-long');
});
