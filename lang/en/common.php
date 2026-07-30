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

    'table' => [
        'search' => 'Search…',
        'per_page' => 'Rows per page',
        'summary' => 'Showing :from to :to of :total',
        'empty' => 'No data found.',
        'selected' => ':count of :total rows on this page selected',
        'filter' => [
            'clear' => 'Clear filter',
        ],
        'row_actions' => 'Open row actions',
        'columns' => [
            'label' => 'Columns',
            'description' => 'Toggle columns',
        ],
        'select' => [
            'all' => 'Select every row on this page',
            'row' => 'Select row',
        ],
        'sort' => [
            'ascending' => 'Sort ascending',
            'descending' => 'Sort descending',
        ],
        'pagination' => [
            'label' => 'Pagination',
            'first' => 'First page',
            'previous' => 'Previous page',
            'next' => 'Next page',
            'last' => 'Last page',
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
