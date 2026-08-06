<?php

return [

    'title' => 'Backups',
    'description' => 'Scheduled database and media archives, and the runs that produced them.',

    'archive' => [
        'title' => 'Archives',
        'description' => 'Every archive currently kept on the configured destinations.',
        'empty' => 'No archives yet. The first scheduled backup will create one.',
        'filename' => 'Archive',
        'disk' => 'Disk',
        'size' => 'Size',
        'created_at' => 'Created',
    ],

    'run' => [
        'title' => 'Recent runs',
        'description' => 'The last ten backup and restore attempts, whichever started them.',
        'empty' => 'No runs recorded yet.',
        'type' => 'Type',
        'status' => 'Status',
        'started_by' => 'Started by',
        'scheduled' => 'Schedule',
        'started_at' => 'Started',
        'completed_at' => 'Finished',
    ],

    'type' => [
        'backup' => 'Backup',
        'restore' => 'Restore',
    ],

    'status' => [
        'pending' => 'Queued',
        'running' => 'Running',
        'completed' => 'Completed',
        'failed' => 'Failed',
    ],

    'action' => [
        'run' => 'Back up now',
        'running' => 'Backup in progress…',
        'download' => 'Download archive',
        'delete' => 'Delete archive',
        'upload' => 'Upload backup',
        'restore' => 'Restore archive',
    ],

    'confirm' => [
        'delete' => [
            'title' => 'Delete this archive?',
            'description' => 'The archive :filename is removed from its destination disk. This cannot be undone.',
            'confirm' => 'Delete',
            'cancel' => 'Cancel',
        ],
        'restore' => [
            'title' => 'Restore this archive?',
            'description' => 'Restoring :filename replaces the current database and puts the archived media back. A copy of the current database is taken first, next to the archives on the destination disk. Everyone signed in is signed out and queued work is discarded. The restore runs in the background.',
            'confirm' => 'Restore',
            'cancel' => 'Cancel',
        ],
        'upload' => [
            'title' => 'Upload an archive',
            'description' => 'Choose a ZIP archive produced by this application. It is checked entry by entry and rejected if it holds anything a backup would not.',
            'select' => 'Choose a ZIP file',
            'confirm' => 'Upload',
            'cancel' => 'Cancel',
        ],
    ],

    'message' => [
        'started' => 'The backup has been queued. It runs in the background.',
        'restore_started' => 'The restore has been queued. It runs in the background, and this page reports how it ends.',
        'already_running' => 'A backup or restore is already running. Wait for it to finish before starting another.',
        'deleted' => 'The archive has been deleted.',
        'uploaded' => 'The archive has been uploaded.',
    ],

    'validation' => [
        'archive' => 'This file is not a backup archive of this application.',
    ],

    'error' => [
        'failed' => 'The backup did not complete.',
        'missing_archive' => 'The backup reported success but no archive was found on the destination.',
        'restore_failed' => 'The restore did not complete.',
        'restore_missing_archive' => 'The archive is no longer on the destination, so nothing was restored.',
        'restore_unreadable_archive' => 'The archive could not be unpacked, so nothing was restored.',
        'restore_unsupported_dump' => 'The archive holds compressed database dumps, which cannot be replayed. Nothing was restored.',
        'restore_snapshot_failed' => 'The current database could not be copied first, so nothing was restored.',
        'restore_import_failed' => 'The restore stopped part way through: the database is incomplete. The copy taken beforehand is on the backup disk, and the log records its name.',
    ],

    'notice' => [
        'worker' => 'Backups run on the :connection queue connection and need a worker processing it.',
    ],

];
