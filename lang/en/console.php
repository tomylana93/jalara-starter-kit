<?php

return [

    'inspire' => [
        'description' => 'Display an inspiring quote',
    ],

    'prune_orphan_images' => [
        'mode' => [
            'dry_run' => 'Dry run: reporting unreferenced images older than :hours hours without deleting anything.',
            'delete' => 'Deleting unreferenced images older than :hours hours.',
        ],
        'disk' => 'Disk :disk — candidates: :candidates, deleted: :deleted, skipped: :skipped.',
    ],

];
