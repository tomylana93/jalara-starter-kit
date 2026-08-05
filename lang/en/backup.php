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
        'description' => 'The last ten backup attempts, whichever started them.',
        'empty' => 'No runs recorded yet.',
        'status' => 'Status',
        'started_by' => 'Started by',
        'scheduled' => 'Schedule',
        'started_at' => 'Started',
        'completed_at' => 'Finished',
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
    ],

    'confirm' => [
        'delete' => [
            'title' => 'Delete this archive?',
            'description' => 'The archive :filename is removed from its destination disk. This cannot be undone.',
            'confirm' => 'Delete',
            'cancel' => 'Cancel',
        ],
    ],

    'message' => [
        'started' => 'The backup has been queued. It runs in the background.',
        'already_running' => 'A backup is already running. Wait for it to finish before starting another.',
        'deleted' => 'The archive has been deleted.',
    ],

    'error' => [
        'failed' => 'The backup did not complete.',
        'missing_archive' => 'The backup reported success but no archive was found on the destination.',
    ],

    'notice' => [
        'worker' => 'Backups run on the :connection queue connection and need a worker processing it.',
    ],

];
