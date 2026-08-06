<?php

return [

    'layout' => [
        'title' => 'Master data',
        'description' => 'Reference records the application runs on.',
    ],

    'user' => [
        'title' => 'User',
        'description' => 'Application users, their role, and their access status.',

        'create' => [
            'title' => 'Add user',
            'description' => 'A new user starts active and signs in with the configured default password.',
        ],

        'edit' => [
            'title' => 'Edit user',
            'description' => 'Change the name, email, role, or access status of an existing user.',
        ],

        'import' => [
            'title' => 'Import users',
            'description' => 'Create users in bulk from a spreadsheet. The file is accepted whole or rejected whole.',

            'help' => 'The template shows the expected columns. Unknown columns are ignored, and every row becomes a new user.',

            'label' => [
                'file' => 'Spreadsheet',
            ],

            'button' => [
                'open' => 'Import',
                'template' => 'Download template',
                'submit' => 'Import users',
            ],

            'message' => [
                'imported' => ':count users have been created.',
                'password_missing' => 'A default password has to be configured in the user provisioning settings before users can be imported.',
            ],

            'error' => [
                'more' => 'And :count more problems.',
            ],

            'validation' => [
                'unreadable' => 'The file could not be read as an XLSX spreadsheet.',
                'columns' => 'The header row is missing the columns :columns.',
                'empty' => 'The spreadsheet holds no rows.',
                'too_many_rows' => 'One import accepts at most :max rows.',
                'row' => 'Row :row: :message',
            ],
        ],

        'label' => [
            'name' => 'Name',
            'email' => 'Email',
            'role' => 'Role',
            'status' => 'Status',
            'created_at' => 'Created',
            'actions' => 'Actions',
        ],

        'placeholder' => [
            'name' => 'Full name',
            'email' => 'name@example.com',
            'role' => 'Select a role',
            'status' => 'Select a status',
            'search' => 'Search by name or email…',
        ],

        'button' => [
            'create' => 'Add user',
            'save' => 'Save',
            'cancel' => 'Cancel',
            'edit' => 'Edit',
            'export' => 'Export XLSX',
        ],

        'message' => [
            'created' => 'The user has been created.',
            'updated' => 'The user has been updated.',
        ],

        'empty' => [
            'title' => 'No users found',
            'description' => 'No user matches the current search.',
        ],

        'filter' => [
            'status' => 'Status',
            'role' => 'Role',
        ],

        'role_missing' => 'No role',
    ],

];
