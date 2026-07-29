<?php

return [

    'error' => [
        'title' => 'Something went wrong.',
    ],

    'password' => [
        'button' => [
            'show' => 'Show password',
            'hide' => 'Hide password',
        ],
    ],

    'upload' => [
        'empty' => 'No image',
        'status' => [
            'idle' => 'No image selected.',
            'uploading' => 'Uploading…',
            'processing' => 'Processing…',
            'error' => 'Upload failed.',
            'cancelled' => 'The upload was cancelled.',
            'done' => 'Saved.',
        ],
        'action' => [
            'upload' => 'Upload',
            'replace' => 'Replace',
            'retry' => 'Try again',
            'remove' => 'Remove',
        ],
        'guard' => [
            'title' => 'Upload in progress',
            'description' => 'Leaving this page now would cancel the upload. Please wait until it finishes.',
            'cancelled' => 'The upload was cancelled because the page navigated away.',
        ],
    ],

];
