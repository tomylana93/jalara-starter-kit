<?php

use Spatie\Backup\Notifications\Notifiable;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

/*
 * The published Spatie configuration is deliberately trimmed to the options this
 * application actually decides on. Channels and drivers that are not configured
 * are removed rather than left at their defaults, so the file never implies a
 * capability that is neither wired nor tested.
 */

$maximumStorageInMegabytes = (int) env('BACKUP_MAX_STORAGE_MB', 2000);

/*
 * Read from the raw environment, not `config('app.name')`: the application name
 * is a runtime setting an administrator can change, and Spatie derives the
 * destination folder from this value. Following the setting would strand every
 * existing archive under the previous name.
 */
$applicationName = env('APP_NAME', 'Laravel');

$destinationDisks = array_values(array_filter(
    array_map(trim(...), explode(',', (string) env('BACKUP_DISKS', 'backups'))),
));

/*
 * With no recipient configured, backups still run - they simply announce
 * nothing. That is expressed by an empty notification map rather than an empty
 * address: Spatie validates `notifications.mail.to` as soon as its config object
 * is resolved during boot, and a null or empty value throws for every command
 * and request in the application, not just for backups. The placeholder below is
 * never contacted, because no notification is registered to any channel.
 */
$notificationEmail = env('BACKUP_NOTIFICATION_EMAIL');
$notificationsEnabled = is_string($notificationEmail) && $notificationEmail !== '';

return [

    'backup' => [
        'name' => $applicationName,

        'source' => [
            'files' => [
                /*
                 * Only the media prefixes whose rows point at them. The staging
                 * directory `app/private/image-uploads` is deliberately absent:
                 * it holds bytes awaiting queue processing, is deleted after the
                 * publishing transaction commits, and is swept by
                 * `images:prune-orphans` - archiving it would preserve nothing a
                 * restore could use.
                 */
                'include' => [
                    storage_path('app/public'),
                    storage_path('app/private/chat'),
                ],

                'exclude' => [],

                'follow_links' => false,

                'ignore_unreadable_directories' => false,

                /*
                 * Store media at their project-relative paths inside the archive
                 * so a restore can be unpacked over the application root.
                 */
                'relative_path' => base_path(),
            ],

            'databases' => [
                env('DB_CONNECTION', 'sqlite'),
            ],
        ],

        'database_dump_compressor' => null,

        'database_dump_file_timestamp_format' => null,

        'database_dump_filename_base' => 'database',

        'database_dump_file_extension' => '',

        'destination' => [
            'compression_method' => ZipArchive::CM_DEFAULT,

            'compression_level' => 9,

            'filename_prefix' => '',

            /*
             * Never the `public` disk: it is symlinked from `public/storage`, so
             * an archive written there would be downloadable without
             * authentication. `backups` roots outside `storage/app` so archives
             * are neither inside the backed-up tree nor mixed with application
             * media.
             */
            'disks' => $destinationDisks,

            'continue_on_failure' => false,
        ],

        'temporary_directory' => storage_path('backup-temp'),

        /*
         * Archive encryption is off. While archives stay on a private local disk
         * an attacker able to read them can almost certainly read `.env` as well,
         * so a password stored beside them adds key-loss risk without adding
         * protection. Enabling it becomes worthwhile once an off-site disk is
         * added to `BACKUP_DISKS`.
         */
        'password' => null,

        'encryption' => 'default',

        'verify_backup' => false,

        /*
         * A backup that failed part way is not retried automatically: retrying
         * only multiplies half-written archives. Failures are mailed and repeated
         * deliberately by an operator.
         */
        'tries' => 1,

        'retry_delay' => 0,
    ],

    /*
     * Failures only. Success notifications on a daily schedule train operators to
     * ignore the channel, which is precisely how a genuine failure goes unread.
     */
    'notifications' => [
        'notifications' => [
            /*
             * Every notification class must appear here even when it is muted:
             * Spatie indexes this map by class name and an absent key is an
             * undefined-key error at send time, not a silent skip. Channels are
             * emptied instead, which `via()` filters down to no delivery.
             *
             * Success notifications stay muted permanently. A daily archive that
             * mails on success trains operators to ignore the channel, which is
             * exactly how a real failure goes unread.
             */
            BackupHasFailedNotification::class => $notificationsEnabled ? ['mail'] : [],
            UnhealthyBackupWasFoundNotification::class => $notificationsEnabled ? ['mail'] : [],
            CleanupHasFailedNotification::class => $notificationsEnabled ? ['mail'] : [],
            BackupWasSuccessfulNotification::class => [],
            HealthyBackupWasFoundNotification::class => [],
            CleanupWasSuccessfulNotification::class => [],
        ],

        'notifiable' => Notifiable::class,

        'mail' => [
            'to' => $notificationsEnabled ? $notificationEmail : 'backup@example.invalid',

            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', 'Example'),
            ],
        ],

        /*
         * Slack is unused, but the key cannot be removed: unlike `discord` and
         * `webhook`, Spatie reads it unguarded and reading a trimmed config
         * would fail at boot.
         */
        'slack' => [
            'webhook_url' => '',
            'channel' => null,
            'username' => null,
            'icon' => null,
        ],
    ],

    'log_channel' => null,

    /*
     * Catches the failure mode plain failure notifications cannot see: a backup
     * that silently stopped happening. It cannot see a dead cron, which would
     * stop the monitor too - that gap needs an external heartbeat.
     */
    'monitor_backups' => [
        [
            'name' => $applicationName,
            'disks' => $destinationDisks,
            'health_checks' => [
                MaximumAgeInDays::class => 1,
                MaximumStorageInMegabytes::class => $maximumStorageInMegabytes,
            ],
        ],
    ],

    'cleanup' => [
        'strategy' => DefaultStrategy::class,

        'default_strategy' => [
            'keep_all_backups_for_days' => 7,

            'keep_daily_backups_for_days' => 16,

            'keep_weekly_backups_for_weeks' => 8,

            'keep_monthly_backups_for_months' => 4,

            'keep_yearly_backups_for_years' => 2,

            /*
             * This ceiling overrides every retention step above it: Spatie deletes
             * the oldest archives until the total fits. It is the knob that
             * actually decides how much history survives, which is why it is the
             * one value read from the environment.
             */
            'delete_oldest_backups_when_using_more_megabytes_than' => $maximumStorageInMegabytes,
        ],

        'tries' => 1,

        'retry_delay' => 0,
    ],

    /*
     * Application-level scheduling. Spatie ignores unknown top-level keys, so the
     * whole feature stays in one file. `routes/console.php` reads these through
     * `config()`; reading `env()` there would return null under `config:cache`
     * and silently unschedule every backup.
     */
    'schedule' => [
        'time' => env('BACKUP_SCHEDULE_TIME', '05:00'),
        'timezone' => env('BACKUP_SCHEDULE_TIMEZONE', 'Asia/Jakarta'),
    ],
];
