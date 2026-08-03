<?php

return [

    'upload' => [
        'status' => [
            'pending' => 'Waiting to be processed.',
            'processing' => 'Processing the image.',
        ],
        'message' => [
            'cancelled' => 'Upload cancelled.',
            'conflict' => 'Another upload for this image is still in progress.',
            'conflict_other_owner' => 'Another administrator is already uploading this image. Try again once they are done.',
            'timed_out' => 'Processing is taking longer than expected.',
        ],
        'error' => [
            'processing_failed' => 'The image could not be processed.',
            'unauthorized' => 'The image could not be applied because access changed.',
            'target_unavailable' => 'The image could not be applied because its destination is no longer available.',
        ],
        'button' => [
            'cancel' => 'Cancel upload',
            'retry' => 'Try again',
            'check_again' => 'Check again',
        ],
    ],

];
